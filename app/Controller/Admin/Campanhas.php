<?php

namespace App\Controller\Admin;

use App\Utils\View;
use App\Common\Helpers\TenantHelper;
use App\Common\Helpers\CampanhaSegmentoHelper;
use App\Common\Communication\CampanhaWorker;
use App\Common\Communication\WhatsappEscolaService;
use App\Common\Communication\WhatsappMediaStorage;
use App\Model\Entity\Campanhas as EntityCampanhas;
use App\Model\Entity\CampanhaFila;
use App\Common\Communication\EvolutionApiService;

class Campanhas extends Page {

	private static $statusLabels = [
		'rascunho'   => 'Rascunho',
		'agendada'   => 'Agendada',
		'enviando'   => 'Enviando',
		'concluida'  => 'Concluída',
		'pausada'    => 'Pausada',
		'cancelada'  => 'Cancelada',
	];

	public static function index($request) {
		$content = View::render('admin/modules/campanhas/index', []);
		return parent::getPanel('Campanhas', $content, 'config');
	}

	public static function getInfo($request) {
		if (!EntityCampanhas::tabelaExiste()) {
			return json_encode([
				'success' => false,
				'message' => 'Crie as tabelas campanhas e campanha_fila no phpMyAdmin.',
			]);
		}

		$postVars = $request->getPostVars();
		$acao = $postVars['acao'] ?? '';

		switch ($acao) {
			case 'listar':
				return self::listar($postVars);
			case 'salvar':
				return self::salvar($postVars);
			case 'preview':
				return self::preview($postVars);
			case 'iniciar':
				return self::iniciar($postVars);
			case 'pausar':
				return self::pausar($postVars);
			case 'cancelar':
				return self::cancelar($postVars);
			case 'detalhes':
				return self::detalhes($postVars);
			case 'processar':
				return self::processarFila($postVars);
			case 'listar_grupos_wa':
				return self::listarGruposWa();
			default:
				return json_encode(['success' => false, 'message' => 'Ação inválida.']);
		}
	}

	private static function listarGruposWa(): string {
		$idAdmin = TenantHelper::getIdAdmin();
		$res = WhatsappEscolaService::listarGruposEListas($idAdmin);
		return json_encode([
			'success' => !empty($res['ok']),
			'message' => $res['message'] ?? '',
			'itens' => $res['itens'] ?? [],
		], JSON_UNESCAPED_UNICODE);
	}

	private static function listar(array $postVars): string {
		$idAdmin = TenantHelper::getIdAdmin();
		$canal = self::normalizarCanal($postVars['canal'] ?? '');
		$where = 'id_admin = '.(int)$idAdmin;
		if ($canal !== '') {
			$where .= ' AND canal = "'.addslashes($canal).'"';
		}

		// Avança a fila se o intervalo de grupos já liberou (não depende só do botão/cron)
		self::tickFilaLeve($idAdmin);

		$results = EntityCampanhas::get($where, 'id DESC', '50');

		$lista = [];
		while ($row = $results->fetchObject(EntityCampanhas::class)) {
			$lista[] = self::formatarCampanha($row);
		}

		return json_encode([
			'success'   => true,
			'campanhas' => $lista,
			'pacing'    => CampanhaWorker::infoPacingGrupo($idAdmin),
		]);
	}

	/** Processa 1 item se houver campanha enviando (respeita pacing de grupos). */
	private static function tickFilaLeve(int $idAdmin): void {
		$results = EntityCampanhas::get(
			'id_admin = '.(int)$idAdmin.' AND status = "enviando"'
		);
		$temAtiva = false;
		$soGrupos = true;
		while ($c = $results->fetchObject(EntityCampanhas::class)) {
			$temAtiva = true;
			if ($c->ehCampanhaGrupos()) {
				CampanhaWorker::reabastecerFilaGrupos($c);
			} else {
				$soGrupos = false;
			}
		}
		if (!$temAtiva) {
			return;
		}

		$pacing = CampanhaWorker::infoPacingGrupo($idAdmin);
		if ($soGrupos && empty($pacing['pode_enviar'])) {
			return;
		}

		CampanhaWorker::processar($idAdmin, 1, false);
		$ativos = EntityCampanhas::get('id_admin = '.(int)$idAdmin.' AND status = "enviando"');
		while ($c = $ativos->fetchObject(EntityCampanhas::class)) {
			$c->recalcularTotais();
		}
	}

	private static function formatarCampanha(EntityCampanhas $c): array {
		$pendentes = CampanhaFila::contarPorCampanha((int)$c->id, (int)$c->id_admin, 'pendente');
		$canal = ($c->canal ?? 'email') === 'whatsapp' ? 'whatsapp' : 'email';

		return [
			'id'          => (int)$c->id,
			'titulo'      => $c->titulo,
			'assunto'     => $c->assunto,
			'canal'       => $canal,
			'canal_label' => $canal === 'whatsapp' ? 'WhatsApp' : 'E-mail',
			'status'      => $c->status,
			'status_label'=> self::$statusLabels[$c->status] ?? $c->status,
			'total'       => (int)$c->total,
			'enviados'    => (int)$c->enviados,
			'erros'       => (int)$c->erros,
			'pendentes'   => $pendentes,
			'eh_grupos'   => $c->ehCampanhaGrupos() ? 1 : 0,
			'criada_em'   => $c->criada_em ? date('d/m/Y H:i', strtotime($c->criada_em)) : '',
			'segmento'    => json_decode($c->segmento ?? '{}', true) ?: [],
			'mensagem'    => $c->mensagem,
			'midia'       => self::extrairMidiaSegmento($c->segmento ?? null),
		];
	}

	private static function salvar(array $postVars): string {
		$idAdmin = TenantHelper::getIdAdmin();
		$usuarioId = TenantHelper::getUsuarioId();

		$titulo = trim($postVars['titulo'] ?? '');
		$assunto = trim($postVars['assunto'] ?? '');
		$mensagem = trim($postVars['mensagem'] ?? '');
		$id = (int)($postVars['id'] ?? 0);
		$removerMidia = !empty($postVars['remover_midia']);

		if ($titulo === '') {
			return json_encode(['success' => false, 'message' => 'Preencha o título.']);
		}

		$ob = null;
		$editavelCompleto = true;
		$editavelConteudo = true;

		if ($id > 0) {
			$ob = EntityCampanhas::getById($id, $idAdmin);
			if (!$ob instanceof EntityCampanhas) {
				return json_encode(['success' => false, 'message' => 'Campanha não encontrada.']);
			}
			$statusAtual = (string)$ob->status;
			$editavelConteudo = in_array($statusAtual, ['rascunho', 'pausada', 'enviando'], true);
			$editavelCompleto = ($statusAtual === 'rascunho');
			if (!$editavelConteudo) {
				return json_encode(['success' => false, 'message' => 'Esta campanha não pode ser editada neste status.']);
			}
		}

		if ($id > 0 && !$editavelCompleto) {
			// Em envio: só conteúdo (mensagem/mídia/título)
			$canal = ($ob->canal ?? 'email') === 'whatsapp' ? 'whatsapp' : 'email';
			$segmento = json_decode($ob->segmento ?? '{}', true) ?: [];
			if ($canal === 'whatsapp' && $assunto === '') {
				$assunto = $titulo;
			}
			if ($canal === 'email' && ($assunto === '' || $mensagem === '')) {
				return json_encode(['success' => false, 'message' => 'Preencha assunto e mensagem do e-mail.']);
			}
			if ($removerMidia) {
				unset($segmento['midia']);
			}
		} else {
			$canal = self::normalizarCanal($postVars['canal'] ?? 'email') ?: 'email';
			$tipoSegmento = $postVars['segmento_tipo'] ?? 'alunos_matriculados';
			$statusLead = $postVars['status_lead'] ?? '';

			if ($canal === 'email' && ($assunto === '' || $mensagem === '')) {
				return json_encode(['success' => false, 'message' => 'Preencha assunto e mensagem do e-mail.']);
			}
			if ($canal === 'whatsapp' && $assunto === '') {
				$assunto = $titulo;
			}
			if (!array_key_exists($tipoSegmento, CampanhaSegmentoHelper::getTipos())) {
				return json_encode(['success' => false, 'message' => 'Segmento inválido.']);
			}
			if ($tipoSegmento === 'whatsapp_grupos' && $canal !== 'whatsapp') {
				return json_encode(['success' => false, 'message' => 'Grupos/listas só podem ser usados no canal WhatsApp.']);
			}

			$segmento = [
				'tipo'        => $tipoSegmento,
				'status_lead' => $statusLead,
			];
			if ($tipoSegmento === 'whatsapp_grupos') {
				$destinos = self::parseDestinosGrupos($postVars);
				if (empty($destinos)) {
					return json_encode(['success' => false, 'message' => 'Selecione ao menos um grupo ou lista de transmissão.']);
				}
				$segmento['destinos'] = $destinos;
			}

			if ($id > 0) {
				$segAntigo = json_decode($ob->segmento ?? '{}', true) ?: [];
				if (!$removerMidia && empty($_FILES['arquivo']['tmp_name']) && !empty($segAntigo['midia'])) {
					$segmento['midia'] = $segAntigo['midia'];
				}
			} else {
				$ob = new EntityCampanhas;
				$ob->id_admin = $idAdmin;
				$ob->criada_por = $usuarioId;
				$ob->tipo = 'manual';
				$ob->status = 'rascunho';
			}
		}

		if ($canal === 'whatsapp' && !$removerMidia && !empty($_FILES['arquivo']) && is_array($_FILES['arquivo'])) {
			$midiaTipo = strtolower(trim((string)($postVars['midia_tipo'] ?? '')));
			if (!in_array($midiaTipo, ['image', 'document', 'audio'], true)) {
				$ft = (string)($_FILES['arquivo']['type'] ?? '');
				if (strpos($ft, 'image/') === 0) {
					$midiaTipo = 'image';
				} elseif (strpos($ft, 'audio/') === 0) {
					$midiaTipo = 'audio';
				} else {
					$midiaTipo = 'document';
				}
			}
			$saved = WhatsappMediaStorage::salvarUpload($idAdmin, $_FILES['arquivo']);
			if (!$saved) {
				return json_encode(['success' => false, 'message' => 'Falha ao salvar mídia (máx. 15 MB).']);
			}
			$segmento['midia'] = [
				'tipo' => $midiaTipo,
				'path' => $saved['relative'],
				'nome' => basename((string)($_FILES['arquivo']['name'] ?? $saved['relative'])),
				'mime' => $saved['mimetype'] ?? null,
				'url'  => $saved['url'] ?? WhatsappMediaStorage::urlPublica($saved['relative']),
			];
		}

		if ($canal === 'whatsapp' && $mensagem === '' && empty($segmento['midia'])) {
			return json_encode(['success' => false, 'message' => 'Informe uma mensagem e/ou anexe imagem, documento ou áudio.']);
		}

		$ob->canal = $canal;
		$ob->titulo = $titulo;
		$ob->assunto = $assunto;
		$ob->mensagem = $mensagem;
		$ob->segmento = json_encode($segmento, JSON_UNESCAPED_UNICODE);

		if ($id > 0) {
			$ok = $ob->atualizar();
		} else {
			$ok = $ob->cadastrar();
		}

		if (!$ok) {
			return json_encode(['success' => false, 'message' => 'Não foi possível salvar a campanha.']);
		}

		return json_encode([
			'success'  => true,
			'message'  => !$editavelCompleto ? 'Mensagem/mídia atualizadas. Valem para os próximos envios.' : 'Campanha salva.',
			'campanha' => self::formatarCampanha($ob),
		]);
	}

	private static function preview(array $postVars): string {
		$idAdmin = TenantHelper::getIdAdmin();
		$canal = self::normalizarCanal($postVars['canal'] ?? 'email') ?: 'email';
		$segmento = self::montarSegmento($postVars);
		$destinatarios = CampanhaSegmentoHelper::resolverDestinatarios($idAdmin, $segmento, $canal);
		$amostra = array_slice($destinatarios, 0, 5);

		return json_encode([
			'success' => true,
			'total'   => count($destinatarios),
			'amostra' => $amostra,
			'canal'   => $canal,
		]);
	}

	private static function iniciar(array $postVars): string {
		$idAdmin = TenantHelper::getIdAdmin();
		$id = (int)($postVars['id'] ?? 0);

		$ob = EntityCampanhas::getById($id, $idAdmin);
		if (!$ob instanceof EntityCampanhas) {
			return json_encode(['success' => false, 'message' => 'Campanha não encontrada.']);
		}

		if (!in_array($ob->status, ['rascunho', 'pausada'], true)) {
			return json_encode(['success' => false, 'message' => 'Campanha não pode ser iniciada neste status.']);
		}

		$canal = ($ob->canal ?? 'email') === 'whatsapp' ? 'whatsapp' : 'email';

		if ($canal === 'whatsapp') {
			$statusWa = WhatsappEscolaService::status($idAdmin);
			if (empty($statusWa['conectado'])) {
				return json_encode([
					'success' => false,
					'message' => 'WhatsApp não está conectado. Pareie o número em Configurações → Comunicação antes de iniciar.',
				]);
			}
		}

		if ($ob->status === 'pausada') {
			$ob->status = 'enviando';
			$ob->agendada_para = null;
			$ob->atualizar();
			$isGrupo = $ob->ehCampanhaGrupos();
			if ($isGrupo) {
				CampanhaWorker::reabastecerFilaGrupos($ob);
			}
			$resumo = CampanhaWorker::processar($idAdmin, $isGrupo ? 1 : 3, false);
			$ob = EntityCampanhas::getById($id, $idAdmin);
			$ob->recalcularTotais();
			$pend = CampanhaFila::contarPorCampanha($id, $idAdmin, 'pendente');
			if ($isGrupo) {
				CampanhaWorker::agendarContinuacaoGrupos($idAdmin, $id);
			}
			$pacing = CampanhaWorker::infoPacingGrupo($idAdmin);
			return json_encode([
				'success'  => true,
				'message'  => $isGrupo
					? 'Campanha retomada. Reenvio recorrente ativo (~'.$pacing['delay_minutos'].' min). Enviados nesta rodada: '.((int)($resumo['enviados'] ?? 0)).'.'
					: 'Campanha retomada. Enviados nesta rodada: '.((int)($resumo['enviados'] ?? 0)).'. Pendentes: '.$pend,
				'campanha' => self::formatarCampanha($ob),
				'worker'   => $resumo,
				'pacing'   => $pacing,
			]);
		}

		$segmento = json_decode($ob->segmento ?? '{}', true) ?: [];
		$isGrupo = ($segmento['tipo'] ?? '') === 'whatsapp_grupos';
		$destinatarios = CampanhaSegmentoHelper::resolverDestinatarios($idAdmin, $segmento, $canal);

		if (empty($destinatarios)) {
			$msg = $canal === 'whatsapp'
				? 'Nenhum destinatário com WhatsApp válido neste segmento.'
				: 'Nenhum destinatário com e-mail válido para este segmento.';
			return json_encode(['success' => false, 'message' => $msg]);
		}

		CampanhaFila::limparCampanha($id, $idAdmin);

		$itens = [];
		foreach ($destinatarios as $dest) {
			$itens[] = [
				'campanha_id'       => $id,
				'id_admin'          => $idAdmin,
				'destinatario_tipo' => $dest['destinatario_tipo'],
				'destinatario_id'   => $dest['destinatario_id'] ?? null,
				'nome'              => $dest['nome'] ?? '',
				'contato'           => $dest['contato'],
				'curso'             => $dest['curso'] ?? '',
			];
		}

		CampanhaFila::inserirLote($itens);

		$ob->status = 'enviando';
		$ob->total = count($itens);
		$ob->enviados = 0;
		$ob->erros = 0;
		$ob->agendada_para = null;
		$ob->atualizar();

		// Grupos: 1ª mensagem agora; depois reenvia nos mesmos grupos no intervalo até Encerrar
		$resumo = CampanhaWorker::processar($idAdmin, $isGrupo ? 1 : 2, false);

		$ob = EntityCampanhas::getById($id, $idAdmin);
		$ob->recalcularTotais();

		if ($isGrupo) {
			CampanhaWorker::agendarContinuacaoGrupos($idAdmin, $id);
		}

		$pacing = CampanhaWorker::infoPacingGrupo($idAdmin);
		$pend = CampanhaFila::contarPorCampanha($id, $idAdmin, 'pendente');
		$msg = $isGrupo
			? 'Campanha iniciada (recorrente). 1ª mensagem enviada. A mesma mensagem será reenviada aos grupos selecionados a cada ~'.$pacing['delay_minutos'].' min até você Encerrar.'
			: 'Campanha iniciada. '.$ob->enviados.' enviados, '.$ob->erros.' erros. Pendentes: '.$pend;

		return json_encode([
			'success'  => true,
			'message'  => $msg,
			'campanha' => self::formatarCampanha($ob),
			'worker'   => $resumo,
			'pacing'   => $pacing,
		]);
	}

	private static function pausar(array $postVars): string {
		$idAdmin = TenantHelper::getIdAdmin();
		$id = (int)($postVars['id'] ?? 0);
		$ob = EntityCampanhas::getById($id, $idAdmin);

		if (!$ob instanceof EntityCampanhas || $ob->status !== 'enviando') {
			return json_encode(['success' => false, 'message' => 'Campanha não está em envio.']);
		}

		$ob->status = 'pausada';
		$ob->atualizar();

		return json_encode(['success' => true, 'message' => 'Campanha pausada.', 'campanha' => self::formatarCampanha($ob)]);
	}

	private static function cancelar(array $postVars): string {
		$idAdmin = TenantHelper::getIdAdmin();
		$id = (int)($postVars['id'] ?? 0);
		$ob = EntityCampanhas::getById($id, $idAdmin);

		if (!$ob instanceof EntityCampanhas) {
			return json_encode(['success' => false, 'message' => 'Campanha não encontrada.']);
		}

		$pend = CampanhaFila::contarPorCampanha($id, $idAdmin, 'pendente');
		CampanhaFila::cancelarPendentes($id, $idAdmin);

		// Sem pendentes e já houve envio: encerra como concluída; senão cancela
		if ($pend <= 0 && (int)$ob->enviados > 0) {
			$ob->status = 'concluida';
			$msg = 'Campanha encerrada.';
		} else {
			$ob->status = 'cancelada';
			$msg = 'Campanha cancelada. Pendentes removidos da fila.';
		}
		$ob->atualizar();
		$ob->recalcularTotais();

		return json_encode(['success' => true, 'message' => $msg, 'campanha' => self::formatarCampanha($ob)]);
	}

	private static function detalhes(array $postVars): string {
		$idAdmin = TenantHelper::getIdAdmin();
		$id = (int)($postVars['id'] ?? 0);
		$ob = EntityCampanhas::getById($id, $idAdmin);

		if (!$ob instanceof EntityCampanhas) {
			return json_encode(['success' => false, 'message' => 'Campanha não encontrada.']);
		}

		$erros = [];
		$results = CampanhaFila::get(
			'campanha_id = '.(int)$id.' AND id_admin = '.(int)$idAdmin.' AND status = "erro"',
			'id DESC',
			'10'
		);

		while ($row = $results->fetchObject(CampanhaFila::class)) {
			$erros[] = [
				'nome'    => $row->nome,
				'contato' => $row->contato,
				'erro'    => $row->erro_msg,
			];
		}

		return json_encode([
			'success'  => true,
			'campanha' => self::formatarCampanha($ob),
			'erros'    => $erros,
			'mensagem' => $ob->mensagem,
			'assunto'  => $ob->assunto,
		]);
	}

	private static function processarFila(array $postVars): string {
		$idAdmin = TenantHelper::getIdAdmin();
		$limite = min(10, max(1, (int)($postVars['limite'] ?? 5)));
		$silencioso = !empty($postVars['silencioso']);
		// Auto-poll: sem sleep longo na request web
		$resumo = CampanhaWorker::processar($idAdmin, $limite, !$silencioso);

		$results = EntityCampanhas::get('id_admin = '.(int)$idAdmin.' AND status = "enviando"');
		while ($c = $results->fetchObject(EntityCampanhas::class)) {
			$c->recalcularTotais();
		}

		$pacing = CampanhaWorker::infoPacingGrupo($idAdmin);
		$msg = 'Processados: '.$resumo['processados'].'. Enviados: '.$resumo['enviados'].'. Erros: '.$resumo['erros'].'.';
		if ((int)$resumo['enviados'] === 0 && !empty($resumo['escolas'][$idAdmin]['whatsapp']['motivo'])) {
			$motivo = $resumo['escolas'][$idAdmin]['whatsapp']['motivo'];
			if ($motivo === 'pacing_grupo' && $pacing['proximo_em_segundos'] > 0) {
				$min = (int)ceil($pacing['proximo_em_segundos'] / 60);
				$msg .= ' Aguardando intervalo de grupos (~'.$min.' min).';
			}
		}

		return json_encode([
			'success' => true,
			'message' => $msg,
			'resumo'  => $resumo,
			'pacing'  => $pacing,
		]);
	}

	private static function montarSegmento(array $postVars): array {
		$seg = [
			'tipo'        => $postVars['segmento_tipo'] ?? 'alunos_matriculados',
			'status_lead' => $postVars['status_lead'] ?? '',
		];
		if (($seg['tipo'] ?? '') === 'whatsapp_grupos') {
			$seg['destinos'] = self::parseDestinosGrupos($postVars);
		}
		return $seg;
	}

	private static function parseDestinosGrupos(array $postVars): array {
		$raw = $postVars['destinos_json'] ?? '[]';
		if (is_array($raw)) {
			$data = $raw;
		} else {
			$data = json_decode((string)$raw, true);
		}
		if (!is_array($data)) {
			return [];
		}
		$out = [];
		foreach ($data as $d) {
			if (!is_array($d)) {
				continue;
			}
			$jid = EvolutionApiService::normalizarDestino((string)($d['jid'] ?? ''));
			if (!EvolutionApiService::isJidGrupoOuLista($jid)) {
				continue;
			}
			$out[] = [
				'jid'  => $jid,
				'nome' => trim((string)($d['nome'] ?? '')) ?: $jid,
				'kind' => (strpos(strtolower($jid), '@broadcast') !== false || ($d['kind'] ?? '') === 'lista')
					? 'lista'
					: 'grupo',
			];
		}
		return $out;
	}

	private static function normalizarCanal($canal): string {
		$canal = strtolower(trim((string)$canal));
		if ($canal === 'whatsapp' || $canal === 'email') {
			return $canal;
		}
		return '';
	}

	private static function extrairMidiaSegmento($segmentoRaw): ?array {
		$seg = is_array($segmentoRaw) ? $segmentoRaw : (json_decode((string)$segmentoRaw, true) ?: []);
		$m = $seg['midia'] ?? null;
		if (!is_array($m) || empty($m['path'])) {
			return null;
		}
		$path = (string)$m['path'];
		return [
			'tipo' => (string)($m['tipo'] ?? 'document'),
			'path' => $path,
			'nome' => (string)($m['nome'] ?? basename($path)),
			'mime' => $m['mime'] ?? null,
			'url'  => $m['url'] ?? WhatsappMediaStorage::urlPublica($path),
		];
	}
}

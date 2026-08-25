<?php

namespace App\Controller\Admin;

use App\Utils\View;
use App\Common\Helpers\TenantHelper;
use App\Common\Helpers\CampanhaSegmentoHelper;
use App\Common\Communication\CampanhaWorker;
use App\Common\Communication\WhatsappEscolaService;
use App\Common\Communication\WhatsappMediaStorage;
use App\Common\Helpers\SocialMediaStorage;
use App\Model\Entity\Campanhas as EntityCampanhas;
use App\Model\Entity\CampanhaFila;
use App\Model\Entity\CampanhaWorkerRun;
use App\Model\Entity\SocialBiblioteca;
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
		return parent::getPanel('Campanhas', $content, 'campanhas');
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
			case 'biblioteca_listar':
				return self::bibliotecaListar($postVars);
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

		$page = max(1, (int)($postVars['page'] ?? 1));
		$limit = 20;
		$rowCount = EntityCampanhas::get($where, null, null, 'COUNT(*) as q')->fetch(\PDO::FETCH_ASSOC);
		$total = (int)($rowCount['q'] ?? 0);
		$pages = max(1, (int)ceil($total / $limit));
		if ($page > $pages) {
			$page = $pages;
		}
		$offset = ($page - 1) * $limit;
		$results = EntityCampanhas::get($where, 'id DESC', $offset.','.$limit);

		$lista = [];
		while ($row = $results->fetchObject(EntityCampanhas::class)) {
			$lista[] = self::formatarCampanha($row);
		}

		return json_encode([
			'success'   => true,
			'campanhas' => $lista,
			'pacing'    => CampanhaWorker::infoPacingGrupo($idAdmin),
			'pacing_1a1' => CampanhaWorker::infoPacing1a1($idAdmin),
			'cron'      => self::metaCron(),
			'pagination' => [
				'page' => $page,
				'pages' => $pages,
				'total' => $total,
				'limit' => $limit,
			],
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
		if (!$soGrupos) {
			$pacing1a1 = CampanhaWorker::infoPacing1a1($idAdmin);
			if (empty($pacing1a1['pode_enviar'])) {
				return;
			}
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
			$parcelasAtrasoMin = self::normalizarParcelasAtrasoMin($postVars['parcelas_atraso_min'] ?? 1);

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
			if ($tipoSegmento === 'inadimplentes') {
				$segmento['parcelas_atraso_min'] = $parcelasAtrasoMin;
			}
			if ($tipoSegmento === 'whatsapp_grupos') {
				$destinos = self::parseDestinosGrupos($postVars);
				if (empty($destinos)) {
					return json_encode(['success' => false, 'message' => 'Selecione ao menos um grupo ou lista de transmissão.']);
				}
				$segmento['destinos'] = $destinos;
			}

			if ($id > 0) {
				$segAntigo = json_decode($ob->segmento ?? '{}', true) ?: [];
				if (!$removerMidia && empty($_FILES['arquivo']['tmp_name']) && empty($postVars['midia_biblioteca_path']) && !empty($segAntigo['midia'])) {
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
				'origem' => 'upload',
			];
		} elseif ($canal === 'whatsapp' && !$removerMidia) {
			$bibPath = trim((string)($postVars['midia_biblioteca_path'] ?? ''));
			if ($bibPath !== '' && empty($_FILES['arquivo']['tmp_name'])) {
				$midiaBib = self::resolverMidiaBiblioteca($idAdmin, $bibPath);
				if (!$midiaBib) {
					return json_encode(['success' => false, 'message' => 'Mídia da biblioteca inválida ou não encontrada.']);
				}
				$segmento['midia'] = $midiaBib;
			}
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
			// WhatsApp 1:1: aplicar delay (anti-rajada); grupos usam pacing próprio
			$aplicarDelay = ($canal === 'whatsapp' && !$isGrupo);
			$resumo = CampanhaWorker::processar($idAdmin, 1, $aplicarDelay);
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

		// Grupos: 1ª mensagem agora; 1:1 WhatsApp com delay (anti-rajada)
		$aplicarDelay = ($canal === 'whatsapp' && !$isGrupo);
		$resumo = CampanhaWorker::processar($idAdmin, 1, $aplicarDelay);

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
		$silencioso = !empty($postVars['silencioso']);
		// Poll web: 1 msg por request; manual/cron pode usar limite maior
		$limiteMax = $silencioso ? 1 : 10;
		$limite = min($limiteMax, max(1, (int)($postVars['limite'] ?? 5)));
		$resumo = CampanhaWorker::processar($idAdmin, $limite, !$silencioso);

		$results = EntityCampanhas::get('id_admin = '.(int)$idAdmin.' AND status = "enviando"');
		while ($c = $results->fetchObject(EntityCampanhas::class)) {
			$c->recalcularTotais();
		}

		$pacing = CampanhaWorker::infoPacingGrupo($idAdmin);
		$pacing1a1 = CampanhaWorker::infoPacing1a1($idAdmin);
		$msg = 'Processados: '.$resumo['processados'].'. Enviados: '.$resumo['enviados'].'. Erros: '.$resumo['erros'].'.';
		if ((int)$resumo['enviados'] === 0 && !empty($resumo['escolas'][$idAdmin]['whatsapp']['motivo'])) {
			$motivo = $resumo['escolas'][$idAdmin]['whatsapp']['motivo'];
			if ($motivo === 'pacing_grupo' && $pacing['proximo_em_segundos'] > 0) {
				$min = (int)ceil($pacing['proximo_em_segundos'] / 60);
				$msg .= ' Aguardando intervalo de grupos (~'.$min.' min).';
			} elseif ($motivo === 'pacing_1a1' && $pacing1a1['proximo_em_segundos'] > 0) {
				$msg .= ' Aguardando intervalo 1:1 (~'.$pacing1a1['proximo_em_segundos'].' s).';
			}
		}

		return json_encode([
			'success' => true,
			'message' => $msg,
			'resumo'  => $resumo,
			'pacing'  => $pacing,
			'pacing_1a1' => $pacing1a1,
		]);
	}

	private static function montarSegmento(array $postVars): array {
		$seg = [
			'tipo'        => $postVars['segmento_tipo'] ?? 'alunos_matriculados',
			'status_lead' => $postVars['status_lead'] ?? '',
		];
		if (($seg['tipo'] ?? '') === 'inadimplentes') {
			$seg['parcelas_atraso_min'] = self::normalizarParcelasAtrasoMin($postVars['parcelas_atraso_min'] ?? 1);
		}
		if (($seg['tipo'] ?? '') === 'whatsapp_grupos') {
			$seg['destinos'] = self::parseDestinosGrupos($postVars);
		}
		return $seg;
	}

	/** 1–6 = mínimo de parcelas em atraso (N ou mais). */
	private static function normalizarParcelasAtrasoMin($valor): int {
		$n = (int)$valor;
		if ($n < 1) {
			return 1;
		}
		if ($n > 6) {
			return 6;
		}
		return $n;
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
		$path = ltrim(str_replace('\\', '/', (string)$m['path']), '/');
		$url = $m['url'] ?? null;
		if (!$url) {
			if (strpos($path, 'uploads/social/') === 0) {
				$url = SocialMediaStorage::urlPublica($path);
			} else {
				$url = WhatsappMediaStorage::urlPublica($path);
			}
		}
		return [
			'tipo' => (string)($m['tipo'] ?? 'document'),
			'path' => $path,
			'nome' => (string)($m['nome'] ?? basename($path)),
			'mime' => $m['mime'] ?? null,
			'url'  => $url,
			'origem' => $m['origem'] ?? (strpos($path, 'uploads/social/') === 0 ? 'biblioteca' : 'upload'),
		];
	}

	private static function metaCron(): array {
		$ultima = CampanhaWorkerRun::ultima();
		$tokenOk = defined('SYSTEM_TOKEN') && SYSTEM_TOKEN !== '';
		$base = rtrim((string)URL, '/').'/cron/campanhas?token=';
		$tokenHint = $tokenOk ? '***' : 'SEU_SYSTEM_TOKEN';
		return [
			'tabela_ok' => CampanhaWorkerRun::tabelaExiste(),
			'token_configurado' => $tokenOk,
			'ultima' => $ultima,
			'url_cron' => $base.$tokenHint.'&limite=1',
			'hint' => !$tokenOk
				? 'Defina SYSTEM_TOKEN no .env e configure o cron no cPanel (a cada 1 min).'
				: (empty($ultima)
					? 'Com o painel fechado, campanhas dependem do cron no cPanel. Configure a URL abaixo.'
					: 'Cron registrado. Campanhas seguem mesmo com o painel fechado.'),
		];
	}

	private static function bibliotecaListar(array $postVars): string {
		if (!SocialBiblioteca::tabelaExiste()) {
			return json_encode([
				'success' => false,
				'sql_ok' => false,
				'message' => 'Execute database/social_fase_a_produto.sql',
			], JSON_UNESCAPED_UNICODE);
		}
		$idAdmin = TenantHelper::getIdAdmin();
		$tipo = trim((string)($postVars['tipo'] ?? 'image'));
		$tipo = ($tipo === 'image' || $tipo === 'video') ? $tipo : 'image';
		$formato = trim((string)($postVars['formato'] ?? ''));
		$formato = in_array($formato, ['feed', 'story'], true) ? $formato : null;
		$itens = [];
		foreach (SocialBiblioteca::listByAdmin($idAdmin, $tipo, $formato, 120) as $b) {
			$itens[] = [
				'id' => (int)$b->id,
				'titulo' => (string)($b->titulo ?? ''),
				'tipo' => $b->tipo,
				'formato' => $b->formato ?? null,
				'path' => $b->path_local,
				'url' => $b->urlPublica(),
				'mime' => $b->mime,
			];
		}
		return json_encode(['success' => true, 'sql_ok' => true, 'itens' => $itens], JSON_UNESCAPED_UNICODE);
	}

	/** @return array{tipo:string,path:string,nome:string,mime:?string,url:string,origem:string}|null */
	private static function resolverMidiaBiblioteca(int $idAdmin, string $pathRel): ?array {
		$pathRel = ltrim(str_replace(['..', '\\'], ['', '/'], trim($pathRel)), '/');
		if ($pathRel === '' || strpos($pathRel, 'uploads/social/') !== 0) {
			return null;
		}
		$prefix = 'uploads/social/'.(int)$idAdmin.'/';
		if (strpos($pathRel, $prefix) !== 0 && !SocialBiblioteca::pathEmUso($idAdmin, $pathRel)) {
			return null;
		}
		$abs = SocialMediaStorage::caminhoAbsoluto($pathRel);
		if (!is_file($abs)) {
			return null;
		}
		$bib = null;
		if (SocialBiblioteca::tabelaExiste()) {
			$rows = SocialBiblioteca::listByAdmin($idAdmin, null, null, 500);
			foreach ($rows as $b) {
				if ((string)$b->path_local === $pathRel) {
					$bib = $b;
					break;
				}
			}
		}
		$finfo = new \finfo(FILEINFO_MIME_TYPE);
		$mime = (string)($finfo->file($abs) ?: ($bib->mime ?? 'image/jpeg'));
		$tipo = strpos($mime, 'image/') === 0 ? 'image' : 'document';
		$nome = $bib && $bib->titulo ? (string)$bib->titulo : basename($pathRel);
		return [
			'tipo' => $tipo,
			'path' => $pathRel,
			'nome' => $nome,
			'mime' => $mime ?: null,
			'url'  => SocialMediaStorage::urlPublica($pathRel),
			'origem' => 'biblioteca',
		];
	}
}

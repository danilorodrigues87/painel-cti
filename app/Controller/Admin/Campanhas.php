<?php

namespace App\Controller\Admin;

use App\Utils\View;
use App\Common\Helpers\TenantHelper;
use App\Common\Helpers\CampanhaSegmentoHelper;
use App\Common\Communication\CampanhaWorker;
use App\Common\Communication\WhatsappEscolaService;
use App\Model\Entity\Campanhas as EntityCampanhas;
use App\Model\Entity\CampanhaFila;

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
			default:
				return json_encode(['success' => false, 'message' => 'Ação inválida.']);
		}
	}

	private static function listar(array $postVars): string {
		$idAdmin = TenantHelper::getIdAdmin();
		$canal = self::normalizarCanal($postVars['canal'] ?? '');
		$where = 'id_admin = '.(int)$idAdmin;
		if ($canal !== '') {
			$where .= ' AND canal = "'.addslashes($canal).'"';
		}

		$results = EntityCampanhas::get($where, 'id DESC', '50');

		$lista = [];
		while ($row = $results->fetchObject(EntityCampanhas::class)) {
			$lista[] = self::formatarCampanha($row);
		}

		return json_encode(['success' => true, 'campanhas' => $lista]);
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
			'criada_em'   => $c->criada_em ? date('d/m/Y H:i', strtotime($c->criada_em)) : '',
			'segmento'    => json_decode($c->segmento ?? '{}', true) ?: [],
			'mensagem'    => $c->mensagem,
		];
	}

	private static function salvar(array $postVars): string {
		$idAdmin = TenantHelper::getIdAdmin();
		$usuarioId = TenantHelper::getUsuarioId();

		$titulo = trim($postVars['titulo'] ?? '');
		$assunto = trim($postVars['assunto'] ?? '');
		$mensagem = trim($postVars['mensagem'] ?? '');
		$canal = self::normalizarCanal($postVars['canal'] ?? 'email') ?: 'email';
		$tipoSegmento = $postVars['segmento_tipo'] ?? 'alunos_matriculados';
		$statusLead = $postVars['status_lead'] ?? '';
		$id = (int)($postVars['id'] ?? 0);

		if ($titulo === '' || $mensagem === '') {
			return json_encode(['success' => false, 'message' => 'Preencha título e mensagem.']);
		}
		if ($canal === 'email' && $assunto === '') {
			return json_encode(['success' => false, 'message' => 'Preencha o assunto do e-mail.']);
		}
		if ($canal === 'whatsapp' && $assunto === '') {
			$assunto = $titulo;
		}

		if (!array_key_exists($tipoSegmento, CampanhaSegmentoHelper::getTipos())) {
			return json_encode(['success' => false, 'message' => 'Segmento inválido.']);
		}

		$segmento = [
			'tipo'        => $tipoSegmento,
			'status_lead' => $statusLead,
		];

		if ($id > 0) {
			$ob = EntityCampanhas::getById($id, $idAdmin);
			if (!$ob instanceof EntityCampanhas) {
				return json_encode(['success' => false, 'message' => 'Campanha não encontrada.']);
			}
			if (!in_array($ob->status, ['rascunho', 'pausada'], true)) {
				return json_encode(['success' => false, 'message' => 'Esta campanha não pode ser editada.']);
			}
		} else {
			$ob = new EntityCampanhas;
			$ob->id_admin = $idAdmin;
			$ob->criada_por = $usuarioId;
			$ob->tipo = 'manual';
			$ob->status = 'rascunho';
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
			'message'  => 'Campanha salva.',
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
			$ob->atualizar();
			$resumo = CampanhaWorker::processar($idAdmin, 3, false);
			$ob = EntityCampanhas::getById($id, $idAdmin);
			$ob->recalcularTotais();
			return json_encode([
				'success'  => true,
				'message'  => 'Campanha retomada. Pendentes: '.CampanhaFila::contarPorCampanha($id, $idAdmin, 'pendente'),
				'campanha' => self::formatarCampanha($ob),
				'worker'   => $resumo,
			]);
		}

		$segmento = json_decode($ob->segmento ?? '{}', true) ?: [];
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
			];
		}

		CampanhaFila::inserirLote($itens);

		$ob->status = 'enviando';
		$ob->total = count($itens);
		$ob->enviados = 0;
		$ob->erros = 0;
		$ob->atualizar();

		$resumo = CampanhaWorker::processar($idAdmin, 2, false);

		$ob = EntityCampanhas::getById($id, $idAdmin);
		$ob->recalcularTotais();

		return json_encode([
			'success'  => true,
			'message'  => 'Campanha iniciada. '.$ob->enviados.' enviados, '.$ob->erros.' erros. Pendentes: '.CampanhaFila::contarPorCampanha($id, $idAdmin, 'pendente'),
			'campanha' => self::formatarCampanha($ob),
			'worker'   => $resumo,
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

		CampanhaFila::cancelarPendentes($id, $idAdmin);
		$ob->status = 'cancelada';
		$ob->atualizar();
		$ob->recalcularTotais();

		return json_encode(['success' => true, 'message' => 'Campanha cancelada.', 'campanha' => self::formatarCampanha($ob)]);
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
		$resumo = CampanhaWorker::processar($idAdmin, $limite, true);

		$results = EntityCampanhas::get('id_admin = '.(int)$idAdmin.' AND status = "enviando"');
		while ($c = $results->fetchObject(EntityCampanhas::class)) {
			$c->recalcularTotais();
		}

		return json_encode([
			'success' => true,
			'message' => 'Processados: '.$resumo['processados'].'. Enviados: '.$resumo['enviados'].'. Erros: '.$resumo['erros'].'.',
			'resumo'  => $resumo,
		]);
	}

	private static function montarSegmento(array $postVars): array {
		return [
			'tipo'        => $postVars['segmento_tipo'] ?? 'alunos_matriculados',
			'status_lead' => $postVars['status_lead'] ?? '',
		];
	}

	private static function normalizarCanal($canal): string {
		$canal = strtolower(trim((string)$canal));
		if ($canal === 'whatsapp' || $canal === 'email') {
			return $canal;
		}
		return '';
	}
}

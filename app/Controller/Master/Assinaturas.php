<?php

namespace App\Controller\Master;

use App\Utils\View;
use App\Common\Helpers\MercadoPagoCtiHelper;
use App\Common\Helpers\SaasAssinaturaService;
use App\Model\Entity\EscolasAssinantes;
use App\Model\Entity\PlanosAssinatura;
use App\Model\Entity\SaasFatura;

class Assinaturas extends Page {

	public static function index($request) {
		if (!SaasFatura::tabelaExiste()) {
			$content = View::render('master/modules/assinaturas/sql', []);
			return parent::getPanel('Assinaturas — Master', $content, 'assinaturas');
		}

		$escolas = [];
		$results = EscolasAssinantes::getEscolas(null, 'nome ASC');
		while ($e = $results->fetchObject(EscolasAssinantes::class)) {
			$escolas[] = [
				'id'   => (int)$e->id,
				'nome' => (string)$e->nome,
			];
		}

		$mpOk = MercadoPagoCtiHelper::configurado();
		$webhook = $mpOk ? MercadoPagoCtiHelper::webhookUrl() : '';
		$content = View::render('master/modules/assinaturas/index', [
			'escolas_json'      => json_encode($escolas, JSON_UNESCAPED_UNICODE),
			'mp_ok'             => $mpOk ? '1' : '0',
			'mp_ok_hidden'      => $mpOk ? 'd-none' : '',
			'webhook_url_json'  => json_encode($webhook, JSON_UNESCAPED_UNICODE),
			'grace_dias'        => (string)SaasAssinaturaService::GRACE_DIAS,
			'competencia'       => date('Y-m'),
		]);
		return parent::getPanel('Assinaturas — Master', $content, 'assinaturas');
	}

	public static function getInfo($request) {
		if (!SaasFatura::tabelaExiste()) {
			return json_encode([
				'success' => false,
				'message' => 'Execute database/saas_assinatura.sql no phpMyAdmin.',
			]);
		}

		$post = $request->getPostVars();
		$acao = $post['acao'] ?? '';

		switch ($acao) {
			case 'listar':
				return self::listar($post);
			case 'dashboard':
				return json_encode([
					'success' => true,
					'dashboard' => SaasAssinaturaService::dashboardStats(),
				], JSON_UNESCAPED_UNICODE);
			case 'gerar':
				return self::gerar($post);
			case 'gerar_mes':
				return self::gerarMes($post);
			case 'reenviar_pix':
				return self::reenviarPix($post);
			case 'reenviar_email':
				return self::reenviarEmail($post);
			case 'marcar_paga':
				return self::marcarPagaManual($post);
			case 'processar':
				return self::processarWorker($post);
			default:
				return json_encode(['success' => false, 'message' => 'Ação inválida.']);
		}
	}

	private static function listar(array $post): string {
		$where = ['1=1'];
		$status = trim((string)($post['status'] ?? ''));
		$idAdmin = (int)($post['id_admin'] ?? 0);
		$competencia = trim((string)($post['competencia'] ?? ''));

		if ($status !== '' && in_array($status, ['aberta', 'pago', 'vencida', 'cancelada'], true)) {
			$where[] = 'status = "'.addslashes($status).'"';
		}
		if ($idAdmin > 0) {
			$where[] = 'id_admin = '.$idAdmin;
		}
		if ($competencia !== '' && preg_match('/^\d{4}-\d{2}$/', $competencia)) {
			$where[] = 'competencia = "'.addslashes($competencia).'"';
		}

		$results = SaasFatura::get(implode(' AND ', $where), 'vencimento DESC, id DESC', '200');
		$lista = [];
		while ($f = $results->fetchObject(SaasFatura::class)) {
			$lista[] = SaasAssinaturaService::formatar($f);
		}

		return json_encode([
			'success' => true,
			'faturas' => $lista,
			'mp_ok'   => MercadoPagoCtiHelper::configurado(),
			'dashboard' => SaasAssinaturaService::dashboardStats(),
		]);
	}

	private static function gerar(array $post): string {
		$idAdmin = (int)($post['id_admin'] ?? 0);
		$competencia = trim((string)($post['competencia'] ?? '')) ?: null;
		if ($idAdmin <= 0) {
			return json_encode(['success' => false, 'message' => 'Selecione a escola.']);
		}
		$r = SaasAssinaturaService::gerarFaturaEscola($idAdmin, $competencia);
		return json_encode([
			'success' => $r['ok'],
			'message' => $r['message'],
			'fatura'  => $r['fatura'] ?? null,
		]);
	}

	private static function gerarMes(array $post): string {
		$competencia = trim((string)($post['competencia'] ?? '')) ?: date('Y-m');
		if (!preg_match('/^\d{4}-\d{2}$/', $competencia)) {
			return json_encode(['success' => false, 'message' => 'Competência inválida.']);
		}

		$geradas = 0;
		$erros = [];
		$results = EscolasAssinantes::getEscolas(null, 'id ASC');
		while ($e = $results->fetchObject(EscolasAssinantes::class)) {
			SaasAssinaturaService::encerrarTrialSeExpirado($e);
			if (SaasAssinaturaService::emTrialAtivo($e)) {
				continue;
			}
			if (!SaasAssinaturaService::escolaCobravel($e)) {
				continue;
			}
			$r = SaasAssinaturaService::gerarFaturaEscola((int)$e->id, $competencia);
			if ($r['ok']) {
				$geradas++;
			} else {
				$erros[] = '#'.(int)$e->id.' '.$e->nome.': '.$r['message'];
			}
		}

		return json_encode([
			'success' => true,
			'message' => $geradas.' fatura(s) processada(s) para '.$competencia.'.',
			'geradas' => $geradas,
			'erros'   => array_slice($erros, 0, 20),
		]);
	}

	private static function reenviarPix(array $post): string {
		$id = (int)($post['id'] ?? 0);
		$fat = SaasFatura::getById($id);
		if (!$fat instanceof SaasFatura) {
			return json_encode(['success' => false, 'message' => 'Fatura não encontrada.']);
		}
		if ($fat->status === 'pago') {
			return json_encode(['success' => false, 'message' => 'Fatura já paga.']);
		}
		if (!MercadoPagoCtiHelper::configurado()) {
			return json_encode(['success' => false, 'message' => 'Configure MP_CTI_ACCESS_TOKEN no .env.']);
		}

		// Novo PIX (substitui o anterior)
		$fat->mp_payment_id = null;
		$fat->pix_copia_cola = null;
		$fat->pix_qr_base64 = null;
		$ok = SaasAssinaturaService::anexarPix($fat);
		if (!$ok) {
			$err = \App\Common\Gateways\MercadoPago\Pix::getUltimoErro();
			return json_encode([
				'success' => false,
				'message' => $err ?: 'Falha ao gerar PIX no Mercado Pago.',
			]);
		}

		return json_encode([
			'success' => true,
			'message' => 'PIX gerado.',
			'fatura'  => SaasAssinaturaService::formatar($fat),
		]);
	}

	private static function reenviarEmail(array $post): string {
		$id = (int)($post['id'] ?? 0);
		$fat = SaasFatura::getById($id);
		if (!$fat instanceof SaasFatura) {
			return json_encode(['success' => false, 'message' => 'Fatura não encontrada.']);
		}
		if ($fat->status === 'pago') {
			return json_encode(['success' => false, 'message' => 'Fatura já paga.']);
		}
		$ok = SaasAssinaturaService::enviarEmailCobranca($fat, null, true);
		return json_encode([
			'success' => $ok,
			'message' => $ok ? 'E-mail reenviado.' : 'Falha ao enviar (verifique SMTP sistema e e-mail da escola).',
			'fatura'  => SaasAssinaturaService::formatar($fat),
		]);
	}

	private static function marcarPagaManual(array $post): string {
		$id = (int)($post['id'] ?? 0);
		$fat = SaasFatura::getById($id);
		if (!$fat instanceof SaasFatura) {
			return json_encode(['success' => false, 'message' => 'Fatura não encontrada.']);
		}
		if ($fat->status === 'pago') {
			return json_encode(['success' => true, 'message' => 'Já estava paga.', 'fatura' => SaasAssinaturaService::formatar($fat)]);
		}
		SaasAssinaturaService::marcarPaga($fat);
		return json_encode([
			'success' => true,
			'message' => 'Fatura marcada como paga. Escola reativada se estava suspensa.',
			'fatura'  => SaasAssinaturaService::formatar($fat),
		]);
	}

	private static function processarWorker(array $post): string {
		$idAdmin = (int)($post['id_admin'] ?? 0);
		$resumo = SaasAssinaturaService::processar($idAdmin > 0 ? $idAdmin : null);
		return json_encode([
			'success' => true,
			'message' => 'Processamento concluído.',
			'resumo'  => $resumo,
		]);
	}
}

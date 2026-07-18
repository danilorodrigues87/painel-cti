<?php

namespace App\Controller\Webhook;

use App\Common\Helpers\MercadoPagoEscolaHelper;
use App\Common\Gateways\MercadoPago\Pix;
use App\Model\Entity\Caixa;
use App\Model\Entity\EscolaIntegracoes;

class MercadoPago {

	public static function receber($request, $idAdmin, $token): string {
		$idAdmin = (int)$idAdmin;
		if ($idAdmin <= 0 || !MercadoPagoEscolaHelper::validarWebhookToken($idAdmin, (string)$token)) {
			return json_encode(['success' => false, 'message' => 'Token inválido.']);
		}

		$raw = file_get_contents('php://input');
		$payload = json_decode((string)$raw, true);
		if (!is_array($payload)) {
			$post = $request->getPostVars();
			$payload = is_array($post) ? $post : [];
		}

		$type = strtolower((string)($payload['type'] ?? $payload['topic'] ?? ''));
		$action = strtolower((string)($payload['action'] ?? ''));
		$dataId = (string)($payload['data']['id'] ?? $payload['id'] ?? '');

		// Notificação antiga: topic=payment&id=...
		if ($dataId === '' && isset($payload['resource'])) {
			$dataId = basename((string)$payload['resource']);
		}

		if ($type !== '' && $type !== 'payment' && strpos($type, 'payment') === false) {
			return json_encode(['success' => true, 'ignored' => true]);
		}

		if ($dataId === '' && preg_match('/payment/i', $action) !== 1) {
			return json_encode(['success' => true, 'ignored' => true, 'reason' => 'sem_id']);
		}

		$pix = MercadoPagoEscolaHelper::pixDaEscola($idAdmin);
		if (!$pix instanceof Pix) {
			return json_encode(['success' => false, 'message' => 'Mercado Pago não configurado.']);
		}

		$pagamento = $pix->consultarPagamento($dataId);
		if (!$pagamento || ($pagamento['status'] ?? '') !== 'approved') {
			return json_encode(['success' => true, 'status' => $pagamento['status'] ?? 'unknown']);
		}

		$ok = self::baixarTitulo($idAdmin, $pagamento);
		return json_encode(['success' => true, 'baixado' => $ok]);
	}

	/** @param array{id:string,status:string,transaction_amount:float,external_reference:string,date_approved:?string} $pagamento */
	private static function baixarTitulo(int $idAdmin, array $pagamento): bool {
		$paymentId = preg_replace('/\D/', '', (string)$pagamento['id']);
		if ($paymentId === '') {
			return false;
		}

		$titulo = Caixa::getCaixa(
			"txt_id = '".$paymentId."' AND id_admin = ".(int)$idAdmin,
			null,
			'1'
		)->fetchObject(Caixa::class);

		if (!$titulo instanceof Caixa) {
			// fallback external_reference = id_admin:caixaId
			$ref = (string)($pagamento['external_reference'] ?? '');
			if (preg_match('/^(\d+):(\d+)$/', $ref, $m) && (int)$m[1] === $idAdmin) {
				$titulo = Caixa::getCaixa(
					'id = '.(int)$m[2].' AND id_admin = '.(int)$idAdmin,
					null,
					'1'
				)->fetchObject(Caixa::class);
			}
		}

		if (!$titulo instanceof Caixa) {
			return false;
		}

		$status = (string)($titulo->status ?? '');
		if ($status === '1' || $status === 'pago' || (int)$status === 1) {
			return true;
		}

		$dataPagamento = date('Y-m-d');
		if (!empty($pagamento['date_approved'])) {
			try {
				$dataPagamento = (new \DateTimeImmutable((string)$pagamento['date_approved']))->format('Y-m-d');
			} catch (\Throwable $e) {
				// keep today
			}
		}

		$valorPago = (float)$pagamento['transaction_amount'];
		if ($valorPago <= 0) {
			$valorPago = (float)$titulo->valor;
		}

		$titulo->id_admin = $idAdmin;
		$titulo->txt_id = $paymentId;
		$titulo->valor_pago = $valorPago;
		$titulo->data_pagamento = $dataPagamento;
		$titulo->ultima_alteracao = date('Y-m-d H:i:s');
		$titulo->baixaViaApi();

		return true;
	}

	public static function ping($idAdmin, $token): string {
		$idAdmin = (int)$idAdmin;
		if (!MercadoPagoEscolaHelper::validarWebhookToken($idAdmin, (string)$token)) {
			return json_encode(['ok' => false, 'message' => 'Token inválido.']);
		}
		$cfg = EscolaIntegracoes::getByIdAdmin($idAdmin);
		return json_encode([
			'ok'      => true,
			'service' => 'mercadopago',
			'mp_ativo'=> $cfg instanceof EscolaIntegracoes ? (int)$cfg->mp_ativo : 0,
		]);
	}
}

<?php

namespace App\Controller\Webhook;

use App\Common\Helpers\MetaGraphHelper;
use App\Model\Entity\EscolaIntegracoes;

/**
 * Webhook Meta Graph.
 * GET: verificação hub.challenge
 * POST: eventos (Fase 2 — comments/automação; por enquanto só ack)
 */
class Meta {

	/** Verify global (App) — GET /webhook/meta */
	public static function verifyGlobal($request) {
		$q = $request->getQueryParams() ?: [];
		return self::hubChallenge($q);
	}

	/** GET/POST /webhook/meta/{idAdmin}/{token} */
	public static function receber($request, $idAdmin, $token) {
		$idAdmin = (int)$idAdmin;
		$method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));

		if ($method === 'GET') {
			$q = $request->getQueryParams() ?: [];
			return self::hubChallenge($q);
		}

		$cfg = EscolaIntegracoes::getByIdAdmin($idAdmin);
		$esperado = ($cfg instanceof EscolaIntegracoes) ? (string)($cfg->meta_webhook_token ?? '') : '';
		if ($esperado === '' || !hash_equals($esperado, (string)$token)) {
			http_response_code(403);
			return json_encode(['success' => false, 'message' => 'Token inválido.']);
		}

		// Validação de assinatura (se App Secret configurado)
		$raw = file_get_contents('php://input');
		$sig = (string)($_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '');
		$secret = MetaGraphHelper::appSecret();
		if ($secret !== '' && $sig !== '') {
			$expected = 'sha256='.hash_hmac('sha256', (string)$raw, $secret);
			if (!hash_equals($expected, $sig)) {
				http_response_code(403);
				return json_encode(['success' => false, 'message' => 'Assinatura inválida.']);
			}
		}

		$payload = json_decode((string)$raw, true);
		// Fase 2: processar comments → automações
		// Por enquanto apenas confirma recebimento.
		if (is_array($payload)) {
			// noop
		}

		return json_encode(['success' => true]);
	}

	private static function hubChallenge(array $q) {
		$mode = (string)($q['hub_mode'] ?? $q['hub.mode'] ?? '');
		$token = (string)($q['hub_verify_token'] ?? $q['hub.verify_token'] ?? '');
		$challenge = (string)($q['hub_challenge'] ?? $q['hub.challenge'] ?? '');
		$expected = MetaGraphHelper::webhookVerifyToken();
		if ($mode === 'subscribe' && $expected !== '' && hash_equals($expected, $token)) {
			header('Content-Type: text/plain');
			return $challenge;
		}
		http_response_code(403);
		return 'Forbidden';
	}
}

<?php

namespace App\Controller\Cron;

use App\Common\Communication\CampanhaWorker;
use App\Model\Entity\Campanhas;

/**
 * Disparo HTTP do worker de campanhas (e-mail + WhatsApp).
 * GET/POST /cron/campanhas?token={SYSTEM_TOKEN}
 *
 * HostGator / cPanel: a cada 1–5 min, sem depender de sessão no painel.
 */
class Campanhas {

	public static function run($request) {
		header('Content-Type: application/json; charset=utf-8');
		$q = $request->getQueryParams() ?: [];
		$post = $request->getPostVars() ?: [];
		$token = (string)($q['token'] ?? $post['token'] ?? '');
		$expected = defined('SYSTEM_TOKEN') ? (string)SYSTEM_TOKEN : (string)(getenv('SYSTEM_TOKEN') ?: '');
		if ($expected === '' || $token === '' || !hash_equals($expected, $token)) {
			http_response_code(403);
			return json_encode(['success' => false, 'message' => 'Token inválido.']);
		}
		if (!Campanhas::tabelaExiste()) {
			http_response_code(500);
			return json_encode(['success' => false, 'message' => 'Tabelas de campanha ausentes.']);
		}
		$idAdmin = (int)($q['id_admin'] ?? $post['id_admin'] ?? 0);
		$limite = (int)($q['limite'] ?? $post['limite'] ?? 15);
		if ($limite < 1 || $limite > 50) {
			$limite = 15;
		}
		$resumo = CampanhaWorker::processar($idAdmin, $limite, true);
		return json_encode(['success' => true, 'resumo' => $resumo], JSON_UNESCAPED_UNICODE);
	}
}

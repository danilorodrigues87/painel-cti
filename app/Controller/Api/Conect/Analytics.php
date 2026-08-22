<?php

namespace App\Controller\Api\Conect;

use App\Model\Entity\CjAnalytics;

class Analytics {

	private const RATE_WINDOW = 3600;
	private const RATE_MAX = 120;
	private const DEBUG_LOG = __DIR__.'/../../../../debug-6b4d05.log';

	private static function debugLog(string $location, string $message, array $data, string $hypothesisId): void {
		// #region agent log
		@file_put_contents(self::DEBUG_LOG, json_encode([
			'sessionId'    => '6b4d05',
			'location'     => $location,
			'message'      => $message,
			'data'         => $data,
			'timestamp'    => (int)(microtime(true) * 1000),
			'hypothesisId' => $hypothesisId,
		], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n", FILE_APPEND);
		// #endregion
	}

	private static function respond(array $body, int $code = 200): array {
		return [
			'code' => $code,
			'json' => json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
		];
	}

	public static function event($request): array {
		if (!CjAnalytics::tabelasExistem()) {
			self::debugLog('Analytics.php:event', 'analytics tables missing', [], 'H2');
			return self::respond(['ok' => false, 'message' => 'Analytics não instalado.'], 503);
		}
		if (!self::permitirEvento()) {
			self::debugLog('Analytics.php:event', 'rate limit hit', [], 'H3');
			return self::respond(['ok' => false, 'message' => 'Limite atingido.'], 429);
		}

		$post = $request->getPostVars() ?: [];
		$tipo = (string)($post['tipo'] ?? '');
		self::debugLog('Analytics.php:event', 'event received', [
			'tipo'       => $tipo,
			'plataforma' => (string)($post['plataforma'] ?? ''),
			'path'       => (string)($post['path'] ?? ''),
			'postKeys'   => array_keys($post),
		], 'H4');

		if ($tipo === 'pageview') {
			$ok = CjAnalytics::registrarPageview(
				(string)($post['visitorKey'] ?? ''),
				(string)($post['path'] ?? '/'),
				isset($post['referrer']) ? (string)$post['referrer'] : null
			);
			return self::respond(['ok' => $ok]);
		}

		if ($tipo === 'share') {
			try {
				$ok = CjAnalytics::registrarShare(
					(string)($post['plataforma'] ?? ''),
					(string)($post['path'] ?? '/'),
					isset($post['slug']) ? (string)$post['slug'] : null,
					isset($post['titulo']) ? (string)$post['titulo'] : null
				);
				self::debugLog('Analytics.php:share', 'share result', [
					'ok'         => $ok,
					'plataforma' => (string)($post['plataforma'] ?? ''),
				], 'H2-H4');
				return self::respond(['ok' => $ok]);
			} catch (\Throwable $e) {
				self::debugLog('Analytics.php:share', 'share exception', [
					'error'      => $e->getMessage(),
					'plataforma' => (string)($post['plataforma'] ?? ''),
				], 'H2');
				return self::respond(['ok' => false, 'message' => 'Erro ao registrar share.'], 500);
			}
		}

		self::debugLog('Analytics.php:event', 'invalid tipo', ['tipo' => $tipo], 'H4');
		return self::respond(['ok' => false, 'message' => 'Tipo inválido.'], 400);
	}

	private static function permitirEvento(): bool {
		$ip = (string)($_SERVER['REMOTE_ADDR'] ?? '0');
		$dir = sys_get_temp_dir().'/conect_analytics_rate';
		if (!is_dir($dir)) {
			@mkdir($dir, 0755, true);
		}
		$file = $dir.'/'.md5($ip).'.json';
		$now = time();
		$data = ['times' => []];
		if (is_file($file)) {
			$decoded = json_decode((string)file_get_contents($file), true);
			if (is_array($decoded) && isset($decoded['times']) && is_array($decoded['times'])) {
				$data = $decoded;
			}
		}
		$data['times'] = array_values(array_filter(
			$data['times'],
			static fn($t) => is_int($t) && $t > ($now - self::RATE_WINDOW)
		));
		if (count($data['times']) >= self::RATE_MAX) {
			return false;
		}
		$data['times'][] = $now;
		@file_put_contents($file, json_encode($data));
		return true;
	}
}

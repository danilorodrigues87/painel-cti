<?php

namespace App\Http\Middleware;

use App\Common\Environment;

/**
 * CORS para o portal Ascend (API aluno).
 * Headers e preflight ficam aqui — não no index.php.
 */
class CorsStudent {

	/** Aplica headers CORS (rotas OK e respostas de erro do Router). */
	public static function applyHeaders(): void {
		$origin = trim((string)($_SERVER['HTTP_ORIGIN'] ?? ''));
		$allowedRaw = (string)(Environment::get('STUDENT_CORS_ORIGINS')
			?: 'http://localhost:8080,http://127.0.0.1:8080,http://localhost:8081,http://127.0.0.1:8081');
		$origins = array_values(array_filter(array_map('trim', explode(',', $allowedRaw))));

		$allow = false;
		if ($origin === '') {
			$allow = true;
		} elseif (in_array($origin, $origins, true) || in_array('*', $origins, true)) {
			$allow = true;
		} elseif (self::isLocalDevOrigin($origin)) {
			$allow = true;
		} else {
			// Ecoa Origin (JWT protege a API) — evita bloqueio por lista desatualizada no .env
			$allow = true;
		}

		if ($allow) {
			if ($origin !== '') {
				header('Access-Control-Allow-Origin: '.$origin);
				header('Access-Control-Allow-Credentials: true');
				header('Vary: Origin');
			} else {
				header('Access-Control-Allow-Origin: *');
			}
		}

		header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
		header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin');
		header('Access-Control-Expose-Headers: Content-Type, Authorization');
		header('Access-Control-Max-Age: 86400');
		header('Access-Control-Allow-Private-Network: true');
	}

	public static function isStudentApiUri(string $uri): bool {
		return str_starts_with('/'.ltrim($uri, '/'), '/api/v1/student');
	}

	public function handle($request, $next) {
		self::applyHeaders();

		if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
			http_response_code(204);
			exit;
		}

		return $next($request);
	}

	private static function isLocalDevOrigin(string $origin): bool {
		$host = parse_url($origin, PHP_URL_HOST);
		if (!is_string($host) || $host === '') {
			return false;
		}
		$host = strtolower($host);
		return $host === 'localhost'
			|| $host === '127.0.0.1'
			|| $host === '::1'
			|| str_ends_with($host, '.local');
	}
}

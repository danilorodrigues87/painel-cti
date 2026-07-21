<?php

namespace App\Http\Middleware;

/**
 * CORS para o portal Ascend (API aluno).
 */
class CorsStudent {

	public function handle($request, $next) {
		$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
		$allowed = getenv('STUDENT_CORS_ORIGINS');
		$origins = array_filter(array_map('trim', explode(',', $allowed ?: 'http://localhost:8080,http://127.0.0.1:8080')));

		if ($origin !== '' && in_array($origin, $origins, true)) {
			header('Access-Control-Allow-Origin: '.$origin);
			header('Access-Control-Allow-Credentials: true');
			header('Vary: Origin');
		} elseif ($origin === '' || in_array('*', $origins, true)) {
			header('Access-Control-Allow-Origin: '.($origin ?: '*'));
		}

		header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
		header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
		header('Access-Control-Max-Age: 86400');

		// Chrome Private Network Access (localhost ↔ 127.0.0.1)
		if (!empty($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_PRIVATE_NETWORK'])) {
			header('Access-Control-Allow-Private-Network: true');
		}

		if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
			http_response_code(204);
			exit;
		}

		return $next($request);
	}
}

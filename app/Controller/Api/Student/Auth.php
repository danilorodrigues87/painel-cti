<?php

namespace App\Controller\Api\Student;

use App\Model\Entity\User;
use App\Model\Entity\EscolasAssinantes;
use App\Common\Helpers\StudentApiMapper;
use Firebase\JWT\JWT;

class Auth {

	private static function json($data, int $code = 200): array {
		return ['_http' => $code, 'body' => $data];
	}

	public static function respond($result) {
		$code = is_array($result) && isset($result['_http']) ? (int)$result['_http'] : 200;
		$body = is_array($result) && isset($result['body']) ? $result['body'] : $result;
		return [
			'code' => $code,
			'json' => json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
		];
	}

	public static function login($request) {
		$post = $request->getPostVars() ?: [];
		$email = trim((string)($post['email'] ?? ''));
		$password = (string)($post['password'] ?? $post['senha'] ?? '');
		if ($email === '' || $password === '') {
			return self::respond(self::json(['message' => 'Informe email e senha.'], 400));
		}
		$user = User::getUserByEmail($email);
		if (!$user instanceof User || !password_verify($password, (string)$user->senha)) {
			return self::respond(self::json(['message' => 'Credenciais inválidas.'], 401));
		}
		if (($user->nivel ?? '') !== 'Cliente') {
			return self::respond(self::json(['message' => 'Acesso apenas para alunos.'], 403));
		}
		$escola = EscolasAssinantes::getEscolaById((int)$user->id_admin);
		if (!$escola || !$escola->isAtiva()) {
			return self::respond(self::json(['message' => 'Escola inativa.'], 403));
		}

		$expiresIn = 86400;
		$payload = [
			'sub' => (int)$user->id,
			'email' => $user->email,
			'id_admin' => (int)$user->id_admin,
			'nivel' => 'Cliente',
			'iat' => time(),
			'exp' => time() + $expiresIn,
		];
		$token = JWT::encode($payload, getenv('JWT_KEY') ?: 'change-me', 'HS256');

		return self::respond(self::json([
			'user' => StudentApiMapper::user($user),
			'tokens' => StudentApiMapper::tokens($token, $expiresIn),
		]));
	}

	public static function forgotPassword($request) {
		return self::respond(self::json(['ok' => true]));
	}

	public static function firstAccess($request) {
		// MVP: mesmo fluxo de login após definir senha no painel
		return self::login($request);
	}
}

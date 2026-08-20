<?php

namespace App\Controller\Api\ConectEmpresa;

use App\Common\Helpers\ConectApiMapper;
use App\Model\Entity\CjEmpresa;
use App\Model\Entity\User;

class Perfil {

	private static function respond(array $body, int $code = 200): array {
		return [
			'code' => $code,
			'json' => json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
		];
	}

	public static function atualizar($request): array {
		$user = $request->user ?? null;
		$empresa = $request->empresa ?? null;
		if (!$user instanceof User || !$empresa instanceof CjEmpresa) {
			return self::respond(['message' => 'Não autenticado.'], 401);
		}
		if (!CjEmpresa::tabelaExiste()) {
			return self::respond(['message' => 'Módulo não instalado.'], 503);
		}

		$post = $request->getPostVars() ?: [];
		$dados = [];

		if (array_key_exists('nomeFantasia', $post) || array_key_exists('nome_fantasia', $post)) {
			$fantasia = trim((string)($post['nomeFantasia'] ?? $post['nome_fantasia'] ?? ''));
			if ($fantasia !== '') {
				$dados['nome_fantasia'] = $fantasia;
				$user->nome = $fantasia;
				$user->atualizar();
			}
		}
		if (array_key_exists('contatoNome', $post) || array_key_exists('contato_nome', $post)) {
			$dados['contato_nome'] = trim((string)($post['contatoNome'] ?? $post['contato_nome'] ?? ''));
		}
		if (array_key_exists('whatsapp', $post)) {
			$dados['whatsapp'] = preg_replace('/\D+/', '', (string)$post['whatsapp']);
		}
		if (array_key_exists('email', $post)) {
			$email = strtolower(trim((string)$post['email']));
			if ($email !== '') {
				$dados['email'] = $email;
			}
		}
		if (array_key_exists('cidadeId', $post) || array_key_exists('cidade_id', $post)) {
			$cidadeId = (int)($post['cidadeId'] ?? $post['cidade_id'] ?? 0);
			$dados['cidade_id'] = $cidadeId > 0 ? $cidadeId : null;
		}
		if (array_key_exists('bairro', $post)) {
			$bairro = trim((string)$post['bairro']);
			$dados['bairro'] = $bairro !== '' ? $bairro : null;
		}
		if (array_key_exists('uf', $post)) {
			$uf = strtoupper(substr(trim((string)$post['uf']), 0, 2));
			$dados['uf'] = $uf !== '' ? $uf : null;
		}

		if ($dados === []) {
			return self::respond(['message' => 'Nada para atualizar.'], 400);
		}

		CjEmpresa::atualizar((int)$empresa->id, $dados);
		$empresaAtual = CjEmpresa::getById((int)$empresa->id);

		return self::respond([
			'message' => 'Perfil atualizado.',
			'user'    => ConectApiMapper::userEmpresa($user, $empresaAtual),
			'empresa' => ConectApiMapper::empresaPerfil($empresaAtual),
		]);
	}
}

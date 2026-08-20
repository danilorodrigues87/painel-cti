<?php

namespace App\Controller\Api\Conect;

use App\Common\Helpers\ConectApiMapper;
use App\Model\Entity\CjCandidato;
use App\Model\Entity\CjCandidatoHabilidade;
use App\Model\Entity\User;

class Perfil {

	use PerfilCandidatoResponse;

	private static function respond(array $body, int $code = 200): array {
		return [
			'code' => $code,
			'json' => json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
		];
	}

	public static function atualizar($request): array {
		$user = $request->user ?? null;
		$candidato = $request->candidato ?? null;
		if (!$user instanceof User || !$candidato instanceof CjCandidato) {
			return self::respond(['message' => 'Não autenticado.'], 401);
		}
		if (!CjCandidato::tabelaExiste()) {
			return self::respond(['message' => 'Módulo não instalado.'], 503);
		}

		$post = $request->getPostVars() ?: [];
		$dados = [];

		if (array_key_exists('nome', $post)) {
			$nome = trim((string)$post['nome']);
			if ($nome === '') {
				return self::respond(['message' => 'Nome é obrigatório.'], 400);
			}
			$dados['nome'] = $nome;
			$user->nome = $nome;
			$user->atualizar();
		}

		if (array_key_exists('whatsapp', $post)) {
			$dados['whatsapp'] = preg_replace('/\D+/', '', (string)$post['whatsapp']);
		}
		if (array_key_exists('resumo', $post)) {
			$dados['resumo'] = trim((string)$post['resumo']);
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
		if (array_key_exists('disponibilidade', $post)) {
			$disp = trim((string)$post['disponibilidade']);
			if (!in_array($disp, ['imediata', '15_dias', '30_dias', 'a_combinar'], true)) {
				$disp = 'imediata';
			}
			$dados['disponibilidade'] = $disp;
		}

		if ($dados !== []) {
			CjCandidato::atualizar((int)$candidato->id, $dados);
		}

		if (array_key_exists('habilidades', $post) && is_array($post['habilidades'])) {
			CjCandidatoHabilidade::sincronizar((int)$candidato->id, $post['habilidades']);
		}

		$candidatoAtual = CjCandidato::getById((int)$candidato->id);
		if (!$candidatoAtual) {
			return self::respond(['message' => 'Perfil não encontrado.'], 404);
		}

		return self::respond(array_merge(
			['message' => 'Perfil atualizado.'],
			self::perfilResponse($user, $candidatoAtual)
		));
	}
}

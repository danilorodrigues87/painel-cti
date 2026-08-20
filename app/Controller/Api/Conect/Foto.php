<?php

namespace App\Controller\Api\Conect;

use App\Common\Helpers\UserFotoHelper;
use App\Model\Entity\CjCandidato;
use App\Model\Entity\User;

class Foto {

	use PerfilCandidatoResponse;

	private static function respond(array $body, int $code = 200): array {
		return [
			'code' => $code,
			'json' => json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
		];
	}

	public static function upload($request): array {
		$user = $request->user ?? null;
		$candidato = $request->candidato ?? null;
		if (!$user instanceof User || !$candidato instanceof CjCandidato) {
			return self::respond(['message' => 'Não autenticado.'], 401);
		}

		$files = $request->getFileVars();
		$post = $request->getPostVars() ?: [];
		$restaurar = !empty($post['restaurar']);
		$usarPortal = !empty($post['usarPortal']);

		if ($usarPortal && User::temColunaFoto() && !empty($user->foto)) {
			CjCandidato::atualizar((int)$candidato->id, ['foto' => (string)$user->foto]);
		} elseif ($restaurar) {
			CjCandidato::atualizar((int)$candidato->id, ['foto' => null]);
		} else {
			$nova = UserFotoHelper::processarUpload($files['foto'] ?? null, $candidato->foto ?? null);
			if ($nova !== null) {
				CjCandidato::atualizar((int)$candidato->id, ['foto' => $nova]);
				if (User::temColunaFoto()) {
					$user->foto = $nova;
					$user->atualizar();
				}
			}
		}

		$candidatoAtual = CjCandidato::getById((int)$candidato->id);
		if (!$candidatoAtual) {
			return self::respond(['message' => 'Perfil não encontrado.'], 404);
		}

		return self::respond(array_merge(
			['message' => 'Foto atualizada.'],
			self::perfilResponse($user, $candidatoAtual)
		));
	}
}

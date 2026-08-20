<?php

namespace App\Controller\Api\Conect;

use App\Common\Helpers\ConectApiMapper;
use App\Common\Helpers\ConectCandidatoFormacaoHelper;
use App\Model\Entity\CjCandidato;
use App\Model\Entity\CjCandidatoFormacao;
use App\Model\Entity\CjCandidatoHabilidade;
use App\Model\Entity\User;

trait PerfilCandidatoResponse {

	private static function perfilResponse(User $user, CjCandidato $candidato): array {
		if (($user->nivel ?? '') === 'Cliente' || ($candidato->tipo ?? '') === 'aluno') {
			ConectCandidatoFormacaoHelper::syncAllForUsuario((int)$user->id);
		}

		if (empty($candidato->foto) && User::temColunaFoto() && !empty($user->foto)) {
			CjCandidato::atualizar((int)$candidato->id, ['foto' => (string)$user->foto]);
			$candidato = CjCandidato::getById((int)$candidato->id) ?? $candidato;
		}

		$habilidades = CjCandidatoHabilidade::listarPorCandidato((int)$candidato->id);
		$formacao = ConectCandidatoFormacaoHelper::listarParaApi((int)$candidato->id);
		$temSelo = CjCandidatoFormacao::temSeloCertificado((int)$candidato->id);

		return [
			'user'      => ConectApiMapper::userCandidato($user, $candidato),
			'candidato' => ConectApiMapper::candidatoPerfil($candidato, $habilidades, $formacao, $temSelo),
		];
	}
}

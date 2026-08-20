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

		$habilidades = CjCandidatoHabilidade::listarPorCandidato((int)$candidato->id);
		$formacao = ConectCandidatoFormacaoHelper::listarParaApi((int)$candidato->id);
		$temSelo = CjCandidatoFormacao::temSeloCertificado((int)$candidato->id);

		return [
			'user'      => ConectApiMapper::userCandidato($user, $candidato),
			'candidato' => ConectApiMapper::candidatoPerfil($candidato, $habilidades, $formacao, $temSelo),
		];
	}
}

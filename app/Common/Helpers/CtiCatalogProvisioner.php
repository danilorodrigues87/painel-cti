<?php

namespace App\Common\Helpers;

use App\Common\CtiCatalog;
use App\Model\Entity\EscolasAssinantes;
use App\Model\Entity\LmsCurso;
use App\Model\Entity\LmsEscolaCursoCti;
use App\Model\Entity\PlanosCurso;

class CtiCatalogProvisioner {

	public static function syncEscola(int $idAdmin): void {
		if ($idAdmin <= 0 || !LmsEscolaCursoCti::tabelaExiste() || !PlanosCurso::tabelaExiste()) {
			return;
		}
		if (CtiCatalog::isEscolaCatalogo($idAdmin)) {
			return;
		}

		$escola = EscolasAssinantes::getEscolaById($idAdmin);
		if (!$escola instanceof EscolasAssinantes) {
			return;
		}

		$planId = EscolasAssinantes::temColunaPlanId() ? (int)($escola->plan_id ?? 0) : 0;
		$cursoIds = $planId > 0 ? PlanosCurso::idsPorPlano($planId) : [];

		$validos = [];
		foreach ($cursoIds as $cursoId) {
			$curso = LmsCurso::getById((int)$cursoId);
			if (!$curso instanceof LmsCurso || !CtiCatalog::isCursoCti($curso)) {
				continue;
			}
			LmsEscolaCursoCti::upsert($idAdmin, (int)$curso->id, $planId);
			$validos[] = (int)$curso->id;
		}

		LmsEscolaCursoCti::desativarForaLista($idAdmin, $validos);
	}

	public static function syncTodasEscolasDoPlano(int $planId): void {
		if ($planId <= 0 || !EscolasAssinantes::temColunaPlanId()) {
			return;
		}
		$results = EscolasAssinantes::getEscolas('plan_id = '.(int)$planId);
		while ($e = $results->fetchObject(EscolasAssinantes::class)) {
			self::syncEscola((int)$e->id);
		}
	}
}

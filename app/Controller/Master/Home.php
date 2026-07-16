<?php

namespace App\Controller\Master;

use App\Utils\View;
use App\Model\Entity\EscolasAssinantes;

class Home extends Page {

	public static function index($request) {
		$stats = self::contarEscolas();

		$content = View::render('master/modules/home/index', [
			'total'    => $stats['total'],
			'ativas'   => $stats['ativas'],
			'inativas' => $stats['inativas'],
		]);

		return parent::getPanel('Dashboard Master', $content, 'home');
	}

	private static function contarEscolas(): array {
		$total = 0;
		$ativas = 0;
		$results = EscolasAssinantes::getEscolas(null, 'nome ASC', null, 'id, ativo');
		while ($row = $results->fetch(\PDO::FETCH_ASSOC)) {
			$total++;
			if (EscolasAssinantes::isAtivaValor($row['ativo'] ?? null)) {
				$ativas++;
			}
		}
		return [
			'total'    => $total,
			'ativas'   => $ativas,
			'inativas' => max(0, $total - $ativas),
		];
	}
}

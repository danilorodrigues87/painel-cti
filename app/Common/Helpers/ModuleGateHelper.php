<?php

namespace App\Common\Helpers;

use App\Common\SystemModules;
use App\Model\Entity\EscolasAssinantes;

class ModuleGateHelper {

	private static $cacheEscola = [];

	public static function getSlugsEscola(int $idAdmin): array {
		if ($idAdmin <= 0) {
			return SystemModules::getSlugs();
		}

		if (isset(self::$cacheEscola[$idAdmin])) {
			return self::$cacheEscola[$idAdmin];
		}

		$escola = EscolasAssinantes::getEscolaById($idAdmin);
		if (!$escola) {
			self::$cacheEscola[$idAdmin] = SystemModules::getSlugs();
			return self::$cacheEscola[$idAdmin];
		}

		$raw = $escola->modulos_liberados ?? null;

		if ($raw === null || $raw === '') {
			self::$cacheEscola[$idAdmin] = SystemModules::getSlugs();
			return self::$cacheEscola[$idAdmin];
		}

		$decoded = json_decode($raw, true);
		if (!is_array($decoded) || empty($decoded)) {
			self::$cacheEscola[$idAdmin] = SystemModules::getSlugs();
			return self::$cacheEscola[$idAdmin];
		}

		$slugsValidos = SystemModules::getSlugs();
		$slugsMap = array_flip($slugsValidos);
		$filtrados = [];

		foreach ($decoded as $slug) {
			$slug = (string)$slug;
			if (isset($slugsMap[$slug])) {
				$filtrados[] = $slug;
			}
		}

		self::$cacheEscola[$idAdmin] = !empty($filtrados) ? $filtrados : SystemModules::getSlugs();
		return self::$cacheEscola[$idAdmin];
	}

	public static function getModulosEscola(int $idAdmin): array {
		return SystemModules::slugsParaLabels(self::getSlugsEscola($idAdmin));
	}

	public static function getModulosDisponiveisParaEscola(int $idAdmin): array {
		return self::getModulosEscola($idAdmin);
	}

	public static function normalizarAcessoUsuario(array $acessoUsuario): array {
		$labels = [];
		foreach ($acessoUsuario as $item) {
			if ($item === '' || $item === 0 || $item === '0') {
				continue;
			}
			$label = SystemModules::normalizarLabel((string)$item);
			if ($label !== null) {
				$labels[] = $label;
			}
		}
		return array_values(array_unique($labels));
	}

	public static function getModulosEfetivos(int $idAdmin, array $acessoUsuario): array {
		$escola = self::getModulosEscola($idAdmin);
		$usuario = self::normalizarAcessoUsuario($acessoUsuario);

		if (empty($escola)) {
			return $usuario;
		}

		return array_values(array_intersect($escola, $usuario));
	}

	public static function podeAcessar(string $label, int $idAdmin, array $acessoUsuario): bool {
		$label = SystemModules::normalizarLabel($label);
		if ($label === null) {
			return false;
		}
		return in_array($label, self::getModulosEfetivos($idAdmin, $acessoUsuario), true);
	}

	public static function sanitizarAcesso(int $idAdmin, array $acessoUsuario): array {
		$efetivos = self::getModulosEfetivos($idAdmin, $acessoUsuario);
		return !empty($efetivos) ? $efetivos : [''];
	}

	public static function escolaTemTodosModulos(int $idAdmin): bool {
		$escola = EscolasAssinantes::getEscolaById($idAdmin);
		if (!$escola) {
			return true;
		}
		$raw = $escola->modulos_liberados ?? null;
		return $raw === null || $raw === '';
	}

	public static function limparCache(?int $idAdmin = null): void {
		if ($idAdmin === null) {
			self::$cacheEscola = [];
			return;
		}
		unset(self::$cacheEscola[$idAdmin]);
	}

}

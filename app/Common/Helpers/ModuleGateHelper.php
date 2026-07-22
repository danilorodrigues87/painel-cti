<?php

namespace App\Common\Helpers;

use App\Common\SystemModules;
use App\Model\Entity\EscolasAssinantes;

class ModuleGateHelper {

	private static $cacheEscola = [];

	public static function getSlugsEscola(int $idAdmin): array {
		if ($idAdmin <= 0) {
			return self::expandirSlugsDependentes(SystemModules::getSlugs());
		}

		if (isset(self::$cacheEscola[$idAdmin])) {
			return self::$cacheEscola[$idAdmin];
		}

		$escola = EscolasAssinantes::getEscolaById($idAdmin);
		if (!$escola) {
			self::$cacheEscola[$idAdmin] = self::expandirSlugsDependentes(SystemModules::getSlugs());
			return self::$cacheEscola[$idAdmin];
		}

		$raw = $escola->modulos_liberados ?? null;

		if ($raw === null || $raw === '') {
			self::$cacheEscola[$idAdmin] = self::expandirSlugsDependentes(SystemModules::getSlugs());
			return self::$cacheEscola[$idAdmin];
		}

		$decoded = json_decode($raw, true);
		if (!is_array($decoded) || empty($decoded)) {
			self::$cacheEscola[$idAdmin] = self::expandirSlugsDependentes(SystemModules::getSlugs());
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
		self::$cacheEscola[$idAdmin] = self::expandirSlugsDependentes(self::$cacheEscola[$idAdmin]);
		return self::$cacheEscola[$idAdmin];
	}

	/**
	 * Plano com `ead` também libera o submódulo `conquistas_ead` (checkbox separado).
	 * @param string[] $slugs
	 * @return string[]
	 */
	private static function expandirSlugsDependentes(array $slugs): array {
		if (in_array('ead', $slugs, true) && !in_array('conquistas_ead', $slugs, true)) {
			$slugs[] = 'conquistas_ead';
		}
		return array_values(array_unique($slugs));
	}

	public static function getModulosEscola(int $idAdmin): array {
		return SystemModules::slugsParaLabels(self::getSlugsEscola($idAdmin));
	}

	/**
	 * Labels exibidos no checklist de funcionários.
	 * Exclui itens que só o Diretor usa via menu automático (não fazem sentido para equipe).
	 */
	public static function getModulosDisponiveisParaEscola(int $idAdmin): array {
		$labels = self::getModulosEscola($idAdmin);
		$somenteDiretor = ['Dados da escola', 'Assinatura'];
		return array_values(array_filter($labels, static function ($l) use ($somenteDiretor) {
			return !in_array($l, $somenteDiretor, true);
		}));
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

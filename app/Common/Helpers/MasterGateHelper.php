<?php

namespace App\Common\Helpers;

class MasterGateHelper {

	/** @return string[] */
	public static function emailsPermitidos(): array {
		$raw = (string)(getenv('MASTER_EMAILS') ?: '');
		if (trim($raw) === '') {
			return [];
		}
		$parts = preg_split('/[\s,;]+/', $raw) ?: [];
		$emails = [];
		foreach ($parts as $p) {
			$p = strtolower(trim($p));
			if ($p !== '' && filter_var($p, FILTER_VALIDATE_EMAIL)) {
				$emails[$p] = true;
			}
		}
		return array_keys($emails);
	}

	public static function isMasterEmail(?string $email): bool {
		$email = strtolower(trim((string)$email));
		if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
			return false;
		}
		return in_array($email, self::emailsPermitidos(), true);
	}

	public static function isMasterSession(): bool {
		$email = $_SESSION['usuario-mvc-1']['email'] ?? '';
		if (self::isMasterEmail((string)$email)) {
			$_SESSION['usuario-mvc-1']['is_master'] = true;
			return true;
		}
		$_SESSION['usuario-mvc-1']['is_master'] = false;
		return false;
	}
}

<?php

namespace App\Model\Entity;

use App\Model\Db\Database;
use PDO;

class CjPortalBranding {

	public static function get(): array {
		try {
			$stmt = (new Database('cj_portal_branding'))->select('id = 1', null, '1');
			$row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
			return is_array($row) ? $row : ['nome_portal' => 'Conecta Jovem'];
		} catch (\Throwable $e) {
			return ['nome_portal' => 'Conecta Jovem'];
		}
	}
}

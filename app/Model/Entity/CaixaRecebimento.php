<?php

namespace App\Model\Entity;

use App\Model\Db\Database;

class CaixaRecebimento {

	public $id;
	public $id_admin;
	public $caixa_id;
	public $tipo_pagamento;
	public $valor;
	public $data_pagamento;
	public $id_usuario;
	public $created_at;

	private static $cacheTabela = null;

	public static function tabelaExiste(): bool {
		if (self::$cacheTabela !== null) {
			return self::$cacheTabela;
		}
		try {
			$stmt = (new Database())->execute("SHOW TABLES LIKE 'caixa_recebimentos'");
			self::$cacheTabela = (bool)$stmt->fetch();
		} catch (\Throwable $e) {
			self::$cacheTabela = false;
		}
		return self::$cacheTabela;
	}

	/** @return self[] */
	public static function listByCaixa(int $caixaId, int $idAdmin = 0): array {
		if (!self::tabelaExiste() || $caixaId <= 0) {
			return [];
		}
		$where = 'caixa_id = '.(int)$caixaId;
		if ($idAdmin > 0) {
			$where .= ' AND id_admin = '.(int)$idAdmin;
		}
		$stmt = (new Database('caixa_recebimentos'))->select($where, 'id ASC');
		$out = [];
		while ($row = $stmt->fetchObject(self::class)) {
			$out[] = $row;
		}
		return $out;
	}

	public static function somaPorCaixa(int $caixaId, int $idAdmin = 0): float {
		if (!self::tabelaExiste() || $caixaId <= 0) {
			return 0.0;
		}
		$where = 'caixa_id = '.(int)$caixaId;
		if ($idAdmin > 0) {
			$where .= ' AND id_admin = '.(int)$idAdmin;
		}
		$stmt = (new Database('caixa_recebimentos'))->select($where, null, null, 'COALESCE(SUM(valor),0) AS s');
		$row = $stmt->fetch(\PDO::FETCH_ASSOC);
		return round((float)($row['s'] ?? 0), 2);
	}

	public function inserir(): bool {
		if (!self::tabelaExiste()) {
			return false;
		}
		(new Database('caixa_recebimentos'))->insert([
			'id_admin' => (int)$this->id_admin,
			'caixa_id' => (int)$this->caixa_id,
			'tipo_pagamento' => (string)$this->tipo_pagamento,
			'valor' => round((float)$this->valor, 2),
			'data_pagamento' => (string)$this->data_pagamento,
			'id_usuario' => $this->id_usuario !== null ? (int)$this->id_usuario : null,
		]);
		return true;
	}
}

<?php

namespace App\Model\Entity;

use App\Model\Db\Database;
use PDO;

class CjCandidato {

	public $id;
	public $id_usuario;
	public $id_admin;
	public $tipo = 'externo';
	public $cadastrado_por_usuario_id;
	public $nome;
	public $email;
	public $whatsapp;
	public $cidade_id;
	public $bairro;
	public $uf;
	public $resumo;
	public $status = 'ativo';
	public $crm_lead_id;

	public static function tabelaExiste(): bool {
		static $ok = null;
		if ($ok !== null) {
			return $ok;
		}
		try {
			$stmt = (new Database())->execute("SHOW TABLES LIKE 'cj_candidatos'");
			$ok = $stmt && $stmt->rowCount() > 0;
		} catch (\Throwable $e) {
			$ok = false;
		}
		return $ok;
	}

	public static function getById(int $id): ?self {
		if (!self::tabelaExiste() || $id <= 0) {
			return null;
		}
		$row = self::select('c.id = '.(int)$id, null, '1')->fetchObject(self::class);
		return $row instanceof self ? $row : null;
	}

	public static function getByUsuarioId(int $idUsuario): ?self {
		if (!self::tabelaExiste() || $idUsuario <= 0) {
			return null;
		}
		$row = self::select('c.id_usuario = '.(int)$idUsuario, null, '1')->fetchObject(self::class);
		return $row instanceof self ? $row : null;
	}

	public static function getByEmail(string $email): ?self {
		$email = strtolower(trim($email));
		if (!self::tabelaExiste() || $email === '') {
			return null;
		}
		$row = self::select('LOWER(c.email) = "'.addslashes($email).'"', null, '1')->fetchObject(self::class);
		return $row instanceof self ? $row : null;
	}

	public static function select(?string $where = null, ?string $order = null, ?string $limit = null) {
		return (new Database('cj_candidatos c'))->select($where, $order, $limit, 'c.*');
	}

	public static function inserir(array $dados): ?int {
		if (!self::tabelaExiste()) {
			return null;
		}
		$id = (new Database('cj_candidatos'))->insert($dados);
		return $id ? (int)$id : null;
	}

	public static function atualizar(int $id, array $dados): bool {
		if (!self::tabelaExiste() || $id <= 0) {
			return false;
		}
		return (new Database('cj_candidatos'))->update('id = '.(int)$id, $dados);
	}

	public static function listarPorEscola(int $idAdmin, int $limit = 50): array {
		if (!self::tabelaExiste() || $idAdmin <= 0) {
			return [];
		}
		$stmt = self::select('c.id_admin = '.(int)$idAdmin, 'c.created_at DESC', (string)$limit);
		return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
	}
}

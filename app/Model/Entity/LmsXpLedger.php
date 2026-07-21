<?php

namespace App\Model\Entity;

use App\Model\Db\Database;

class LmsXpLedger {

	public $id;
	public $id_admin;
	public $id_aluno;
	public $fonte;
	public $id_ref;
	public $xp = 0;
	public $created_at;

	public static function getByUnique(int $idAluno, string $fonte, string $idRef) {
		return (new Database('lms_xp_ledger'))->select(
			'id_aluno = '.(int)$idAluno
			.' AND fonte = "'.addslashes($fonte).'"'
			.' AND id_ref = "'.addslashes($idRef).'"'
		)->fetchObject(self::class);
	}

	public static function sumByAluno(int $idAluno, int $idAdmin): int {
		$row = (new Database('lms_xp_ledger'))->select(
			'id_aluno = '.(int)$idAluno.' AND id_admin = '.(int)$idAdmin,
			null,
			null,
			'SUM(xp) AS total'
		)->fetch(\PDO::FETCH_ASSOC);
		return (int)($row['total'] ?? 0);
	}

	public static function rankingByAdmin(int $idAdmin, int $limit = 500): array {
		$sql = 'SELECT id_aluno, SUM(xp) AS xp_total
			FROM lms_xp_ledger
			WHERE id_admin = '.(int)$idAdmin.'
			GROUP BY id_aluno
			ORDER BY xp_total DESC
			LIMIT '.(int)$limit;
		$stmt = (new Database('lms_xp_ledger'))->execute($sql);
		return $stmt ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : [];
	}

	public function salvar(): int {
		$db = new Database('lms_xp_ledger');
		$this->id = $db->insert([
			'id_admin' => (int)$this->id_admin,
			'id_aluno' => (int)$this->id_aluno,
			'fonte' => $this->fonte,
			'id_ref' => $this->id_ref,
			'xp' => (int)$this->xp,
		]);
		return (int)$this->id;
	}
}

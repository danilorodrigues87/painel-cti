<?php

namespace App\Model\Entity;

class LmsAula extends LmsBase {

	public $id;
	public $id_modulo;
	public $id_admin;
	public $titulo;
	public $descricao;
	public $ordem = 0;
	public $bloqueado = 0;

	protected static function table(): string {
		return 'lms_aulas';
	}

	public static function listByModulo(int $idModulo, int $idAdmin): array {
		$stmt = self::get(
			'id_modulo = '.(int)$idModulo.' AND id_admin = '.(int)$idAdmin,
			'ordem ASC, id ASC'
		);
		$rows = [];
		while ($r = $stmt->fetchObject(self::class)) {
			$rows[] = $r;
		}
		return $rows;
	}

	public function salvar(): int {
		$dados = [
			'id_modulo' => (int)$this->id_modulo,
			'id_admin' => (int)$this->id_admin,
			'titulo' => $this->titulo,
			'descricao' => $this->descricao,
			'ordem' => (int)$this->ordem,
			'bloqueado' => (int)$this->bloqueado,
		];
		if (!empty($this->id)) {
			$this->updateRow((int)$this->id, (int)$this->id_admin, $dados);
			return (int)$this->id;
		}
		$this->id = $this->insertRow($dados);
		return (int)$this->id;
	}

	public function excluir(): bool {
		return $this->deleteRow((int)$this->id, (int)$this->id_admin);
	}
}

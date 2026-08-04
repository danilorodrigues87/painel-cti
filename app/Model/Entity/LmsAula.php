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
	public $tipo_conteudo = 'video';
	public $voz_narracao = 'alloy';
	public $interativa_status = 'rascunho';

	protected static function table(): string {
		return 'lms_aulas';
	}

	public static function temColunaInterativa(): bool {
		static $ok = null;
		if ($ok !== null) {
			return $ok;
		}
		try {
			$db = new \App\Model\Db\Database();
			$stmt = $db->execute("SHOW COLUMNS FROM `lms_aulas` LIKE 'tipo_conteudo'");
			$ok = $stmt && $stmt->rowCount() > 0;
		} catch (\Throwable $e) {
			$ok = false;
		}
		return $ok;
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
		if (self::temColunaInterativa()) {
			$tipo = (string)($this->tipo_conteudo ?? 'video');
			if ($tipo !== 'interativa') {
				$tipo = 'video';
			}
			$status = (string)($this->interativa_status ?? 'rascunho');
			if ($status !== 'publicada') {
				$status = 'rascunho';
			}
			$dados['tipo_conteudo'] = $tipo;
			$dados['voz_narracao'] = $this->voz_narracao !== null && $this->voz_narracao !== ''
				? (string)$this->voz_narracao
				: 'alloy';
			$dados['interativa_status'] = $status;
		}
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

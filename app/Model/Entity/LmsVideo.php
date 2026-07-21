<?php

namespace App\Model\Entity;

class LmsVideo extends LmsBase {

	public $id;
	public $id_aula;
	public $id_admin;
	public $titulo;
	public $url;
	public $provider = 'youtube';
	public $duracao_min = 0;
	public $ordem = 0;

	protected static function table(): string {
		return 'lms_videos';
	}

	public static function listByAula(int $idAula, int $idAdmin): array {
		$stmt = self::get(
			'id_aula = '.(int)$idAula.' AND id_admin = '.(int)$idAdmin,
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
			'id_aula' => (int)$this->id_aula,
			'id_admin' => (int)$this->id_admin,
			'titulo' => $this->titulo,
			'url' => $this->url,
			'provider' => in_array($this->provider, ['youtube', 'private'], true) ? $this->provider : 'youtube',
			'duracao_min' => (int)$this->duracao_min,
			'ordem' => (int)$this->ordem,
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

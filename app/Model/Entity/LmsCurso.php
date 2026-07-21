<?php

namespace App\Model\Entity;

class LmsCurso extends LmsBase {

	public $id;
	public $id_admin;
	public $id_trilha;
	public $slug;
	public $short_description;
	public $cover_url;
	public $banner_url;
	public $level = 'Iniciante';
	public $objectives;
	public $instructor_name;
	public $instructor_title;
	public $instructor_bio;
	public $instructor_avatar_url;
	public $publicado = 0;
	public $created_at;
	public $updated_at;

	protected static function table(): string {
		return 'lms_cursos';
	}

	public static function getByTrilha(int $idTrilha, int $idAdmin) {
		return self::get(
			'id_trilha = '.(int)$idTrilha.' AND id_admin = '.(int)$idAdmin
		)->fetchObject(self::class);
	}

	public static function getBySlug(string $slug, int $idAdmin) {
		$slug = addslashes($slug);
		return self::get(
			"slug = '{$slug}' AND id_admin = ".(int)$idAdmin
		)->fetchObject(self::class);
	}

	public function salvar(): int {
		$dados = [
			'id_admin' => (int)$this->id_admin,
			'id_trilha' => (int)$this->id_trilha,
			'slug' => $this->slug,
			'short_description' => $this->short_description,
			'cover_url' => $this->cover_url,
			'banner_url' => $this->banner_url,
			'level' => $this->level ?: 'Iniciante',
			'objectives' => is_string($this->objectives)
				? $this->objectives
				: json_encode($this->objectives ?? [], JSON_UNESCAPED_UNICODE),
			'instructor_name' => $this->instructor_name,
			'instructor_title' => $this->instructor_title,
			'instructor_bio' => $this->instructor_bio,
			'instructor_avatar_url' => $this->instructor_avatar_url,
			'publicado' => (int)$this->publicado,
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

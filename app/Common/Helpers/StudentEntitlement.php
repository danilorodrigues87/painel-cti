<?php

namespace App\Common\Helpers;

use App\Model\Entity\Matriculas;
use App\Model\Entity\LmsCurso;
use App\Model\Entity\LmsModulo;
use App\Model\Entity\LmsAula;
use App\Model\Entity\Trilhas;

class StudentEntitlement {

	/** Matrículas ativas do aluno (status=0, fim >= hoje). */
	public static function matriculasAtivas(int $idAluno, int $idAdmin): array {
		$stmt = Matriculas::getMatriculas(
			'id_aluno = '.(int)$idAluno
			.' AND id_admin = '.(int)$idAdmin
			.' AND status = 0'
			.' AND (fim IS NULL OR fim >= CURDATE())'
		);
		$rows = [];
		while ($r = $stmt->fetchObject(Matriculas::class)) {
			$rows[] = $r;
		}
		return $rows;
	}

	public static function idsTrilhasMatriculadas(int $idAluno, int $idAdmin): array {
		$ids = [];
		foreach (self::matriculasAtivas($idAluno, $idAdmin) as $m) {
			$ids[] = (int)$m->id_trilha;
		}
		return array_values(array_unique($ids));
	}

	public static function podeAcessarCurso(LmsCurso $curso, int $idAluno, int $idAdmin): bool {
		if ((int)$curso->id_admin !== $idAdmin || (int)$curso->publicado !== 1) {
			return false;
		}
		return in_array((int)$curso->id_trilha, self::idsTrilhasMatriculadas($idAluno, $idAdmin), true);
	}

	public static function cursoDoAluno($idCursoOrTrilhaOrSlug, int $idAluno, int $idAdmin, bool $bySlug = false): ?LmsCurso {
		if ($bySlug || !ctype_digit((string)$idCursoOrTrilhaOrSlug)) {
			$curso = LmsCurso::getBySlug((string)$idCursoOrTrilhaOrSlug, $idAdmin);
		} else {
			$curso = LmsCurso::getByIdAdmin((int)$idCursoOrTrilhaOrSlug, $idAdmin);
			if (!$curso) {
				$curso = LmsCurso::getByTrilha((int)$idCursoOrTrilhaOrSlug, $idAdmin);
			}
		}
		if (!$curso instanceof LmsCurso) {
			return null;
		}
		return self::podeAcessarCurso($curso, $idAluno, $idAdmin) ? $curso : null;
	}

	public static function aulaPertenceCurso(LmsAula $aula, LmsCurso $curso, int $idAdmin): bool {
		$mod = LmsModulo::getByIdAdmin((int)$aula->id_modulo, $idAdmin);
		return $mod && (int)$mod->id_curso === (int)$curso->id;
	}

	public static function nomeTrilha(int $idTrilha): string {
		$t = Trilhas::getTrilhaById($idTrilha);
		return $t ? (string)$t->nome : 'Curso';
	}
}

<?php

namespace App\Common\Helpers;

use App\Model\Entity\Matriculas;
use App\Model\Entity\LmsCurso;
use App\Model\Entity\LmsModulo;
use App\Model\Entity\LmsAula;
use App\Model\Entity\LmsMatriculaEad;
use App\Model\Entity\Trilhas;

class StudentEntitlement {

	/** Matrículas comerciais ativas (carnê/contrato) — não liberam portal EAD. */
	public static function matriculasAtivas(int $idAluno, int $idAdmin): array {
		$stmt = Matriculas::getMatriculas(
			'id_aluno = '.(int)$idAluno
			.' AND id_admin = '.(int)$idAdmin
			.' AND '.MatriculaStatusHelper::sqlAtiva('')
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

	/** IDs de cursos com matrícula EAD ativa. */
	public static function idsCursosEad(int $idAluno, int $idAdmin): array {
		$ids = [];
		foreach (LmsMatriculaEad::listAtivasAluno($idAluno, $idAdmin) as $m) {
			$ids[] = (int)$m->id_curso;
		}
		return array_values(array_unique($ids));
	}

	public static function podeAcessarCurso(LmsCurso $curso, int $idAluno, int $idAdmin): bool {
		if ((int)$curso->publicado !== 1) {
			return false;
		}
		if (!LmsMatriculaEadHelper::escolaPodeUsarCurso($curso, $idAdmin)) {
			return false;
		}
		$mat = LmsMatriculaEad::getAtiva($idAluno, (int)$curso->id);
		return $mat !== null && (int)$mat->id_admin === $idAdmin;
	}

	public static function cursoDoAluno($idCursoOrTrilhaOrSlug, int $idAluno, int $idAdmin, bool $bySlug = false): ?LmsCurso {
		$curso = null;
		if ($bySlug || !ctype_digit((string)$idCursoOrTrilhaOrSlug)) {
			$curso = LmsCurso::getBySlug((string)$idCursoOrTrilhaOrSlug, $idAdmin);
			if (!$curso) {
				// slug de curso licenciado (outro tenant)
				foreach (self::idsCursosEad($idAluno, $idAdmin) as $idC) {
					$c = LmsCurso::getById((int)$idC);
					if ($c && (string)$c->slug === (string)$idCursoOrTrilhaOrSlug) {
						$curso = $c;
						break;
					}
				}
			}
		} else {
			$id = (int)$idCursoOrTrilhaOrSlug;
			$curso = LmsCurso::getByIdAdmin($id, $idAdmin);
			if (!$curso) {
				$curso = LmsCurso::getById($id);
			}
			if (!$curso) {
				$curso = LmsCurso::getByTrilha($id, $idAdmin);
			}
		}
		if (!$curso instanceof LmsCurso) {
			return null;
		}
		return self::podeAcessarCurso($curso, $idAluno, $idAdmin) ? $curso : null;
	}

	public static function aulaPertenceCurso(LmsAula $aula, LmsCurso $curso, int $idAdminConteudo = 0): bool {
		$idOwner = $idAdminConteudo > 0 ? $idAdminConteudo : (int)$curso->id_admin;
		$mod = LmsModulo::getByIdAdmin((int)$aula->id_modulo, $idOwner);
		if (!$mod) {
			$mod = LmsModulo::getById((int)$aula->id_modulo);
		}
		return $mod && (int)$mod->id_curso === (int)$curso->id;
	}

	public static function nomeTrilha(int $idTrilha): string {
		$t = Trilhas::getTrilhaById($idTrilha);
		return $t ? (string)$t->nome : 'Curso';
	}

	/** id_admin dono do conteúdo (criador do curso). */
	public static function idAdminConteudo(LmsCurso $curso): int {
		return (int)$curso->id_admin;
	}
}

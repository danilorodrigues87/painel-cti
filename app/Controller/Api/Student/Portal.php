<?php

namespace App\Controller\Api\Student;

use App\Common\Helpers\LmsHelper;
use App\Common\Helpers\StudentApiMapper;
use App\Common\Helpers\StudentEntitlement;
use App\Common\Helpers\LmsXpHelper;
use App\Model\Entity\LmsCurso;
use App\Model\Entity\LmsAula;
use App\Model\Entity\LmsProgressoAula;
use App\Model\Entity\User;

class Portal {

	private static function ok($data, int $code = 200): array {
		return [
			'code' => $code,
			'json' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
		];
	}

	private static function err(string $msg, int $code = 400): array {
		return self::ok(['message' => $msg], $code);
	}

	private static function user($request): User {
		return $request->user;
	}

	public static function me($request) {
		return self::ok(StudentApiMapper::user(self::user($request)));
	}

	public static function listCourses($request) {
		if (!LmsHelper::tabelasExistem()) {
			return self::ok([]);
		}
		$u = self::user($request);
		$idAdmin = (int)$u->id_admin;
		$idAluno = (int)$u->id;
		$out = [];
		foreach (StudentEntitlement::idsTrilhasMatriculadas($idAluno, $idAdmin) as $idTrilha) {
			$curso = LmsCurso::getByTrilha($idTrilha, $idAdmin);
			if ($curso instanceof LmsCurso && (int)$curso->publicado === 1) {
				$out[] = StudentApiMapper::course($curso, $idAluno, $idAdmin, true);
			}
		}
		return self::ok($out);
	}

	public static function getCourse($request, $idOrSlug) {
		$u = self::user($request);
		$bySlug = !ctype_digit((string)$idOrSlug);
		$curso = StudentEntitlement::cursoDoAluno($idOrSlug, (int)$u->id, (int)$u->id_admin, $bySlug);
		if (!$curso) {
			return self::err('Curso não encontrado ou sem acesso.', 404);
		}
		return self::ok(StudentApiMapper::course($curso, (int)$u->id, (int)$u->id_admin, true));
	}

	public static function getLesson($request, $courseId, $lessonId) {
		$u = self::user($request);
		$curso = StudentEntitlement::cursoDoAluno($courseId, (int)$u->id, (int)$u->id_admin, !ctype_digit((string)$courseId));
		if (!$curso) {
			return self::err('Curso não encontrado ou sem acesso.', 404);
		}
		$aula = LmsAula::getByIdAdmin((int)$lessonId, (int)$u->id_admin);
		if (!$aula || !StudentEntitlement::aulaPertenceCurso($aula, $curso, (int)$u->id_admin)) {
			return self::err('Aula não encontrada.', 404);
		}

		$prog = LmsProgressoAula::getAlunoAula((int)$u->id, (int)$aula->id);
		if (!$prog) {
			$prog = new LmsProgressoAula();
			$prog->id_aluno = (int)$u->id;
			$prog->id_aula = (int)$aula->id;
			$prog->id_admin = (int)$u->id_admin;
		}
		$prog->ultimo_acesso = date('Y-m-d H:i:s');
		$prog->salvar();

		$coursePayload = StudentApiMapper::course($curso, (int)$u->id, (int)$u->id_admin, true);
		$lessonPayload = null;
		foreach ($coursePayload['modules'] as $mod) {
			foreach ($mod['lessons'] as $l) {
				if ((string)$l['id'] === (string)$aula->id) {
					$lessonPayload = $l;
					break 2;
				}
			}
		}
		if (!$lessonPayload) {
			return self::err('Aula não encontrada.', 404);
		}
		return self::ok(['course' => $coursePayload, 'lesson' => $lessonPayload]);
	}

	public static function completeLesson($request, $courseId, $lessonId) {
		$u = self::user($request);
		$curso = StudentEntitlement::cursoDoAluno($courseId, (int)$u->id, (int)$u->id_admin, !ctype_digit((string)$courseId));
		if (!$curso) {
			return self::err('Curso não encontrado ou sem acesso.', 404);
		}
		$aula = LmsAula::getByIdAdmin((int)$lessonId, (int)$u->id_admin);
		if (!$aula || !StudentEntitlement::aulaPertenceCurso($aula, $curso, (int)$u->id_admin)) {
			return self::err('Aula não encontrada.', 404);
		}
		$prog = LmsProgressoAula::getAlunoAula((int)$u->id, (int)$aula->id);
		if (!$prog) {
			$prog = new LmsProgressoAula();
			$prog->id_aluno = (int)$u->id;
			$prog->id_aula = (int)$aula->id;
			$prog->id_admin = (int)$u->id_admin;
			$prog->ciclo = 1;
			$prog->precisa_revisar = 0;
			$prog->unidade_aprovada = 0;
		}
		\App\Common\Helpers\LmsUnidadeAvaliacaoHelper::onCompleteLesson($prog);
		$prog->salvar();

		$lesson = StudentApiMapper::lesson($aula, (int)$aula->id_modulo, (int)$u->id_admin, true, false);
		$xp = LmsXpHelper::creditLessonComplete(
			(int)$u->id_admin,
			(int)$u->id,
			(int)$aula->id,
			(int)($lesson['durationMinutes'] ?? 0)
		);

		return self::ok([
			'ok' => true,
			'xpEarned' => $xp,
			'cycle' => (int)$prog->ciclo,
			'message' => (int)$prog->ciclo > 1
				? 'Aula revisada. Você ganhou +3 tentativas nas atividades.'
				: 'Aula concluída. Atividades liberadas.',
		]);
	}

	public static function dashboard($request) {
		$listRes = self::listCourses($request);
		$courses = json_decode($listRes['json'], true) ?: [];
		$overall = 0;
		if (count($courses)) {
			$overall = (int)round(array_sum(array_column($courses, 'progressPercent')) / count($courses));
		}
		$current = null;
		foreach ($courses as $c) {
			if (($c['progressPercent'] ?? 0) > 0 && ($c['progressPercent'] ?? 0) < 100) {
				$current = $c;
				break;
			}
		}
		if (!$current && !empty($courses)) {
			$current = $courses[0];
		}
		$continueLesson = null;
		if ($current) {
			$lessonId = $current['lastAccessedLessonId'] ?? null;
			$found = null;
			foreach ($current['modules'] ?? [] as $m) {
				foreach ($m['lessons'] ?? [] as $l) {
					if ($lessonId && (string)$l['id'] === (string)$lessonId && empty($l['locked'])) {
						$found = $l;
						break 2;
					}
				}
			}
			if (!$found) {
				foreach ($current['modules'] ?? [] as $m) {
					foreach ($m['lessons'] ?? [] as $l) {
						if (empty($l['locked']) && empty($l['completed'])) {
							$found = $l;
							break 2;
						}
					}
				}
			}
			if (!$found) {
				foreach ($current['modules'] ?? [] as $m) {
					foreach ($m['lessons'] ?? [] as $l) {
						if (empty($l['locked'])) {
							$found = $l;
							break 2;
						}
					}
				}
			}
			if ($found) {
				$continueLesson = ['course' => $current, 'lesson' => $found];
			}
		}
		$u = StudentApiMapper::user(self::user($request));
		return self::ok([
			'coursesCount' => count($courses),
			'overallProgress' => $overall,
			'studyMinutes' => 0,
			'streakDays' => 0,
			'currentCourse' => $current,
			'continueLesson' => $continueLesson,
			'recentCourses' => array_slice($courses, 0, 4),
			'notifications' => [],
			'achievements' => [],
			'ranking' => LmsXpHelper::rankingEscola((int)self::user($request)->id_admin, (int)self::user($request)->id, 10)['entries'],
			'xp' => $u['xp'],
			'level' => $u['level'],
			'nextLevelXp' => $u['nextLevelXp'],
		]);
	}

	public static function ranking($request) {
		$u = self::user($request);
		$data = LmsXpHelper::rankingEscola((int)$u->id_admin, (int)$u->id, 50);
		return self::ok($data);
	}
}

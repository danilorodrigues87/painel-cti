<?php

namespace App\Common\Helpers;

use App\Model\Entity\User;
use App\Model\Entity\LmsCurso;
use App\Model\Entity\LmsModulo;
use App\Model\Entity\LmsAula;
use App\Model\Entity\LmsVideo;
use App\Model\Entity\LmsMaterial;
use App\Model\Entity\LmsProgressoAula;
use App\Model\Entity\LmsAtividade;
use App\Model\Entity\LmsQuestao;
use App\Model\Entity\LmsRoleplayCenario;
use App\Model\Entity\LmsAtividadeTentativa;
use App\Model\Entity\LmsRoleplaySessao;
use App\Model\Entity\CategoryCourses;
use App\Model\Entity\Trilhas;

/**
 * Converte rows LMS → shapes Ascend (camelCase).
 */
class StudentApiMapper {

	public static function user(User $u): array {
		$idAdmin = (int)$u->id_admin;
		$idAluno = (int)$u->id;
		$xp = LmsXpHelper::totalAluno($idAluno, $idAdmin);
		$level = LmsXpHelper::levelFromXp($xp);
		LmsXpHelper::creditDailyStreak($idAdmin, $idAluno);
		$xp = LmsXpHelper::totalAluno($idAluno, $idAdmin);
		$level = LmsXpHelper::levelFromXp($xp);
		return [
			'id' => (string)$u->id,
			'name' => (string)$u->nome,
			'email' => (string)$u->email,
			'phone' => $u->whatsapp ?? null,
			'city' => $u->cidade ?? null,
			'avatarUrl' => null,
			'role' => 'student',
			'xp' => $xp,
			'level' => $level,
			'nextLevelXp' => LmsXpHelper::xpForNextLevel($level),
			'streakDays' => 0,
			'totalStudyMinutes' => 0,
			'createdAt' => date('c'),
			'firstAccess' => false,
		];
	}

	public static function tokens(string $accessToken, int $expiresIn = 86400): array {
		return [
			'accessToken' => $accessToken,
			'refreshToken' => $accessToken,
			'expiresIn' => $expiresIn,
		];
	}

	public static function course(LmsCurso $curso, int $idAluno, int $idAdmin, bool $withModules = true): array {
		$trilha = Trilhas::getTrilhaById((int)$curso->id_trilha);
		$nome = $trilha ? (string)$trilha->nome : 'Curso';
		$carga = $trilha ? (int)$trilha->carga_h : 0;
		$catName = '';
		if ($trilha && !empty($trilha->id_categoria)) {
			$cat = CategoryCourses::getCategoryById((int)$trilha->id_categoria);
			if ($cat) {
				$catName = (string)$cat->nome;
			}
		}
		$objectives = json_decode((string)($curso->objectives ?? '[]'), true);
		if (!is_array($objectives)) {
			$objectives = [];
		}

		$modules = [];
		$lessonsCount = 0;
		$completedCount = 0;
		$curriculumCount = 0;
		$curriculumDone = 0;
		$lastAccessed = null;
		$progressMap = [];
		foreach (LmsProgressoAula::listByAluno($idAluno, $idAdmin) as $p) {
			$progressMap[(int)$p->id_aula] = $p;
			if ($p->ultimo_acesso && ($lastAccessed === null || $p->ultimo_acesso > $lastAccessed['at'])) {
				$lastAccessed = ['id' => (string)$p->id_aula, 'at' => $p->ultimo_acesso];
			}
		}

		$assessmentDone = self::assessmentDoneMap($idAluno);
		$roleplayDone = self::roleplayDoneMap($idAluno, $idAdmin);

		$prevUnidadeOk = true; // 1ª aula do módulo libera se não bloqueada no admin

		foreach (LmsModulo::listByCurso((int)$curso->id, $idAdmin) as $mod) {
			$lessons = [];
			$curriculum = [];
			$prevUnidadeOk = true;
			foreach (LmsAula::listByModulo((int)$mod->id, $idAdmin) as $aula) {
				$lessonsCount++;
				$prog = $progressMap[(int)$aula->id] ?? null;
				$precisaRevisar = $prog && (int)($prog->precisa_revisar ?? 0) === 1;
				$unidadeOk = $prog && (int)($prog->unidade_aprovada ?? 0) === 1;
				$assistida = $prog && !empty($prog->concluida_em) && !$precisaRevisar;
				$ciclo = $prog ? max(1, (int)($prog->ciclo ?? 1)) : 1;

				$adminLocked = ((int)($aula->bloqueado ?? 0) === 1);
				$lessonLocked = $adminLocked || !$prevUnidadeOk;
				$completed = $unidadeOk || ($assistida && count(LmsUnidadeAvaliacaoHelper::itensAvaliados((int)$aula->id, $idAdmin)) === 0);
				if ($completed) {
					$completedCount++;
				}
				$lessonPayload = self::lesson($aula, (int)$mod->id, $idAdmin, $assistida || $unidadeOk, $lessonLocked);
				$lessonPayload['needsRewatch'] = $precisaRevisar;
				$lessonPayload['unitScore'] = $prog && $prog->nota_unidade !== null ? (float)$prog->nota_unidade : null;
				$lessonPayload['unitPassed'] = $unidadeOk;
				$lessonPayload['cycle'] = $ciclo;
				$lessons[] = $lessonPayload;

				$aulaCurriculum = [];
				$aulaCurriculum[] = [
					'kind' => 'lesson',
					'id' => (string)$aula->id,
					'title' => (string)$aula->titulo,
					'order' => (int)$aula->ordem,
					'durationMinutes' => (int)$lessonPayload['durationMinutes'],
					'completed' => $assistida || $unidadeOk,
					'locked' => $lessonLocked,
					'needsRewatch' => $precisaRevisar,
					'unitScore' => $lessonPayload['unitScore'],
					'unitPassed' => $unidadeOk,
					'cycle' => $ciclo,
				];

				foreach (LmsAtividade::listByAula((int)$aula->id, $idAdmin) as $at) {
					$notaCiclo = LmsUnidadeAvaliacaoHelper::melhorNotaAtividade($idAluno, (int)$at->id, $ciclo);
					// "completed" na sequência = já fez pelo menos 1 tentativa no ciclo (não exige ≥70 individual)
					$doneSeq = $notaCiclo !== null;
					$aulaCurriculum[] = [
						'kind' => 'assessment',
						'id' => (string)$at->id,
						'title' => (string)$at->titulo,
						'order' => (int)$at->ordem,
						'durationMinutes' => (int)$at->duracao_min,
						'completed' => $doneSeq,
						'locked' => false,
						'score' => $notaCiclo,
					];
				}

				foreach (LmsRoleplayCenario::listByAula((int)$aula->id, $idAdmin) as $rp) {
					$notaCiclo = LmsUnidadeAvaliacaoHelper::melhorNotaRoleplay($idAluno, (int)$rp->id, $idAdmin, $ciclo);
					$doneSeq = $notaCiclo !== null;
					$aulaCurriculum[] = [
						'kind' => 'roleplay',
						'id' => (string)$rp->id,
						'title' => (string)$rp->titulo,
						'order' => 0,
						'durationMinutes' => (int)$rp->estimated_minutes,
						'completed' => $doneSeq,
						'locked' => false,
						'score' => $notaCiclo,
					];
				}

				// Sequência: aula assistida libera atividades; cada item feito libera o próximo
				$lessonReady = ($assistida || $unidadeOk) && !$precisaRevisar;
				foreach ($aulaCurriculum as $idx => &$it) {
					if ($it['kind'] !== 'lesson') {
						if (!$lessonReady) {
							$it['locked'] = true;
						} else {
							$prev = $aulaCurriculum[$idx - 1];
							$it['locked'] = empty($prev['completed']);
						}
					}
					$curriculum[] = $it;
					$curriculumCount++;
					if (!empty($it['completed'])) {
						$curriculumDone++;
					}
				}
				unset($it);

				$semAvaliacao = count(LmsUnidadeAvaliacaoHelper::itensAvaliados((int)$aula->id, $idAdmin)) === 0;
				$prevUnidadeOk = $unidadeOk || ($semAvaliacao && $assistida);
			}

			foreach (LmsRoleplayCenario::listByModuloSemAula((int)$mod->id, $idAdmin) as $rp) {
				$done = !empty($roleplayDone[(int)$rp->id]);
				$curriculum[] = [
					'kind' => 'roleplay',
					'id' => (string)$rp->id,
					'title' => (string)$rp->titulo,
					'order' => 999,
					'durationMinutes' => (int)$rp->estimated_minutes,
					'completed' => $done,
					'locked' => false,
				];
				$curriculumCount++;
				if ($done) {
					$curriculumDone++;
				}
			}

			if ($withModules) {
				$modules[] = [
					'id' => (string)$mod->id,
					'courseId' => (string)$curso->id,
					'title' => (string)$mod->titulo,
					'order' => (int)$mod->ordem,
					'locked' => ((int)($mod->bloqueado ?? 0) === 1),
					'lessons' => $lessons,
					'curriculum' => $curriculum,
				];
			}
		}

		$progressPercent = $lessonsCount > 0 ? (int)round(($completedCount / $lessonsCount) * 100) : 0;
		$desc = $trilha->descricao ?? ($curso->short_description ?? '');

		return [
			'id' => (string)$curso->id,
			'slug' => (string)$curso->slug,
			'title' => $nome,
			'description' => strip_tags((string)$desc),
			'shortDescription' => (string)($curso->short_description ?: mb_substr(strip_tags((string)$desc), 0, 160)),
			'coverUrl' => ($curso->cover_url ?: null),
			'bannerUrl' => ($curso->banner_url ?: null),
			'instructor' => [
				'id' => 'inst_'.(int)$curso->id,
				'name' => (string)($curso->instructor_name ?: 'Instrutor'),
				'avatarUrl' => self::safeUrl($curso->instructor_avatar_url ?? null),
				'title' => $curso->instructor_title ?: null,
				'bio' => $curso->instructor_bio ?: null,
			],
			'categories' => $catName !== '' ? [$catName] : [],
			'level' => (string)($curso->level ?: 'Iniciante'),
			'workloadHours' => $carga,
			'estimatedMinutes' => $carga * 60,
			'rating' => 0,
			'ratingCount' => 0,
			'progressPercent' => $progressPercent,
			'objectives' => $objectives,
			'modulesCount' => count($modules),
			'lessonsCount' => $lessonsCount,
			'curriculumCount' => $curriculumCount,
			'curriculumCompleted' => $curriculumDone,
			'modules' => $withModules ? $modules : [],
			'enrolled' => true,
			'lastAccessedLessonId' => $lastAccessed['id'] ?? null,
		];
	}

	private static function safeUrl($url): ?string {
		$url = is_string($url) ? trim($url) : '';
		if ($url === '' || !preg_match('#^https?://#i', $url)) {
			return null;
		}
		return $url;
	}

	private static function assessmentDoneMap(int $idAluno): array {
		$map = [];
		try {
			foreach (LmsAtividadeTentativa::listPassedByAluno($idAluno) as $t) {
				$map[(int)$t->id_atividade] = true;
			}
		} catch (\Throwable $e) {
			/* tabela / método ausente */
		}
		return $map;
	}

	private static function roleplayDoneMap(int $idAluno, int $idAdmin): array {
		$map = [];
		try {
			foreach (LmsRoleplaySessao::listByAluno($idAluno, $idAdmin) as $s) {
				if (!empty($s->ended_at) || ($s->status ?? '') === 'finished' || $s->score !== null) {
					if (!empty($s->id_cenario)) {
						$map[(int)$s->id_cenario] = true;
					}
				}
			}
		} catch (\Throwable $e) {
			/* ignore */
		}
		return $map;
	}

	public static function lesson(LmsAula $aula, int $moduleId, int $idAdmin, bool $completed, bool $locked): array {
		$videos = [];
		$totalMin = 0;
		foreach (LmsVideo::listByAula((int)$aula->id, $idAdmin) as $v) {
			$totalMin += (int)$v->duracao_min;
			$provider = (string)($v->provider ?: 'youtube');
			$url = LmsHelper::normalizeVideoUrl((string)$v->url, $provider);
			$videos[] = [
				'id' => (string)$v->id,
				'title' => (string)($v->titulo ?: 'Vídeo'),
				'url' => $url,
				'provider' => $provider,
				'durationMinutes' => (int)$v->duracao_min,
				'order' => (int)$v->ordem,
			];
		}
		$resources = [];
		foreach (LmsMaterial::listByAula((int)$aula->id, $idAdmin) as $m) {
			$resources[] = [
				'id' => (string)$m->id,
				'label' => (string)$m->label,
				'url' => (string)$m->url,
				'type' => (string)$m->tipo,
			];
		}
		$first = $videos[0] ?? null;
		return [
			'id' => (string)$aula->id,
			'moduleId' => (string)$moduleId,
			'title' => (string)$aula->titulo,
			'description' => $aula->descricao ?: null,
			'durationMinutes' => $totalMin,
			'videoUrl' => $first['url'] ?? null,
			'videoProvider' => $first['provider'] ?? null,
			'videos' => $videos,
			'completed' => $completed,
			'locked' => $locked,
			'order' => (int)$aula->ordem,
			'resources' => $resources,
		];
	}

	public static function assessment(LmsAtividade $at, int $idAdmin, bool $includeAnswers = false): array {
		$questions = [];
		foreach (LmsQuestao::listByAtividade((int)$at->id, $idAdmin) as $q) {
			$ops = json_decode((string)($q->opcoes ?? '[]'), true);
			if (!is_array($ops)) {
				$ops = [];
			}
			$options = [];
			foreach ($ops as $i => $op) {
				if (is_array($op)) {
					$options[] = [
						'id' => (string)($op['id'] ?? $op['value'] ?? $i),
						'label' => (string)($op['label'] ?? $op['text'] ?? $op['id'] ?? $i),
					];
				} else {
					$options[] = ['id' => (string)$op, 'label' => (string)$op];
				}
			}
			// V/F legado sem opções: injeta Verdadeiro/Falso
			if ((string)$q->tipo === 'boolean' && count($options) === 0) {
				$options = [
					['id' => 'true', 'label' => 'Verdadeiro'],
					['id' => 'false', 'label' => 'Falso'],
				];
			}
			$item = [
				'id' => (string)$q->id,
				'type' => (string)$q->tipo,
				'prompt' => (string)$q->enunciado,
				'options' => $options,
			];
			if ($includeAnswers) {
				$item['correctAnswer'] = $q->resposta_correta;
			}
			$questions[] = $item;
		}
		return [
			'id' => (string)$at->id,
			'courseId' => (string)$at->id_curso,
			'title' => (string)$at->titulo,
			'description' => (string)($at->descricao ?? ''),
			'durationMinutes' => (int)$at->duracao_min,
			'attempts' => (int)$at->tentativas_max,
			'questions' => $questions,
		];
	}

	public static function roleplayScenario(LmsRoleplayCenario $rp, string $courseTitle = '', string $moduleTitle = '', bool $forStudent = true): array {
		$obj = json_decode((string)($rp->objectives ?? '[]'), true);
		$crit = json_decode((string)($rp->criteria ?? '[]'), true);
		$charName = self::shortAiLabel((string)($rp->ai_character_name ?? ''), 'Personagem');
		$aiRole = self::shortAiLabel((string)($rp->ai_role ?? ''), 'Cliente');
		$userRole = trim((string)($rp->user_role ?? '')) ?: 'Aluno';
		$out = [
			'id' => (string)$rp->id,
			'courseId' => (string)$rp->id_curso,
			'courseTitle' => $courseTitle,
			'moduleTitle' => $moduleTitle,
			'title' => (string)$rp->titulo,
			'theme' => (string)($rp->tema ?? ''),
			'scenario' => (string)($rp->cenario ?? ''),
			'userRole' => mb_substr($userRole, 0, 80),
			'aiRole' => $aiRole,
			'aiCharacterName' => $charName,
			'aiCharacterAvatarUrl' => $rp->ai_character_avatar_url ?: null,
			'objectives' => is_array($obj) ? $obj : [],
			'criteria' => is_array($crit) ? $crit : [],
			'difficulty' => (string)($rp->difficulty ?: 'medium'),
			'minScore' => (int)$rp->min_score,
			'initialMessage' => (string)($rp->initial_message ?? ''),
			'estimatedMinutes' => (int)$rp->estimated_minutes,
		];
		if ($forStudent) {
			$out['basePrompt'] = '';
			$out['initialPersonality'] = '';
		} else {
			$out['basePrompt'] = (string)($rp->base_prompt ?? '');
			$out['initialPersonality'] = (string)($rp->initial_personality ?? '');
			$out['aiRole'] = (string)($rp->ai_role ?? $aiRole);
			$out['aiCharacterName'] = (string)($rp->ai_character_name ?: $charName);
		}
		return $out;
	}

	/** Evita vazar prompt longo no rótulo exibido ao aluno. */
	private static function shortAiLabel(string $raw, string $fallback): string {
		$s = trim($raw);
		if ($s === '') {
			return $fallback;
		}
		if (mb_strlen($s) <= 48 && !preg_match('/^você\s+é\b/iu', $s)) {
			return $s;
		}
		if (preg_match('/["“”\']([^"“”\']{2,40})["“”\']/', $s, $m)) {
			return trim($m[1]);
		}
		if (preg_match('/\b(Dona|Sr\.?|Sra\.?|Dr\.?)\s+[A-Za-zÀ-ú]+/u', $s, $m)) {
			return trim($m[0]);
		}
		return $fallback;
	}
}

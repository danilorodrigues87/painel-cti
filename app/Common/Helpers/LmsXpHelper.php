<?php

namespace App\Common\Helpers;

use App\Model\Entity\LmsXpLedger;
use App\Model\Entity\User;

/**
 * XP / níveis / ranking por escola (id_admin).
 */
class LmsXpHelper {

	public static function tabelasExistem(): bool {
		static $ok = null;
		if ($ok !== null) {
			return $ok;
		}
		try {
			$pdo = new \PDO(
				'mysql:host='.getenv('DB_HOST').';dbname='.getenv('DB_NAME').';charset=utf8mb4',
				getenv('DB_USER'),
				getenv('DB_PASS') ?: '',
				[\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
			);
			$stmt = $pdo->query("SHOW TABLES LIKE 'lms_xp_ledger'");
			$ok = $stmt && $stmt->rowCount() > 0;
		} catch (\Throwable $e) {
			$ok = false;
		}
		return $ok;
	}

	public static function levelFromXp(int $xp): int {
		return (int)floor(sqrt(max(0, $xp) / 50)) + 1;
	}

	public static function xpForNextLevel(int $level): int {
		$level = max(1, $level);
		return (int)(($level * $level) * 50);
	}

	/** Credita XP uma vez por (aluno, fonte, id_ref). Retorna xp creditado (0 se já existia). */
	public static function credit(int $idAdmin, int $idAluno, string $fonte, string $idRef, int $xp): int {
		if (!self::tabelasExistem() || $xp <= 0 || $idAluno <= 0) {
			return 0;
		}
		$existing = LmsXpLedger::getByUnique($idAluno, $fonte, $idRef);
		if ($existing) {
			return 0;
		}
		$row = new LmsXpLedger();
		$row->id_admin = $idAdmin;
		$row->id_aluno = $idAluno;
		$row->fonte = $fonte;
		$row->id_ref = $idRef;
		$row->xp = $xp;
		$row->salvar();
		return $xp;
	}

	public static function totalAluno(int $idAluno, int $idAdmin): int {
		if (!self::tabelasExistem()) {
			return 0;
		}
		return LmsXpLedger::sumByAluno($idAluno, $idAdmin);
	}

	public static function creditLessonComplete(int $idAdmin, int $idAluno, int $idAula, int $duracaoMin): int {
		$xp = 10 + min(max(0, $duracaoMin), 30);
		return self::credit($idAdmin, $idAluno, 'lesson_complete', (string)$idAula, $xp);
	}

	public static function creditAssessment(int $idAdmin, int $idAluno, int $idAtividade, float $score, bool $passed): int {
		if ($passed) {
			$xp = 30 + (int)round(40 * ($score / 100));
			return self::credit($idAdmin, $idAluno, 'assessment_pass', (string)$idAtividade, $xp);
		}
		return self::credit($idAdmin, $idAluno, 'assessment_attempt', (string)$idAtividade, 5);
	}

	public static function creditRoleplay(int $idAdmin, int $idAluno, int $idSessao, float $score): int {
		$xp = 40 + (int)round($score * 0.3);
		return self::credit($idAdmin, $idAluno, 'roleplay_complete', (string)$idSessao, $xp);
	}

	public static function creditDailyStreak(int $idAdmin, int $idAluno): int {
		$ref = date('Y-m-d');
		return self::credit($idAdmin, $idAluno, 'streak_daily', $ref, 5);
	}

	/** Ranking da escola: top N + posição do aluno. */
	public static function rankingEscola(int $idAdmin, int $idAluno, int $limit = 20): array {
		if (!self::tabelasExistem()) {
			return ['entries' => [], 'me' => null];
		}
		$rows = LmsXpLedger::rankingByAdmin($idAdmin, 500);
		$entries = [];
		$me = null;
		$pos = 0;
		foreach ($rows as $r) {
			$pos++;
			$user = User::getUserById((int)$r['id_aluno']);
			$entry = [
				'id' => (string)$r['id_aluno'],
				'name' => $user ? (string)$user->nome : 'Aluno',
				'avatarUrl' => null,
				'xp' => (int)$r['xp_total'],
				'level' => self::levelFromXp((int)$r['xp_total']),
				'position' => $pos,
			];
			if ((int)$r['id_aluno'] === $idAluno) {
				$me = $entry;
			}
			if ($pos <= $limit) {
				$entries[] = $entry;
			}
		}
		return ['entries' => $entries, 'me' => $me];
	}
}

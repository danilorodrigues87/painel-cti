<?php

namespace App\Common\Helpers;

use App\Model\Entity\EmailAniversarioLog;

class AniversariantesHelper {

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public static function listar(int $idAdmin, string $periodo = 'mes', string $busca = '', ?int $mes = null): array {
		$periodo = in_array($periodo, ['hoje', 'semana', 'mes'], true) ? $periodo : 'mes';
		if ($mes === null || $mes < 1 || $mes > 12) {
			$mes = (int)date('m');
		}

		$params = ['id_admin' => $idAdmin];
		$where = 'u.id_admin = :id_admin
			AND u.nivel = "Cliente"
			AND u.nascimento IS NOT NULL
			AND u.nascimento != "0000-00-00"';

		$busca = trim($busca);
		if ($busca !== '') {
			$where .= ' AND u.nome LIKE :busca';
			$params['busca'] = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $busca).'%';
		}

		if ($periodo === 'hoje') {
			$where .= ' AND MONTH(u.nascimento) = :mes_h AND DAY(u.nascimento) = :dia_h';
			$params['mes_h'] = (int)date('m');
			$params['dia_h'] = (int)date('d');
		} elseif ($periodo === 'mes') {
			$where .= ' AND MONTH(u.nascimento) = :mes';
			$params['mes'] = $mes;
		}

		$sql = '
			SELECT
				u.id,
				u.nome,
				u.nascimento,
				u.email,
				u.whatsapp,
				(
					SELECT GROUP_CONCAT(DISTINCT t.nome ORDER BY t.nome SEPARATOR ", ")
					FROM matriculas m
					INNER JOIN trilhas t ON t.id = m.id_trilha
					WHERE m.id_aluno = u.id
					  AND m.id_admin = u.id_admin
					  AND m.status = 0
					  AND (m.fim IS NULL OR m.fim >= CURDATE())
				) AS cursos,
				EXISTS(
					SELECT 1 FROM matriculas m2
					WHERE m2.id_aluno = u.id
					  AND m2.id_admin = u.id_admin
					  AND m2.status = 0
					  AND (m2.fim IS NULL OR m2.fim >= CURDATE())
				) AS matricula_ativa
			FROM usuarios u
			WHERE '.$where.'
			ORDER BY MONTH(u.nascimento), DAY(u.nascimento), u.nome ASC
			LIMIT 500
		';

		$stmt = self::pdo()->prepare($sql);
		$stmt->execute($params);
		$rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

		$ano = (int)date('Y');
		$lista = [];
		foreach ($rows as $row) {
			if ($periodo === 'semana' && !self::aniversarioNosProximosDias((string)$row['nascimento'], 7)) {
				continue;
			}
			$lista[] = self::formatarLinha($row, $ano);
		}

		return $lista;
	}

	public static function segmentoTipoParaPeriodo(string $periodo): string {
		switch ($periodo) {
			case 'hoje':
				return 'aniversariantes_dia';
			case 'semana':
				return 'aniversariantes_mes';
			default:
				return 'aniversariantes_mes';
		}
	}

	/**
	 * @param int[] $ids
	 * @return array<int,array<string,mixed>>
	 */
	public static function buscarPorIds(int $idAdmin, array $ids): array {
		$ids = array_values(array_filter(array_map('intval', $ids)));
		if (!$ids) {
			return [];
		}

		$sql = '
			SELECT
				u.id,
				u.nome,
				u.nascimento,
				u.email,
				u.whatsapp,
				"" AS cursos,
				EXISTS(
					SELECT 1 FROM matriculas m2
					WHERE m2.id_aluno = u.id
					  AND m2.id_admin = u.id_admin
					  AND m2.status = 0
					  AND (m2.fim IS NULL OR m2.fim >= CURDATE())
				) AS matricula_ativa
			FROM usuarios u
			WHERE u.id_admin = :id_admin
			  AND u.nivel = "Cliente"
			  AND u.id IN ('.implode(',', $ids).')
		';

		$stmt = self::pdo()->prepare($sql);
		$stmt->execute(['id_admin' => $idAdmin]);
		$rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

		$ano = (int)date('Y');
		$lista = [];
		foreach ($rows as $row) {
			$lista[] = self::formatarLinha($row, $ano);
		}

		return $lista;
	}

	private static function formatarLinha(array $row, int $ano): array {
		$nasc = (string)($row['nascimento'] ?? '');
		$id = (int)($row['id'] ?? 0);

		return [
			'id' => $id,
			'nome' => (string)($row['nome'] ?? ''),
			'nascimento' => $nasc,
			'nascimento_fmt' => $nasc !== '' ? date('d/m', strtotime($nasc)) : '',
			'idade' => self::calcularIdade($nasc),
			'email' => trim((string)($row['email'] ?? '')),
			'whatsapp' => trim((string)($row['whatsapp'] ?? '')),
			'curso' => trim((string)($row['cursos'] ?? '')),
			'matricula_ativa' => !empty($row['matricula_ativa']),
			'enviado_ano' => EmailAniversarioLog::tabelaExiste()
				&& EmailAniversarioLog::jaEnviou($id, $ano),
		];
	}

	private static function calcularIdade(string $nascimento): ?int {
		if ($nascimento === '' || $nascimento === '0000-00-00') {
			return null;
		}
		try {
			$birth = new \DateTime($nascimento);
			$today = new \DateTime('today');
			return (int)$today->diff($birth)->y;
		} catch (\Throwable $e) {
			return null;
		}
	}

	private static function aniversarioNosProximosDias(string $nascimento, int $dias): bool {
		if ($nascimento === '' || $nascimento === '0000-00-00') {
			return false;
		}
		try {
			$nasc = new \DateTime($nascimento);
			$hoje = new \DateTime('today');
			$mes = (int)$nasc->format('m');
			$dia = (int)$nasc->format('d');
			$ano = (int)$hoje->format('Y');

			$proximo = new \DateTime(sprintf('%04d-%02d-%02d', $ano, $mes, $dia));
			if ($proximo < $hoje) {
				$proximo = new \DateTime(sprintf('%04d-%02d-%02d', $ano + 1, $mes, $dia));
			}

			$limite = (clone $hoje)->modify('+'.max(0, $dias - 1).' days');
			return $proximo >= $hoje && $proximo <= $limite;
		} catch (\Throwable $e) {
			return false;
		}
	}

	private static function pdo(): \PDO {
		static $pdo = null;
		if ($pdo instanceof \PDO) {
			return $pdo;
		}
		$pdo = new \PDO(
			'mysql:host='.getenv('DB_HOST').';dbname='.getenv('DB_NAME').';charset=utf8mb4',
			getenv('DB_USER'),
			getenv('DB_PASS'),
			[\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
		);
		return $pdo;
	}
}

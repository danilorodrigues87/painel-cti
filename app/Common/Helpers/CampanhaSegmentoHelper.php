<?php

namespace App\Common\Helpers;

use App\Model\Entity\CrmLeads;

class CampanhaSegmentoHelper {

	public static function getTipos(): array {
		return [
			'alunos_matriculados' => 'Alunos matriculados (ativos)',
			'ex_alunos'           => 'Ex-alunos (sem matrícula ativa)',
			'aniversariantes_mes' => 'Aniversariantes do mês',
			'leads'               => 'Leads do CRM',
			'inadimplentes'       => 'Inadimplentes (mensalidades em atraso)',
		];
	}

	public static function resolverDestinatarios(int $idAdmin, array $segmento): array {
		$tipo = $segmento['tipo'] ?? 'alunos_matriculados';

		switch ($tipo) {
			case 'ex_alunos':
				return self::exAlunos($idAdmin);
			case 'aniversariantes_mes':
				return self::aniversariantesMes($idAdmin);
			case 'leads':
				return self::leads($idAdmin, $segmento);
			case 'inadimplentes':
				return self::inadimplentes($idAdmin, $segmento);
			case 'alunos_matriculados':
			default:
				return self::alunosMatriculados($idAdmin);
		}
	}

	public static function aplicarVariaveis(string $texto, array $vars): string {
		$mapa = [
			'{nome}'  => $vars['nome'] ?? '',
			'{email}' => $vars['contato'] ?? '',
			'{curso}' => $vars['curso'] ?? '',
			'{escola}' => $vars['escola'] ?? '',
		];

		return str_replace(array_keys($mapa), array_values($mapa), $texto);
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

	private static function filtrarComEmail(array $destinatarios): array {
		$unicos = [];
		$vistos = [];

		foreach ($destinatarios as $item) {
			$email = EmailValidator::normalizar($item['contato'] ?? '');
			if (!EmailValidator::isValido($email)) {
				continue;
			}
			if (isset($vistos[$email])) {
				continue;
			}
			$vistos[$email] = true;
			$item['contato'] = $email;
			$unicos[] = $item;
		}

		return $unicos;
	}

	private static function alunosMatriculados(int $idAdmin): array {
		$hoje = date('Y-m-d');
		$sql = '
			SELECT DISTINCT u.id, u.nome, u.email, t.nome AS curso
			FROM usuarios u
			INNER JOIN matriculas m ON m.id_aluno = u.id AND m.id_admin = u.id_admin
			LEFT JOIN trilhas t ON t.id = m.id_trilha
			WHERE u.id_admin = :id_admin
			  AND u.nivel = "Cliente"
			  AND m.status = 0
			  AND m.fim >= :hoje
			  AND u.email IS NOT NULL
			  AND u.email != ""
		';

		$stmt = self::pdo()->prepare($sql);
		$stmt->execute(['id_admin' => $idAdmin, 'hoje' => $hoje]);

		return self::filtrarComEmail(self::mapearLinhas($stmt->fetchAll(\PDO::FETCH_ASSOC), 'aluno'));
	}

	private static function exAlunos(int $idAdmin): array {
		$hoje = date('Y-m-d');
		$sql = '
			SELECT DISTINCT u.id, u.nome, u.email, "" AS curso
			FROM usuarios u
			WHERE u.id_admin = :id_admin
			  AND u.nivel = "Cliente"
			  AND u.email IS NOT NULL
			  AND u.email != ""
			  AND u.id NOT IN (
			    SELECT m.id_aluno
			    FROM matriculas m
			    WHERE m.id_admin = :id_admin2
			      AND m.status = 0
			      AND m.fim >= :hoje
			  )
		';

		$stmt = self::pdo()->prepare($sql);
		$stmt->execute(['id_admin' => $idAdmin, 'id_admin2' => $idAdmin, 'hoje' => $hoje]);

		return self::filtrarComEmail(self::mapearLinhas($stmt->fetchAll(\PDO::FETCH_ASSOC), 'aluno'));
	}

	private static function aniversariantesMes(int $idAdmin): array {
		$mes = (int)date('m');
		$sql = '
			SELECT DISTINCT u.id, u.nome, u.email, "" AS curso
			FROM usuarios u
			WHERE u.id_admin = :id_admin
			  AND u.nivel = "Cliente"
			  AND u.nascimento IS NOT NULL
			  AND u.nascimento != "0000-00-00"
			  AND MONTH(u.nascimento) = :mes
			  AND u.email IS NOT NULL
			  AND u.email != ""
		';

		$stmt = self::pdo()->prepare($sql);
		$stmt->execute(['id_admin' => $idAdmin, 'mes' => $mes]);

		return self::filtrarComEmail(self::mapearLinhas($stmt->fetchAll(\PDO::FETCH_ASSOC), 'aluno'));
	}

	private static function leads(int $idAdmin, array $segmento): array {
		$where = 'id_admin = '.(int)$idAdmin;
		$status = $segmento['status_lead'] ?? '';

		if ($status !== '' && in_array($status, ['novo','em_atendimento','matriculado','perdido'], true)) {
			$where .= ' AND status = "'.addslashes($status).'"';
		}

		$where .= ' AND email IS NOT NULL AND email != ""';

		$results = CrmLeads::getLeads($where, 'nome ASC');
		$lista = [];

		while ($lead = $results->fetchObject(CrmLeads::class)) {
			$lista[] = [
				'destinatario_tipo' => 'lead',
				'destinatario_id'   => (int)$lead->id,
				'nome'              => $lead->nome,
				'contato'           => trim($lead->email),
				'curso'             => $lead->curso_interesse ?? '',
			];
		}

		return self::filtrarComEmail($lista);
	}

	private static function inadimplentes(int $idAdmin, array $segmento): array {
		$diasMin = max(1, (int)($segmento['dias_atraso_min'] ?? 1));
		$hoje = date('Y-m-d');
		$dataLimite = date('Y-m-d', strtotime('-'.$diasMin.' days'));

		$sql = '
			SELECT DISTINCT
				u.id,
				u.nome,
				u.email,
				c.descricao AS curso,
				c.valor,
				c.vencimento
			FROM caixa c
			INNER JOIN matriculas m ON m.id = c.id_ref AND m.id_admin = c.id_admin
			INNER JOIN usuarios u ON u.id = m.id_aluno AND u.id_admin = c.id_admin
			WHERE c.id_admin = :id_admin
			  AND c.tipo_transacao = "Entrada"
			  AND (c.status = 0 OR c.status = "0" OR c.status = "Em aberto")
			  AND c.vencimento <= :data_limite
			  AND m.status = 0
			  AND u.email IS NOT NULL
			  AND u.email != ""
			ORDER BY c.vencimento ASC
		';

		$stmt = self::pdo()->prepare($sql);
		$stmt->execute(['id_admin' => $idAdmin, 'data_limite' => $dataLimite]);
		$linhas = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

		$lista = [];
		foreach ($linhas as $row) {
			$lista[] = [
				'destinatario_tipo' => 'aluno',
				'destinatario_id'   => (int)$row['id'],
				'nome'              => $row['nome'],
				'contato'           => trim($row['email']),
				'curso'             => ($row['curso'] ?? '').' (venc. '.date('d/m/Y', strtotime($row['vencimento'])).')',
			];
		}

		return self::filtrarComEmail($lista);
	}

	private static function mapearLinhas(array $linhas, string $tipo): array {
		$lista = [];

		foreach ($linhas as $row) {
			$lista[] = [
				'destinatario_tipo' => $tipo,
				'destinatario_id'   => (int)($row['id'] ?? 0),
				'nome'              => $row['nome'] ?? '',
				'contato'           => trim($row['email'] ?? ''),
				'curso'             => $row['curso'] ?? '',
			];
		}

		return $lista;
	}
}

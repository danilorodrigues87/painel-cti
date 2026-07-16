<?php

namespace App\Common\Helpers;

use App\Common\Communication\EvolutionApiService;
use App\Model\Entity\CrmLeads;

class CampanhaSegmentoHelper {

	public static function getTipos(): array {
		return [
			'alunos_matriculados' => 'Alunos matriculados (ativos)',
			'ex_alunos'           => 'Ex-alunos (sem matrícula ativa)',
			'aniversariantes_mes' => 'Aniversariantes do mês',
			'aniversariantes_dia' => 'Aniversariantes de hoje',
			'leads'               => 'Leads do CRM',
			'inadimplentes'       => 'Inadimplentes (mensalidades em atraso)',
			'whatsapp_grupos'     => 'Grupos e listas de transmissão (WhatsApp)',
		];
	}

	/**
	 * @param string $canal email|whatsapp
	 */
	public static function resolverDestinatarios(int $idAdmin, array $segmento, string $canal = 'email'): array {
		$canal = $canal === 'whatsapp' ? 'whatsapp' : 'email';
		$tipo = $segmento['tipo'] ?? 'alunos_matriculados';

		// Destinos manuais (JID de grupo/lista) — só WhatsApp
		if ($tipo === 'whatsapp_grupos') {
			return self::destinosGruposListas($segmento);
		}

		switch ($tipo) {
			case 'ex_alunos':
				$lista = self::exAlunos($idAdmin, $canal);
				break;
			case 'aniversariantes_mes':
				$lista = self::aniversariantesMes($idAdmin, $canal);
				break;
			case 'aniversariantes_dia':
				$lista = self::aniversariantesDia($idAdmin, false, $canal);
				break;
			case 'aniversariantes_dia_matriculados':
				$lista = self::aniversariantesDia($idAdmin, true, $canal);
				break;
			case 'leads':
				$lista = self::leads($idAdmin, $segmento, $canal);
				break;
			case 'inadimplentes':
				$lista = self::inadimplentes($idAdmin, $segmento, $canal);
				break;
			case 'alunos_matriculados':
			default:
				$lista = self::alunosMatriculados($idAdmin, $canal);
				break;
		}

		return $canal === 'whatsapp'
			? self::filtrarComWhatsapp($lista)
			: self::filtrarComEmail($lista);
	}

	/** @param array{destinos?:array} $segmento */
	private static function destinosGruposListas(array $segmento): array {
		$lista = [];
		$vistos = [];
		foreach ($segmento['destinos'] ?? [] as $d) {
			if (!is_array($d)) {
				continue;
			}
			$jid = EvolutionApiService::normalizarDestino((string)($d['jid'] ?? ''));
			if ($jid === '' || !EvolutionApiService::isJidGrupoOuLista($jid)) {
				continue;
			}
			if (isset($vistos[$jid])) {
				continue;
			}
			$vistos[$jid] = true;
			$kind = (($d['kind'] ?? '') === 'lista' || strpos(strtolower($jid), '@broadcast') !== false)
				? 'lista'
				: 'grupo';
			$lista[] = [
				'destinatario_tipo' => $kind,
				'destinatario_id'   => null,
				'nome'              => trim((string)($d['nome'] ?? '')) ?: $jid,
				'contato'           => $jid,
				'curso'             => '',
			];
		}
		return $lista;
	}

	public static function aplicarVariaveis(string $texto, array $vars): string {
		$mapa = [
			'{nome}'     => $vars['nome'] ?? '',
			'{email}'    => $vars['contato'] ?? '',
			'{whatsapp}' => $vars['contato'] ?? '',
			'{telefone}' => $vars['contato'] ?? '',
			'{curso}'    => $vars['curso'] ?? '',
			'{escola}'   => $vars['escola'] ?? '',
		];

		return str_replace(array_keys($mapa), array_values($mapa), $texto);
	}

	/** Converte HTML de campanha/e-mail em texto para WhatsApp. */
	public static function textoParaWhatsapp(string $html): string {
		$t = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
		$t = preg_replace('#<(br|/?p|/?div|/?li|/?tr)[^>]*>#i', "\n", $t) ?? $t;
		$t = strip_tags($t);
		$t = preg_replace("/[ \t]+/", ' ', $t) ?? $t;
		$t = preg_replace("/\n{3,}/", "\n\n", $t) ?? $t;
		return trim($t);
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

	private static function filtrarComWhatsapp(array $destinatarios): array {
		$unicos = [];
		$vistos = [];

		foreach ($destinatarios as $item) {
			$tel = EvolutionApiService::normalizarTelefone((string)($item['contato'] ?? ''));
			if ($tel === '' || strlen($tel) < 12) {
				continue;
			}
			if (isset($vistos[$tel])) {
				continue;
			}
			$vistos[$tel] = true;
			$item['contato'] = $tel;
			$unicos[] = $item;
		}

		return $unicos;
	}

	private static function alunosMatriculados(int $idAdmin, string $canal): array {
		$hoje = date('Y-m-d');
		$campo = $canal === 'whatsapp' ? 'u.whatsapp' : 'u.email';
		$sql = '
			SELECT DISTINCT u.id, u.nome, '.$campo.' AS contato, t.nome AS curso
			FROM usuarios u
			INNER JOIN matriculas m ON m.id_aluno = u.id AND m.id_admin = u.id_admin
			LEFT JOIN trilhas t ON t.id = m.id_trilha
			WHERE u.id_admin = :id_admin
			  AND u.nivel = "Cliente"
			  AND m.status = 0
			  AND m.fim >= :hoje
			  AND '.$campo.' IS NOT NULL
			  AND '.$campo.' != ""
		';

		$stmt = self::pdo()->prepare($sql);
		$stmt->execute(['id_admin' => $idAdmin, 'hoje' => $hoje]);

		return self::mapearLinhas($stmt->fetchAll(\PDO::FETCH_ASSOC), 'aluno');
	}

	private static function exAlunos(int $idAdmin, string $canal): array {
		$hoje = date('Y-m-d');
		$campo = $canal === 'whatsapp' ? 'u.whatsapp' : 'u.email';
		$sql = '
			SELECT DISTINCT u.id, u.nome, '.$campo.' AS contato, "" AS curso
			FROM usuarios u
			WHERE u.id_admin = :id_admin
			  AND u.nivel = "Cliente"
			  AND '.$campo.' IS NOT NULL
			  AND '.$campo.' != ""
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

		return self::mapearLinhas($stmt->fetchAll(\PDO::FETCH_ASSOC), 'aluno');
	}

	private static function aniversariantesMes(int $idAdmin, string $canal): array {
		$mes = (int)date('m');
		$campo = $canal === 'whatsapp' ? 'u.whatsapp' : 'u.email';
		$sql = '
			SELECT DISTINCT u.id, u.nome, '.$campo.' AS contato, "" AS curso
			FROM usuarios u
			WHERE u.id_admin = :id_admin
			  AND u.nivel = "Cliente"
			  AND u.nascimento IS NOT NULL
			  AND u.nascimento != "0000-00-00"
			  AND MONTH(u.nascimento) = :mes
			  AND '.$campo.' IS NOT NULL
			  AND '.$campo.' != ""
		';

		$stmt = self::pdo()->prepare($sql);
		$stmt->execute(['id_admin' => $idAdmin, 'mes' => $mes]);

		return self::mapearLinhas($stmt->fetchAll(\PDO::FETCH_ASSOC), 'aluno');
	}

	private static function aniversariantesDia(int $idAdmin, bool $apenasMatriculados, string $canal = 'email'): array {
		$mes = (int)date('m');
		$dia = (int)date('d');
		$hoje = date('Y-m-d');
		$campo = $canal === 'whatsapp' ? 'u.whatsapp' : 'u.email';

		if ($apenasMatriculados) {
			$sql = '
				SELECT DISTINCT u.id, u.nome, '.$campo.' AS contato, "" AS curso
				FROM usuarios u
				INNER JOIN matriculas m ON m.id_aluno = u.id AND m.id_admin = u.id_admin
				WHERE u.id_admin = :id_admin
				  AND u.nivel = "Cliente"
				  AND u.nascimento IS NOT NULL
				  AND u.nascimento != "0000-00-00"
				  AND MONTH(u.nascimento) = :mes
				  AND DAY(u.nascimento) = :dia
				  AND m.status = 0
				  AND m.fim >= :hoje
				  AND '.$campo.' IS NOT NULL
				  AND '.$campo.' != ""
			';
			$stmt = self::pdo()->prepare($sql);
			$stmt->execute(['id_admin' => $idAdmin, 'mes' => $mes, 'dia' => $dia, 'hoje' => $hoje]);
		} else {
			$sql = '
				SELECT DISTINCT u.id, u.nome, '.$campo.' AS contato, "" AS curso
				FROM usuarios u
				WHERE u.id_admin = :id_admin
				  AND u.nivel = "Cliente"
				  AND u.nascimento IS NOT NULL
				  AND u.nascimento != "0000-00-00"
				  AND MONTH(u.nascimento) = :mes
				  AND DAY(u.nascimento) = :dia
				  AND '.$campo.' IS NOT NULL
				  AND '.$campo.' != ""
			';
			$stmt = self::pdo()->prepare($sql);
			$stmt->execute(['id_admin' => $idAdmin, 'mes' => $mes, 'dia' => $dia]);
		}

		return self::mapearLinhas($stmt->fetchAll(\PDO::FETCH_ASSOC), 'aluno');
	}

	private static function leads(int $idAdmin, array $segmento, string $canal): array {
		$where = 'id_admin = '.(int)$idAdmin;
		$status = $segmento['status_lead'] ?? '';

		if ($status !== '' && in_array($status, ['novo','em_atendimento','matriculado','perdido'], true)) {
			$where .= ' AND status = "'.addslashes($status).'"';
		}

		if ($canal === 'whatsapp') {
			$where .= ' AND whatsapp IS NOT NULL AND whatsapp != ""';
		} else {
			$where .= ' AND email IS NOT NULL AND email != ""';
		}

		$results = CrmLeads::getLeads($where, 'nome ASC');
		$lista = [];

		while ($lead = $results->fetchObject(CrmLeads::class)) {
			$lista[] = [
				'destinatario_tipo' => 'lead',
				'destinatario_id'   => (int)$lead->id,
				'nome'              => $lead->nome,
				'contato'           => trim($canal === 'whatsapp' ? (string)$lead->whatsapp : (string)$lead->email),
				'curso'             => $lead->curso_interesse ?? '',
			];
		}

		return $lista;
	}

	private static function inadimplentes(int $idAdmin, array $segmento, string $canal): array {
		$diasMin = max(1, (int)($segmento['dias_atraso_min'] ?? 1));
		$dataLimite = date('Y-m-d', strtotime('-'.$diasMin.' days'));
		$campo = $canal === 'whatsapp' ? 'u.whatsapp' : 'u.email';

		$sql = '
			SELECT DISTINCT
				u.id,
				u.nome,
				'.$campo.' AS contato,
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
			  AND '.$campo.' IS NOT NULL
			  AND '.$campo.' != ""
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
				'contato'           => trim((string)($row['contato'] ?? '')),
				'curso'             => ($row['curso'] ?? '').' (venc. '.date('d/m/Y', strtotime($row['vencimento'])).')',
			];
		}

		return $lista;
	}

	private static function mapearLinhas(array $linhas, string $tipo): array {
		$lista = [];

		foreach ($linhas as $row) {
			$lista[] = [
				'destinatario_tipo' => $tipo,
				'destinatario_id'   => (int)($row['id'] ?? 0),
				'nome'              => $row['nome'] ?? '',
				'contato'           => trim((string)($row['contato'] ?? $row['email'] ?? $row['whatsapp'] ?? '')),
				'curso'             => $row['curso'] ?? '',
			];
		}

		return $lista;
	}
}

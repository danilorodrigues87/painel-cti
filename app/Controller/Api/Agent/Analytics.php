<?php

namespace App\Controller\Api\Agent;

use App\Common\Helpers\AgentAnalyticsHelper;
use App\Model\Entity\AgentApiKey;
use App\Model\Entity\EscolasAssinantes;

/**
 * API Agent read-only (OpenClaw / automações).
 */
class Analytics {

	private static function ok(array $data, int $code = 200): array {
		return [
			'code' => $code,
			'json' => json_encode(array_merge(['success' => true], $data), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
		];
	}

	private static function fail(string $message, int $code = 400): array {
		return [
			'code' => $code,
			'json' => json_encode(['success' => false, 'erro' => $message], JSON_UNESCAPED_UNICODE),
		];
	}

	private static function audit($request, int $status): void {
		$key = $request->agentKey ?? null;
		$path = (string)($request->getUri() ?? '');
		$ip = $_SERVER['REMOTE_ADDR'] ?? null;
		AgentApiKey::audit(
			$key instanceof AgentApiKey ? (int)$key->id : null,
			$request->agentEscopo ?? null,
			isset($request->agentIdAdmin) ? $request->agentIdAdmin : null,
			(string)($request->getHttpMethod() ?? 'GET'),
			$path,
			$status,
			$ip
		);
	}

	/** Resolve id_admin: escola = chave; master = query/path. */
	private static function resolveIdAdmin($request, $idPath = null): array {
		$escopo = $request->agentEscopo ?? '';
		if ($escopo === 'escola') {
			$id = (int)($request->agentIdAdmin ?? 0);
			if ($idPath !== null && (int)$idPath > 0 && (int)$idPath !== $id) {
				return [null, self::fail('Chave de escola não pode acessar outro tenant', 403)];
			}
			return [$id, null];
		}

		$id = $idPath !== null && $idPath !== '' ? (int)$idPath : (int)($request->getQueryParams()['id_admin'] ?? 0);
		if ($id <= 0) {
			return [null, self::fail('Informe id_admin (query ou path)', 400)];
		}
		$escola = EscolasAssinantes::getEscolaById($id);
		if (!$escola) {
			return [null, self::fail('Escola não encontrada', 404)];
		}
		return [$id, null];
	}

	public static function health($request): array {
		self::audit($request, 200);
		$key = $request->agentKey;
		return self::ok([
			'status' => 'ok',
			'escopo' => $request->agentEscopo,
			'id_admin' => $request->agentIdAdmin,
			'key' => [
				'id' => (int)$key->id,
				'nome' => (string)$key->nome,
				'scopes' => $key->getScopes(),
			],
			'server_time' => date('c'),
			'version' => 'agent-v1',
		]);
	}

	public static function escolas($request): array {
		if (($request->agentEscopo ?? '') !== 'master') {
			$res = self::fail('Apenas chave Master lista escolas', 403);
			self::audit($request, 403);
			return $res;
		}
		self::audit($request, 200);
		return self::ok(['escolas' => AgentAnalyticsHelper::listarEscolas()]);
	}

	public static function resumo($request, $idAdmin = null): array {
		[$id, $err] = self::resolveIdAdmin($request, $idAdmin);
		if ($err) {
			self::audit($request, $err['code']);
			return $err;
		}
		self::audit($request, 200);
		return self::ok(['resumo' => AgentAnalyticsHelper::resumo($id)]);
	}

	public static function agendaHoje($request, $idAdmin = null): array {
		[$id, $err] = self::resolveIdAdmin($request, $idAdmin);
		if ($err) {
			self::audit($request, $err['code']);
			return $err;
		}
		$limit = (int)($request->getQueryParams()['limit'] ?? 100);
		self::audit($request, 200);
		return self::ok(AgentAnalyticsHelper::agendaHoje($id, $limit));
	}

	public static function inadimplentes($request, $idAdmin = null): array {
		[$id, $err] = self::resolveIdAdmin($request, $idAdmin);
		if ($err) {
			self::audit($request, $err['code']);
			return $err;
		}
		$periodo = (string)($request->getQueryParams()['periodo'] ?? 'mes');
		$limit = (int)($request->getQueryParams()['limit'] ?? 50);
		$listar = (($request->getQueryParams()['lista'] ?? '1') !== '0');
		self::audit($request, 200);
		$data = $listar
			? AgentAnalyticsHelper::inadimplentesLista($id, $periodo, $limit)
			: AgentAnalyticsHelper::inadimplentesTotais($id, $periodo);
		return self::ok($data);
	}

	public static function aReceber($request, $idAdmin = null): array {
		[$id, $err] = self::resolveIdAdmin($request, $idAdmin);
		if ($err) {
			self::audit($request, $err['code']);
			return $err;
		}
		$periodo = (string)($request->getQueryParams()['periodo'] ?? 'semana');
		self::audit($request, 200);
		return self::ok(AgentAnalyticsHelper::aReceber($id, $periodo));
	}

	public static function crm($request, $idAdmin = null): array {
		[$id, $err] = self::resolveIdAdmin($request, $idAdmin);
		if ($err) {
			self::audit($request, $err['code']);
			return $err;
		}
		$q = $request->getQueryParams();
		self::audit($request, 200);
		return self::ok(AgentAnalyticsHelper::crmResumo(
			$id,
			(string)($q['de'] ?? ''),
			(string)($q['ate'] ?? '')
		));
	}

	public static function matriculas($request, $idAdmin = null): array {
		[$id, $err] = self::resolveIdAdmin($request, $idAdmin);
		if ($err) {
			self::audit($request, $err['code']);
			return $err;
		}
		self::audit($request, 200);
		return self::ok(AgentAnalyticsHelper::matriculasResumo($id));
	}

	public static function whatsapp($request, $idAdmin = null): array {
		[$id, $err] = self::resolveIdAdmin($request, $idAdmin);
		if ($err) {
			self::audit($request, $err['code']);
			return $err;
		}
		self::audit($request, 200);
		return self::ok(AgentAnalyticsHelper::whatsapp($id));
	}
}

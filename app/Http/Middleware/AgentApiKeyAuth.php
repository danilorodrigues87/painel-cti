<?php

namespace App\Http\Middleware;

use App\Model\Entity\AgentApiKey;
use App\Model\Entity\EscolasAssinantes;
use App\Common\Helpers\ModuleGateHelper;

class AgentApiKeyAuth {

	public function handle($request, $next) {
		if (!AgentApiKey::tabelaExiste()) {
			throw new \Exception('Agent API não configurada. Execute database/agent_api.sql.', 503);
		}

		$headers = $request->getHeaders();
		$auth = $headers['Authorization'] ?? ($headers['authorization'] ?? '');
		$token = is_string($auth) ? preg_replace('/^Bearer\s+/i', '', trim($auth)) : '';
		if ($token === '') {
			$token = trim((string)($request->getQueryParams()['api_key'] ?? ''));
		}
		if ($token === '') {
			throw new \Exception('API key ausente', 401);
		}

		$obKey = AgentApiKey::autenticar($token);
		if (!$obKey instanceof AgentApiKey) {
			throw new \Exception('API key inválida', 401);
		}

		if (!$obKey->checkAndBumpRateLimit()) {
			throw new \Exception('Rate limit excedido (máx. '.AgentApiKey::RATE_LIMIT_PER_MIN.'/min)', 429);
		}

		if ($obKey->isMaster()) {
			$request->agentKey = $obKey;
			$request->agentEscopo = 'master';
			$request->agentIdAdmin = null;
			return $next($request);
		}

		$idAdmin = (int)$obKey->id_admin;
		if ($idAdmin <= 0) {
			throw new \Exception('Chave de escola inválida', 403);
		}

		$escola = EscolasAssinantes::getEscolaById($idAdmin);
		if (!$escola || !$escola->isAtiva()) {
			throw new \Exception('Escola inativa', 403);
		}

		$slugs = ModuleGateHelper::getSlugsEscola($idAdmin);
		if (!in_array('assistente_ia', $slugs, true)) {
			throw new \Exception('Módulo Assistente IA não liberado no plano desta escola', 403);
		}

		if (!\App\Model\Entity\AgentEscolaConfig::isAgentAtivo($idAdmin)) {
			throw new \Exception('Assistente IA desta escola está desativado pelo Master', 403);
		}

		$request->agentKey = $obKey;
		$request->agentEscopo = 'escola';
		$request->agentIdAdmin = $idAdmin;
		return $next($request);
	}
}

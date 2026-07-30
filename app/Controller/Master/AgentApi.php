<?php

namespace App\Controller\Master;

use App\Utils\View;
use App\Model\Entity\AgentApiKey;
use App\Model\Entity\AgentEscolaConfig;
use App\Model\Entity\EscolasAssinantes;
use App\Common\Helpers\ModuleGateHelper;

class AgentApi extends Page {

	public static function index($request) {
		$sqlKeys = AgentApiKey::tabelaExiste();
		$sqlCfg = AgentEscolaConfig::tabelaExiste();
		$content = View::render('master/modules/agent-api/index', [
			'sql_ok' => ($sqlKeys && $sqlCfg) ? '1' : '0',
			'alert_sql_class' => ($sqlKeys && $sqlCfg) ? 'd-none' : '',
			'api_base' => rtrim(URL, '/').'/api/v1/agent',
		]);
		return parent::getPanel('Agent API / OpenClaw', $content, 'agent');
	}

	public static function getInfo($request) {
		header('Content-Type: application/json; charset=utf-8');
		$post = $request->getPostVars();
		$acao = (string)($post['acao'] ?? '');

		if ($acao === 'listar_master') {
			return self::listarMaster();
		}
		if ($acao === 'criar_master') {
			return self::criarMaster($post);
		}
		if ($acao === 'revogar') {
			return self::revogar($post);
		}
		if ($acao === 'listar_escolas') {
			return self::listarEscolas();
		}
		if ($acao === 'criar_escola') {
			return self::criarEscola($post);
		}
		if ($acao === 'set_ativo') {
			return self::setAtivo($post);
		}
		if ($acao === 'detalhe_escola') {
			return self::detalheEscola($post);
		}
		if ($acao === 'revelar_segredos') {
			return self::revelarSegredos($post);
		}

		return json_encode(['success' => false, 'message' => 'Ação inválida.']);
	}

	private static function listarMaster(): string {
		if (!AgentApiKey::tabelaExiste()) {
			return json_encode(['success' => false, 'message' => 'Execute database/agent_api.sql.']);
		}
		return json_encode([
			'success' => true,
			'keys' => AgentApiKey::listarMaster(),
			'api_base' => rtrim(URL, '/').'/api/v1/agent',
		], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}

	private static function criarMaster(array $post): string {
		if (!AgentApiKey::tabelaExiste()) {
			return json_encode(['success' => false, 'message' => 'Execute database/agent_api.sql.']);
		}
		$nome = trim((string)($post['nome'] ?? 'Master OpenClaw'));
		$created = AgentApiKey::criar($nome, 'master', null, ['read:all']);
		if (!$created) {
			return json_encode(['success' => false, 'message' => 'Falha ao criar chave.']);
		}
		return json_encode([
			'success' => true,
			'message' => 'Chave Master criada. Copie agora — não será exibida de novo.',
			'plain' => $created['plain'],
			'key' => $created['key']->toPublicArray(),
		], JSON_UNESCAPED_UNICODE);
	}

	private static function revogar(array $post): string {
		if (!AgentApiKey::tabelaExiste()) {
			return json_encode(['success' => false, 'message' => 'Execute database/agent_api.sql.']);
		}
		$id = (int)($post['id'] ?? 0);
		$ob = AgentApiKey::getById($id);
		if (!$ob) {
			return json_encode(['success' => false, 'message' => 'Chave não encontrada.']);
		}
		$ob->revogar();
		return json_encode(['success' => true, 'message' => 'Chave revogada.']);
	}

	private static function listarEscolas(): string {
		$out = [];
		$st = EscolasAssinantes::getEscolas(null, 'nome ASC', null, 'id, nome, ativo');
		while ($r = $st->fetch(\PDO::FETCH_ASSOC)) {
			$id = (int)($r['id'] ?? 0);
			$temModulo = in_array('assistente_ia', ModuleGateHelper::getSlugsEscola($id), true);
			if (!$temModulo) {
				continue;
			}
			$cfg = AgentEscolaConfig::tabelaExiste()
				? AgentEscolaConfig::getByIdAdmin($id)
				: null;
			$keys = AgentApiKey::tabelaExiste() ? AgentApiKey::listarEscola($id) : [];
			$ativas = array_values(array_filter($keys, static fn($k) => !empty($k['ativo'])));
			$resumo = $cfg instanceof AgentEscolaConfig ? $cfg->toMasterResumoArray() : null;

			$out[] = [
				'id_admin' => $id,
				'nome' => (string)($r['nome'] ?? ''),
				'escola_ativa' => EscolasAssinantes::isAtivaValor($r['ativo'] ?? 1),
				'agent_ativo' => $cfg instanceof AgentEscolaConfig && (int)$cfg->agent_ativo === 1,
				'llm_pronto' => $resumo['llm_pronto'] ?? false,
				'telegram_pronto' => $resumo['telegram_pronto'] ?? false,
				'keys_ativas' => count($ativas),
				'key_prefix' => $ativas[0]['key_prefix'] ?? null,
			];
		}

		return json_encode([
			'success' => true,
			'escolas' => $out,
			'api_base' => rtrim(URL, '/').'/api/v1/agent',
			'sql_config_ok' => AgentEscolaConfig::tabelaExiste(),
		], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}

	private static function criarEscola(array $post): string {
		if (!AgentApiKey::tabelaExiste()) {
			return json_encode(['success' => false, 'message' => 'Execute database/agent_api.sql.']);
		}
		$idAdmin = (int)($post['id_admin'] ?? 0);
		$escola = EscolasAssinantes::getEscolaById($idAdmin);
		if (!$escola) {
			return json_encode(['success' => false, 'message' => 'Escola não encontrada.']);
		}
		if (!in_array('assistente_ia', ModuleGateHelper::getSlugsEscola($idAdmin), true)) {
			return json_encode(['success' => false, 'message' => 'Plano sem módulo Assistente IA.']);
		}

		$nome = trim((string)($post['nome'] ?? ''));
		if ($nome === '') {
			$nome = 'OpenClaw — '.(string)($escola->nome ?? 'escola '.$idAdmin);
		}

		$created = AgentApiKey::criar($nome, 'escola', $idAdmin, ['read:all']);
		if (!$created) {
			return json_encode(['success' => false, 'message' => 'Falha ao criar chave.']);
		}

		if (AgentEscolaConfig::tabelaExiste()) {
			AgentEscolaConfig::setAgentAtivo($idAdmin, true);
		}

		return json_encode([
			'success' => true,
			'message' => 'Chave da escola criada e assistente ativado. Copie agora.',
			'plain' => $created['plain'],
			'key' => $created['key']->toPublicArray(),
			'id_admin' => $idAdmin,
		], JSON_UNESCAPED_UNICODE);
	}

	private static function setAtivo(array $post): string {
		if (!AgentEscolaConfig::tabelaExiste()) {
			return json_encode(['success' => false, 'message' => 'Execute database/agent_escola_config.sql.']);
		}
		$idAdmin = (int)($post['id_admin'] ?? 0);
		$ativo = !empty($post['ativo']);
		if ($idAdmin <= 0) {
			return json_encode(['success' => false, 'message' => 'Escola inválida.']);
		}
		if (!AgentEscolaConfig::setAgentAtivo($idAdmin, $ativo)) {
			return json_encode(['success' => false, 'message' => AgentEscolaConfig::getUltimoErro() ?: 'Falha.']);
		}
		return json_encode([
			'success' => true,
			'message' => $ativo ? 'Assistente ativado.' : 'Assistente desativado.',
			'agent_ativo' => $ativo,
		]);
	}

	private static function detalheEscola(array $post): string {
		$idAdmin = (int)($post['id_admin'] ?? 0);
		$escola = EscolasAssinantes::getEscolaById($idAdmin);
		if (!$escola) {
			return json_encode(['success' => false, 'message' => 'Escola não encontrada.']);
		}
		$cfg = AgentEscolaConfig::tabelaExiste()
			? AgentEscolaConfig::getByIdAdmin($idAdmin)
			: null;
		$keys = AgentApiKey::tabelaExiste() ? AgentApiKey::listarEscola($idAdmin) : [];

		return json_encode([
			'success' => true,
			'id_admin' => $idAdmin,
			'nome' => (string)($escola->nome ?? ''),
			'config' => $cfg instanceof AgentEscolaConfig ? $cfg->toMasterResumoArray() : null,
			'keys' => $keys,
			'api_base' => rtrim(URL, '/').'/api/v1/agent',
		], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}

	private static function revelarSegredos(array $post): string {
		if (!AgentEscolaConfig::tabelaExiste()) {
			return json_encode(['success' => false, 'message' => 'Execute database/agent_escola_config.sql.']);
		}
		$idAdmin = (int)($post['id_admin'] ?? 0);
		$cfg = AgentEscolaConfig::getByIdAdmin($idAdmin);
		if (!$cfg instanceof AgentEscolaConfig) {
			return json_encode(['success' => false, 'message' => 'Escola ainda não cadastrou segredos.']);
		}
		return json_encode([
			'success' => true,
			'id_admin' => $idAdmin,
			'llm_provider' => (string)($cfg->llm_provider ?? ''),
			'llm_model' => (string)($cfg->llm_model ?? ''),
			'llm_api_key' => $cfg->getLlmApiKeyDescriptografada() ?: '',
			'telegram_bot_token' => $cfg->getTelegramBotTokenDescriptografado() ?: '',
			'telegram_bot_username' => (string)($cfg->telegram_bot_username ?? ''),
			'telegram_chat_id' => (string)($cfg->telegram_chat_id ?? ''),
			'telegram_notas' => (string)($cfg->telegram_notas ?? ''),
		], JSON_UNESCAPED_UNICODE);
	}
}

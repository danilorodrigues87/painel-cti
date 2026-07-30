<?php

namespace App\Controller\Admin;

use App\Utils\View;
use App\Session\User\Login as SessionUser;
use App\Common\Helpers\TenantHelper;
use App\Common\Helpers\ModuleGateHelper;
use App\Model\Entity\AgentApiKey;
use App\Model\Entity\AgentEscolaConfig;
use App\Model\Entity\EscolaIntegracoes;

class ConfigAssistente extends Page {

	private static function assertAcesso($request, bool $api = false): bool {
		$user = SessionUser::getUserLogedData();
		if (($user['usuario']['nivel'] ?? '') !== 'Diretor') {
			if (!$api) {
				$request->getRouter()->redirect('/painel');
			}
			return false;
		}
		$idAdmin = (int)($user['usuario']['id_admin'] ?? 0);
		if (!in_array('assistente_ia', ModuleGateHelper::getSlugsEscola($idAdmin), true)) {
			if (!$api) {
				$request->getRouter()->redirect('/painel');
			}
			return false;
		}
		return true;
	}

	public static function index($request) {
		if (!self::assertAcesso($request)) {
			return '';
		}
		$sqlOk = AgentEscolaConfig::tabelaExiste();
		$content = View::render('admin/modules/config/assistente', [
			'sql_ok' => $sqlOk ? '1' : '0',
			'alert_sql_class' => $sqlOk ? 'd-none' : '',
		]);
		return parent::getPanel('Assistente IA', $content, 'config', $request);
	}

	public static function getInfo($request) {
		header('Content-Type: application/json; charset=utf-8');
		if (!self::assertAcesso($request, true)) {
			return json_encode(['success' => false, 'message' => 'Acesso negado.']);
		}

		$idAdmin = TenantHelper::getIdAdmin();
		$post = $request->getPostVars();
		$acao = (string)($post['acao'] ?? '');

		if ($acao === 'carregar') {
			return self::carregar($idAdmin);
		}

		if ($acao === 'salvar') {
			return self::salvar($idAdmin, $post);
		}

		if ($acao === 'pedagogica_ref') {
			return self::pedagogicaRef($idAdmin);
		}

		return json_encode(['success' => false, 'message' => 'Ação inválida.']);
	}

	private static function carregar(int $idAdmin): string {
		if (!AgentEscolaConfig::tabelaExiste()) {
			return json_encode([
				'success' => false,
				'coluna_ok' => false,
				'message' => 'Execute database/agent_escola_config.sql no phpMyAdmin.',
			]);
		}

		$cfg = AgentEscolaConfig::getByIdAdmin($idAdmin);
		$dados = $cfg instanceof AgentEscolaConfig
			? $cfg->toEscolaPublicArray()
			: [
				'agent_ativo' => false,
				'llm_ativo' => false,
				'llm_provider' => '',
				'llm_model' => '',
				'llm_key_salva' => false,
				'llm_key_mask' => '',
				'telegram_bot_username' => '',
				'telegram_chat_id' => '',
				'telegram_notas' => '',
				'telegram_token_salvo' => false,
				'telegram_token_mask' => '',
				'llm_pronto' => false,
				'telegram_pronto' => false,
			];

		$keys = AgentApiKey::tabelaExiste() ? AgentApiKey::listarEscola($idAdmin) : [];
		$keysAtivas = array_values(array_filter($keys, static function ($k) {
			return !empty($k['ativo']);
		}));

		return json_encode([
			'success' => true,
			'coluna_ok' => true,
			'id_admin' => $idAdmin,
			'config' => $dados,
			'agent_keys' => $keysAtivas,
			'agent_api_pronta' => !empty($keysAtivas) && !empty($dados['agent_ativo']),
			'tem_ia_pedagogica' => self::temPedagogicaPronta($idAdmin),
		], JSON_UNESCAPED_UNICODE);
	}

	private static function temPedagogicaPronta(int $idAdmin): bool {
		if (!EscolaIntegracoes::temColunasAi()) {
			return false;
		}
		$cfg = EscolaIntegracoes::getByIdAdmin($idAdmin);
		return $cfg instanceof EscolaIntegracoes && $cfg->temAiAtivo();
	}

	private static function pedagogicaRef(int $idAdmin): string {
		if (!EscolaIntegracoes::temColunasAi()) {
			return json_encode(['success' => false, 'message' => 'IA Pedagógica não disponível.']);
		}
		$cfg = EscolaIntegracoes::getByIdAdmin($idAdmin);
		if (!$cfg instanceof EscolaIntegracoes) {
			return json_encode(['success' => false, 'message' => 'Nenhuma IA Pedagógica configurada.']);
		}
		$key = $cfg->getAiApiKeyDescriptografada();
		if (!$key) {
			return json_encode(['success' => false, 'message' => 'IA Pedagógica sem chave salva.']);
		}
		return json_encode([
			'success' => true,
			'provider' => (string)($cfg->ai_provider ?? ''),
			'model' => (string)($cfg->ai_model ?? ''),
			'api_key' => $key,
			'message' => 'Dados da IA Pedagógica carregados. Revise e salve se quiser usar a mesma chave no OpenClaw.',
		], JSON_UNESCAPED_UNICODE);
	}

	private static function salvar(int $idAdmin, array $post): string {
		if (!AgentEscolaConfig::tabelaExiste()) {
			return json_encode([
				'success' => false,
				'message' => 'Execute database/agent_escola_config.sql no phpMyAdmin.',
			]);
		}

		$cfg = AgentEscolaConfig::getByIdAdmin($idAdmin);
		if (!$cfg instanceof AgentEscolaConfig) {
			$cfg = new AgentEscolaConfig();
			$cfg->id_admin = $idAdmin;
		}

		$provider = (string)($post['llm_provider'] ?? '');
		$cfg->llm_ativo = !empty($post['llm_ativo']) ? 1 : 0;
		$cfg->llm_provider = in_array($provider, ['openai', 'gemini', 'outro'], true) ? $provider : null;
		$cfg->llm_model = trim((string)($post['llm_model'] ?? ''));
		$cfg->telegram_bot_username = trim((string)($post['telegram_bot_username'] ?? ''));
		$cfg->telegram_chat_id = trim((string)($post['telegram_chat_id'] ?? ''));
		$cfg->telegram_notas = trim((string)($post['telegram_notas'] ?? ''));

		$llmKey = trim((string)($post['llm_api_key'] ?? ''));
		$tgToken = trim((string)($post['telegram_bot_token'] ?? ''));

		if (!$cfg->salvarPelaEscola(
			$llmKey !== '' ? $llmKey : null,
			$tgToken !== '' ? $tgToken : null
		)) {
			return json_encode([
				'success' => false,
				'message' => AgentEscolaConfig::getUltimoErro() ?: 'Falha ao salvar.',
			]);
		}

		return json_encode([
			'success' => true,
			'message' => 'Configuração do Assistente IA salva.',
		]);
	}
}

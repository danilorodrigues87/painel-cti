<?php

namespace App\Controller\Admin;

use App\Utils\View;
use App\Session\User\Login as SessionUser;
use App\Common\Helpers\TenantHelper;
use App\Common\Helpers\ModuleGateHelper;
use App\Model\Entity\EscolaIntegracoes;

class ConfigIa extends Page {

	private static function assertAcesso($request, bool $api = false): bool {
		$user = SessionUser::getUserLogedData();
		if (($user['usuario']['nivel'] ?? '') !== 'Diretor') {
			if (!$api) {
				$request->getRouter()->redirect('/painel');
			}
			return false;
		}
		$idAdmin = (int)($user['usuario']['id_admin'] ?? 0);
		$mods = ModuleGateHelper::getModulosEfetivos($idAdmin, $user['usuario']['acesso'] ?? []);
		if (!in_array('ead', ModuleGateHelper::getSlugsEscola($idAdmin), true)
			|| !in_array('Cursos Online', $mods, true)) {
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
		$content = View::render('admin/modules/config/ia', []);
		return parent::getPanel('IA Pedagógica', $content, 'config', $request);
	}

	public static function getInfo($request) {
		if (!self::assertAcesso($request, true)) {
			return json_encode(['success' => false, 'message' => 'Acesso negado.']);
		}
		$post = $request->getPostVars();
		$acao = $post['acao'] ?? '';
		if ($acao === 'carregar') {
			return self::carregar();
		}
		if ($acao === 'salvar') {
			return self::salvar($post);
		}
		return json_encode(['success' => false, 'message' => 'Ação inválida.']);
	}

	private static function carregar(): string {
		$idAdmin = TenantHelper::getIdAdmin();
		$colOk = EscolaIntegracoes::temColunasAi();
		$cfg = EscolaIntegracoes::getByIdAdmin($idAdmin);
		$keyMask = '';
		if ($cfg instanceof EscolaIntegracoes) {
			$plain = $cfg->getAiApiKeyDescriptografada();
			if ($plain) {
				$len = strlen($plain);
				$keyMask = $len > 8 ? substr($plain, 0, 4).str_repeat('*', max(4, $len - 8)).substr($plain, -4) : '********';
			}
		}
		return json_encode([
			'success' => true,
			'coluna_ok' => $colOk,
			'ai_ativo' => $cfg instanceof EscolaIntegracoes ? (int)$cfg->ai_ativo : 0,
			'ai_provider' => $cfg instanceof EscolaIntegracoes ? ($cfg->ai_provider ?: '') : '',
			'ai_model' => $cfg instanceof EscolaIntegracoes ? ($cfg->ai_model ?: '') : '',
			'key_salva' => $keyMask !== '',
			'key_mask' => $keyMask,
			'ai_pronto' => $cfg instanceof EscolaIntegracoes && $cfg->temAiAtivo(),
		], JSON_UNESCAPED_UNICODE);
	}

	private static function salvar(array $post): string {
		if (!EscolaIntegracoes::temColunasAi()) {
			return json_encode([
				'success' => false,
				'message' => 'Execute o SQL database/lms_ead.sql no phpMyAdmin.',
			]);
		}
		$idAdmin = TenantHelper::getIdAdmin();
		$cfg = EscolaIntegracoes::getByIdAdmin($idAdmin);
		if (!$cfg instanceof EscolaIntegracoes) {
			$cfg = new EscolaIntegracoes();
			$cfg->id_admin = $idAdmin;
		}
		$cfg->ai_ativo = !empty($post['ai_ativo']) ? 1 : 0;
		$provider = (string)($post['ai_provider'] ?? '');
		$cfg->ai_provider = in_array($provider, ['openai', 'gemini', 'outro'], true) ? $provider : null;
		$cfg->ai_model = trim((string)($post['ai_model'] ?? ''));
		$key = trim((string)($post['ai_api_key'] ?? ''));
		if (!$cfg->salvarAi($key !== '' ? $key : null)) {
			return json_encode(['success' => false, 'message' => EscolaIntegracoes::getUltimoErro() ?: 'Falha ao salvar.']);
		}
		return json_encode(['success' => true, 'message' => 'Configuração de IA salva.', 'ai_pronto' => $cfg->temAiAtivo()]);
	}
}

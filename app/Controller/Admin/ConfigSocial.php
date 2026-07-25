<?php

namespace App\Controller\Admin;

use App\Utils\View;
use App\Session\User\Login as SessionUser;
use App\Common\Helpers\TenantHelper;
use App\Common\Helpers\ModuleGateHelper;
use App\Common\Helpers\MetaGraphHelper;
use App\Model\Entity\EscolaIntegracoes;

class ConfigSocial extends Page {

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
		if (!in_array('social', ModuleGateHelper::getSlugsEscola($idAdmin), true)
			|| !in_array('Redes sociais', $mods, true)) {
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
		$content = View::render('admin/modules/config/social', []);
		return parent::getPanel('Redes sociais', $content, 'config', $request);
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
		if ($acao === 'testar') {
			return self::testar();
		}
		if ($acao === 'oauth_url') {
			return self::oauthUrl();
		}
		if ($acao === 'desconectar') {
			return self::desconectar();
		}
		return json_encode(['success' => false, 'message' => 'Ação inválida.']);
	}

	private static function mask(?string $plain): string {
		if (!$plain) {
			return '';
		}
		$len = strlen($plain);
		return $len > 8
			? substr($plain, 0, 4).str_repeat('*', max(4, $len - 8)).substr($plain, -4)
			: '********';
	}

	private static function carregar(): string {
		$idAdmin = TenantHelper::getIdAdmin();
		$colOk = EscolaIntegracoes::temColunasMeta();
		$cfg = EscolaIntegracoes::getByIdAdmin($idAdmin);
		$tokenMask = '';
		if ($cfg instanceof EscolaIntegracoes) {
			$tokenMask = self::mask($cfg->getMetaPageTokenDescriptografada());
		}
		$wh = ($cfg instanceof EscolaIntegracoes && !empty($cfg->meta_webhook_token))
			? (string)$cfg->meta_webhook_token
			: '';
		$webhookUrl = $wh !== ''
			? rtrim((string)URL, '/').'/webhook/meta/'.$idAdmin.'/'.$wh
			: '';

		return json_encode([
			'success' => true,
			'coluna_ok' => $colOk,
			'app_ok' => MetaGraphHelper::appConfigurado(),
			'meta_fb_ativo' => $cfg instanceof EscolaIntegracoes ? (int)$cfg->meta_fb_ativo : 0,
			'meta_ig_ativo' => $cfg instanceof EscolaIntegracoes ? (int)$cfg->meta_ig_ativo : 0,
			'meta_page_id' => $cfg instanceof EscolaIntegracoes ? (string)($cfg->meta_page_id ?? '') : '',
			'meta_page_name' => $cfg instanceof EscolaIntegracoes ? (string)($cfg->meta_page_name ?? '') : '',
			'meta_ig_user_id' => $cfg instanceof EscolaIntegracoes ? (string)($cfg->meta_ig_user_id ?? '') : '',
			'meta_ig_username' => $cfg instanceof EscolaIntegracoes ? (string)($cfg->meta_ig_username ?? '') : '',
			'token_salvo' => $tokenMask !== '',
			'token_mask' => $tokenMask,
			'meta_pronto' => $cfg instanceof EscolaIntegracoes && $cfg->temMetaPronto(),
			'meta_conectado_em' => $cfg instanceof EscolaIntegracoes ? (string)($cfg->meta_conectado_em ?? '') : '',
			'webhook_url' => $webhookUrl,
		], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}

	private static function salvar(array $post): string {
		if (!EscolaIntegracoes::temColunasMeta()) {
			return json_encode([
				'success' => false,
				'message' => 'Execute database/escola_integracoes_meta.sql no phpMyAdmin.',
			]);
		}
		$idAdmin = TenantHelper::getIdAdmin();
		$cfg = EscolaIntegracoes::getByIdAdmin($idAdmin);
		if (!$cfg instanceof EscolaIntegracoes) {
			$cfg = new EscolaIntegracoes();
			$cfg->id_admin = $idAdmin;
		}
		$cfg->meta_fb_ativo = !empty($post['meta_fb_ativo']) ? 1 : 0;
		$cfg->meta_ig_ativo = !empty($post['meta_ig_ativo']) ? 1 : 0;
		$pageId = trim((string)($post['meta_page_id'] ?? ''));
		$igId = trim((string)($post['meta_ig_user_id'] ?? ''));
		if ($pageId !== '') {
			$cfg->meta_page_id = $pageId;
		}
		if (isset($post['meta_page_name'])) {
			$cfg->meta_page_name = trim((string)$post['meta_page_name']) ?: null;
		}
		if ($igId !== '') {
			$cfg->meta_ig_user_id = $igId;
		}
		if (isset($post['meta_ig_username'])) {
			$cfg->meta_ig_username = trim((string)$post['meta_ig_username']) ?: null;
		}
		$token = trim((string)($post['meta_page_token'] ?? ''));
		if (!$cfg->salvarMeta($token !== '' ? $token : null)) {
			return json_encode(['success' => false, 'message' => EscolaIntegracoes::getUltimoErro() ?: 'Falha ao salvar.']);
		}
		$cfg = EscolaIntegracoes::getByIdAdmin($idAdmin);
		return json_encode([
			'success' => true,
			'message' => 'Configuração salva.',
			'meta_pronto' => $cfg instanceof EscolaIntegracoes && $cfg->temMetaPronto(),
		], JSON_UNESCAPED_UNICODE);
	}

	private static function testar(): string {
		$idAdmin = TenantHelper::getIdAdmin();
		$res = MetaGraphHelper::testarEscola($idAdmin);
		return json_encode([
			'success' => !empty($res['ok']),
			'message' => $res['message'] ?? ($res['ok'] ? 'OK' : 'Falha'),
			'page_name' => $res['page_name'] ?? null,
			'ig_username' => $res['ig_username'] ?? null,
			'auth_error' => !empty($res['auth_error']),
		], JSON_UNESCAPED_UNICODE);
	}

	private static function oauthUrl(): string {
		if (!MetaGraphHelper::appConfigurado()) {
			return json_encode([
				'success' => false,
				'message' => 'Configure META_APP_ID e META_APP_SECRET no .env do servidor.',
			]);
		}
		$idAdmin = TenantHelper::getIdAdmin();
		$state = bin2hex(random_bytes(16));
		if (session_status() !== PHP_SESSION_ACTIVE) {
			@session_start();
		}
		$_SESSION['meta_oauth_state'] = $state;
		$_SESSION['meta_oauth_id_admin'] = $idAdmin;
		return json_encode([
			'success' => true,
			'url' => MetaGraphHelper::oauthAuthorizeUrl($idAdmin, $state),
		], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}

	private static function desconectar(): string {
		$idAdmin = TenantHelper::getIdAdmin();
		$cfg = EscolaIntegracoes::getByIdAdmin($idAdmin);
		if (!$cfg instanceof EscolaIntegracoes) {
			return json_encode(['success' => true, 'message' => 'Já desconectado.']);
		}
		$cfg->meta_fb_ativo = 0;
		$cfg->meta_ig_ativo = 0;
		$cfg->meta_page_id = null;
		$cfg->meta_page_name = null;
		$cfg->meta_ig_user_id = null;
		$cfg->meta_ig_username = null;
		$cfg->meta_conectado_em = null;
		$cfg->meta_token_expires_at = null;
		// Limpa token criptografando string vazia via update direto
		if (!EscolaIntegracoes::temColunasMeta()) {
			return json_encode(['success' => false, 'message' => 'SQL Meta ausente.']);
		}
		$db = new \App\Model\Db\Database('escola_integracoes');
		$db->update('id_admin = '.(int)$idAdmin, [
			'meta_fb_ativo' => 0,
			'meta_ig_ativo' => 0,
			'meta_page_id' => null,
			'meta_page_name' => null,
			'meta_ig_user_id' => null,
			'meta_ig_username' => null,
			'meta_page_token' => null,
			'meta_conectado_em' => null,
			'meta_token_expires_at' => null,
		]);
		return json_encode(['success' => true, 'message' => 'Conta desconectada.']);
	}

	/** Callback OAuth Meta (GET). */
	public static function oauthCallback($request) {
		if (!self::assertAcesso($request)) {
			return '';
		}
		$q = $request->getQueryParams() ?: [];
		$code = (string)($q['code'] ?? '');
		$state = (string)($q['state'] ?? '');
		$err = (string)($q['error_description'] ?? $q['error'] ?? '');
		if ($err !== '') {
			$request->getRouter()->redirect('/painel/config/social?oauth=erro&msg='.rawurlencode($err));
			return '';
		}
		if (session_status() !== PHP_SESSION_ACTIVE) {
			@session_start();
		}
		$expected = (string)($_SESSION['meta_oauth_state'] ?? '');
		if ($code === '' || $state === '' || $expected === '' || !hash_equals($expected, $state)) {
			$request->getRouter()->redirect('/painel/config/social?oauth=erro&msg='.rawurlencode('State OAuth inválido.'));
			return '';
		}
		unset($_SESSION['meta_oauth_state']);

		$tok = MetaGraphHelper::trocarCodePorToken($code);
		if (empty($tok['ok']) || empty($tok['access_token'])) {
			$request->getRouter()->redirect('/painel/config/social?oauth=erro&msg='.rawurlencode($tok['message'] ?? 'Falha no code.'));
			return '';
		}
		$long = MetaGraphHelper::longLivedUserToken((string)$tok['access_token']);
		$userToken = !empty($long['ok']) && !empty($long['access_token'])
			? (string)$long['access_token']
			: (string)$tok['access_token'];

		$pages = MetaGraphHelper::listarPages($userToken);
		if (empty($pages['ok']) || empty($pages['pages'])) {
			$request->getRouter()->redirect('/painel/config/social?oauth=erro&msg='.rawurlencode($pages['message'] ?? 'Nenhuma Page encontrada.'));
			return '';
		}

		// Usa a primeira Page (UI de escolha pode vir depois)
		$p = $pages['pages'][0];
		$idAdmin = TenantHelper::getIdAdmin();
		$cfg = EscolaIntegracoes::getByIdAdmin($idAdmin);
		if (!$cfg instanceof EscolaIntegracoes) {
			$cfg = new EscolaIntegracoes();
			$cfg->id_admin = $idAdmin;
		}
		$cfg->meta_page_id = $p['page_id'];
		$cfg->meta_page_name = $p['page_name'];
		$cfg->meta_ig_user_id = $p['ig_user_id'] !== '' ? $p['ig_user_id'] : null;
		$cfg->meta_ig_username = $p['ig_username'] !== '' ? $p['ig_username'] : null;
		$cfg->meta_fb_ativo = 1;
		$cfg->meta_ig_ativo = $p['ig_user_id'] !== '' ? 1 : 0;
		$cfg->meta_conectado_em = date('Y-m-d H:i:s');
		if (!empty($long['expires_in'])) {
			$cfg->meta_token_expires_at = date('Y-m-d H:i:s', time() + (int)$long['expires_in']);
		}
		$cfg->salvarMeta($p['page_token']);

		$n = count($pages['pages']);
		$request->getRouter()->redirect('/painel/config/social?oauth=ok&pages='.$n);
		return '';
	}
}

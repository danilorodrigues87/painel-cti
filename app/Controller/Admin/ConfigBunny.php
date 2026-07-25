<?php

namespace App\Controller\Admin;

use App\Utils\View;
use App\Session\User\Login as SessionUser;
use App\Common\Helpers\TenantHelper;
use App\Common\Helpers\ModuleGateHelper;
use App\Common\Helpers\BunnyStreamHelper;
use App\Model\Entity\EscolaIntegracoes;

class ConfigBunny extends Page {

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
		$content = View::render('admin/modules/config/bunny', []);
		return parent::getPanel('Bunny Stream', $content, 'config', $request);
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
		$colOk = EscolaIntegracoes::temColunasBunny();
		$cfg = EscolaIntegracoes::getByIdAdmin($idAdmin);
		$apiMask = '';
		$tokenMask = '';
		if ($cfg instanceof EscolaIntegracoes) {
			$apiMask = self::mask($cfg->getBunnyApiKeyDescriptografada());
			$tokenMask = self::mask($cfg->getBunnyTokenKeyDescriptografada());
		}
		return json_encode([
			'success' => true,
			'coluna_ok' => $colOk,
			'bunny_ativo' => $cfg instanceof EscolaIntegracoes ? (int)$cfg->bunny_ativo : 0,
			'bunny_library_id' => $cfg instanceof EscolaIntegracoes ? (string)($cfg->bunny_library_id ?? '') : '',
			'bunny_cdn_hostname' => $cfg instanceof EscolaIntegracoes ? (string)($cfg->bunny_cdn_hostname ?? '') : '',
			'api_key_salva' => $apiMask !== '',
			'api_key_mask' => $apiMask,
			'token_key_salva' => $tokenMask !== '',
			'token_key_mask' => $tokenMask,
			'bunny_pronto' => $cfg instanceof EscolaIntegracoes && $cfg->temBunnyAtivo(),
		], JSON_UNESCAPED_UNICODE);
	}

	private static function salvar(array $post): string {
		if (!EscolaIntegracoes::temColunasBunny()) {
			return json_encode([
				'success' => false,
				'message' => 'Execute database/escola_integracoes_bunny.sql no phpMyAdmin.',
			]);
		}
		$idAdmin = TenantHelper::getIdAdmin();
		$cfg = EscolaIntegracoes::getByIdAdmin($idAdmin);
		if (!$cfg instanceof EscolaIntegracoes) {
			$cfg = new EscolaIntegracoes();
			$cfg->id_admin = $idAdmin;
		}
		$cfg->bunny_ativo = !empty($post['bunny_ativo']) ? 1 : 0;
		$cfg->bunny_library_id = trim((string)($post['bunny_library_id'] ?? '')) ?: null;
		$host = trim((string)($post['bunny_cdn_hostname'] ?? ''));
		$host = preg_replace('#^https?://#i', '', $host);
		$host = rtrim((string)$host, '/');
		$cfg->bunny_cdn_hostname = $host !== '' ? $host : null;
		$apiKey = trim((string)($post['bunny_api_key'] ?? ''));
		$tokenKey = trim((string)($post['bunny_token_key'] ?? ''));
		if (!$cfg->salvarBunny($apiKey !== '' ? $apiKey : null, $tokenKey !== '' ? $tokenKey : null)) {
			return json_encode(['success' => false, 'message' => EscolaIntegracoes::getUltimoErro() ?: 'Falha ao salvar.']);
		}
		// recarrega para temBunnyAtivo
		$cfg = EscolaIntegracoes::getByIdAdmin($idAdmin);
		return json_encode([
			'success' => true,
			'message' => 'Configuração Bunny salva.',
			'bunny_pronto' => $cfg instanceof EscolaIntegracoes && $cfg->temBunnyAtivo(),
		], JSON_UNESCAPED_UNICODE);
	}

	private static function testar(): string {
		$idAdmin = TenantHelper::getIdAdmin();
		$res = BunnyStreamHelper::testar($idAdmin);
		return json_encode([
			'success' => !empty($res['ok']),
			'message' => $res['message'] ?? ($res['ok'] ? 'OK' : 'Falha'),
			'name' => $res['name'] ?? null,
		], JSON_UNESCAPED_UNICODE);
	}
}

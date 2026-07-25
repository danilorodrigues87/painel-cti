<?php

namespace App\Common\Helpers;

use App\Common\Environment;
use App\Model\Entity\EscolaIntegracoes;

/**
 * Chamadas Graph API Meta (Facebook Page + Instagram Professional).
 * App CTI: META_APP_ID / META_APP_SECRET no .env
 */
class MetaGraphHelper {

	public static function graphVersion(): string {
		$v = trim((string)Environment::get('META_GRAPH_VERSION', 'v21.0'));
		return $v !== '' ? $v : 'v21.0';
	}

	public static function appId(): string {
		return trim((string)Environment::get('META_APP_ID', ''));
	}

	public static function appSecret(): string {
		return trim((string)Environment::get('META_APP_SECRET', ''));
	}

	public static function webhookVerifyToken(): string {
		return trim((string)Environment::get('META_WEBHOOK_VERIFY_TOKEN', ''));
	}

	public static function appConfigurado(): bool {
		return self::appId() !== '' && self::appSecret() !== '';
	}

	public static function oauthRedirectUri(): string {
		return rtrim((string)URL, '/').'/painel/config/social/oauth/callback';
	}

	/** Escopos Fase 1 (publicação). */
	public static function oauthScopes(): string {
		return implode(',', [
			'pages_show_list',
			'pages_manage_posts',
			'pages_read_engagement',
			'instagram_basic',
			'instagram_content_publish',
			'business_management',
		]);
	}

	public static function oauthAuthorizeUrl(int $idAdmin, string $state): string {
		$q = http_build_query([
			'client_id' => self::appId(),
			'redirect_uri' => self::oauthRedirectUri(),
			'state' => $state,
			'scope' => self::oauthScopes(),
			'response_type' => 'code',
		]);
		return 'https://www.facebook.com/'.self::graphVersion().'/dialog/oauth?'.$q;
	}

	/**
	 * @return array{ok:bool,access_token?:string,expires_in?:int,message?:string}
	 */
	public static function trocarCodePorToken(string $code): array {
		return self::get('oauth/access_token', [
			'client_id' => self::appId(),
			'client_secret' => self::appSecret(),
			'redirect_uri' => self::oauthRedirectUri(),
			'code' => $code,
		], null);
	}

	/**
	 * @return array{ok:bool,access_token?:string,expires_in?:int,message?:string}
	 */
	public static function longLivedUserToken(string $shortToken): array {
		return self::get('oauth/access_token', [
			'grant_type' => 'fb_exchange_token',
			'client_id' => self::appId(),
			'client_secret' => self::appSecret(),
			'fb_exchange_token' => $shortToken,
		], null);
	}

	/**
	 * Lista Pages do usuário + IG Business Account se houver.
	 * @return array{ok:bool,pages?:array,message?:string}
	 */
	public static function listarPages(string $userToken): array {
		$res = self::get('me/accounts', [
			'fields' => 'id,name,access_token,instagram_business_account{id,username}',
			'limit' => 50,
		], $userToken);
		if (empty($res['ok'])) {
			return $res;
		}
		$pages = [];
		foreach (($res['data']['data'] ?? []) as $p) {
			$ig = $p['instagram_business_account'] ?? null;
			$pages[] = [
				'page_id' => (string)($p['id'] ?? ''),
				'page_name' => (string)($p['name'] ?? ''),
				'page_token' => (string)($p['access_token'] ?? ''),
				'ig_user_id' => $ig ? (string)($ig['id'] ?? '') : '',
				'ig_username' => $ig ? (string)($ig['username'] ?? '') : '',
			];
		}
		return ['ok' => true, 'pages' => $pages];
	}

	/**
	 * @return array{ok:bool,message?:string,page_name?:string,ig_username?:string}
	 */
	public static function testarEscola(int $idAdmin): array {
		$cfg = EscolaIntegracoes::getByIdAdmin($idAdmin);
		if (!$cfg instanceof EscolaIntegracoes || !EscolaIntegracoes::temColunasMeta()) {
			return ['ok' => false, 'message' => 'Execute database/escola_integracoes_meta.sql'];
		}
		$token = $cfg->getMetaPageTokenDescriptografada();
		$pageId = trim((string)($cfg->meta_page_id ?? ''));
		if (!$token || $pageId === '') {
			return ['ok' => false, 'message' => 'Conecte a Page (token/page_id ausentes).'];
		}
		$res = self::get($pageId, ['fields' => 'id,name'], $token);
		if (empty($res['ok'])) {
			$msg = (string)($res['message'] ?? 'Falha ao consultar Page');
			if (stripos($msg, '190') !== false || stripos($msg, 'Session') !== false || stripos($msg, 'OAuth') !== false) {
				return ['ok' => false, 'message' => 'Token inválido ou revogado. Reconecte a conta.', 'auth_error' => true];
			}
			return ['ok' => false, 'message' => $msg];
		}
		$out = [
			'ok' => true,
			'page_name' => (string)($res['data']['name'] ?? $pageId),
			'message' => 'Page OK',
		];
		$igId = trim((string)($cfg->meta_ig_user_id ?? ''));
		if ($igId !== '') {
			$ig = self::get($igId, ['fields' => 'id,username'], $token);
			if (!empty($ig['ok'])) {
				$out['ig_username'] = (string)($ig['data']['username'] ?? $igId);
				$out['message'] = 'Page + Instagram OK';
			} else {
				$out['message'] = 'Page OK; Instagram falhou: '.($ig['message'] ?? 'erro');
			}
		}
		return $out;
	}

	/**
	 * Publica foto na Page (URL pública).
	 * @return array{ok:bool,id?:string,message?:string,auth_error?:bool}
	 */
	public static function publicarFotoPage(string $pageId, string $pageToken, string $imageUrl, string $caption): array {
		return self::post($pageId.'/photos', [
			'url' => $imageUrl,
			'caption' => $caption,
			'published' => 'true',
		], $pageToken);
	}

	/**
	 * Publica imagem no Instagram (container + publish).
	 * @return array{ok:bool,id?:string,message?:string,auth_error?:bool}
	 */
	public static function publicarImagemInstagram(string $igUserId, string $pageToken, string $imageUrl, string $caption): array {
		$container = self::post($igUserId.'/media', [
			'image_url' => $imageUrl,
			'caption' => $caption,
		], $pageToken);
		if (empty($container['ok'])) {
			return $container;
		}
		$creationId = (string)($container['data']['id'] ?? '');
		if ($creationId === '') {
			return ['ok' => false, 'message' => 'Meta não retornou creation_id do container IG.'];
		}
		// Aguarda container pronto (até ~30s)
		for ($i = 0; $i < 10; $i++) {
			$st = self::get($creationId, ['fields' => 'status_code'], $pageToken);
			$code = (string)($st['data']['status_code'] ?? '');
			if ($code === 'FINISHED' || $code === 'PUBLISHED') {
				break;
			}
			if ($code === 'ERROR') {
				return ['ok' => false, 'message' => 'Container IG com erro no processamento.'];
			}
			usleep(1500000);
		}
		$pub = self::post($igUserId.'/media_publish', [
			'creation_id' => $creationId,
		], $pageToken);
		if (empty($pub['ok'])) {
			return $pub;
		}
		return [
			'ok' => true,
			'id' => (string)($pub['data']['id'] ?? $creationId),
		];
	}

	/**
	 * @return array{ok:bool,data?:array,message?:string,auth_error?:bool,id?:string}
	 */
	public static function get(string $path, array $query, ?string $accessToken): array {
		if ($accessToken) {
			$query['access_token'] = $accessToken;
		}
		$url = 'https://graph.facebook.com/'.self::graphVersion().'/'.ltrim($path, '?');
		$url .= (strpos($url, '?') === false ? '?' : '&').http_build_query($query);
		return self::request('GET', $url, null);
	}

	/**
	 * @return array{ok:bool,data?:array,message?:string,auth_error?:bool,id?:string}
	 */
	public static function post(string $path, array $fields, string $accessToken): array {
		$fields['access_token'] = $accessToken;
		$url = 'https://graph.facebook.com/'.self::graphVersion().'/'.ltrim($path, '/');
		return self::request('POST', $url, $fields);
	}

	/**
	 * @return array{ok:bool,data?:array,message?:string,auth_error?:bool,id?:string}
	 */
	private static function request(string $method, string $url, ?array $postFields): array {
		$ch = curl_init($url);
		curl_setopt_array($ch, [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT => 60,
			CURLOPT_CUSTOMREQUEST => $method,
		]);
		if ($method === 'POST' && $postFields !== null) {
			curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
		}
		$raw = curl_exec($ch);
		$errno = curl_errno($ch);
		$err = curl_error($ch);
		$http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		if ($errno) {
			return ['ok' => false, 'message' => 'cURL: '.$err];
		}
		$data = json_decode((string)$raw, true);
		if (!is_array($data)) {
			return ['ok' => false, 'message' => 'Resposta inválida da Meta (HTTP '.$http.').'];
		}
		if (isset($data['error'])) {
			$code = (int)($data['error']['code'] ?? 0);
			$msg = (string)($data['error']['message'] ?? 'Erro Meta');
			$auth = in_array($code, [190, 102, 463, 467], true)
				|| stripos($msg, 'Session has expired') !== false
				|| stripos($msg, 'Error validating access token') !== false;
			return [
				'ok' => false,
				'message' => $msg.($code ? ' (#'.$code.')' : ''),
				'auth_error' => $auth,
			];
		}
		$id = isset($data['id']) ? (string)$data['id'] : (isset($data['post_id']) ? (string)$data['post_id'] : null);
		$out = ['ok' => true, 'data' => $data];
		if ($id) {
			$out['id'] = $id;
		}
		if (isset($data['access_token'])) {
			$out['access_token'] = (string)$data['access_token'];
			$out['expires_in'] = (int)($data['expires_in'] ?? 0);
		}
		return $out;
	}
}

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

	/** Escopos publicação + automações (comentários → DM). Requer App Review para Live. */
	public static function oauthScopes(): string {
		return implode(',', [
			'pages_show_list',
			'pages_manage_posts',
			'pages_read_engagement',
			'pages_manage_metadata',
			'pages_manage_engagement',
			'pages_messaging',
			'instagram_basic',
			'instagram_content_publish',
			'instagram_manage_comments',
			'instagram_manage_messages',
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
	 * Aguarda container IG e publica.
	 * @return array{ok:bool,id?:string,message?:string,auth_error?:bool}
	 */
	public static function aguardarEPublicarIg(string $igUserId, string $pageToken, string $creationId, int $tentativas = 40): array {
		if ($creationId === '') {
			return ['ok' => false, 'message' => 'Meta não retornou creation_id do container IG.'];
		}
		for ($i = 0; $i < $tentativas; $i++) {
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
	 * Publica imagem no feed do Instagram (container + publish).
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
		return self::aguardarEPublicarIg($igUserId, $pageToken, (string)($container['data']['id'] ?? ''));
	}

	/**
	 * Story IG (imagem ou vídeo). Caption não se aplica a Stories na API.
	 * @return array{ok:bool,id?:string,message?:string,auth_error?:bool}
	 */
	public static function publicarStoryInstagram(
		string $igUserId,
		string $pageToken,
		string $mediaUrl,
		string $tipo = 'image'
	): array {
		$fields = ['media_type' => 'STORIES'];
		if ($tipo === 'video') {
			$fields['video_url'] = $mediaUrl;
		} else {
			$fields['image_url'] = $mediaUrl;
		}
		$container = self::post($igUserId.'/media', $fields, $pageToken);
		if (empty($container['ok'])) {
			return $container;
		}
		return self::aguardarEPublicarIg(
			$igUserId,
			$pageToken,
			(string)($container['data']['id'] ?? ''),
			$tipo === 'video' ? 60 : 40
		);
	}

	/**
	 * Reel IG (vídeo).
	 * @return array{ok:bool,id?:string,message?:string,auth_error?:bool}
	 */
	public static function publicarReelInstagram(
		string $igUserId,
		string $pageToken,
		string $videoUrl,
		string $caption = ''
	): array {
		$fields = [
			'media_type' => 'REELS',
			'video_url' => $videoUrl,
			'share_to_feed' => 'true',
		];
		if ($caption !== '') {
			$fields['caption'] = $caption;
		}
		$container = self::post($igUserId.'/media', $fields, $pageToken);
		if (empty($container['ok'])) {
			return $container;
		}
		return self::aguardarEPublicarIg($igUserId, $pageToken, (string)($container['data']['id'] ?? ''), 60);
	}

	/**
	 * Carrossel IG (2–10 itens image/video).
	 * @param array<int,array{url:string,tipo:string}> $itens
	 * @return array{ok:bool,id?:string,message?:string,auth_error?:bool}
	 */
	public static function publicarCarouselInstagram(
		string $igUserId,
		string $pageToken,
		array $itens,
		string $caption = ''
	): array {
		$children = [];
		foreach ($itens as $item) {
			$url = trim((string)($item['url'] ?? ''));
			$tipo = (($item['tipo'] ?? 'image') === 'video') ? 'video' : 'image';
			if ($url === '') {
				continue;
			}
			$fields = ['is_carousel_item' => 'true'];
			if ($tipo === 'video') {
				$fields['media_type'] = 'VIDEO';
				$fields['video_url'] = $url;
			} else {
				$fields['image_url'] = $url;
			}
			$c = self::post($igUserId.'/media', $fields, $pageToken);
			if (empty($c['ok'])) {
				return $c;
			}
			$cid = (string)($c['data']['id'] ?? '');
			if ($cid === '') {
				return ['ok' => false, 'message' => 'Falha ao criar item do carrossel IG.'];
			}
			// Vídeo no carrossel precisa processar antes do parent
			if ($tipo === 'video') {
				for ($i = 0; $i < 40; $i++) {
					$st = self::get($cid, ['fields' => 'status_code'], $pageToken);
					$code = (string)($st['data']['status_code'] ?? '');
					if ($code === 'FINISHED' || $code === 'PUBLISHED') {
						break;
					}
					if ($code === 'ERROR') {
						return ['ok' => false, 'message' => 'Item de vídeo do carrossel falhou.'];
					}
					usleep(1500000);
				}
			}
			$children[] = $cid;
		}
		if (count($children) < 2) {
			return ['ok' => false, 'message' => 'Carrossel exige pelo menos 2 mídias válidas.'];
		}
		$parentFields = [
			'media_type' => 'CAROUSEL',
			'children' => implode(',', $children),
		];
		if ($caption !== '') {
			$parentFields['caption'] = $caption;
		}
		$parent = self::post($igUserId.'/media', $parentFields, $pageToken);
		if (empty($parent['ok'])) {
			return $parent;
		}
		return self::aguardarEPublicarIg($igUserId, $pageToken, (string)($parent['data']['id'] ?? ''), 40);
	}

	/**
	 * Assina a Page nos campos de webhook (feed/comments via Page + messaging).
	 * @return array{ok:bool,message?:string}
	 */
	public static function subscribePageApps(string $pageId, string $pageToken): array {
		$fields = 'feed,messages,message_deliveries,message_reads';
		$r = self::post($pageId.'/subscribed_apps', [
			'subscribed_fields' => $fields,
		], $pageToken);
		if (empty($r['ok'])) {
			return ['ok' => false, 'message' => $r['message'] ?? 'Falha ao assinar webhooks da Page.'];
		}
		return ['ok' => true, 'message' => 'Page inscrita nos webhooks.'];
	}

	/**
	 * Private reply Instagram (comentário → DM) via Page messages API.
	 * @return array{ok:bool,id?:string,message?:string,auth_error?:bool}
	 */
	public static function privateReplyInstagram(string $pageId, string $pageToken, string $commentId, string $text): array {
		return self::postJson($pageId.'/messages', [
			'recipient' => ['comment_id' => $commentId],
			'message' => ['text' => $text],
		], $pageToken);
	}

	/**
	 * Private reply Facebook Page (comentário → Messenger).
	 * @return array{ok:bool,id?:string,message?:string,auth_error?:bool}
	 */
	public static function privateReplyFacebook(string $commentId, string $pageToken, string $text): array {
		return self::post($commentId.'/private_replies', [
			'message' => $text,
		], $pageToken);
	}

	/**
	 * POST JSON (necessário para Messenger / IG private replies).
	 * @return array{ok:bool,data?:array,message?:string,auth_error?:bool,id?:string}
	 */
	public static function postJson(string $path, array $body, string $accessToken): array {
		$url = 'https://graph.facebook.com/'.self::graphVersion().'/'.ltrim($path, '/');
		$url .= (strpos($url, '?') === false ? '?' : '&').'access_token='.rawurlencode($accessToken);
		$ch = curl_init($url);
		$json = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		curl_setopt_array($ch, [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT => 60,
			CURLOPT_CUSTOMREQUEST => 'POST',
			CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
			CURLOPT_POSTFIELDS => $json,
		]);
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
		$id = isset($data['message_id']) ? (string)$data['message_id']
			: (isset($data['id']) ? (string)$data['id'] : null);
		$out = ['ok' => true, 'data' => $data];
		if ($id) {
			$out['id'] = $id;
		}
		return $out;
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

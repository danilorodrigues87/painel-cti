<?php

namespace App\Common\Helpers;

use App\Model\Entity\EscolaIntegracoes;

/**
 * Integração Bunny Stream por escola (keys em escola_integracoes).
 */
class BunnyStreamHelper {

	private const API = 'https://video.bunnycdn.com';

	public static function config(int $idAdmin): ?EscolaIntegracoes {
		if (!EscolaIntegracoes::temColunasBunny()) {
			return null;
		}
		$cfg = EscolaIntegracoes::getByIdAdmin($idAdmin);
		return $cfg instanceof EscolaIntegracoes ? $cfg : null;
	}

	public static function pronto(int $idAdmin): bool {
		$cfg = self::config($idAdmin);
		return $cfg !== null && $cfg->temBunnyAtivo();
	}

	/**
	 * @return array{ok:bool,videoId?:string,message?:string}
	 */
	public static function criarVideo(int $idAdmin, string $titulo): array {
		$cfg = self::config($idAdmin);
		if (!$cfg || !$cfg->temBunnyAtivo()) {
			return ['ok' => false, 'message' => 'Bunny não configurado nesta escola.'];
		}
		$libraryId = trim((string)$cfg->bunny_library_id);
		$key = $cfg->getBunnyApiKeyDescriptografada();
		if ($libraryId === '' || !$key) {
			return ['ok' => false, 'message' => 'Library ID ou AccessKey ausentes.'];
		}
		$titulo = trim($titulo) !== '' ? trim($titulo) : 'Aula EAD';
		$res = self::request(
			'POST',
			self::API.'/library/'.rawurlencode($libraryId).'/videos',
			$key,
			['title' => $titulo]
		);
		if (!$res['ok']) {
			return ['ok' => false, 'message' => $res['message'] ?? 'Falha ao criar vídeo no Bunny.'];
		}
		$guid = (string)($res['data']['guid'] ?? '');
		if ($guid === '') {
			return ['ok' => false, 'message' => 'Bunny não retornou o GUID do vídeo.'];
		}
		return ['ok' => true, 'videoId' => $guid];
	}

	/**
	 * Assinatura para upload TUS/PUT (AuthorizationSignature).
	 * @return array{ok:bool,libraryId?:string,videoId?:string,expires?:int,signature?:string,message?:string}
	 */
	public static function assinaturaUpload(int $idAdmin, string $videoId): array {
		$cfg = self::config($idAdmin);
		if (!$cfg || !$cfg->temBunnyAtivo()) {
			return ['ok' => false, 'message' => 'Bunny não configurado.'];
		}
		$libraryId = trim((string)$cfg->bunny_library_id);
		$key = $cfg->getBunnyApiKeyDescriptografada();
		$videoId = trim($videoId);
		if ($libraryId === '' || !$key || $videoId === '') {
			return ['ok' => false, 'message' => 'Dados Bunny incompletos.'];
		}
		$expires = time() + 3600;
		$signature = hash('sha256', $libraryId.$key.$expires.$videoId);
		return [
			'ok' => true,
			'libraryId' => $libraryId,
			'videoId' => $videoId,
			'expires' => $expires,
			'signature' => $signature,
			'uploadUrl' => self::API.'/tusupload',
			'putUrl' => self::API.'/library/'.rawurlencode($libraryId).'/videos/'.rawurlencode($videoId),
			'accessKey' => $key,
		];
	}

	/**
	 * @return array{ok:bool,status?:string,length?:int,encodeProgress?:int,message?:string}
	 */
	public static function statusVideo(int $idAdmin, string $videoId): array {
		$cfg = self::config($idAdmin);
		if (!$cfg || !$cfg->temBunnyAtivo()) {
			return ['ok' => false, 'message' => 'Bunny não configurado.'];
		}
		$libraryId = trim((string)$cfg->bunny_library_id);
		$key = $cfg->getBunnyApiKeyDescriptografada();
		$videoId = trim($videoId);
		if ($libraryId === '' || !$key || $videoId === '') {
			return ['ok' => false, 'message' => 'Dados Bunny incompletos.'];
		}
		$res = self::request(
			'GET',
			self::API.'/library/'.rawurlencode($libraryId).'/videos/'.rawurlencode($videoId),
			$key
		);
		if (!$res['ok']) {
			return ['ok' => false, 'message' => $res['message'] ?? 'Falha ao consultar status.'];
		}
		$data = $res['data'] ?? [];
		// status: 0=created,1=uploaded,2=processing,3=transcoding,4=finished,5=error,6=uploadFailed
		$code = (int)($data['status'] ?? 0);
		$map = [
			0 => 'uploading',
			1 => 'processing',
			2 => 'processing',
			3 => 'processing',
			4 => 'ready',
			5 => 'error',
			6 => 'error',
		];
		$length = (int)($data['length'] ?? 0); // seconds
		return [
			'ok' => true,
			'status' => $map[$code] ?? 'processing',
			'bunnyCode' => $code,
			'length' => $length,
			'durationMinutes' => (int)max(1, (int)ceil($length / 60)),
			'encodeProgress' => (int)($data['encodeProgress'] ?? 0),
			'title' => (string)($data['title'] ?? ''),
		];
	}

	/**
	 * @return array{ok:bool,message?:string}
	 */
	public static function excluirVideo(int $idAdmin, string $videoId): array {
		$cfg = self::config($idAdmin);
		if (!$cfg || !$cfg->temBunnyAtivo()) {
			return ['ok' => true]; // nada a fazer no Bunny
		}
		$libraryId = trim((string)$cfg->bunny_library_id);
		$key = $cfg->getBunnyApiKeyDescriptografada();
		$videoId = trim($videoId);
		if ($libraryId === '' || !$key || $videoId === '') {
			return ['ok' => true];
		}
		$res = self::request(
			'DELETE',
			self::API.'/library/'.rawurlencode($libraryId).'/videos/'.rawurlencode($videoId),
			$key
		);
		if (!$res['ok'] && ($res['http'] ?? 0) !== 404) {
			return ['ok' => false, 'message' => $res['message'] ?? 'Falha ao excluir no Bunny.'];
		}
		return ['ok' => true];
	}

	/**
	 * URL HLS assinada para o player Ascend.
	 * Usa Token Authentication do Bunny Stream (Security da Library):
	 * SHA256_HEX(token_security_key + video_id + expires) — NÃO o formato Advanced do Pull Zone.
	 *
	 * @return array{ok:bool,playbackUrl?:string,expiresAt?:int,libraryId?:string,videoId?:string,message?:string}
	 */
	public static function urlPlayback(int $idAdmin, string $videoId, int $ttlSec = 7200): array {
		$cfg = self::config($idAdmin);
		if (!$cfg) {
			return ['ok' => false, 'message' => 'Integração Bunny indisponível (rode o SQL de colunas Bunny).'];
		}
		if ((int)($cfg->bunny_ativo ?? 0) !== 1) {
			return ['ok' => false, 'message' => 'Bunny Stream desativado na escola dona do curso.'];
		}

		$host = trim((string)($cfg->bunny_cdn_hostname ?? ''));
		$host = preg_replace('#^https?://#i', '', $host);
		$host = rtrim((string)$host, '/');
		$libraryId = trim((string)($cfg->bunny_library_id ?? ''));
		$videoId = trim($videoId);
		$tokenKey = $cfg->getBunnyTokenKeyDescriptografada();

		if ($host === '') {
			return ['ok' => false, 'message' => 'CDN Hostname Bunny ausente na escola dona do curso.'];
		}
		if ($videoId === '') {
			return ['ok' => false, 'message' => 'Video ID Bunny ausente.'];
		}
		// Blob criptografado existe mas não abre (APP_KEY diferente / chave corrompida)
		if (trim((string)($cfg->bunny_token_key ?? '')) !== '' && !$tokenKey) {
			return [
				'ok' => false,
				'message' => 'Token Key Bunny não pôde ser lido — cole de novo em Configurações → Bunny Stream e salve.',
			];
		}

		$expires = time() + max(300, min(86400, $ttlSec));
		$plainUrl = 'https://'.$host.'/'.$videoId.'/playlist.m3u8';

		if (!$tokenKey) {
			// Token Auth desligado na library OU chave ainda não salva
			return [
				'ok' => true,
				'playbackUrl' => $plainUrl,
				'expiresAt' => $expires,
				'libraryId' => $libraryId,
				'videoId' => $videoId,
			];
		}

		// Docs Stream: SHA256_HEX(security_key + video_id + expiration)
		$token = hash('sha256', $tokenKey.$videoId.(string)$expires);
		$playbackUrl = $plainUrl.'?token='.$token.'&expires='.$expires;

		return [
			'ok' => true,
			'playbackUrl' => $playbackUrl,
			'expiresAt' => $expires,
			'libraryId' => $libraryId,
			'videoId' => $videoId,
		];
	}

	/**
	 * Atualiza bunny_status no banco consultando a API (útil se o poll do editor parou cedo).
	 */
	public static function sincronizarStatusVideo(\App\Model\Entity\LmsVideo $v, int $idAdmin): string {
		if (($v->provider ?? '') !== 'bunny' || empty($v->bunny_video_id)) {
			return (string)($v->bunny_status ?? '');
		}
		$st = self::statusVideo($idAdmin, (string)$v->bunny_video_id);
		if (empty($st['ok'])) {
			return (string)($v->bunny_status ?? 'error');
		}
		$v->bunny_status = (string)$st['status'];
		if (($st['status'] ?? '') === 'ready' && !empty($st['durationMinutes'])) {
			$v->duracao_min = (int)$st['durationMinutes'];
		}
		if (($st['status'] ?? '') === 'error') {
			$v->bunny_error = 'Falha no encode Bunny.';
		}
		$v->salvar();
		return (string)$v->bunny_status;
	}

	/**
	 * Testa AccessKey listando a library.
	 * @return array{ok:bool,message?:string,name?:string}
	 */
	public static function testar(int $idAdmin): array {
		$cfg = self::config($idAdmin);
		if (!$cfg) {
			return ['ok' => false, 'message' => 'Execute database/escola_integracoes_bunny.sql'];
		}
		$libraryId = trim((string)$cfg->bunny_library_id);
		$key = $cfg->getBunnyApiKeyDescriptografada();
		if ($libraryId === '' || !$key) {
			return ['ok' => false, 'message' => 'Informe Library ID e AccessKey.'];
		}
		$res = self::request(
			'GET',
			self::API.'/library/'.rawurlencode($libraryId),
			$key
		);
		if (!$res['ok']) {
			return ['ok' => false, 'message' => $res['message'] ?? 'Falha na conexão.'];
		}
		return [
			'ok' => true,
			'message' => 'Conexão OK.',
			'name' => (string)($res['data']['Name'] ?? $res['data']['name'] ?? 'Library'),
		];
	}

	/**
	 * @return array{ok:bool,http?:int,data?:array,message?:string}
	 */
	private static function request(string $method, string $url, string $accessKey, ?array $jsonBody = null): array {
		$ch = curl_init($url);
		$headers = [
			'Accept: application/json',
			'AccessKey: '.$accessKey,
		];
		$opts = [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CUSTOMREQUEST => $method,
			CURLOPT_TIMEOUT => 60,
			CURLOPT_HTTPHEADER => $headers,
		];
		if ($jsonBody !== null) {
			$body = json_encode($jsonBody, JSON_UNESCAPED_UNICODE);
			$headers[] = 'Content-Type: application/json';
			$opts[CURLOPT_HTTPHEADER] = $headers;
			$opts[CURLOPT_POSTFIELDS] = $body;
		}
		curl_setopt_array($ch, $opts);
		$raw = curl_exec($ch);
		$errno = curl_errno($ch);
		$err = curl_error($ch);
		$http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		if ($errno) {
			return ['ok' => false, 'http' => 0, 'message' => 'cURL: '.$err];
		}
		$data = null;
		if (is_string($raw) && $raw !== '') {
			$decoded = json_decode($raw, true);
			$data = is_array($decoded) ? $decoded : null;
		}
		if ($http < 200 || $http >= 300) {
			$msg = is_array($data)
				? (string)($data['Message'] ?? $data['message'] ?? $data['title'] ?? ('HTTP '.$http))
				: ('HTTP '.$http);
			return ['ok' => false, 'http' => $http, 'data' => $data ?: [], 'message' => $msg];
		}
		return ['ok' => true, 'http' => $http, 'data' => $data ?: []];
	}
}

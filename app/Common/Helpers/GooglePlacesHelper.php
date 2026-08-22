<?php

namespace App\Common\Helpers;

use App\Common\Environment;

class GooglePlacesHelper {

	private const API_URL = 'https://places.googleapis.com/v1/places:searchText';
	private const RATE_WINDOW = 3600;
	private const RATE_MAX = 30;

	public static function apiKey(): string {
		Environment::load(__DIR__.'/../../../');
		return trim((string)(Environment::get('GOOGLE_PLACES_API_KEY') ?: ''));
	}

	public static function configurado(): bool {
		return self::apiKey() !== '';
	}

	/**
	 * @return array{success:bool,message?:string,items?:array,nextPageToken?:string|null}
	 */
	public static function searchText(string $query, ?string $pageToken = null): array {
		$key = self::apiKey();
		if ($key === '') {
			return ['success' => false, 'message' => 'Configure GOOGLE_PLACES_API_KEY no .env do painel.'];
		}
		$query = trim($query);
		if (mb_strlen($query) < 3) {
			return ['success' => false, 'message' => 'Informe pelo menos 3 caracteres na busca.'];
		}
		if (!self::permitirRequisicao()) {
			return ['success' => false, 'message' => 'Limite de buscas Google atingido (30/hora). Tente mais tarde.'];
		}

		$body = [
			'textQuery'      => $query,
			'languageCode'   => 'pt-BR',
			'regionCode'     => 'BR',
			'pageSize'       => 20,
		];
		if ($pageToken !== null && trim($pageToken) !== '') {
			$body['pageToken'] = trim($pageToken);
		}

		$fieldMask = implode(',', [
			'places.id',
			'places.displayName',
			'places.formattedAddress',
			'places.nationalPhoneNumber',
			'places.internationalPhoneNumber',
			'places.googleMapsUri',
			'places.websiteUri',
			'places.rating',
			'nextPageToken',
		]);

		$response = self::httpPost(self::API_URL, $body, [
			'Content-Type: application/json',
			'X-Goog-Api-Key: '.$key,
			'X-Goog-FieldMask: '.$fieldMask,
		]);

		if (!$response['ok']) {
			return ['success' => false, 'message' => $response['message'] ?? 'Falha ao consultar Google Places.'];
		}

		$data = $response['json'];
		if (!is_array($data)) {
			return ['success' => false, 'message' => 'Resposta inválida da API Google.'];
		}

		if (!empty($data['error']['message'])) {
			return ['success' => false, 'message' => (string)$data['error']['message']];
		}

		$items = [];
		foreach (($data['places'] ?? []) as $place) {
			if (!is_array($place)) {
				continue;
			}
			$normalized = self::normalizarPlace($place);
			if ($normalized !== null) {
				$items[] = $normalized;
			}
		}

		return [
			'success'        => true,
			'items'          => $items,
			'nextPageToken'  => isset($data['nextPageToken']) ? (string)$data['nextPageToken'] : null,
		];
	}

	/** @param array<string,mixed> $place */
	private static function normalizarPlace(array $place): ?array {
		$rawId = (string)($place['id'] ?? '');
		$placeId = str_starts_with($rawId, 'places/') ? substr($rawId, 7) : $rawId;
		if ($placeId === '') {
			return null;
		}

		$nome = '';
		if (isset($place['displayName']) && is_array($place['displayName'])) {
			$nome = trim((string)($place['displayName']['text'] ?? ''));
		}
		if ($nome === '') {
			$nome = 'Estabelecimento';
		}

		$telefone = trim((string)($place['nationalPhoneNumber'] ?? ''));
		if ($telefone === '') {
			$telefone = trim((string)($place['internationalPhoneNumber'] ?? ''));
		}
		$digits = preg_replace('/\D+/', '', $telefone) ?: '';
		if ($digits !== '' && strlen($digits) >= 10 && strlen($digits) <= 11) {
			$digits = '55'.$digits;
		}

		$nota = isset($place['rating']) ? (float)$place['rating'] : null;

		return [
			'placeId'         => $placeId,
			'nome'            => $nome,
			'endereco'        => trim((string)($place['formattedAddress'] ?? '')),
			'telefone'        => $telefone,
			'whatsappDigits'  => $digits,
			'whatsappUrl'     => $digits !== '' ? 'https://wa.me/'.$digits : '',
			'mapsUrl'         => trim((string)($place['googleMapsUri'] ?? '')),
			'site'            => trim((string)($place['websiteUri'] ?? '')),
			'nota'            => $nota,
		];
	}

	/** @param array<string,mixed> $body */
	private static function httpPost(string $url, array $body, array $headers): array {
		$ch = curl_init($url);
		if ($ch === false) {
			return ['ok' => false, 'message' => 'cURL indisponível.'];
		}
		curl_setopt_array($ch, [
			CURLOPT_POST           => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT        => 30,
			CURLOPT_HTTPHEADER     => $headers,
			CURLOPT_POSTFIELDS     => json_encode($body, JSON_UNESCAPED_UNICODE),
		]);
		$raw = curl_exec($ch);
		$code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$err = curl_error($ch);
		curl_close($ch);

		if ($raw === false) {
			return ['ok' => false, 'message' => $err !== '' ? $err : 'Erro de rede.'];
		}

		$json = json_decode((string)$raw, true);
		if ($code >= 400) {
			$msg = is_array($json) && !empty($json['error']['message'])
				? (string)$json['error']['message']
				: 'HTTP '.$code;
			return ['ok' => false, 'message' => $msg, 'json' => $json];
		}

		return ['ok' => true, 'json' => $json];
	}

	private static function permitirRequisicao(): bool {
		$ip = (string)($_SERVER['REMOTE_ADDR'] ?? '0');
		$dir = sys_get_temp_dir().'/prospeccao_places_rate';
		if (!is_dir($dir)) {
			@mkdir($dir, 0755, true);
		}
		$file = $dir.'/'.md5($ip).'.json';
		$now = time();
		$data = ['times' => []];
		if (is_file($file)) {
			$decoded = json_decode((string)file_get_contents($file), true);
			if (is_array($decoded) && isset($decoded['times']) && is_array($decoded['times'])) {
				$data = $decoded;
			}
		}
		$data['times'] = array_values(array_filter(
			$data['times'],
			static fn($t) => is_int($t) && $t > ($now - self::RATE_WINDOW)
		));
		if (count($data['times']) >= self::RATE_MAX) {
			return false;
		}
		$data['times'][] = $now;
		@file_put_contents($file, json_encode($data));
		return true;
	}
}

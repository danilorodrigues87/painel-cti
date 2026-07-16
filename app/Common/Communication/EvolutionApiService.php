<?php

namespace App\Common\Communication;

use App\Common\Environment;

/**
 * Cliente HTTP para Evolution API (WhatsApp).
 * Credenciais globais no .env; cada escola usa uma instância própria.
 */
class EvolutionApiService {

	private $baseUrl;
	private $apiKey;
	private $lastError = null;
	private $lastHttpCode = 0;

	public function __construct(?string $baseUrl = null, ?string $apiKey = null) {
		$this->baseUrl = rtrim($baseUrl ?? (string)Environment::get('EVOLUTION_URL', ''), '/');
		$this->apiKey = $apiKey ?? (string)Environment::get('EVOLUTION_API_KEY', '');
	}

	public static function fromEnv(): self {
		return new self();
	}

	public function isConfigured(): bool {
		return $this->baseUrl !== '' && $this->apiKey !== '';
	}

	public function getBaseUrl(): string {
		return $this->baseUrl;
	}

	public function getLastError(): ?string {
		return $this->lastError;
	}

	public function getLastHttpCode(): int {
		return $this->lastHttpCode;
	}

	public static function nomeInstancia(int $idAdmin): string {
		return 'escola_'.(int)$idAdmin;
	}

	public static function webhookToken(int $idAdmin): string {
		$secret = (string)(Environment::get('EVOLUTION_WEBHOOK_SECRET')
			?: Environment::get('SYSTEM_TOKEN')
			?: 'painel-cti');
		return hash_hmac('sha256', 'evolution-webhook-'.(int)$idAdmin, $secret);
	}

	public static function webhookUrl(int $idAdmin): string {
		$token = self::webhookToken($idAdmin);
		return rtrim((string)URL, '/').'/webhook/evolution/'.(int)$idAdmin.'/'.$token;
	}

	/** Normaliza telefone BR para dígitos (com DDI 55 quando possível). */
	public static function normalizarTelefone(string $telefone): string {
		$digitos = preg_replace('/\D+/', '', $telefone) ?? '';
		if ($digitos === '') {
			return '';
		}
		if (strpos($digitos, '55') === 0 && strlen($digitos) >= 12) {
			return $digitos;
		}
		if (strlen($digitos) === 10 || strlen($digitos) === 11) {
			return '55'.$digitos;
		}
		return $digitos;
	}

	public function fetchInstances(): ?array {
		return $this->request('GET', '/instance/fetchInstances');
	}

	public function connectionState(string $instance): ?array {
		return $this->request('GET', '/instance/connectionState/'.rawurlencode($instance));
	}

	public function connect(string $instance): ?array {
		return $this->request('GET', '/instance/connect/'.rawurlencode($instance));
	}

	public function createInstance(string $instanceName, ?string $webhookUrl = null): ?array {
		$body = [
			'instanceName' => $instanceName,
			'qrcode'       => true,
			'integration'  => 'WHATSAPP-BAILEYS',
		];

		if ($webhookUrl) {
			$body['webhook'] = [
				'enabled' => true,
				'url'     => $webhookUrl,
				'byEvents'=> false,
				'base64'  => false,
				'events'  => [
					'MESSAGES_UPSERT',
					'CONNECTION_UPDATE',
					'QRCODE_UPDATED',
					'SEND_MESSAGE',
				],
			];
		}

		return $this->request('POST', '/instance/create', $body);
	}

	public function setWebhook(string $instance, string $webhookUrl): ?array {
		$body = [
			'webhook' => [
				'enabled' => true,
				'url'     => $webhookUrl,
				'byEvents'=> false,
				'base64'  => false,
				'events'  => [
					'MESSAGES_UPSERT',
					'CONNECTION_UPDATE',
					'QRCODE_UPDATED',
					'SEND_MESSAGE',
				],
			],
		];
		return $this->request('POST', '/webhook/set/'.rawurlencode($instance), $body);
	}

	public function sendText(string $instance, string $number, string $text): ?array {
		$number = self::normalizarTelefone($number);
		if ($number === '' || trim($text) === '') {
			$this->lastError = 'Número ou mensagem inválidos.';
			return null;
		}

		return $this->request('POST', '/message/sendText/'.rawurlencode($instance), [
			'number' => $number,
			'text'   => $text,
		]);
	}

	public function logout(string $instance): ?array {
		return $this->request('DELETE', '/instance/logout/'.rawurlencode($instance));
	}

	public function deleteInstance(string $instance): ?array {
		return $this->request('DELETE', '/instance/delete/'.rawurlencode($instance));
	}

	/**
	 * Extrai status de conexão de respostas variadas da Evolution.
	 */
	public static function extrairEstado(?array $payload): string {
		if ($payload === null) {
			return 'unknown';
		}

		$candidatos = [
			$payload['instance']['state'] ?? null,
			$payload['instance']['status'] ?? null,
			$payload['state'] ?? null,
			$payload['status'] ?? null,
			$payload['connectionStatus'] ?? null,
		];

		foreach ($candidatos as $v) {
			if (is_string($v) && $v !== '') {
				return strtolower($v);
			}
		}

		return 'unknown';
	}

	/**
	 * Extrai imagem do QR (data URI). Não usa o campo "code" (texto do WhatsApp).
	 */
	public static function extrairQrBase64(?array $payload): ?string {
		if ($payload === null) {
			return null;
		}

		$candidatos = [];

		foreach ([
			$payload['base64'] ?? null,
			is_string($payload['qrcode'] ?? null) ? $payload['qrcode'] : null,
			$payload['qrcode']['base64'] ?? null,
			$payload['qr']['base64'] ?? null,
			$payload['data']['base64'] ?? null,
			$payload['data']['qrcode']['base64'] ?? null,
		] as $v) {
			if (is_string($v) && $v !== '') {
				$candidatos[] = $v;
			}
		}

		foreach ($candidatos as $v) {
			$img = self::normalizarImagemQr($v);
			if ($img !== null) {
				return $img;
			}
		}

		return null;
	}

	/**
	 * Texto bruto do QR (campo code da Evolution) — precisa virar imagem.
	 */
	public static function extrairQrCodeString(?array $payload): ?string {
		if ($payload === null) {
			return null;
		}

		$candidatos = [
			$payload['code'] ?? null,
			$payload['qrcode']['code'] ?? null,
			$payload['qr']['code'] ?? null,
			$payload['data']['code'] ?? null,
			$payload['data']['qrcode']['code'] ?? null,
		];

		foreach ($candidatos as $v) {
			if (!is_string($v) || $v === '') {
				continue;
			}
			// Evita confundir base64/data URI com o code do WhatsApp
			if (self::pareceImagemBase64($v)) {
				continue;
			}
			return $v;
		}

		return null;
	}

	/**
	 * Data URI da imagem, ou URL de QR gerado a partir do code.
	 */
	public static function montarQrParaExibicao(?array $payload): ?string {
		$img = self::extrairQrBase64($payload);
		if ($img !== null) {
			return $img;
		}

		$code = self::extrairQrCodeString($payload);
		if ($code === null || $code === '') {
			return null;
		}

		return 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&ecc=M&margin=10&data='
			.rawurlencode($code);
	}

	private static function normalizarImagemQr(string $v): ?string {
		$v = trim($v);
		if ($v === '') {
			return null;
		}
		if (strpos($v, 'data:image') === 0) {
			return $v;
		}
		if (!self::pareceImagemBase64($v)) {
			return null;
		}
		return 'data:image/png;base64,'.$v;
	}

	private static function pareceImagemBase64(string $v): bool {
		if (strpos($v, 'data:image') === 0) {
			return true;
		}
		// PNG/JPEG em base64 costumam começar assim; o code do WA tem "@" e é bem diferente
		if (strpos($v, '@') !== false) {
			return false;
		}
		if (strlen($v) < 64) {
			return false;
		}
		$sample = substr($v, 0, 32);
		return (bool)preg_match('#^[A-Za-z0-9+/]#', $sample)
			&& (strpos($sample, 'iVBOR') === 0 || strpos($sample, '/9j/') === 0 || strlen($v) > 200);
	}

	/**
	 * Tenta obter QR com algumas tentativas (a Evolution às vezes demora 1–2s).
	 */
	public function obterQrComRetry(string $instance, int $tentativas = 4, int $sleepMs = 800): ?array {
		$ultimo = null;
		for ($i = 0; $i < $tentativas; $i++) {
			if ($i > 0) {
				usleep($sleepMs * 1000);
			}
			$ultimo = $this->connect($instance);
			if ($ultimo === null) {
				continue;
			}
			if (self::montarQrParaExibicao($ultimo) !== null) {
				return $ultimo;
			}
			$estado = self::extrairEstado($ultimo);
			if (in_array($estado, ['open', 'connected'], true)) {
				return $ultimo;
			}
		}
		return $ultimo;
	}

	private function request(string $method, string $path, ?array $body = null): ?array {
		$this->lastError = null;
		$this->lastHttpCode = 0;

		if (!$this->isConfigured()) {
			$this->lastError = 'Evolution API não configurada no .env (EVOLUTION_URL / EVOLUTION_API_KEY).';
			return null;
		}

		$url = $this->baseUrl.$path;
		$ch = curl_init($url);
		$headers = [
			'apikey: '.$this->apiKey,
			'Content-Type: application/json',
			'Accept: application/json',
		];

		curl_setopt_array($ch, [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CUSTOMREQUEST  => strtoupper($method),
			CURLOPT_HTTPHEADER     => $headers,
			CURLOPT_TIMEOUT        => 60,
			CURLOPT_CONNECTTIMEOUT => 20,
			CURLOPT_SSL_VERIFYPEER => true,
		]);

		if ($body !== null) {
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE));
		}

		$raw = curl_exec($ch);
		$errno = curl_errno($ch);
		$error = curl_error($ch);
		$this->lastHttpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if ($errno) {
			$this->lastError = 'Falha de conexão com Evolution: '.$error;
			return null;
		}

		$decoded = json_decode((string)$raw, true);
		if (!is_array($decoded)) {
			$this->lastError = 'Resposta inválida da Evolution (HTTP '.$this->lastHttpCode.').';
			return null;
		}

		if ($this->lastHttpCode >= 400) {
			$msg = $decoded['message'] ?? $decoded['error'] ?? $decoded['response']['message'] ?? null;
			if (is_array($msg)) {
				$msg = json_encode($msg, JSON_UNESCAPED_UNICODE);
			}
			$this->lastError = is_string($msg) && $msg !== ''
				? $msg
				: ('Erro Evolution HTTP '.$this->lastHttpCode);
			return $decoded;
		}

		return $decoded;
	}
}

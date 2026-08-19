<?php

namespace App\Common\Helpers;

/**
 * Envio de push via OneSignal REST API (disparado junto com notificações in-app).
 */
class OneSignalPushService {

	public static function enviarEscola(
		int $idAdmin,
		string $tipo,
		string $titulo,
		string $mensagem,
		?string $linkPath
	): void {
		if (!OneSignalHelper::configurado() || $idAdmin <= 0) {
			return;
		}

		$titulo = trim($titulo);
		$mensagem = trim($mensagem);
		if ($titulo === '') {
			$titulo = 'CTI Painel';
		}
		if ($mensagem === '') {
			$mensagem = 'Nova atualização no painel.';
		}

		$filters = [
			['field' => 'tag', 'key' => 'id_admin', 'relation' => '=', 'value' => (string)$idAdmin],
		];
		$tagCanal = OneSignalHelper::tagCanalPorTipo($tipo);
		if ($tagCanal !== null) {
			$filters[] = ['operator' => 'AND'];
			$filters[] = ['field' => 'tag', 'key' => $tagCanal, 'relation' => '=', 'value' => '1'];
		}

		$urlAbs = null;
		if ($linkPath !== null && $linkPath !== '') {
			$path = $linkPath[0] === '/' ? $linkPath : '/'.$linkPath;
			$urlAbs = rtrim((string)URL, '/').$path;
		}

		$payload = [
			'app_id'   => OneSignalHelper::appId(),
			'filters'  => $filters,
			'headings' => ['en' => mb_substr($titulo, 0, 100), 'pt' => mb_substr($titulo, 0, 100)],
			'contents' => ['en' => mb_substr($mensagem, 0, 240), 'pt' => mb_substr($mensagem, 0, 240)],
		];
		if ($urlAbs !== null) {
			$payload['url'] = $urlAbs;
		}

		self::postNotification($payload);
	}

	/**
	 * @param array<string,mixed> $payload
	 */
	private static function postNotification(array $payload): void {
		$key = OneSignalHelper::restApiKey();
		if ($key === '') {
			return;
		}

		$ch = curl_init('https://api.onesignal.com/notifications');
		curl_setopt_array($ch, [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_POST           => true,
			CURLOPT_HTTPHEADER     => [
				'Content-Type: application/json; charset=utf-8',
				'Authorization: Key '.$key,
			],
			CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
			CURLOPT_TIMEOUT        => 8,
			CURLOPT_CONNECTTIMEOUT => 5,
		]);
		curl_exec($ch);
		curl_close($ch);
	}
}

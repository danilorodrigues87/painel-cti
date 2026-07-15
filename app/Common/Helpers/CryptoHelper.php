<?php

namespace App\Common\Helpers;

use App\Common\Environment;

class CryptoHelper {

	private static function key(): string {
		$key = Environment::get('APP_KEY');
		if (empty($key)) {
			$key = Environment::get('SYSTEM_TOKEN', 'painel-cti-fallback-key');
		}
		return hash('sha256', (string)$key, true);
	}

	public static function encrypt(?string $plain): ?string {
		if ($plain === null || $plain === '') {
			return null;
		}

		$iv = random_bytes(16);
		$cipher = openssl_encrypt($plain, 'AES-256-CBC', self::key(), OPENSSL_RAW_DATA, $iv);
		if ($cipher === false) {
			return null;
		}

		return base64_encode($iv.$cipher);
	}

	public static function decrypt(?string $encoded): ?string {
		if ($encoded === null || $encoded === '') {
			return null;
		}

		$raw = base64_decode($encoded, true);
		if ($raw === false || strlen($raw) < 17) {
			return null;
		}

		$iv = substr($raw, 0, 16);
		$cipher = substr($raw, 16);
		$plain = openssl_decrypt($cipher, 'AES-256-CBC', self::key(), OPENSSL_RAW_DATA, $iv);

		return $plain === false ? null : $plain;
	}
}

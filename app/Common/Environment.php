<?php

namespace App\Common;

class Environment {
	/**
	 * Carrega variáveis de ambiente
	 */
	public static function load($dir) {
		// Verifica se o arquivo .env existe
		if(!file_exists($dir.'/.env')) {
			return false;
		}

		// Define as variáveis de ambiente
		$lines = file($dir.'/.env');
		foreach($lines as $line) {
			$line = trim($line);
			
			// Ignora linhas vazias ou comentários
			if(empty($line) || strpos($line, '#') === 0) {
				continue;
			}

			// Separa chave e valor
			if(strpos($line, '=') !== false) {
				list($key, $value) = explode('=', $line, 2);
				putenv(trim($key).'='.trim($value));
			}
		}
	}

	/**
	 * Obtém uma variável de ambiente com fallback
	 */
	public static function get($key, $default = null) {
		$value = getenv($key);
		return $value !== false ? $value : $default;
	}
}
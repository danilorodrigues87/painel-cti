<?php

namespace App\Common\Helpers;

/**
 * Web App Manifest dinâmico (start_url/scope conforme deploy /app ou raiz).
 */
class PwaHelper {

	public static function manifestArray(): array {
		$base = rtrim((string)URL, '/');
		$icon = $base.'/resources/assets/img/icons/icone.png';

		return [
			'name'             => 'CTI Educacional — Painel',
			'short_name'       => 'CTI Painel',
			'description'      => 'Painel administrativo CTI Educacional — atendimento, gestão escolar e redes sociais.',
			'start_url'        => $base.'/painel',
			'scope'            => $base.'/',
			'id'               => $base.'/painel',
			'display'          => 'standalone',
			'orientation'      => 'any',
			'background_color' => '#212529',
			'theme_color'      => '#212529',
			'lang'             => 'pt-BR',
			'categories'       => ['business', 'productivity'],
			'icons'            => [
				[
					'src'   => $icon,
					'sizes' => '192x192',
					'type'  => 'image/png',
					'purpose' => 'any',
				],
				[
					'src'   => $icon,
					'sizes' => '512x512',
					'type'  => 'image/png',
					'purpose' => 'any',
				],
				[
					'src'   => $icon,
					'sizes' => '512x512',
					'type'  => 'image/png',
					'purpose' => 'maskable',
				],
			],
		];
	}

	public static function manifestJson(): string {
		$json = json_encode(self::manifestArray(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
		return $json !== false ? $json : '{}';
	}

	public static function serviceWorkerAllowedHeader(): string {
		$base = rtrim((string)URL, '/');
		return $base.'/';
	}
}

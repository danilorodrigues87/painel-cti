<?php

namespace App\Controller\Admin;

/**
 * Legado: Assistente IA unificado em Configurações de IA.
 */
class ConfigAssistente extends Page {

	public static function index($request) {
		$request->getRouter()->redirect('/painel/config/ia');
		return '';
	}

	public static function getInfo($request) {
		header('Content-Type: application/json; charset=utf-8');
		return json_encode([
			'success' => false,
			'message' => 'Use Configurações de IA em /painel/config/ia.',
			'redirect' => '/painel/config/ia',
		], JSON_UNESCAPED_UNICODE);
	}
}

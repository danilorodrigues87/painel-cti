<?php

use \App\Http\Response;
use \App\Controller\Admin;

$obRouter->get('/painel/config/escola', [
	'middlewares' => [
		'required-admin-login'
	],
	function($request) {
		return new Response(200, Admin\ConfigEscola::index($request));
	}
]);

$obRouter->post('/painel/config/escola', [
	'middlewares' => [
		'required-admin-login'
	],
	function($request) {
		return new Response(200, Admin\ConfigEscola::getInfo($request));
	}
]);

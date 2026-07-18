<?php

use \App\Http\Response;
use \App\Controller\Admin;
use \App\Controller\Webhook;

$obRouter->get('/painel/config/pagamentos', [
	'middlewares' => [
		'required-admin-login'
	],
	function($request) {
		return new Response(200, Admin\ConfigPagamentos::index($request));
	}
]);

$obRouter->post('/painel/config/pagamentos', [
	'middlewares' => [
		'required-admin-login'
	],
	function($request) {
		return new Response(200, Admin\ConfigPagamentos::getInfo($request));
	}
]);

$obRouter->post('/webhook/mercadopago/{idAdmin}/{token}', [
	'middlewares' => [],
	function($request, $idAdmin, $token) {
		return new Response(200, Webhook\MercadoPago::receber($request, $idAdmin, $token), 'application/json');
	}
]);

$obRouter->get('/webhook/mercadopago/{idAdmin}/{token}', [
	'middlewares' => [],
	function($request, $idAdmin, $token) {
		return new Response(200, Webhook\MercadoPago::ping($idAdmin, $token), 'application/json');
	}
]);

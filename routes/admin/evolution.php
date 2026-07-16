<?php

use \App\Http\Response;
use \App\Controller\Webhook;

$obRouter->post('/webhook/evolution/{idAdmin}/{token}', [
	'middlewares' => [],
	function($request, $idAdmin, $token) {
		return new Response(200, Webhook\Evolution::receber($request, $idAdmin, $token), 'application/json');
	}
]);

$obRouter->get('/webhook/evolution/{idAdmin}/{token}', [
	'middlewares' => [],
	function($request, $idAdmin, $token) {
		return new Response(200, json_encode(['ok' => true, 'service' => 'evolution']), 'application/json');
	}
]);

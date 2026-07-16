<?php

use App\Http\Response;
use App\Controller\Master;

$obRouter->get('/master', [
	'middlewares' => ['required-master-login'],
	function ($request) {
		return new Response(200, Master\Home::index($request));
	}
]);

$obRouter->get('/master/escolas', [
	'middlewares' => ['required-master-login'],
	function ($request) {
		return new Response(200, Master\Escolas::index($request));
	}
]);

$obRouter->post('/master/escolas', [
	'middlewares' => ['required-master-login'],
	function ($request) {
		return new Response(200, Master\Escolas::getInfo($request));
	}
]);

$obRouter->get('/master/planos', [
	'middlewares' => ['required-master-login'],
	function ($request) {
		return new Response(200, Master\Planos::index($request));
	}
]);

$obRouter->post('/master/planos', [
	'middlewares' => ['required-master-login'],
	function ($request) {
		return new Response(200, Master\Planos::getInfo($request));
	}
]);

// Voltar do impersonate (usa sessão do diretor + snapshot master)
$obRouter->get('/master/voltar', [
	'middlewares' => ['required-admin-login'],
	function ($request) {
		return new Response(200, Master\Impersonate::voltar($request));
	}
]);

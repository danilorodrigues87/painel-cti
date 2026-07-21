<?php

use App\Http\Response;
use App\Controller\Admin;

$obRouter->get('/painel/ead', [
	'middlewares' => ['required-admin-login'],
	function ($request) {
		return new Response(200, Admin\EadCursos::index($request));
	}
]);

$obRouter->get('/painel/ead/{idTrilha}', [
	'middlewares' => ['required-admin-login'],
	function ($request, $idTrilha) {
		return new Response(200, Admin\EadCursos::editor($request, $idTrilha));
	}
]);

$obRouter->post('/painel/ead', [
	'middlewares' => ['required-admin-login'],
	function ($request) {
		return new Response(200, Admin\EadCursos::getInfo($request));
	}
]);

$obRouter->get('/painel/config/ia', [
	'middlewares' => ['required-admin-login'],
	function ($request) {
		return new Response(200, Admin\ConfigIa::index($request));
	}
]);

$obRouter->post('/painel/config/ia', [
	'middlewares' => ['required-admin-login'],
	function ($request) {
		return new Response(200, Admin\ConfigIa::getInfo($request));
	}
]);

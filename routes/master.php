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

$obRouter->get('/master/assinaturas', [
	'middlewares' => ['required-master-login'],
	function ($request) {
		return new Response(200, Master\Assinaturas::index($request));
	}
]);

$obRouter->post('/master/assinaturas', [
	'middlewares' => ['required-master-login'],
	function ($request) {
		return new Response(200, Master\Assinaturas::getInfo($request), 'application/json');
	}
]);

$obRouter->get('/master/conquistas', [
	'middlewares' => ['required-master-login'],
	function ($request) {
		return new Response(200, Master\Conquistas::index($request));
	}
]);

$obRouter->post('/master/conquistas', [
	'middlewares' => ['required-master-login'],
	function ($request) {
		return new Response(200, Master\Conquistas::getInfo($request), 'application/json');
	}
]);

$obRouter->get('/master/portal-branding', [
	'middlewares' => ['required-master-login'],
	function ($request) {
		return new Response(200, Master\PortalBranding::index($request));
	}
]);

$obRouter->post('/master/portal-branding', [
	'middlewares' => ['required-master-login'],
	function ($request) {
		return new Response(200, Master\PortalBranding::salvar($request), 'application/json');
	}
]);

$obRouter->get('/master/bunny', [
	'middlewares' => ['required-master-login'],
	function ($request) {
		return new Response(200, Master\Bunny::index($request));
	}
]);

$obRouter->post('/master/bunny', [
	'middlewares' => ['required-master-login'],
	function ($request) {
		return new Response(200, Master\Bunny::api($request), 'application/json');
	}
]);

$obRouter->get('/master/documentacao', [
	'middlewares' => ['required-master-login'],
	function ($request) {
		return new Response(200, Master\Documentacao::index($request));
	}
]);

$obRouter->get('/master/chamados', [
	'middlewares' => ['required-master-login'],
	function ($request) {
		return new Response(200, Master\Chamados::index($request));
	}
]);

$obRouter->post('/master/chamados', [
	'middlewares' => ['required-master-login'],
	function ($request) {
		return new Response(200, Master\Chamados::getInfo($request), 'application/json');
	}
]);

$obRouter->get('/master/chamados/anexo/{id}', [
	'middlewares' => ['required-master-login'],
	function ($request, $id) {
		return Master\Chamados::downloadAnexo($request, $id);
	}
]);

$obRouter->post('/master/documentacao', [
	'middlewares' => ['required-master-login'],
	function ($request) {
		return new Response(200, Master\Documentacao::salvar($request), 'application/json');
	}
]);

$obRouter->get('/master/perfil', [
	'middlewares' => ['required-master-login'],
	function ($request) {
		return new Response(200, Master\Perfil::index($request));
	}
]);

$obRouter->post('/master/perfil/salvar', [
	'middlewares' => ['required-master-login'],
	function ($request) {
		return new Response(200, Master\Perfil::salvar($request));
	}
]);

$obRouter->post('/master/perfil/senha', [
	'middlewares' => ['required-master-login'],
	function ($request) {
		return new Response(200, Master\Perfil::alterarSenha($request));
	}
]);

// Voltar do impersonate (usa sessão do diretor + snapshot master)
$obRouter->get('/master/voltar', [
	'middlewares' => ['required-admin-login'],
	function ($request) {
		return new Response(200, Master\Impersonate::voltar($request));
	}
]);

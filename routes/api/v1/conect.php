<?php

use App\Http\Response;
use App\Controller\Api\Conect;
use App\Controller\Api\ConectEmpresa;

$conectMw = ['cors-conect', 'api'];
$conectAuth = ['cors-conect', 'api', 'candidato-jwt'];
$empresaAuth = ['cors-conect', 'api', 'empresa-jwt'];

$respond = static function (array $res) {
	$contentType = $res['contentType'] ?? 'application/json';
	return new Response($res['code'] ?? 200, $res['json'] ?? '{}', $contentType);
};

// ——— Público ———
$obRouter->get('/api/v1/conect/public/branding', [
	'middlewares' => $conectMw,
	function ($request) use ($respond) {
		return $respond(Conect\PublicApi::branding($request));
	}
]);

$obRouter->get('/api/v1/conect/public/vagas', [
	'middlewares' => $conectMw,
	function ($request) use ($respond) {
		return $respond(Conect\PublicApi::vagas($request));
	}
]);

$obRouter->get('/api/v1/conect/public/vagas/{slug}', [
	'middlewares' => $conectMw,
	function ($request, $slug) use ($respond) {
		return $respond(Conect\PublicApi::vagaDetalhe($request, (string)$slug));
	}
]);

$obRouter->get('/api/v1/conect/public/empresas', [
	'middlewares' => $conectMw,
	function ($request) use ($respond) {
		return $respond(Conect\PublicApi::empresas($request));
	}
]);

$obRouter->get('/api/v1/conect/public/cidades', [
	'middlewares' => $conectMw,
	function ($request) use ($respond) {
		return $respond(Conect\PublicApi::cidades($request));
	}
]);

$obRouter->get('/api/v1/conect/public/estados', [
	'middlewares' => $conectMw,
	function ($request) use ($respond) {
		return $respond(Conect\PublicApi::estados($request));
	}
]);

$obRouter->get('/api/v1/conect/public/estados/{id}/cidades', [
	'middlewares' => $conectMw,
	function ($request, $id) use ($respond) {
		return $respond(Conect\PublicApi::cidadesPorEstado($request, (int)$id));
	}
]);

// ——— Candidato auth ———
$obRouter->post('/api/v1/conect/auth/login', [
	'middlewares' => $conectMw,
	function ($request) use ($respond) {
		return $respond(Conect\Auth::login($request));
	}
]);

$obRouter->post('/api/v1/conect/auth/register', [
	'middlewares' => $conectMw,
	function ($request) use ($respond) {
		return $respond(Conect\Auth::register($request));
	}
]);

$obRouter->get('/api/v1/conect/me', [
	'middlewares' => $conectAuth,
	function ($request) use ($respond) {
		return $respond(Conect\Auth::me($request));
	}
]);

$obRouter->put('/api/v1/conect/me', [
	'middlewares' => $conectAuth,
	function ($request) use ($respond) {
		return $respond(Conect\Perfil::atualizar($request));
	}
]);

$obRouter->post('/api/v1/conect/me/foto', [
	'middlewares' => $conectAuth,
	function ($request) use ($respond) {
		return $respond(Conect\Foto::upload($request));
	}
]);

$obRouter->get('/api/v1/conect/candidaturas', [
	'middlewares' => $conectAuth,
	function ($request) use ($respond) {
		return $respond(Conect\Candidaturas::listar($request));
	}
]);

$obRouter->post('/api/v1/conect/candidaturas', [
	'middlewares' => $conectAuth,
	function ($request) use ($respond) {
		return $respond(Conect\Candidaturas::criar($request));
	}
]);

$obRouter->get('/api/v1/conect/notificacoes', [
	'middlewares' => $conectAuth,
	function ($request) use ($respond) {
		return $respond(Conect\Notificacoes::listar($request));
	}
]);

$obRouter->post('/api/v1/conect/notificacoes/{id}/lida', [
	'middlewares' => $conectAuth,
	function ($request, $id) use ($respond) {
		return $respond(Conect\Notificacoes::marcarLida($request, (int)$id));
	}
]);

// ——— Empresa auth ———
$obRouter->post('/api/v1/conect-empresa/auth/login', [
	'middlewares' => $conectMw,
	function ($request) use ($respond) {
		return $respond(ConectEmpresa\Auth::login($request));
	}
]);

$obRouter->post('/api/v1/conect-empresa/auth/register', [
	'middlewares' => $conectMw,
	function ($request) use ($respond) {
		return $respond(ConectEmpresa\Auth::register($request));
	}
]);

$obRouter->get('/api/v1/conect-empresa/me', [
	'middlewares' => $empresaAuth,
	function ($request) use ($respond) {
		return $respond(ConectEmpresa\Auth::me($request));
	}
]);

$obRouter->put('/api/v1/conect-empresa/me', [
	'middlewares' => $empresaAuth,
	function ($request) use ($respond) {
		return $respond(ConectEmpresa\Perfil::atualizar($request));
	}
]);

$obRouter->get('/api/v1/conect-empresa/vagas', [
	'middlewares' => $empresaAuth,
	function ($request) use ($respond) {
		return $respond(ConectEmpresa\Vagas::listar($request));
	}
]);

$obRouter->post('/api/v1/conect-empresa/vagas', [
	'middlewares' => $empresaAuth,
	function ($request) use ($respond) {
		return $respond(ConectEmpresa\Vagas::criar($request));
	}
]);

$obRouter->put('/api/v1/conect-empresa/vagas/{id}', [
	'middlewares' => $empresaAuth,
	function ($request, $id) use ($respond) {
		return $respond(ConectEmpresa\Vagas::atualizar($request, (int)$id));
	}
]);

$obRouter->post('/api/v1/conect-empresa/vagas/{id}/acao', [
	'middlewares' => $empresaAuth,
	function ($request, $id) use ($respond) {
		return $respond(ConectEmpresa\Vagas::acao($request, (int)$id));
	}
]);

$obRouter->get('/api/v1/conect-empresa/candidaturas', [
	'middlewares' => $empresaAuth,
	function ($request) use ($respond) {
		return $respond(ConectEmpresa\Candidaturas::listar($request));
	}
]);

$obRouter->get('/api/v1/conect-empresa/candidaturas/{id}', [
	'middlewares' => $empresaAuth,
	function ($request, $id) use ($respond) {
		return $respond(ConectEmpresa\Candidaturas::detalhe($request, (int)$id));
	}
]);

$obRouter->put('/api/v1/conect-empresa/candidaturas/{id}', [
	'middlewares' => $empresaAuth,
	function ($request, $id) use ($respond) {
		return $respond(ConectEmpresa\Candidaturas::atualizarStatus($request, (int)$id));
	}
]);

$obRouter->post('/api/v1/conect-empresa/logo', [
	'middlewares' => $empresaAuth,
	function ($request) use ($respond) {
		return $respond(ConectEmpresa\Logo::upload($request));
	}
]);

$obRouter->get('/api/v1/conect-empresa/talentos', [
	'middlewares' => $empresaAuth,
	function ($request) use ($respond) {
		return $respond(ConectEmpresa\Talentos::buscar($request));
	}
]);

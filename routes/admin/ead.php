<?php

use App\Http\Response;
use App\Controller\Admin;

$obRouter->get('/painel/ead', [
	'middlewares' => ['required-admin-login'],
	function ($request) {
		return new Response(200, Admin\EadCursos::index($request));
	}
]);

$obRouter->get('/painel/ead/conquistas', [
	'middlewares' => ['required-admin-login'],
	function ($request) {
		return new Response(200, Admin\EadConquistas::index($request));
	}
]);

$obRouter->post('/painel/ead/conquistas', [
	'middlewares' => ['required-admin-login'],
	function ($request) {
		return new Response(200, Admin\EadConquistas::getInfo($request));
	}
]);

$obRouter->get('/painel/ead/progresso', [
	'middlewares' => ['required-admin-login'],
	function ($request) {
		return new Response(200, Admin\EadProgressoTurma::index($request));
	}
]);

$obRouter->post('/painel/ead/progresso', [
	'middlewares' => ['required-admin-login'],
	function ($request) {
		return new Response(200, Admin\EadProgressoTurma::getInfo($request));
	}
]);

$obRouter->get('/painel/ead/aluno/{idAluno}', [
	'middlewares' => ['required-admin-login'],
	function ($request, $idAluno) {
		return new Response(200, Admin\EadAlunoProgresso::index($request, $idAluno));
	}
]);

$obRouter->post('/painel/ead/aluno/{idAluno}', [
	'middlewares' => ['required-admin-login'],
	function ($request, $idAluno) {
		return new Response(200, Admin\EadAlunoProgresso::getInfo($request, $idAluno));
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

<?php

use \App\Http\Response;
use \App\Controller\Admin;

//ROTA DE AGENDA DE AULAS LABORATÓRIOS
$obRouter->get('/painel/agenda/laboratorio',[
	'middlewares' => [
		'required-admin-login'
	],
	function($request){
		return new Response(200,Admin\AgendaLaboratorio::index($request));
	}
]);

//ROTA DE AGENDA DE AULAS LABORATÓRIOS
$obRouter->post('/painel/agenda/laboratorio',[
	'middlewares' => [
		'required-admin-login'
	],
	function($request){
		return new Response(200,Admin\AgendaLaboratorio::getInfo($request));
	}
]);

//ROTA DE AGENDA DE AULAS VER AGENDAMENTOS
$obRouter->post('/painel/agenda/laboratorio/form',[
	'middlewares' => [
		'required-admin-login'
	],
	function($request){
		return new Response(200,Admin\AgendaLaboratorio::verDados($request));
	}
]);

//ROTA DE AGENDA DE AULAS VER E EDITAR DETALHES DE AGENDAMENTO
$obRouter->post('/painel/agenda/laboratorio/editar',[
	'middlewares' => [
		'required-admin-login'
	],
	function($request){
		return new Response(200,Admin\AgendaLaboratorio::editar($request));
	}
]);

//ROTA DE AGENDA DE AULAS EXLUIR AGENDAMENTO
$obRouter->post('/painel/agenda/laboratorio/excluir',[
	'middlewares' => [
		'required-admin-login'
	],
	function($request){
		return new Response(200,Admin\AgendaLaboratorio::excluir($request));
	}
]);

//ROTA DE AGENDA DE AULAS EXLUIR AGENDAMENTO
$obRouter->post('/painel/agenda/laboratorio/horarios',[
	'middlewares' => [
		'required-admin-login'
	],
	function($request){
		return new Response(200,Admin\AgendaLaboratorio::listarHorarios($request));
	}
]);

//ROTA DE AGENDA DE AULAS - SALVAR NOVO AGENDAMENTO OU EDIÇÃO
$obRouter->post('/painel/agenda/laboratorio/salvar',[
	'middlewares' => [
		'required-admin-login'
	],
	function($request){
		return new Response(200,Admin\AgendaLaboratorio::salvar($request));
	}
]);
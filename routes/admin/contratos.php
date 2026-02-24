<?php

use \App\Http\Response;
use \App\Controller\Admin;

//ROTA DE ALUNOS
$obRouter->get('/painel/contratos',[
	'middlewares' => [
		'required-admin-login'
	],
	function($request){
		return new Response(200,Admin\Contratos::index($request));
	}
]);

//ROTA LISTAGEM DE ALUNOS
$obRouter->post('/painel/contratos',[
	'middlewares' => [
		'required-admin-login'
	],
	function($request){
		return new Response(200,Admin\Contratos::getInfo($request));
	}
]);


//ROTA DE FORMULARIO DE EDIÇÃO
$obRouter->post('/painel/contratos/form',[
	'middlewares' => [
		'required-admin-login'
	],
	function($request){
		return new Response(200,Admin\Contratos::getNewContrato($request));
	}
]);

//ROTA DE SALVAMENTO DE DADOS
$obRouter->post('/painel/contratos/save',[
	'middlewares' => [
		'required-admin-login'
	],
	function($request){
		return new Response(200,Admin\Contratos::setNewContrato($request));
	}
]);

//ROTA DE EXCLUSAO
$obRouter->post('/painel/contratos/exclusao',[
	'middlewares' => [
		'required-admin-login'
	],
	function($request){
		return new Response(200,Admin\Contratos::exclusao($request));
	}
]);

//ROTA DE CONFIRMA EXCLUSAO
$obRouter->post('/painel/contratos/excluir',[
	'middlewares' => [
		'required-admin-login'
	],
	function($request){
		return new Response(200,Admin\Contratos::deleteContrato($request));
	}
]);




/**
 * 
 * ROTAS DINAMICAS
 * DEVEM FICAR POR ULTIMO
 * NUNCA COLOQUE UMA ROTA FIXA DEPOIS DESSAS
 * 
 **/


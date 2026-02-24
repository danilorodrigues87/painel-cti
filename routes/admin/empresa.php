<?php

use \App\Http\Response;
use \App\Controller\Admin;

//ROTA DE CLIENTES
$obRouter->get('/painel/empresa',[
	'middlewares' => [
		'required-admin-login'
	],
	function($request){
		return new Response(200,Admin\Empresas::index($request));
	}
]);

//ROTA LISTAGEM DE CLIENTES
$obRouter->post('/painel/empresa',[
	'middlewares' => [
		'required-admin-login'
	],
	function($request){
		return new Response(200,Admin\Empresas::getInfo($request));
	}
]);


//ROTA DE FORMULARIO DE EDIÇÃO
$obRouter->post('/painel/empresa/form',[
	'middlewares' => [
		'required-admin-login'
	],
	function($request){
		return new Response(200,Admin\Empresas::getNewEmpresa($request));
	}
]);

//ROTA DE SALVAMENTO DE DADOS
$obRouter->post('/painel/empresa/save',[
	'middlewares' => [
		'required-admin-login'
	],
	function($request){
		return new Response(200,Admin\Empresas::setNew($request));
	}
]);


//ROTA DE CONFIRMA EXCLUSAO
$obRouter->post('/painel/empresa/excluir',[
	'middlewares' => [
		'required-admin-login'
	],
	function($request){
		return new Response(200,Admin\Empresas::deleteItem($request));
	}
]);




/**
 * 
 * ROTAS DINAMICAS
 * DEVEM FICAR POR ULTIMO
 * NUNCA COLOQUE UMA ROTA FIXA DEPOIS DESSAS
 * 
 **/


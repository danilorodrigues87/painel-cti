<?php

use \App\Http\Response;
use \App\Controller\Admin;

//ROTA DE CLIENTES
$obRouter->get('/painel/diretores',[
	'middlewares' => [
		'required-admin-login'
	],
	function($request){
		return new Response(200,Admin\Diretores::index($request));
	}
]);

//ROTA LISTAGEM DE CLIENTES
$obRouter->post('/painel/diretores',[
	'middlewares' => [
		'required-admin-login'
	],
	function($request){
		return new Response(200,Admin\Diretores::getInfo($request));
	}
]);


//ROTA DE FORMULARIO DE EDIÇÃO
$obRouter->post('/painel/diretores/form',[
	'middlewares' => [
		'required-admin-login'
	],
	function($request){
		return new Response(200,Admin\Diretores::getNewUser($request));
	}
]);

//ROTA DE SALVAMENTO DE DADOS
$obRouter->post('/painel/diretores/save',[
	'middlewares' => [
		'required-admin-login'
	],
	function($request){
		return new Response(200,Admin\Diretores::setNewUser($request));
	}
]);

//ROTA DE CONFIRMA EXCLUSAO
$obRouter->post('/painel/diretores/excluir',[
	'middlewares' => [
		'required-admin-login'
	],
	function($request){
		return new Response(200,Admin\Diretores::deleteUser($request));
	}
]);





/**
 * 
 * ROTAS DINAMICAS
 * DEVEM FICAR POR ULTIMO
 * NUNCA COLOQUE UMA ROTA FIXA DEPOIS DESSAS
 * 
 **/


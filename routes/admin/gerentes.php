<?php

use \App\Http\Response;
use \App\Controller\Admin;

//ROTA DE CLIENTES
$obRouter->get('/painel/gerentes',[
	'middlewares' => [
		'required-admin-login'
	],
	function($request){
		return new Response(200,Admin\Gerentes::index($request));
	}
]);

//ROTA LISTAGEM DE CLIENTES
$obRouter->post('/painel/gerentes',[
	'middlewares' => [
		'required-admin-login'
	],
	function($request){
		return new Response(200,Admin\Gerentes::getInfo($request));
	}
]);


//ROTA DE FORMULARIO DE EDIÇÃO
$obRouter->post('/painel/gerentes/form',[
	'middlewares' => [
		'required-admin-login'
	],
	function($request){
		return new Response(200,Admin\Gerentes::getNewUser($request));
	}
]);

//ROTA DE SALVAMENTO DE DADOS
$obRouter->post('/painel/gerentes/save',[
	'middlewares' => [
		'required-admin-login'
	],
	function($request){
		return new Response(200,Admin\Gerentes::setNewUser($request));
	}
]);


//ROTA DE CONFIRMA EXCLUSAO
$obRouter->post('/painel/gerentes/excluir',[
	'middlewares' => [
		'required-admin-login'
	],
	function($request){
		return new Response(200,Admin\Gerentes::deleteUser($request));
	}
]);
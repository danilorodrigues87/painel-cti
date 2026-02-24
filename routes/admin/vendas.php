<?php

use \App\Http\Response;
use \App\Controller\Admin;

//ROTA DE ALUNOS
$obRouter->get('/painel/vendas',[
	'middlewares' => [
		'required-admin-login'
	],
	function($request){
		return new Response(200,Admin\Vendas::index($request));
	}
]);

//ROTA LISTAGEM DE ALUNOS
$obRouter->post('/painel/vendas',[
	'middlewares' => [
		'required-admin-login'
	],
	function($request){
		return new Response(200,Admin\Vendas::getInfo($request));
	}
]);


//ROTA DE FORMULARIO DE EDIÇÃO
$obRouter->post('/painel/vendas/form',[
	'middlewares' => [
		'required-admin-login'
	],
	function($request){
		return new Response(200,Admin\Vendas::getNewTrilha($request));
	}
]);

//ROTA DE SALVAMENTO DE DADOS
$obRouter->post('/painel/vendas/save',[
	'middlewares' => [
		'required-admin-login'
	],
	function($request){
		return new Response(200,Admin\Vendas::setNewTrilha($request));
	}
]);



/**
 * 
 * ROTAS DINAMICAS
 * DEVEM FICAR POR ULTIMO
 * NUNCA COLOQUE UMA ROTA FIXA DEPOIS DESSAS
 * 
 **/

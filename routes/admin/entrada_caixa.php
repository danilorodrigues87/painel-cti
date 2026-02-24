<?php

use \App\Http\Response;
use \App\Controller\Admin;

//ROTA ENTRADA DE CAIXA
$obRouter->get('/painel/caixa/entrada',[
	'middlewares' => [
		'required-admin-login'
	],
	function($request){
		return new Response(200,Admin\CaixaEntrada::index($request));
	}
]);

//ROTA LISTAGEM ENTRADA CAIXA
$obRouter->post('/painel/caixa/entrada',[
	'middlewares' => [
		'required-admin-login'
	],
	function($request){
		return new Response(200,Admin\CaixaEntrada::getInfo($request));
	}
]);

//ROTA LISTAGEM ENTRADA CAIXA
$obRouter->post('/painel/caixa/entrada/form',[
	'middlewares' => [
		'required-admin-login'
	],
	function($request){
		return new Response(200,Admin\CaixaEntrada::getNewCaixa($request));
	}
]);

//ROTA ENTRADA CAIXA - REGISTRA MOVIMENTO
$obRouter->post('/painel/caixa/entrada/save',[
	'middlewares' => [
		'required-admin-login'
	],
	function($request){
		return new Response(200,Admin\CaixaEntrada::registrarPagamento($request));
	}
]);




/**
 * 
 * ROTAS DINAMICAS
 * DEVEM FICAR POR ULTIMO
 * NUNCA COLOQUE UMA ROTA FIXA DEPOIS DESSAS
 * 
 **/


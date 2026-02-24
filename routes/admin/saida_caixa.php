<?php

use \App\Http\Response;
use \App\Controller\Admin;

//ROTA ENTRADA DE CAIXA
$obRouter->get('/painel/caixa/saida',[
	'middlewares' => [
		'required-admin-login'
	],
	function($request){
		return new Response(200,Admin\CaixaSaida::index($request));
	}
]);

//ROTA LISTAGEM ENTRADA CAIXA
$obRouter->post('/painel/caixa/saida',[
	'middlewares' => [
		'required-admin-login'
	],
	function($request){
		return new Response(200,Admin\CaixaSaida::getInfo($request));
	}
]);

//ROTA LISTAGEM ENTRADA CAIXA
$obRouter->post('/painel/caixa/saida/form',[
	'middlewares' => [
		'required-admin-login'
	],
	function($request){
		return new Response(200,Admin\CaixaSaida::getNewCaixa($request));
	}
]);

//ROTA ENTRADA CAIXA - REGISTRA MOVIMENTO
$obRouter->post('/painel/caixa/saida/save',[
	'middlewares' => [
		'required-admin-login'
	],
	function($request){
		return new Response(200,Admin\CaixaSaida::registrarPagamento($request));
	}
]);
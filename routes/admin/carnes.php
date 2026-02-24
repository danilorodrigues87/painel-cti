<?php

use \App\Http\Response;
use \App\Controller\Admin;

//ROTA DE CARNÊS DE PAGAMENTO
$obRouter->get('/painel/carnes',[
	'middlewares' => [
		'required-admin-login'
	],
	function($request){
		return new Response(200,Admin\Carnes::index($request));
	}
]);

//ROTA LISTAGEM DE CARNÊS
$obRouter->post('/painel/carnes',[
	'middlewares' => [
		'required-admin-login'
	],
	function($request){
		return new Response(200,Admin\Carnes::getInfo($request));
	}
]);

//ROTA LISTAGEM DE PARCELAS
$obRouter->post('/painel/carnes/form',[
	'middlewares' => [
		'required-admin-login'
	],
	function($request){
		return new Response(200,Admin\Carnes::getList($request));
	}
]);

//ROTA VER DETALHES DO TITULO
$obRouter->post('/painel/carnes/dar-baixa',[
	'middlewares' => [
		'required-admin-login'
	],
	function($request){
		return new Response(200,Admin\Carnes::darBaixa($request));
	}
]);


//ROTA REGISTRAR PAGAMENTO
$obRouter->post('/painel/carnes/save',[
	'middlewares' => [
		'required-admin-login'
	],
	function($request){
		return new Response(200,Admin\Carnes::registrarPagamento($request));
	}
]);





/**
 * 
 * ROTAS DINAMICAS
 * DEVEM FICAR POR ULTIMO
 * NUNCA COLOQUE UMA ROTA FIXA DEPOIS DESSAS
 * 
 **/





//ROTA VER DETALHES DO TITULO
$obRouter->get('/painel/carnes/recibo/{id}',[
	'middlewares' => [
		'required-admin-login'
	],
	function($request,$id){
		return new Response(200,Admin\Carnes::recibo($request,$id));
	}
]);

//ROTA VER CARNÊ
$obRouter->get('/painel/carnes/{id}',[
	'middlewares' => [
		'required-admin-login'
	],
	function($request,$id){
		return new Response(200,Admin\Carnes::imprimeCarne($request,$id));
	}
]);
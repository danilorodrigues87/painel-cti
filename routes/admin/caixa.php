<?php

use \App\Http\Response;
use \App\Controller\Admin;

//ROTA DO CAIXA
$obRouter->get('/painel/caixa',[
	'middlewares' => [
		'required-admin-login'
	],
	function($request){
		return new Response(200,Admin\Caixa::index($request));
	}
]);

//ROTA LISTAGEM DO CAIXA
$obRouter->post('/painel/caixa',[
	'middlewares' => [
		'required-admin-login'
	],
	function($request){
		return new Response(200,Admin\Caixa::getInfo($request));
	}
]);


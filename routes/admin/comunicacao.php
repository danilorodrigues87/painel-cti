<?php

use \App\Http\Response;
use \App\Controller\Admin;

$obRouter->get('/painel/config/comunicacao',[
	'middlewares' => [
		'required-admin-login'
	],
	function($request){
		return new Response(200, Admin\ConfigComunicacao::index($request));
	}
]);

$obRouter->post('/painel/config/comunicacao',[
	'middlewares' => [
		'required-admin-login'
	],
	function($request){
		return new Response(200, Admin\ConfigComunicacao::getInfo($request));
	}
]);

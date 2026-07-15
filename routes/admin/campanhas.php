<?php

use \App\Http\Response;
use \App\Controller\Admin;

$obRouter->get('/painel/campanhas',[
	'middlewares' => ['required-admin-login'],
	function($request){
		return new Response(200, Admin\Campanhas::index($request));
	}
]);

$obRouter->post('/painel/campanhas',[
	'middlewares' => ['required-admin-login'],
	function($request){
		return new Response(200, Admin\Campanhas::getInfo($request));
	}
]);

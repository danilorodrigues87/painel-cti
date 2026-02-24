<?php

use \App\Http\Response;
use \App\Controller\Admin;

//ROTA DE ATENDIMENTO
$obRouter->get('/painel/whatsappatm',[
	'middlewares' => [
		'required-admin-login'
	],
	function($request){
		return new Response(200,Admin\WhatsappAtm::index($request));
	}
]);

//ROTA LISTAGEM DE ATENDOMENTOS
$obRouter->post('/painel/whatsappatm',[
	'middlewares' => [
		'required-admin-login'
	],
	function($request){
		return new Response(200,Admin\WhatsappAtm::getInfo($request));
	}
]);

//ROTA DE LISTAGEM DE USUÁRIOS
$obRouter->post('/painel/whatsappatm/users',[
	'middlewares' => [
		'required-admin-login'
	],
	function($request){
		return new Response(200,Admin\WhatsappAtm::getUsers($request));
	}
]);



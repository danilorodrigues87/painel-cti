<?php

use \App\Http\Response;
use \App\Controller\Admin;


//ROTA CARREGA A PAGINA
$obRouter->post('/painel/mensagens/chat',[
	'middlewares' => [
		'required-admin-login'
	],
	function($request){
		return new Response(200,Admin\Mensagens::index($request));
	}
]);


//ROTA DE NOVO CHAT
$obRouter->post('/painel/mensagens/newchat',[
	'middlewares' => [
		'required-admin-login'
	],
	function($request){
		return new Response(200,Admin\Mensagens::getInfo($request));
	}
]);

//ROTA ENVIA MENSAGEM
$obRouter->post('/painel/mensagens/enviar',[
	'middlewares' => [
		'required-admin-login'
	],
	function($request){
		return new Response(200,Admin\Mensagens::enviarMensagem($request));
	}
]);
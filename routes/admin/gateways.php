<?php

use \App\Http\Response;
use \App\Common\Gateways\BancoInter;

//ROTA ENTRADA DE CAIXA
$obRouter->post('/bancointer/pixComVencimento/webhook',[
	function($request){

		return new Response(200,Bancointer\Webhook::Notification($request));
	}
]);

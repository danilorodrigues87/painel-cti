<?php

use \App\Controller\Api\ReceiveMessage;

// Rota para verificação do webhook
$obRouter->get('/api/v1/receiveMessage',[
	function($request){
		return ReceiveMessage::verifyWebhook($request);
	}
]);

// Rota para receber mensagens
$obRouter->post('/api/v1/receiveMessage',[
	function($request){
		return ReceiveMessage::handleMessage($request);
	}
]);


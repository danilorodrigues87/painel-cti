<?php

use \App\Http\Response;
use \App\Controller\Api;

//ROTA RECEBIMENTO DE  VIA API
$obRouter->get('/api/v1/estoque/produtos',[
	'middlewares' => [
		'api',
		'cache'
	],
	function($request){
		return new Response(200,Api\Stq_Produtos::getProdutos($request),'application/json');
	}
]);


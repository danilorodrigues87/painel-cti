<?php
use \App\Http\Response;
use \App\Controller\Api;

//ROTA RECEBIMENTO DE MENSAGEM VIA API
$obRouter->get('/api/v1/testimonies',[
	'middlewares' => [
		'api',
		'cache'
	],
	function($request){
		return new Response(200,Api\Testimony::getTestimonies($request),'application/json');
	}
]);


//ROTA RECEBIMENTO DE MENSAGEM VIA API COM BASE NO ID
$obRouter->get('/api/v1/testimonies/{id}',[
	'middlewares' => [
		'api',
		'cache'
	],
	function($request,$id){
		return new Response(200,Api\Testimony::getTestimony($request,$id),'application/json');
	}
]);

//ROTA DE CADASTRO DE DEPOIMENTOS VIA API
$obRouter->post('/api/v1/testimonies',[
	'middlewares' => [
		'api',
		'user-basic-auth'
	],
	function($request){
		return new Response(201,Api\Testimony::setNewTestimony($request),'application/json');
	}
]);

//ROTA DE ATUALIZAÇÃO DE DEPOIMENTOS VIA API
$obRouter->put('/api/v1/testimonies/{id}',[
	'middlewares' => [
		'api',
		'user-basic-auth'
	],
	function($request,$id){
		return new Response(200,Api\Testimony::setEditTestimony($request,$id),'application/json');
	}
]);

//ROTA DE EXCLUSÃO DE DEPOIMENTOS VIA API
$obRouter->delete('/api/v1/testimonies/{id}',[
	'middlewares' => [
		'api',
		'user-basic-auth'
	],
	function($request,$id){
		return new Response(200,Api\Testimony::setDeleteTestimony($request,$id),'application/json');
	}
]);
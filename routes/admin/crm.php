<?php

use \App\Http\Response;
use \App\Controller\Admin;

//ROTA DO CRM KANBAN
$obRouter->get('/painel/crm',[
	'middlewares' => [
		'required-admin-login'
	],
	function($request){
		return new Response(200,Admin\CrmLeads::index($request));
	}
]);

//ROTA LISTAGEM DE LEADS (KANBAN)
$obRouter->post('/painel/crm',[
	'middlewares' => [
		'required-admin-login'
	],
	function($request){
		return new Response(200,Admin\CrmLeads::getInfo($request));
	}
]);

//ROTA CADASTRAR NOVO LEAD
$obRouter->post('/painel/crm/save',[
	'middlewares' => [
		'required-admin-login'
	],
	function($request){
		return new Response(200,Admin\CrmLeads::cadastrar($request));
	}
]);

//ROTA IMPORTAR LEADS VIA PLANILHA
$obRouter->post('/painel/crm/importar',[
	'middlewares' => [
		'required-admin-login'
	],
	function($request){
		return new Response(200,Admin\CrmLeads::importar($request));
	}
]);

//ROTA ATUALIZAR STATUS DO LEAD (DRAG AND DROP)
$obRouter->post('/painel/crm/status',[
	'middlewares' => [
		'required-admin-login'
	],
	function($request){
		return new Response(200,Admin\CrmLeads::atualizarStatus($request));
	}
]);

//ROTA DETALHES DO LEAD (MODAL)
$obRouter->post('/painel/crm/detalhes',[
	'middlewares' => [
		'required-admin-login'
	],
	function($request){
		return new Response(200,Admin\CrmLeads::getDetalhes($request));
	}
]);

//ROTA SALVAR COMENTÁRIO NO HISTÓRICO
$obRouter->post('/painel/crm/comentario',[
	'middlewares' => [
		'required-admin-login'
	],
	function($request){
		return new Response(200,Admin\CrmLeads::salvarComentario($request));
	}
]);

//ROTA ATUALIZAR DADOS CADASTRAIS DO LEAD
$obRouter->post('/painel/crm/atualizar',[
	'middlewares' => [
		'required-admin-login'
	],
	function($request){
		return new Response(200,Admin\CrmLeads::atualizarDados($request));
	}
]);

//ROTA LISTAR FUNIS
$obRouter->post('/painel/crm/funis',[
	'middlewares' => [
		'required-admin-login'
	],
	function($request){
		return new Response(200,Admin\CrmLeads::getFunis($request));
	}
]);

//ROTA CADASTRAR FUNIL
$obRouter->post('/painel/crm/funis/save',[
	'middlewares' => [
		'required-admin-login'
	],
	function($request){
		return new Response(200,Admin\CrmLeads::cadastrarFunil($request));
	}
]);

//ROTA EXCLUIR FUNIL
$obRouter->post('/painel/crm/funis/delete',[
	'middlewares' => [
		'required-admin-login'
	],
	function($request){
		return new Response(200,Admin\CrmLeads::excluirFunil($request));
	}
]);

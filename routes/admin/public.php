<?php

use App\Http\Response;
use App\Controller\PublicPages;

// Política de privacidade — pública (Meta App Review / Facebook)
$obRouter->get('/privacidade', [
	function ($request) {
		return new Response(200, PublicPages\Privacy::index($request));
	}
]);

$obRouter->get('/privacy', [
	function ($request) {
		return new Response(200, PublicPages\Privacy::index($request));
	}
]);

$obRouter->get('/privacy-policy', [
	function ($request) {
		return new Response(200, PublicPages\Privacy::index($request));
	}
]);

// Exclusão de dados do usuário (Meta User Data Deletion)
$obRouter->get('/exclusao-de-dados', [
	function ($request) {
		return new Response(200, PublicPages\DataDeletion::index($request));
	}
]);

$obRouter->post('/exclusao-de-dados', [
	function ($request) {
		return new Response(200, PublicPages\DataDeletion::index($request));
	}
]);

$obRouter->get('/data-deletion', [
	function ($request) {
		return new Response(200, PublicPages\DataDeletion::index($request));
	}
]);

$obRouter->post('/data-deletion', [
	function ($request) {
		return new Response(200, PublicPages\DataDeletion::index($request));
	}
]);

$obRouter->get('/user-data-deletion', [
	function ($request) {
		return new Response(200, PublicPages\DataDeletion::index($request));
	}
]);

// Central de ajuda (pública)
$obRouter->get('/ajuda', [
	function ($request) {
		return new Response(200, \App\Controller\Admin\Ajuda::indexPublico($request));
	}
]);

$obRouter->get('/ajuda/{slug}', [
	function ($request, $slug) {
		return new Response(200, \App\Controller\Admin\Ajuda::artigoPublico($request, $slug));
	}
]);

<?php

require __DIR__.'/includes/app.php';
use \App\Http\Router;

// CORS cedo para API aluno (antes de qualquer 404/exception sem header)
$uri = $_SERVER['REQUEST_URI'] ?? '';
if (stripos($uri, '/api/v1/student') !== false) {
	$origin = trim((string)($_SERVER['HTTP_ORIGIN'] ?? ''));
	if ($origin !== '') {
		header('Access-Control-Allow-Origin: '.$origin);
		header('Access-Control-Allow-Credentials: true');
		header('Vary: Origin');
	} else {
		header('Access-Control-Allow-Origin: *');
	}
	header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
	header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin');
	header('Access-Control-Allow-Private-Network: true');
	if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
		http_response_code(204);
		exit;
	}
}

//inclde para testes
//include __DIR__.'/testes.php';

$obRouter = new Router(URL);

//INCLUI AS ROTAS DO PAINEL
include __DIR__.'/routes/admin.php';

//INCLUI AS ROTAS DO PAINEL MASTER
include __DIR__.'/routes/master.php';

//INCLUI AS ROTAS DE APIS
include __DIR__.'/routes/api.php';

//IMPRIME O RESPONSE DA ROTA
$obRouter->run()->sendResponse();

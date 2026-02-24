<?php

require __DIR__.'/includes/app.php';
use \App\Http\Router;

//inclde para testes
//include __DIR__.'/testes.php';

$obRouter = new Router(URL);

//INCLUI AS ROTAS DO PAINEL
include __DIR__.'/routes/admin.php';

//INCLUI AS ROTAS DE APIS
include __DIR__.'/routes/api.php';

//IMPRIME O RESPONSE DA ROTA
$obRouter->run()->sendResponse();

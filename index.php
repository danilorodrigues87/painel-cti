<?php

require __DIR__.'/includes/app.php';

use \App\Http\Router;

$obRouter = new Router(URL);

// Rotas do painel
include __DIR__.'/routes/admin.php';

// Rotas do painel master
include __DIR__.'/routes/master.php';

// Rotas de APIs
include __DIR__.'/routes/api.php';

$obRouter->run()->sendResponse();

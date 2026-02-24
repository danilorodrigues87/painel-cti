<?php 
/*
ini_set('display_errors', 1);
error_reporting(E_ALL);
*/
require __DIR__.'/../vendor/autoload.php';

use \App\Utils\View;
use \App\Common\Environment;
use \App\Model\Db\Database;
use \App\Http\Middleware\Queue as MiddlewareQueue;

//CARREGA VARIAVEIS DE AMBIENTE
Environment::load(__DIR__.'/../');

//DEFINE A CONSTANTE DE URL
define('URL', getenv('URL'));
define('SITE', getenv('SITE'));
define('TIMEZONE', getenv('TIMEZONE'));
date_default_timezone_set(TIMEZONE);
header('Content-Type: text/html; charset=utf-8');

//DEFINE A CONSTANTE DE URL
define('SYSTEM_TOKEN', getenv('SYSTEM_TOKEN'));

//DEFINE O VALOR PADRÃO DAS VARIAVEIS
View::init([
'URL' => URL
]);

//DEFINE O MAPEAMENTO DE MIDDLEWARES
MiddlewareQueue::setMap([
	'maintenance' => \App\Http\Middleware\Maintenance::class,
	'required-admin-logout' => \App\Http\Middleware\RequireAdminLogout::class,
	'required-admin-login' => \App\Http\Middleware\RequireAdminLogin::class,
	'api' => \App\Http\Middleware\Api::class,
	'user-basic-auth' => \App\Http\Middleware\UserBasicAuth::class,
	'jwt-auth' => \App\Http\Middleware\JWTAuth::class,
	'cache' => \App\Http\Middleware\Cache::class
]);

//DEFINE O MAPEAMENTO DE MIDDLEWARES PADRÕES (EXECUTA EM TODAS AS ROTAS)
MiddlewareQueue::setDefault([
	'maintenance'
]);
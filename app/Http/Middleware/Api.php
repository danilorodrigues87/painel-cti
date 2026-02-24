<?php 

namespace App\Http\Middleware;

class Api{

	//EXECUTA O MIDDLEWARE
	public function handle($request, $next){

		//ALTERA O CONTENT TYPE PARA JSON
		$request->getRouter()->setContentType('application/json');

		//EXECUTA O PROXIMO NIVEL DO MIDDLEWARE
		return $next($request);
	}

}
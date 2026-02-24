<?php 

namespace App\Http\Middleware;

use \App\Session\User\Login as SessionAdminLogin;

class RequireAdminLogin{

	public function handle($request, $next){

		//VERIFICA SE O USUÁRIO ESTÁ LOGADO
		if(!SessionAdminLogin::isUserLogged()){

			$request->getRouter()->redirect('/');
		}

		//CONTINUA A EXECUÇÃO
		return $next($request);
		
	}
}
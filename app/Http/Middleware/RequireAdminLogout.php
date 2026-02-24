<?php 

namespace App\Http\Middleware;

use \App\Session\User\Login as SessionAdminLogin;

class RequireAdminLogout{

	public function handle($request, $next){

		//VERIFICA SE O USUÁRIO ESTÁ LOGADO
		if(SessionAdminLogin::isUserLogged()){
			$request->getRouter()->redirect('/painel');
		}

		//CONTINUA A EXECUÇÃO
		return $next($request);
		
	}
}
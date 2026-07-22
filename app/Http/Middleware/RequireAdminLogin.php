<?php 

namespace App\Http\Middleware;

use \App\Session\User\Login as SessionAdminLogin;

class RequireAdminLogin{

	public function handle($request, $next){

		//VERIFICA SE O USUÁRIO ESTÁ LOGADO
		if(!SessionAdminLogin::isUserLogged()){
			$request->getRouter()->redirect('/');
		}

		//ATUALIZA PERMISSÕES E STATUS DA SESSÃO
		if(!SessionAdminLogin::syncSessionFromDatabase()){
			$request->getRouter()->redirect('/');
		}

		// Escola suspensa: só Assinatura (+ logout)
		if (SessionAdminLogin::isAssinaturaBloqueada()) {
			$uri = (string)$request->getUri();
			if (!SessionAdminLogin::uriPermitidaQuandoBloqueada($uri)) {
				$request->getRouter()->redirect('/painel/assinatura');
			}
		}

		//CONTINUA A EXECUÇÃO
		return $next($request);
		
	}
}
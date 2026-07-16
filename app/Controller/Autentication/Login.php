<?php

namespace App\Controller\Autentication;

use \App\Utils\View;
use \App\Model\Entity\User;
use \App\Model\Entity\EscolasAssinantes;
use \App\Controller\Admin\Alert;
use \App\Session\User\Login as SessionUserLogin;
use \App\Common\Helpers\MasterGateHelper;

class Login{


	//RETORNA A RENDERIZAÇÃO DA PÁGINA DE LOGIN
	public static function getLogin($request, $errorMessage = null){

		if (!empty($_SESSION['alert'])){
			unset($_SESSION['alert']);
			return self::getLogin($request,'Senha enviada para seu email!');
		}

		//STATUS
		$status = !is_null($errorMessage) ? Alert::getError($errorMessage) : '';

		//CONTEUDO DA PAGINA DE LOGIN
		$content = View::render('login/login',[
			'status' => $status,
		]);

		//RETORNA A PÁGINA COMPLETA
		return View::render('login/page',[
			'title' => 'Login Sistema',
			'content' => $content
		]);
	}

	//DEFINE O LOGIN DO USUÁRIO
	public static function setLogin($request){
		//POST VARS
		$postVars = $request->getPostVars();
		$email = $postVars['email'] ?? '';
		$senha = $postVars['senha'] ?? '';

		$email = filter_var($email, FILTER_SANITIZE_EMAIL);

		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    		return self::getLogin($request, 'Email ou senha inválidos!');
		}

		//BUSCA O USUÁRIO PELO EMAIL
		$obUser = User::getUserByEmail($email);
		if(!$obUser instanceof User){
			return self::getLogin($request,'Email ou senha inválidos!');

		}

		//VERIFICA A SENHA DO USUÁRIO
		if(!password_verify($senha, $obUser->senha)){
			return self::getLogin($request,'Email ou senha inválidos!');
		}

		// verifica se o usuário está ativo antes de logar
		if($obUser->ativo != 's'){
			return self::getLogin($request,'Seu acesso está inativo, contate o suporte.');
		}

		// verifia se o usuário tem permissão para acessar
		if($obUser->nivel == 'Cliente' || $obUser->nivel == 'Empresa'){

			// Retorna um alerta de acesso negado
		return self::getLogin($request,'Você não tem permissão para acessar essa área.');

		}

		$isMaster = MasterGateHelper::isMasterEmail($obUser->email ?? '');
		$escola = EscolasAssinantes::getEscolaById((int)$obUser->id_admin);
		if ($escola instanceof EscolasAssinantes && !$escola->isAtiva() && !$isMaster) {
			return self::getLogin($request, 'Esta escola está inativa. Contate o suporte.');
		}

		//CRIA A SESSÃO DE LOGIN
		SessionUserLogin::login($obUser);

		if ($isMaster) {
			$_SESSION['usuario-mvc-1']['is_master'] = true;
			$request->getRouter()->redirect('/master');
		}

		//REDIRECIONA O USUÁRIO PARA A HOME DO ADMIN
		$request->getRouter()->redirect('/painel');

	}

	//DESLOGA O USUÁRIO
	public static function setLogout($request){
		//DESTROI A SESSÃO DE LOGIN
		SessionUserLogin::logout();

		//REDIRECIONA O USUÁRIO PARA A TELA DE LOGIN
		$request->getRouter()->redirect('/');

	}
}
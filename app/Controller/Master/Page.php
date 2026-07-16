<?php

namespace App\Controller\Master;

use App\Utils\View;
use App\Session\User\Login as SessionUser;

class Page {

	public static function getPanel(string $title, string $content, string $menuAtivo = 'home'): string {
		$user = SessionUser::getUserLogedData();
		$nome = $user['usuario']['nome'] ?? 'Master';

		$menu = View::render('master/panel', [
			'user'            => $nome,
			'current_home'    => $menuAtivo === 'home' ? 'active' : '',
			'current_escolas' => $menuAtivo === 'escolas' ? 'active' : '',
			'current_planos'  => $menuAtivo === 'planos' ? 'active' : '',
		]);

		return View::render('master/page', [
			'title'   => $title,
			'content' => $content,
			'menu'    => $menu,
			'user'    => $nome,
		]);
	}
}

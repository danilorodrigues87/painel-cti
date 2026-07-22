<?php

namespace App\Controller\Master;

use App\Utils\View;
use App\Session\User\Login as SessionUser;
use App\Model\Entity\User as EntityUser;
use App\Common\Helpers\UserFotoHelper;

class Page {

	public static function getPanel(string $title, string $content, string $menuAtivo = 'home'): string {
		$user = SessionUser::getUserLogedData();
		$nome = $user['usuario']['nome'] ?? 'Master';
		$uid = (int)($user['usuario']['id'] ?? 0);
		$fotoUrl = UserFotoHelper::urlPadrao();
		if ($uid > 0 && EntityUser::temColunaFoto()) {
			$ob = EntityUser::getUser('id = '.$uid, null, 1, 'foto')->fetchObject(EntityUser::class);
			$fotoUrl = UserFotoHelper::urlPublica($ob->foto ?? null);
		}

		$menu = View::render('master/panel', [
			'user'                 => $nome,
			'current_home'         => $menuAtivo === 'home' ? 'active' : '',
			'current_escolas'      => $menuAtivo === 'escolas' ? 'active' : '',
			'current_planos'       => $menuAtivo === 'planos' ? 'active' : '',
			'current_assinaturas'  => $menuAtivo === 'assinaturas' ? 'active' : '',
			'current_conquistas'   => $menuAtivo === 'conquistas' ? 'active' : '',
			'current_perfil'       => $menuAtivo === 'perfil' ? 'active' : '',
		]);

		return View::render('master/page', [
			'title'    => $title,
			'content'  => $content,
			'menu'     => $menu,
			'user'     => $nome,
			'foto_url' => $fotoUrl,
		]);
	}
}

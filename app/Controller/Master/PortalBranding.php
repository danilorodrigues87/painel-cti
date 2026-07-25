<?php

namespace App\Controller\Master;

use App\Utils\View;
use App\Model\Entity\PortalAlunoBranding;
use App\Common\Helpers\BrandingHelper;

class PortalBranding extends Page {

	public static function index($request) {
		if (!PortalAlunoBranding::tabelasExistem()) {
			$content = View::render('master/modules/portal-branding/sql', []);
			return parent::getPanel('Portal do aluno — Master', $content, 'portal');
		}

		$row = PortalAlunoBranding::get();
		$logoUrl = BrandingHelper::urlPortalLogo($row->logo ?? null);
		$heroUrl = BrandingHelper::urlPortalLoginHero($row->login_hero ?? null);

		$content = View::render('master/modules/portal-branding/index', [
			'logo_preview' => $logoUrl
				? htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8')
				: htmlspecialchars(URL.'/resources/assets/img/icons/logo-2.png', ENT_QUOTES, 'UTF-8'),
			'hero_preview' => $heroUrl
				? htmlspecialchars($heroUrl, ENT_QUOTES, 'UTF-8')
				: 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?w=800&h=500&fit=crop&auto=format&q=80',
			'tem_logo' => $logoUrl ? '1' : '0',
			'tem_hero' => $heroUrl ? '1' : '0',
			'btn_logo_disabled' => $logoUrl ? '' : 'disabled',
			'btn_hero_disabled' => $heroUrl ? '' : 'disabled',
		]);
		return parent::getPanel('Portal do aluno — Master', $content, 'portal');
	}

	public static function salvar($request) {
		if (!PortalAlunoBranding::tabelasExistem()) {
			return json_encode([
				'success' => false,
				'message' => 'Execute database/portal_aluno_branding.sql no phpMyAdmin.',
			]);
		}

		$post = $request->getPostVars();
		$files = $request->getFileVars();
		$row = PortalAlunoBranding::get();

		$restaurarLogo = !empty($post['restaurar_logo']);
		$restaurarHero = !empty($post['restaurar_hero']);

		if ($restaurarLogo) {
			$row->logo = null;
		} else {
			$row->logo = BrandingHelper::processarUploadPortalLogo($files['logo'] ?? null, $row->logo ?? null);
		}

		if ($restaurarHero) {
			$row->login_hero = null;
		} else {
			$row->login_hero = BrandingHelper::processarUploadPortalLoginHero($files['login_hero'] ?? null, $row->login_hero ?? null);
		}

		if (!$row->salvar()) {
			return json_encode(['success' => false, 'message' => 'Falha ao salvar.']);
		}

		$logoUrl = BrandingHelper::urlPortalLogo($row->logo ?? null);
		$heroUrl = BrandingHelper::urlPortalLoginHero($row->login_hero ?? null);

		return json_encode([
			'success' => true,
			'message' => 'Marca do portal atualizada.',
			'logoUrl' => $logoUrl,
			'loginHeroUrl' => $heroUrl,
		], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}
}

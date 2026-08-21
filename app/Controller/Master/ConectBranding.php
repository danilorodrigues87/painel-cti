<?php

namespace App\Controller\Master;

use App\Utils\View;
use App\Model\Entity\CjPortalBranding;
use App\Common\Helpers\BrandingHelper;
use App\Common\Helpers\ConectRedesSociaisHelper;

class ConectBranding extends Page {

	private const HERO_PADRAO = 'https://images.unsplash.com/photo-1521737711862-ea3e097405db?w=1200&h=675&fit=crop&auto=format&q=80';

	public static function index($request) {
		if (!CjPortalBranding::tabelasExistem()) {
			$content = View::render('master/modules/conect-branding/sql', []);
			return parent::getPanel('Conecta Jovem — Marca', $content, 'conect-branding');
		}

		$row = CjPortalBranding::get();
		$logoUrl = BrandingHelper::urlConectLogo($row->logo ?? null);
		$heroUrl = BrandingHelper::urlConectHero($row->hero_image ?? null);
		$redes = ConectRedesSociaisHelper::decode($row->redes_sociais_json ?? null);

		$content = View::render('master/modules/conect-branding/index', [
			'nome_portal'         => htmlspecialchars((string)($row->nome_portal ?? 'Conecta Jovem'), ENT_QUOTES, 'UTF-8'),
			'texto_institucional' => htmlspecialchars((string)($row->texto_institucional ?? ''), ENT_QUOTES, 'UTF-8'),
			'redes_linkedin'      => htmlspecialchars($redes['linkedin'] ?? '', ENT_QUOTES, 'UTF-8'),
			'redes_instagram'     => htmlspecialchars($redes['instagram'] ?? '', ENT_QUOTES, 'UTF-8'),
			'redes_github'        => htmlspecialchars($redes['github'] ?? '', ENT_QUOTES, 'UTF-8'),
			'redes_portfolio'     => htmlspecialchars($redes['portfolio'] ?? '', ENT_QUOTES, 'UTF-8'),
			'redes_facebook'      => htmlspecialchars($redes['facebook'] ?? '', ENT_QUOTES, 'UTF-8'),
			'redes_tiktok'        => htmlspecialchars($redes['tiktok'] ?? '', ENT_QUOTES, 'UTF-8'),
			'logo_preview'        => $logoUrl
				? htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8')
				: 'https://conectajovem.com.br/logo-conect-jovem.png',
			'hero_preview'        => $heroUrl
				? htmlspecialchars($heroUrl, ENT_QUOTES, 'UTF-8')
				: self::HERO_PADRAO,
			'tem_logo'            => $logoUrl ? '1' : '0',
			'tem_hero'            => $heroUrl ? '1' : '0',
			'btn_logo_disabled'   => $logoUrl ? '' : 'disabled',
			'btn_hero_disabled'   => $heroUrl ? '' : 'disabled',
		]);
		return parent::getPanel('Conecta Jovem — Marca', $content, 'conect-branding');
	}

	public static function salvar($request) {
		if (!CjPortalBranding::tabelasExistem()) {
			return json_encode([
				'success' => false,
				'message' => 'Execute database/conect_jovem.sql no phpMyAdmin.',
			]);
		}

		$post = $request->getPostVars();
		$files = $request->getFileVars();
		$row = CjPortalBranding::get();

		$row->nome_portal = trim((string)($post['nome_portal'] ?? ''));
		if ($row->nome_portal === '') {
			$row->nome_portal = 'Conecta Jovem';
		}
		$row->texto_institucional = trim((string)($post['texto_institucional'] ?? ''));

		$redesInput = [
			'linkedin'  => (string)($post['redes_linkedin'] ?? ''),
			'instagram' => (string)($post['redes_instagram'] ?? ''),
			'github'    => (string)($post['redes_github'] ?? ''),
			'portfolio' => (string)($post['redes_portfolio'] ?? ''),
			'facebook'  => (string)($post['redes_facebook'] ?? ''),
			'tiktok'    => (string)($post['redes_tiktok'] ?? ''),
		];
		$row->redes_sociais_json = ConectRedesSociaisHelper::encode(
			ConectRedesSociaisHelper::sanitizar($redesInput)
		);

		$restaurarLogo = !empty($post['restaurar_logo']);
		$restaurarHero = !empty($post['restaurar_hero']);

		if ($restaurarLogo) {
			$row->logo = null;
		} else {
			$row->logo = BrandingHelper::processarUploadConectLogo($files['logo'] ?? null, $row->logo ?? null);
		}

		if ($restaurarHero) {
			$row->hero_image = null;
		} else {
			$row->hero_image = BrandingHelper::processarUploadConectHero($files['hero_image'] ?? null, $row->hero_image ?? null);
		}

		if (!$row->salvar()) {
			return json_encode(['success' => false, 'message' => 'Falha ao salvar.']);
		}

		return json_encode([
			'success'            => true,
			'message'            => 'Marca do Conecta Jovem atualizada.',
			'logoUrl'            => BrandingHelper::urlConectLogo($row->logo ?? null),
			'heroImageUrl'       => BrandingHelper::urlConectHero($row->hero_image ?? null),
			'nomePortal'         => $row->nome_portal,
			'textoInstitucional' => (string)($row->texto_institucional ?? ''),
		], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}
}

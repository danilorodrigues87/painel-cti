<?php

namespace App\Controller\Master;

use App\Utils\View;
use App\Model\Entity\SiteB2bBranding;
use App\Common\Helpers\BrandingHelper;
use App\Common\Helpers\ConectRedesSociaisHelper;
use App\Common\Helpers\SiteApiMapper;

class SiteBranding extends Page {

	private const HERO_PADRAO = 'https://images.unsplash.com/photo-1524178232363-1fb2b075b655?w=1200&h=675&fit=crop&auto=format&q=80';

	public static function index($request) {
		if (!SiteB2bBranding::tabelasExistem()) {
			$content = View::render('master/modules/site-branding/sql', []);
			return parent::getPanel('Site CTI — Marca', $content, 'site-branding');
		}

		$row = SiteB2bBranding::get();
		$heroUrl = BrandingHelper::urlSiteB2bHero($row->hero_image ?? null);
		$redes = ConectRedesSociaisHelper::decode($row->redes_sociais_json ?? null);
		$rawRedes = json_decode((string)($row->redes_sociais_json ?? ''), true);
		$youtube = is_array($rawRedes) && !empty($rawRedes['youtube']) ? (string)$rawRedes['youtube'] : '';

		$content = View::render('master/modules/site-branding/index', [
			'hero_titulo'           => htmlspecialchars((string)($row->hero_titulo ?? ''), ENT_QUOTES, 'UTF-8'),
			'hero_subtitulo'        => htmlspecialchars((string)($row->hero_subtitulo ?? ''), ENT_QUOTES, 'UTF-8'),
			'hero_cta_texto'        => htmlspecialchars((string)($row->hero_cta_texto ?? 'Solicitar demonstração'), ENT_QUOTES, 'UTF-8'),
			'hero_cta_link'         => htmlspecialchars((string)($row->hero_cta_link ?? '/contato'), ENT_QUOTES, 'UTF-8'),
			'texto_institucional'   => htmlspecialchars((string)($row->texto_institucional ?? ''), ENT_QUOTES, 'UTF-8'),
			'telefone'              => htmlspecialchars((string)($row->telefone ?? ''), ENT_QUOTES, 'UTF-8'),
			'email'                 => htmlspecialchars((string)($row->email ?? ''), ENT_QUOTES, 'UTF-8'),
			'whatsapp'              => htmlspecialchars((string)($row->whatsapp ?? ''), ENT_QUOTES, 'UTF-8'),
			'link_alunos'           => htmlspecialchars((string)($row->link_alunos ?? ''), ENT_QUOTES, 'UTF-8'),
			'stat_escolas'          => htmlspecialchars((string)($row->stat_escolas ?? '10+'), ENT_QUOTES, 'UTF-8'),
			'stat_anos'             => htmlspecialchars((string)($row->stat_anos ?? '15+'), ENT_QUOTES, 'UTF-8'),
			'stat_modulos'          => htmlspecialchars((string)($row->stat_modulos ?? '30+'), ENT_QUOTES, 'UTF-8'),
			'meta_title'            => htmlspecialchars((string)($row->meta_title ?? ''), ENT_QUOTES, 'UTF-8'),
			'meta_description'      => htmlspecialchars((string)($row->meta_description ?? ''), ENT_QUOTES, 'UTF-8'),
			'catalogo_em_breve_chk' => !empty($row->catalogo_cti_em_breve) ? 'checked' : '',
			'redes_facebook'        => htmlspecialchars($redes['facebook'] ?? '', ENT_QUOTES, 'UTF-8'),
			'redes_instagram'       => htmlspecialchars($redes['instagram'] ?? '', ENT_QUOTES, 'UTF-8'),
			'redes_linkedin'        => htmlspecialchars($redes['linkedin'] ?? '', ENT_QUOTES, 'UTF-8'),
			'redes_youtube'         => htmlspecialchars($youtube, ENT_QUOTES, 'UTF-8'),
			'hero_preview'          => $heroUrl
				? htmlspecialchars($heroUrl, ENT_QUOTES, 'UTF-8')
				: self::HERO_PADRAO,
			'tem_hero'              => $heroUrl ? '1' : '0',
			'btn_hero_disabled'     => $heroUrl ? '' : 'disabled',
		]);
		return parent::getPanel('Site CTI — Marca', $content, 'site-branding');
	}

	public static function salvar($request) {
		if (!SiteB2bBranding::tabelasExistem()) {
			return json_encode([
				'success' => false,
				'message' => 'Execute database/site_b2b_branding.sql no phpMyAdmin.',
			]);
		}

		$post = $request->getPostVars();
		$files = $request->getFileVars();
		$row = SiteB2bBranding::get();

		$row->hero_titulo = trim((string)($post['hero_titulo'] ?? ''));
		$row->hero_subtitulo = trim((string)($post['hero_subtitulo'] ?? ''));
		$row->hero_cta_texto = trim((string)($post['hero_cta_texto'] ?? ''));
		$row->hero_cta_link = trim((string)($post['hero_cta_link'] ?? ''));
		$row->texto_institucional = trim((string)($post['texto_institucional'] ?? ''));
		$row->telefone = trim((string)($post['telefone'] ?? ''));
		$row->email = trim((string)($post['email'] ?? ''));
		$row->whatsapp = trim((string)($post['whatsapp'] ?? ''));
		$row->link_alunos = trim((string)($post['link_alunos'] ?? ''));
		$row->stat_escolas = trim((string)($post['stat_escolas'] ?? ''));
		$row->stat_anos = trim((string)($post['stat_anos'] ?? ''));
		$row->stat_modulos = trim((string)($post['stat_modulos'] ?? ''));
		$row->meta_title = trim((string)($post['meta_title'] ?? ''));
		$row->meta_description = trim((string)($post['meta_description'] ?? ''));
		$row->catalogo_cti_em_breve = !empty($post['catalogo_cti_em_breve']) ? 1 : 0;

		$redes = ConectRedesSociaisHelper::sanitizar([
			'facebook'  => (string)($post['redes_facebook'] ?? ''),
			'instagram' => (string)($post['redes_instagram'] ?? ''),
			'linkedin'  => (string)($post['redes_linkedin'] ?? ''),
		]);
		$youtube = trim((string)($post['redes_youtube'] ?? ''));
		if ($youtube !== '') {
			if (!preg_match('#^https?://#i', $youtube)) {
				$youtube = 'https://'.$youtube;
			}
			if (filter_var($youtube, FILTER_VALIDATE_URL)) {
				$redes['youtube'] = $youtube;
			}
		}
		$row->redes_sociais_json = json_encode(array_filter($redes), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

		if (!empty($post['restaurar_hero'])) {
			$row->hero_image = null;
		} else {
			$row->hero_image = BrandingHelper::processarUploadSiteB2bHero($files['hero_image'] ?? null, $row->hero_image ?? null);
		}

		if (!$row->salvar()) {
			return json_encode(['success' => false, 'message' => 'Falha ao salvar.']);
		}

		$mapped = SiteApiMapper::brandingFromEntity($row);
		return json_encode([
			'success'  => true,
			'message'  => 'Site CTI atualizado. Atualize ctieducacional.com.br (Ctrl+F5).',
			'branding' => $mapped,
		], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}
}

<?php

namespace App\Common\Helpers;

use App\Model\Entity\EscolaIntegracoes;
use App\Model\Entity\SocialPost;
use App\Model\Entity\SocialPostMidia;

/**
 * Publica posts agendados via Graph API e limpa mídia local.
 */
class SocialPublishService {

	/**
	 * @return array{processados:int,ok:int,erro:int,detalhes:array}
	 */
	public static function processar(int $idAdmin = 0, int $limite = 10): array {
		$resumo = ['processados' => 0, 'ok' => 0, 'erro' => 0, 'detalhes' => []];
		if (!SocialPost::tabelaExiste()) {
			$resumo['detalhes'][] = 'Tabela social_posts ausente.';
			return $resumo;
		}
		foreach (SocialPost::listProntosParaPublicar($limite, $idAdmin) as $post) {
			$resumo['processados']++;
			$r = self::publicarUm($post);
			if (!empty($r['ok'])) {
				$resumo['ok']++;
			} else {
				$resumo['erro']++;
			}
			$resumo['detalhes'][] = [
				'id' => (int)$post->id,
				'ok' => !empty($r['ok']),
				'message' => $r['message'] ?? '',
			];
		}
		return $resumo;
	}

	/**
	 * @return array{ok:bool,message?:string}
	 */
	public static function publicarUm(SocialPost $post): array {
		if (!$post->claimPublicando()) {
			return ['ok' => false, 'message' => 'Já em processamento ou não agendado.'];
		}

		$cfg = EscolaIntegracoes::getByIdAdmin((int)$post->id_admin);
		if (!$cfg instanceof EscolaIntegracoes || !EscolaIntegracoes::temColunasMeta()) {
			return self::falha($post, 'Meta não configurado (SQL/colunas).');
		}
		$token = $cfg->getMetaPageTokenDescriptografada();
		$pageId = trim((string)($cfg->meta_page_id ?? ''));
		$igId = trim((string)($cfg->meta_ig_user_id ?? ''));
		if (!$token || $pageId === '') {
			return self::falha($post, 'Page/token ausentes. Reconecte em Configurações → Redes sociais.');
		}

		$midias = SocialPostMidia::listByPost((int)$post->id, (int)$post->id_admin);
		$primeira = $midias[0] ?? null;
		$url = $primeira ? $primeira->urlPublica() : '';
		if ($url === '') {
			return self::falha($post, 'Post sem mídia (upload ou URL).');
		}
		if (($primeira->tipo ?? 'image') !== 'image') {
			return self::falha($post, 'Fase 1: apenas imagem. Vídeo/Reels na Fase 3.');
		}

		$caption = (string)($post->caption ?? '');
		$canais = (string)$post->canais;
		$querFb = $canais === 'facebook' || $canais === 'ambos';
		$querIg = $canais === 'instagram' || $canais === 'ambos';

		if ($querFb && (int)$cfg->meta_fb_ativo !== 1) {
			return self::falha($post, 'Facebook desativado nas configurações da escola.');
		}
		if ($querIg && (int)$cfg->meta_ig_ativo !== 1) {
			return self::falha($post, 'Instagram desativado nas configurações da escola.');
		}
		if ($querIg && $igId === '') {
			return self::falha($post, 'Instagram User ID ausente. Reconecte a conta.');
		}

		$erros = [];
		if ($querFb) {
			$fb = MetaGraphHelper::publicarFotoPage($pageId, $token, $url, $caption);
			if (empty($fb['ok'])) {
				if (!empty($fb['auth_error'])) {
					return self::falha($post, 'Token Meta inválido/revogado. Reconecte a conta. '.($fb['message'] ?? ''));
				}
				$erros[] = 'FB: '.($fb['message'] ?? 'falha');
			} else {
				$post->fb_post_id = (string)($fb['id'] ?? '');
			}
		}
		if ($querIg) {
			$ig = MetaGraphHelper::publicarImagemInstagram($igId, $token, $url, $caption);
			if (empty($ig['ok'])) {
				if (!empty($ig['auth_error'])) {
					return self::falha($post, 'Token Meta inválido/revogado. Reconecte a conta. '.($ig['message'] ?? ''));
				}
				$erros[] = 'IG: '.($ig['message'] ?? 'falha');
			} else {
				$post->ig_media_id = (string)($ig['id'] ?? '');
			}
		}

		if ($erros && !$post->fb_post_id && !$post->ig_media_id) {
			return self::falha($post, implode(' | ', $erros));
		}

		$post->status = 'publicado';
		$post->publicado_em = date('Y-m-d H:i:s');
		$post->erro_msg = $erros ? implode(' | ', $erros) : null;
		$post->salvar();

		foreach ($midias as $m) {
			if (!empty($m->path_local)) {
				SocialMediaStorage::apagar((string)$m->path_local);
				$m->path_local = null;
				$m->salvar();
			}
		}

		return [
			'ok' => true,
			'message' => $erros ? 'Publicado parcialmente: '.implode(' | ', $erros) : 'Publicado.',
		];
	}

	private static function falha(SocialPost $post, string $msg): array {
		$post->status = 'erro';
		$post->erro_msg = mb_substr($msg, 0, 500);
		$post->salvar();
		return ['ok' => false, 'message' => $msg];
	}
}

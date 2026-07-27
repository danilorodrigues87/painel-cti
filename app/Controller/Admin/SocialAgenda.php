<?php

namespace App\Controller\Admin;

use App\Utils\View;
use App\Session\User\Login as SessionUser;
use App\Common\Helpers\TenantHelper;
use App\Common\Helpers\ModuleGateHelper;
use App\Common\Helpers\SocialMediaStorage;
use App\Common\Helpers\SocialPublishService;
use App\Model\Entity\EscolaIntegracoes;
use App\Model\Entity\SocialPost;
use App\Model\Entity\SocialPostMidia;

class SocialAgenda extends Page {

	private static function assertAcesso($request, bool $api = false): bool {
		$user = SessionUser::getUserLogedData();
		$idAdmin = (int)($user['usuario']['id_admin'] ?? 0);
		$mods = ModuleGateHelper::getModulosEfetivos($idAdmin, $user['usuario']['acesso'] ?? []);
		if (!in_array('social', ModuleGateHelper::getSlugsEscola($idAdmin), true)
			|| !in_array('Redes sociais', $mods, true)) {
			if (!$api) {
				$request->getRouter()->redirect('/painel');
			}
			return false;
		}
		return true;
	}

	private static function json(array $data): string {
		return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}

	public static function index($request) {
		if (!self::assertAcesso($request)) {
			return '';
		}
		$content = View::render('admin/modules/social/agenda', []);
		return parent::getPanel('Redes sociais', $content, 'social', $request);
	}

	public static function getInfo($request) {
		if (!self::assertAcesso($request, true)) {
			return self::json(['success' => false, 'message' => 'Acesso negado.']);
		}
		if (!SocialPost::tabelaExiste()) {
			return self::json([
				'success' => false,
				'sql_ok' => false,
				'message' => 'Execute database/social_posts.sql (e escola_integracoes_meta.sql) no phpMyAdmin.',
			]);
		}
		$post = $request->getPostVars();
		$acao = $post['acao'] ?? '';
		if ($acao === 'semana') {
			return self::semana($post);
		}
		if ($acao === 'salvar') {
			return self::salvar($post);
		}
		if ($acao === 'cancelar') {
			return self::cancelar((int)($post['id'] ?? 0));
		}
		if ($acao === 'publicar_agora') {
			return self::publicarAgora((int)($post['id'] ?? 0));
		}
		if ($acao === 'worker') {
			return self::rodarWorker();
		}
		if ($acao === 'status_meta') {
			return self::statusMeta();
		}
		return self::json(['success' => false, 'message' => 'Ação inválida.']);
	}

	public static function upload($request) {
		if (!self::assertAcesso($request, true)) {
			return self::json(['success' => false, 'message' => 'Acesso negado.']);
		}
		$idAdmin = TenantHelper::getIdAdmin();
		$file = $_FILES['arquivo'] ?? null;
		if (!is_array($file)) {
			return self::json(['success' => false, 'message' => 'Arquivo ausente.']);
		}
		$saved = SocialMediaStorage::salvarUpload($idAdmin, $file);
		if (!$saved) {
			return self::json(['success' => false, 'message' => 'Upload inválido (use imagem ≤8MB ou vídeo ≤100MB).']);
		}
		return self::json([
			'success' => true,
			'path' => $saved['relative'],
			'url' => $saved['url'],
			'tipo' => $saved['tipo'],
			'mime' => $saved['mime'],
			'bytes' => $saved['bytes'],
		]);
	}

	private static function statusMeta(): string {
		$idAdmin = TenantHelper::getIdAdmin();
		$cfg = EscolaIntegracoes::getByIdAdmin($idAdmin);
		return self::json([
			'success' => true,
			'sql_meta' => EscolaIntegracoes::temColunasMeta(),
			'pronto' => $cfg instanceof EscolaIntegracoes && $cfg->temMetaPronto(),
			'fb' => $cfg instanceof EscolaIntegracoes ? (int)$cfg->meta_fb_ativo : 0,
			'ig' => $cfg instanceof EscolaIntegracoes ? (int)$cfg->meta_ig_ativo : 0,
			'page_name' => $cfg instanceof EscolaIntegracoes ? (string)($cfg->meta_page_name ?? '') : '',
			'ig_username' => $cfg instanceof EscolaIntegracoes ? (string)($cfg->meta_ig_username ?? '') : '',
		]);
	}

	private static function semana(array $post): string {
		$idAdmin = TenantHelper::getIdAdmin();
		$inicio = trim((string)($post['inicio'] ?? ''));
		if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $inicio)) {
			// segunda da semana atual
			$dt = new \DateTimeImmutable('now', new \DateTimeZone('America/Sao_Paulo'));
			$n = (int)$dt->format('N'); // 1=seg
			$inicio = $dt->modify('-'.($n - 1).' days')->format('Y-m-d');
		}
		$ini = new \DateTimeImmutable($inicio.' 00:00:00', new \DateTimeZone('America/Sao_Paulo'));
		$fim = $ini->modify('+6 days')->format('Y-m-d');
		$inicio = $ini->format('Y-m-d');

		$itens = [];
		foreach (SocialPost::listSemana($idAdmin, $inicio, $fim) as $p) {
			$midias = [];
			foreach (SocialPostMidia::listByPost((int)$p->id, $idAdmin) as $m) {
				$midias[] = [
					'id' => (int)$m->id,
					'tipo' => $m->tipo,
					'url' => $m->urlPublica(),
					'path' => $m->path_local,
				];
			}
			$itens[] = [
				'id' => (int)$p->id,
				'canais' => $p->canais,
				'formato' => (string)($p->formato ?? 'feed'),
				'caption' => (string)$p->caption,
				'status' => $p->status,
				'agendado_em' => $p->agendado_em,
				'publicado_em' => $p->publicado_em,
				'erro_msg' => $p->erro_msg,
				'fb_post_id' => $p->fb_post_id,
				'ig_media_id' => $p->ig_media_id,
				'midias' => $midias,
			];
		}

		return self::json([
			'success' => true,
			'sql_ok' => true,
			'inicio' => $inicio,
			'fim' => $fim,
			'itens' => $itens,
		]);
	}

	private static function salvar(array $post): string {
		$idAdmin = TenantHelper::getIdAdmin();
		$user = SessionUser::getUserLogedData();
		$id = (int)($post['id'] ?? 0);
		$caption = trim((string)($post['caption'] ?? ''));
		$canais = (string)($post['canais'] ?? 'ambos');
		if (!in_array($canais, ['facebook', 'instagram', 'ambos'], true)) {
			$canais = 'ambos';
		}
		$formato = (string)($post['formato'] ?? 'feed');
		if (!in_array($formato, ['feed', 'story', 'reel', 'carousel'], true)) {
			$formato = 'feed';
		}
		if (!SocialPost::temColunaFormato() && $formato !== 'feed') {
			return self::json([
				'success' => false,
				'message' => 'Execute database/social_posts_formato.sql no phpMyAdmin para Story/Reel/Carrossel.',
			]);
		}
		if ($formato === 'story' || $formato === 'reel') {
			$canais = 'instagram';
		}

		$agendado = trim((string)($post['agendado_em'] ?? ''));
		if ($agendado !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}/', $agendado)) {
			return self::json(['success' => false, 'message' => 'Data/hora inválida.']);
		}
		$agendadoSql = $agendado !== ''
			? str_replace('T', ' ', substr($agendado, 0, 16)).':00'
			: null;
		$status = trim((string)($post['status'] ?? 'agendado'));
		if (!in_array($status, ['rascunho', 'agendado'], true)) {
			$status = 'agendado';
		}
		if ($status === 'agendado' && !$agendadoSql) {
			return self::json(['success' => false, 'message' => 'Informe data/hora para agendar.']);
		}

		// midias: JSON [{path,url_externa,tipo}] ou legado path_local / url_externa único
		$listaMidias = [];
		if (!empty($post['midias'])) {
			$decoded = is_string($post['midias']) ? json_decode((string)$post['midias'], true) : $post['midias'];
			if (is_array($decoded)) {
				foreach ($decoded as $row) {
					if (!is_array($row)) {
						continue;
					}
					$path = trim((string)($row['path'] ?? $row['path_local'] ?? ''));
					$urlExt = trim((string)($row['url_externa'] ?? $row['url'] ?? ''));
					$tipo = trim((string)($row['tipo'] ?? 'image'));
					if (!in_array($tipo, ['image', 'video'], true)) {
						$tipo = 'image';
					}
					if ($path === '' && $urlExt === '') {
						continue;
					}
					$listaMidias[] = ['path' => $path, 'url_externa' => $urlExt, 'tipo' => $tipo];
				}
			}
		}
		if (!$listaMidias) {
			$path = trim((string)($post['path_local'] ?? ''));
			$urlExt = trim((string)($post['url_externa'] ?? ''));
			$tipo = trim((string)($post['tipo_midia'] ?? 'image'));
			if (!in_array($tipo, ['image', 'video'], true)) {
				$tipo = 'image';
			}
			if ($path !== '' || $urlExt !== '') {
				$listaMidias[] = ['path' => $path, 'url_externa' => $urlExt, 'tipo' => $tipo];
			}
		}

		$nMid = count($listaMidias);
		if ($formato === 'carousel' && $nMid > 0 && ($nMid < 2 || $nMid > 10)) {
			return self::json(['success' => false, 'message' => 'Carrossel exige de 2 a 10 mídias.']);
		}
		if ($formato === 'reel' && $nMid > 0 && ($listaMidias[0]['tipo'] ?? '') !== 'video') {
			return self::json(['success' => false, 'message' => 'Reel exige um vídeo.']);
		}
		if ($formato === 'feed' && $nMid > 0 && ($listaMidias[0]['tipo'] ?? '') !== 'image') {
			return self::json(['success' => false, 'message' => 'Feed exige imagem. Use Reel para vídeo.']);
		}

		if ($id > 0) {
			$ob = SocialPost::getById($id, $idAdmin);
			if (!$ob || !in_array($ob->status, ['rascunho', 'agendado', 'erro'], true)) {
				return self::json(['success' => false, 'message' => 'Post não editável.']);
			}
		} else {
			$ob = new SocialPost();
			$ob->id_admin = $idAdmin;
			$ob->created_by = (int)($user['usuario']['id'] ?? 0) ?: null;
		}

		$ob->caption = $caption;
		$ob->canais = $canais;
		$ob->formato = $formato;
		$ob->agendado_em = $agendadoSql;
		$ob->status = $status === 'agendado' ? 'agendado' : 'rascunho';
		$ob->erro_msg = null;
		$ob->salvar();

		$existentes = SocialPostMidia::listByPost((int)$ob->id, $idAdmin);
		if ($listaMidias) {
			foreach ($existentes as $m) {
				if (!empty($m->path_local)) {
					SocialMediaStorage::apagar((string)$m->path_local);
				}
				$m->excluir();
			}
			foreach ($listaMidias as $ord => $row) {
				$m = new SocialPostMidia();
				$m->id_post = (int)$ob->id;
				$m->id_admin = $idAdmin;
				$m->ordem = $ord;
				$m->tipo = $row['tipo'];
				if ($row['path'] !== '') {
					$m->path_local = $row['path'];
					$m->url_externa = null;
				} else {
					$m->url_externa = $row['url_externa'];
				}
				$m->salvar();
			}
		} elseif (!$existentes && $status === 'agendado') {
			return self::json(['success' => false, 'message' => 'Envie mídia (upload ou URL HTTPS).']);
		}

		$lote = [];
		if (!empty($post['lote_horarios'])) {
			$decoded = json_decode((string)$post['lote_horarios'], true);
			if (is_array($decoded)) {
				$lote = $decoded;
			}
		}
		$criados = [(int)$ob->id];
		$midiasRef = SocialPostMidia::listByPost((int)$ob->id, $idAdmin);
		foreach ($lote as $h) {
			$h = trim((string)$h);
			if (!preg_match('/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}/', $h)) {
				continue;
			}
			$hsql = str_replace('T', ' ', substr($h, 0, 16)).':00';
			$novo = new SocialPost();
			$novo->id_admin = $idAdmin;
			$novo->created_by = $ob->created_by;
			$novo->caption = $caption;
			$novo->canais = $canais;
			$novo->formato = $formato;
			$novo->agendado_em = $hsql;
			$novo->status = 'agendado';
			$novo->salvar();
			foreach ($midiasRef as $ord => $src) {
				$nm = new SocialPostMidia();
				$nm->id_post = (int)$novo->id;
				$nm->id_admin = $idAdmin;
				$nm->tipo = $src->tipo;
				$nm->ordem = $ord;
				if (!empty($src->path_local)) {
					$copy = SocialMediaStorage::copiarParaEscola($idAdmin, (string)$src->path_local);
					$nm->path_local = $copy ? $copy['relative'] : $src->path_local;
					$nm->mime = $copy['mime'] ?? $src->mime;
					$nm->bytes = $copy['bytes'] ?? $src->bytes;
				} else {
					$nm->url_externa = $src->url_externa;
				}
				$nm->salvar();
			}
			$criados[] = (int)$novo->id;
		}

		return self::json([
			'success' => true,
			'message' => count($criados) > 1
				? (count($criados).' posts agendados.')
				: 'Post salvo.',
			'ids' => $criados,
		]);
	}

	private static function cancelar(int $id): string {
		$idAdmin = TenantHelper::getIdAdmin();
		$ob = SocialPost::getById($id, $idAdmin);
		if (!$ob || !in_array($ob->status, ['rascunho', 'agendado', 'erro'], true)) {
			return self::json(['success' => false, 'message' => 'Não é possível cancelar.']);
		}
		foreach (SocialPostMidia::listByPost($id, $idAdmin) as $m) {
			if (!empty($m->path_local)) {
				SocialMediaStorage::apagar((string)$m->path_local);
			}
			$m->excluir();
		}
		$ob->status = 'cancelado';
		$ob->salvar();
		return self::json(['success' => true, 'message' => 'Cancelado.']);
	}

	private static function publicarAgora(int $id): string {
		$idAdmin = TenantHelper::getIdAdmin();
		$ob = SocialPost::getById($id, $idAdmin);
		if (!$ob) {
			return self::json(['success' => false, 'message' => 'Post não encontrado.']);
		}
		if (!in_array($ob->status, ['agendado', 'erro', 'rascunho'], true)) {
			return self::json(['success' => false, 'message' => 'Status não permite publicar agora.']);
		}
		$ob->status = 'agendado';
		$ob->agendado_em = date('Y-m-d H:i:s');
		$ob->erro_msg = null;
		$ob->salvar();
		$r = SocialPublishService::publicarUm($ob);
		return self::json([
			'success' => !empty($r['ok']),
			'message' => $r['message'] ?? '',
		]);
	}

	private static function rodarWorker(): string {
		$idAdmin = TenantHelper::getIdAdmin();
		$resumo = SocialPublishService::processar($idAdmin, 20);
		return self::json(['success' => true, 'resumo' => $resumo]);
	}
}

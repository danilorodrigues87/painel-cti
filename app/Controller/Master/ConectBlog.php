<?php

namespace App\Controller\Master;

use App\Common\Helpers\BrandingHelper;
use App\Common\Helpers\ConectApiMapper;
use App\Common\Helpers\ConectBlogSanitizer;
use App\Model\Entity\CjBlogCategoria;
use App\Model\Entity\CjBlogComentario;
use App\Model\Entity\CjBlogPost;
use App\Utils\View;

class ConectBlog extends Page {

	public static function index($request): string {
		if (!CjBlogPost::tabelaExiste()) {
			$content = View::render('master/modules/conect-blog/sql', []);
			return parent::getPanel('Conecta Jovem — Blog', $content, 'conect-blog');
		}

		$posts = CjBlogPost::queryLista(['status_any' => true, 'limit' => 100]);
		$rows = '';
		foreach ($posts as $p) {
			$status = (string)($p['status'] ?? 'rascunho');
			$badge = $status === 'publicado'
				? '<span class="badge bg-success">Publicado</span>'
				: '<span class="badge bg-secondary">Rascunho</span>';
			$cat = htmlspecialchars((string)($p['categoria_nome'] ?? '—'));
			$titulo = htmlspecialchars((string)($p['titulo'] ?? ''));
			$slug = htmlspecialchars((string)($p['slug'] ?? ''));
			$id = (int)$p['id'];
			$pub = htmlspecialchars((string)($p['publicado_em'] ?? $p['created_at'] ?? ''));
			$acaoPub = $status === 'publicado'
				? '<form method="post" action="'.URL.'/master/conect-blog/post/'.$id.'/despublicar" class="d-inline">'
					.'<button type="submit" class="btn btn-sm btn-outline-warning">Despublicar</button></form>'
				: '<form method="post" action="'.URL.'/master/conect-blog/post/'.$id.'/publicar" class="d-inline">'
					.'<button type="submit" class="btn btn-sm btn-success">Publicar</button></form>';
			$rows .= '<tr>'
				.'<td>'.$titulo.'<br><small class="text-muted">/'.$slug.'</small></td>'
				.'<td>'.$cat.'</td>'
				.'<td>'.$badge.'</td>'
				.'<td class="small text-muted">'.$pub.'</td>'
				.'<td class="text-nowrap">'
				.'<a href="'.URL.'/master/conect-blog/editar/'.$id.'" class="btn btn-sm btn-outline-primary me-1">Editar</a>'
				.$acaoPub
				.' <form method="post" action="'.URL.'/master/conect-blog/post/'.$id.'/excluir" class="d-inline" onsubmit="return confirm(\'Excluir este artigo?\');">'
				.'<button type="submit" class="btn btn-sm btn-outline-danger">Excluir</button></form>'
				.'</td></tr>';
		}
		if ($rows === '') {
			$rows = '<tr><td colspan="5" class="text-muted">Nenhum artigo ainda.</td></tr>';
		}

		$comRows = '';
		if (CjBlogComentario::tabelaExiste()) {
			foreach (CjBlogComentario::listarRecentes(30) as $c) {
				$comRows .= '<tr>'
					.'<td>'.htmlspecialchars((string)($c['nome_exibicao'] ?? '')).'</td>'
					.'<td><a href="'.URL.'/master/conect-blog/editar/'.(int)($c['id_post'] ?? 0).'">'
					.htmlspecialchars((string)($c['post_titulo'] ?? '')).'</a></td>'
					.'<td class="small">'.htmlspecialchars(mb_substr((string)($c['texto'] ?? ''), 0, 120)).'…</td>'
					.'<td class="small text-muted">'.htmlspecialchars((string)($c['created_at'] ?? '')).'</td>'
					.'<td><form method="post" action="'.URL.'/master/conect-blog/comentario/'.(int)$c['id'].'/remover" class="d-inline">'
					.'<button type="submit" class="btn btn-sm btn-outline-danger">Remover</button></form></td>'
					.'</tr>';
			}
		}
		if ($comRows === '') {
			$comRows = '<tr><td colspan="5" class="text-muted">Nenhum comentário.</td></tr>';
		}

		$content = View::render('master/modules/conect-blog/index', [
			'rows_posts'      => $rows,
			'rows_comentarios'=> $comRows,
			'total_posts'     => count($posts),
		]);
		return parent::getPanel('Conecta Jovem — Blog', $content, 'conect-blog');
	}

	public static function editar($request, int $id): string {
		if (!CjBlogPost::tabelaExiste()) {
			header('Location: '.URL.'/master/conect-blog');
			exit;
		}
		$post = $id > 0 ? CjBlogPost::getById($id) : null;
		$categorias = CjBlogCategoria::listarTodas();
		$optsCat = '';
		$catSel = (int)($post['id_categoria'] ?? 0);
		foreach ($categorias as $cat) {
			$sel = (int)$cat['id'] === $catSel ? ' selected' : '';
			$optsCat .= '<option value="'.(int)$cat['id'].'"'.$sel.'>'
				.htmlspecialchars((string)$cat['nome']).'</option>';
		}

		$capaUrl = BrandingHelper::urlConectBlogImagem($post['capa'] ?? null);
		$st = (string)($post['status'] ?? 'rascunho');
		$content = View::render('master/modules/conect-blog/edit', [
			'id'               => $id,
			'titulo'           => htmlspecialchars((string)($post['titulo'] ?? ''), ENT_QUOTES, 'UTF-8'),
			'slug'             => htmlspecialchars((string)($post['slug'] ?? ''), ENT_QUOTES, 'UTF-8'),
			'resumo'           => htmlspecialchars((string)($post['resumo'] ?? ''), ENT_QUOTES, 'UTF-8'),
			'corpo_html'       => (string)($post['corpo_html'] ?? ''),
			'autor_nome'       => htmlspecialchars((string)($post['autor_nome'] ?? 'Conecta Jovem'), ENT_QUOTES, 'UTF-8'),
			'meta_title'       => htmlspecialchars((string)($post['meta_title'] ?? ''), ENT_QUOTES, 'UTF-8'),
			'meta_description' => htmlspecialchars((string)($post['meta_description'] ?? ''), ENT_QUOTES, 'UTF-8'),
			'status'           => htmlspecialchars($st, ENT_QUOTES, 'UTF-8'),
			'status_rascunho'  => $st === 'rascunho' ? 'selected' : '',
			'status_publicado' => $st === 'publicado' ? 'selected' : '',
			'opts_categorias'  => $optsCat,
			'capa_preview'     => $capaUrl ? htmlspecialchars($capaUrl, ENT_QUOTES, 'UTF-8') : '',
			'capa_preview_style' => $capaUrl ? '' : 'display:none',
			'tem_capa'         => $capaUrl ? '1' : '0',
			'is_novo'          => $id > 0 ? '0' : '1',
		]);
		return parent::getPanel($id > 0 ? 'Editar artigo' : 'Novo artigo', $content, 'conect-blog');
	}

	public static function salvar($request): string {
		if (!CjBlogPost::tabelaExiste()) {
			return json_encode(['success' => false, 'message' => 'Execute database/conect_jovem_redes_blog.sql.']);
		}
		$post = $request->getPostVars() ?: [];
		$files = $request->getFileVars();
		$id = (int)($post['id'] ?? 0);
		$titulo = trim((string)($post['titulo'] ?? ''));
		if ($titulo === '') {
			return json_encode(['success' => false, 'message' => 'Título é obrigatório.']);
		}

		$slugInput = trim((string)($post['slug'] ?? ''));
		$slug = $slugInput !== '' ? ConectApiMapper::slugify($slugInput) : ConectApiMapper::slugify($titulo);
		$slug = CjBlogPost::slugUnico($slug, $id > 0 ? $id : null);

		$corpo = ConectBlogSanitizer::html((string)($post['corpo_html'] ?? ''));
		if ($corpo === '') {
			return json_encode(['success' => false, 'message' => 'Conteúdo do artigo é obrigatório.']);
		}

		$catId = (int)($post['id_categoria'] ?? 0);
		$status = (string)($post['status'] ?? 'rascunho');
		if (!in_array($status, ['rascunho', 'publicado'], true)) {
			$status = 'rascunho';
		}

		$atual = $id > 0 ? CjBlogPost::getById($id) : null;
		$capa = $atual['capa'] ?? null;
		if (!empty($post['remover_capa'])) {
			$capa = null;
		} else {
			$capa = BrandingHelper::processarUploadConectBlogImagem($files['capa'] ?? null, $capa);
		}

		$dados = [
			'titulo'           => mb_substr($titulo, 0, 220),
			'slug'             => $slug,
			'resumo'           => mb_substr(trim((string)($post['resumo'] ?? '')), 0, 500),
			'capa'             => $capa,
			'corpo_html'       => $corpo,
			'id_categoria'     => $catId > 0 ? $catId : null,
			'autor_nome'       => mb_substr(trim((string)($post['autor_nome'] ?? 'Conecta Jovem')), 0, 120),
			'status'           => $status,
			'meta_title'       => mb_substr(trim((string)($post['meta_title'] ?? '')), 0, 220) ?: null,
			'meta_description' => mb_substr(trim((string)($post['meta_description'] ?? '')), 0, 320) ?: null,
		];

		if ($status === 'publicado') {
			$dados['publicado_em'] = date('Y-m-d H:i:s');
		} elseif ($atual && ($atual['status'] ?? '') === 'publicado') {
			$dados['publicado_em'] = $atual['publicado_em'] ?? date('Y-m-d H:i:s');
		}

		if ($id > 0 && $atual) {
			CjBlogPost::atualizar($id, $dados);
		} else {
			$id = CjBlogPost::inserir($dados);
		}

		return json_encode([
			'success' => true,
			'message' => 'Artigo salvo.',
			'id'      => $id,
			'slug'    => $slug,
			'redirect'=> URL.'/master/conect-blog/editar/'.$id,
		], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}

	public static function uploadImagem($request): string {
		$files = $request->getFileVars();
		$nome = BrandingHelper::processarUploadConectBlogImagem($files['file'] ?? ($files['image'] ?? null), null);
		if ($nome === null || $nome === '') {
			return json_encode(['success' => false, 'message' => 'Falha no upload.']);
		}
		$url = BrandingHelper::urlConectBlogImagem($nome);
		return json_encode([
			'success'  => true,
			'location' => $url,
			'url'      => $url,
		], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}

	public static function publicar($request, int $id): void {
		if ($id > 0 && CjBlogPost::tabelaExiste()) {
			CjBlogPost::atualizar($id, [
				'status'       => 'publicado',
				'publicado_em' => date('Y-m-d H:i:s'),
			]);
		}
		header('Location: '.URL.'/master/conect-blog');
		exit;
	}

	public static function despublicar($request, int $id): void {
		if ($id > 0 && CjBlogPost::tabelaExiste()) {
			CjBlogPost::atualizar($id, ['status' => 'rascunho']);
		}
		header('Location: '.URL.'/master/conect-blog');
		exit;
	}

	public static function excluirPost($request, int $id): void {
		if ($id > 0 && CjBlogPost::tabelaExiste()) {
			CjBlogPost::excluir($id);
		}
		header('Location: '.URL.'/master/conect-blog');
		exit;
	}

	public static function removerComentario($request, int $id): void {
		if ($id > 0 && CjBlogComentario::tabelaExiste()) {
			CjBlogComentario::atualizar($id, ['status' => 'removido']);
		}
		header('Location: '.URL.'/master/conect-blog');
		exit;
	}
}

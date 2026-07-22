<?php

namespace App\Controller\Master;

use App\Utils\View;
use App\Model\Entity\LmsConquistaDef;
use App\Common\Helpers\BrandingHelper;

class Conquistas extends Page {

	private const META_TIPOS = [
		'aulas_concluidas',
		'xp_total',
		'nivel',
		'streak',
		'estudo_min',
		'certificados',
		'atividades_ok',
		'roleplays_ok',
		'nota_min',
		'nota_100',
		'cursos_avaliados',
	];

	private const ICONES = [
		'Sparkles', 'Flame', 'Star', 'Trophy', 'Crown', 'Award', 'BookOpen', 'Clock',
		'Target', 'Zap', 'Heart', 'Rocket', 'Medal', 'GraduationCap', 'Brain', 'Calendar',
		'Mountain', 'Gem', 'Shield', 'Swords',
	];

	public static function index($request) {
		if (!LmsConquistaDef::tabelasExistem()) {
			$content = View::render('master/modules/conquistas/sql', []);
			return parent::getPanel('Conquistas — Master', $content, 'conquistas');
		}
		$content = View::render('master/modules/conquistas/index', [
			'meta_tipos_json' => json_encode(self::META_TIPOS, JSON_UNESCAPED_UNICODE),
			'icones_json' => json_encode(self::ICONES, JSON_UNESCAPED_UNICODE),
		]);
		return parent::getPanel('Conquistas — Master', $content, 'conquistas');
	}

	public static function getInfo($request) {
		if (!LmsConquistaDef::tabelasExistem()) {
			return json_encode([
				'success' => false,
				'message' => 'Execute database/lms_conquistas.sql e lms_conquistas_v2.sql no phpMyAdmin.',
			]);
		}

		$post = $request->getPostVars();
		switch ($post['acao'] ?? '') {
			case 'listar':
				return self::listar();
			case 'detalhes':
				return self::detalhes($post);
			case 'salvar':
				return self::salvar($post, $request->getFileVars());
			case 'excluir':
				return self::excluir($post);
			default:
				return json_encode(['success' => false, 'message' => 'Ação inválida.']);
		}
	}

	private static function listar(): string {
		$results = LmsConquistaDef::get(null, 'ordem ASC, id ASC');
		$lista = [];
		while ($c = $results->fetchObject(LmsConquistaDef::class)) {
			$lista[] = self::formatar($c);
		}
		return json_encode(['success' => true, 'conquistas' => $lista], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}

	private static function detalhes(array $post): string {
		$c = LmsConquistaDef::getById((int)($post['id'] ?? 0));
		if (!$c instanceof LmsConquistaDef) {
			return json_encode(['success' => false, 'message' => 'Conquista não encontrada.']);
		}
		return json_encode(['success' => true, 'conquista' => self::formatar($c)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}

	private static function salvar(array $post, array $files = []): string {
		$id = (int)($post['id'] ?? 0);
		$ob = $id > 0 ? LmsConquistaDef::getById($id) : new LmsConquistaDef();
		if ($id > 0 && !$ob instanceof LmsConquistaDef) {
			return json_encode(['success' => false, 'message' => 'Conquista não encontrada.']);
		}

		$slug = strtolower(trim((string)($post['slug'] ?? '')));
		$slug = preg_replace('/[^a-z0-9_]+/', '_', $slug) ?? '';
		$slug = trim($slug, '_');

		$titulo = trim((string)($post['titulo'] ?? ''));
		$subtitulo = trim((string)($post['subtitulo'] ?? ''));
		$descricao = trim((string)($post['descricao'] ?? ''));
		$como = trim((string)($post['como'] ?? ''));
		$icone = trim((string)($post['icone'] ?? 'Trophy')) ?: 'Trophy';
		$raridade = LmsConquistaDef::normalizarRaridade($post['raridade'] ?? 'bronze');
		$metaTipo = trim((string)($post['meta_tipo'] ?? ''));
		$metaValor = max(1, (int)($post['meta_valor'] ?? 1));
		$ordem = (int)($post['ordem'] ?? 0);
		$ativo = !empty($post['ativo']) ? 1 : 0;

		if ($slug === '' || $titulo === '' || $metaTipo === '') {
			return json_encode(['success' => false, 'message' => 'Preencha slug, título e tipo de meta.']);
		}
		if (!in_array($metaTipo, self::META_TIPOS, true)) {
			return json_encode(['success' => false, 'message' => 'Tipo de meta inválido.']);
		}

		$dup = LmsConquistaDef::getBySlug($slug);
		if ($dup && (int)$dup->id !== $id) {
			return json_encode(['success' => false, 'message' => 'Já existe conquista com este slug.']);
		}

		$ob->slug = $slug;
		$ob->titulo = $titulo;
		$ob->subtitulo = $subtitulo;
		$ob->descricao = $descricao;
		$ob->como = $como !== '' ? $como : null;
		$ob->icone = $icone;
		$ob->raridade = $raridade;
		$ob->meta_tipo = $metaTipo;
		$ob->meta_valor = $metaValor;
		$ob->ordem = $ordem;
		$ob->ativo = $ativo;

		$badgeAtual = $ob->badge_url ?? null;
		$novoBadge = BrandingHelper::processarUploadBadgeConquista($files['badge'] ?? null, $badgeAtual);
		if (!empty($post['remover_badge']) && empty($files['badge']['name'])) {
			$novoBadge = null;
		}
		$ob->badge_url = $novoBadge;

		if ($id > 0) {
			$ob->atualizar();
		} else {
			$ob->cadastrar();
		}

		$salvo = LmsConquistaDef::getById((int)$ob->id) ?: $ob;
		return json_encode([
			'success' => true,
			'message' => 'Conquista salva.',
			'conquista' => self::formatar($salvo),
		], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}

	private static function excluir(array $post): string {
		$id = (int)($post['id'] ?? 0);
		$ob = LmsConquistaDef::getById($id);
		if (!$ob instanceof LmsConquistaDef) {
			return json_encode(['success' => false, 'message' => 'Conquista não encontrada.']);
		}
		$ob->excluir();
		return json_encode(['success' => true, 'message' => 'Conquista excluída.']);
	}

	/** @return array<string,mixed> */
	private static function formatar(LmsConquistaDef $c): array {
		$badge = trim((string)($c->badge_url ?? ''));
		return [
			'id' => (int)$c->id,
			'slug' => (string)$c->slug,
			'titulo' => (string)$c->titulo,
			'subtitulo' => (string)($c->subtitulo ?? ''),
			'descricao' => (string)($c->descricao ?? ''),
			'como' => (string)($c->como ?? ''),
			'icone' => (string)($c->icone ?: 'Trophy'),
			'raridade' => LmsConquistaDef::normalizarRaridade($c->raridade ?? 'bronze'),
			'badge_url' => $badge !== '' ? $badge : null,
			'badge_full_url' => $badge !== '' ? BrandingHelper::urlBadgeConquista($badge) : null,
			'meta_tipo' => (string)$c->meta_tipo,
			'meta_valor' => (int)$c->meta_valor,
			'ordem' => (int)$c->ordem,
			'ativo' => !empty($c->ativo) ? 1 : 0,
		];
	}
}

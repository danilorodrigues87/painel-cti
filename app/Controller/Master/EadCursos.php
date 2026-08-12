<?php

namespace App\Controller\Master;

use App\Utils\View;
use App\Common\CtiCatalog;
use App\Common\Helpers\LmsHelper;
use App\Common\Helpers\TenantHelper;
use App\Controller\Admin\EadCursos as AdminEadCursos;
use App\Model\Entity\LmsCurso;

class EadCursos extends Page {

	public static function index($request) {
		if (!CtiCatalog::tabelasExistem()) {
			$content = View::render('master/modules/ead-cursos/sql', []);
			return parent::getPanel('Cursos CTI — Master', $content, 'ead_cursos');
		}

		$content = View::render('master/modules/ead-cursos/index', []);
		return parent::getPanel('Cursos CTI — Master', $content, 'ead_cursos');
	}

	public static function getInfo($request) {
		if (!CtiCatalog::tabelasExistem()) {
			return json_encode([
				'success' => false,
				'message' => 'Execute database/lms_catalogo_cti.sql no phpMyAdmin.',
			]);
		}

		$post = $request->getPostVars();
		switch ($post['acao'] ?? '') {
			case 'listar':
				return self::listar();
			case 'criar':
				return self::criar($post);
			case 'toggle_publicado':
				return self::togglePublicado($post);
			case 'excluir':
				return self::excluir($post);
			case 'listar_para_planos':
				return self::listarParaPlanos();
			default:
				return json_encode(['success' => false, 'message' => 'Ação inválida.']);
		}
	}

	private static function idCatalogo(): int {
		return CtiCatalog::idAdmin();
	}

	private static function listar(): string {
		$idAdmin = self::idCatalogo();
		if ($idAdmin <= 0) {
			return json_encode(['success' => false, 'message' => 'Não foi possível resolver o tenant do catálogo CTI.']);
		}

		$itens = [];
		foreach (LmsCurso::listarPorOrigem(CtiCatalog::ORIGEM_CTI, $idAdmin) as $c) {
			$itens[] = self::formatarCurso($c, $idAdmin);
		}

		return json_encode(['success' => true, 'itens' => $itens], JSON_UNESCAPED_UNICODE);
	}

	private static function listarParaPlanos(): string {
		$idAdmin = self::idCatalogo();
		$out = [];
		if ($idAdmin <= 0) {
			return json_encode(['success' => true, 'cursos' => $out]);
		}
		foreach (LmsCurso::listarPorOrigem(CtiCatalog::ORIGEM_CTI, $idAdmin) as $c) {
			$cover = trim((string)($c->cover_url ?? ''));
			$out[] = [
				'id' => (int)$c->id,
				'nome' => $c->nomeExibicao(),
				'publicado' => (int)$c->publicado,
				'cover_url' => $cover !== '' ? $cover : null,
				'short_description' => trim((string)($c->short_description ?? '')),
				'aulas' => LmsHelper::contagemAulasCurso((int)$c->id, $idAdmin),
			];
		}
		return json_encode(['success' => true, 'cursos' => $out], JSON_UNESCAPED_UNICODE);
	}

	private static function criar(array $post): string {
		$titulo = trim((string)($post['titulo'] ?? 'Novo curso CTI'));
		$curso = LmsHelper::criarCursoCti($titulo !== '' ? $titulo : 'Novo curso CTI');
		if (!$curso instanceof LmsCurso) {
			return json_encode(['success' => false, 'message' => 'Falha ao criar curso.']);
		}
		return json_encode([
			'success' => true,
			'message' => 'Curso CTI criado.',
			'curso' => self::formatarCurso($curso, self::idCatalogo()),
		], JSON_UNESCAPED_UNICODE);
	}

	private static function togglePublicado(array $post): string {
		$id = (int)($post['id'] ?? 0);
		$idAdmin = self::idCatalogo();
		$curso = LmsCurso::getByIdAdmin($id, $idAdmin);
		if (!$curso instanceof LmsCurso || !CtiCatalog::isCursoCti($curso)) {
			return json_encode(['success' => false, 'message' => 'Curso não encontrado.']);
		}
		$curso->publicado = (int)$curso->publicado === 1 ? 0 : 1;
		$curso->salvar();
		return json_encode([
			'success' => true,
			'message' => $curso->publicado ? 'Curso publicado.' : 'Curso despublicado.',
			'curso' => self::formatarCurso($curso, $idAdmin),
		], JSON_UNESCAPED_UNICODE);
	}

	private static function excluir(array $post): string {
		$id = (int)($post['id'] ?? 0);
		$idAdmin = self::idCatalogo();
		$curso = LmsCurso::getByIdAdmin($id, $idAdmin);
		if (!$curso instanceof LmsCurso || !CtiCatalog::isCursoCti($curso)) {
			return json_encode(['success' => false, 'message' => 'Curso não encontrado.']);
		}
		$curso->excluir();
		return json_encode(['success' => true, 'message' => 'Curso excluído.']);
	}

	/** GET /master/ead-cursos/editor/{id} — editor nativo Master (sem impersonate). */
	public static function editor($request, $idCurso) {
		if (!CtiCatalog::tabelasExistem() || !LmsHelper::tabelasExistem()) {
			$request->getRouter()->redirect('/master/ead-cursos');
			return '';
		}

		$idCurso = (int)$idCurso;
		$idAdmin = self::idCatalogo();
		if ($idAdmin <= 0) {
			CtiCatalog::garantirEscolaCatalogo();
			$idAdmin = self::idCatalogo();
		}

		$curso = LmsCurso::getByIdAdmin($idCurso, $idAdmin);
		if (!$curso instanceof LmsCurso || !CtiCatalog::isCursoCti($curso)) {
			$request->getRouter()->redirect('/master/ead-cursos?erro='.urlencode('Curso CTI não encontrado.'));
			return '';
		}

		$content = View::render('master/modules/ead-cursos/editor', [
			'id_curso' => $idCurso,
			'nome_curso' => htmlspecialchars($curso->nomeExibicao(), ENT_QUOTES, 'UTF-8'),
		]);
		return parent::getPanel('Editor — Curso CTI', $content, 'ead_cursos');
	}

	/** POST /master/ead-cursos/editor/{id} — API do editor (tenant catálogo, sessão Master). */
	public static function editorApi($request, $idCurso): string {
		if (!CtiCatalog::tabelasExistem()) {
			return json_encode(['success' => false, 'message' => 'Execute database/lms_catalogo_cti.sql no phpMyAdmin.']);
		}

		$idCurso = (int)$idCurso;
		$idAdmin = self::idCatalogo();
		if ($idAdmin <= 0) {
			CtiCatalog::garantirEscolaCatalogo();
			$idAdmin = self::idCatalogo();
		}
		if ($idAdmin <= 0) {
			return json_encode(['success' => false, 'message' => 'Tenant catálogo CTI indisponível.']);
		}

		$curso = LmsCurso::getByIdAdmin($idCurso, $idAdmin);
		if (!$curso instanceof LmsCurso || !CtiCatalog::isCursoCti($curso)) {
			return json_encode(['success' => false, 'message' => 'Curso CTI não encontrado.']);
		}

		$post = $request->getPostVars();
		$post['id_curso'] = $idCurso;

		$bloqueadas = [
			'listar', 'criar_curso',
			'listar_matriculas_ead', 'buscar_alunos', 'matricular_ead', 'desmatricular_ead',
		];
		if (in_array((string)($post['acao'] ?? ''), $bloqueadas, true)) {
			return json_encode(['success' => false, 'message' => 'Ação não disponível no editor Master.']);
		}

		return TenantHelper::withTenant($idAdmin, static function () use ($post) {
			return AdminEadCursos::dispatch($post);
		});
	}

	private static function formatarCurso(LmsCurso $c, int $idAdmin): array {
		return [
			'id' => (int)$c->id,
			'nome' => $c->nomeExibicao(),
			'carga_h' => $c->carga_h,
			'status' => LmsHelper::statusEad($c, $idAdmin),
			'publicado' => (int)$c->publicado,
			'aulas' => LmsHelper::contagemAulasCurso((int)$c->id, $idAdmin),
		];
	}
}

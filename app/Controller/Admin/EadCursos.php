<?php

namespace App\Controller\Admin;

use App\Utils\View;
use App\Common\Helpers\TenantHelper;
use App\Common\Helpers\LmsHelper;
use App\Model\Entity\Trilhas as EntityTrilhas;
use App\Model\Entity\LmsCurso;
use App\Model\Entity\LmsModulo;
use App\Model\Entity\LmsAula;
use App\Model\Entity\LmsVideo;
use App\Model\Entity\LmsMaterial;
use App\Model\Entity\LmsAtividade;
use App\Model\Entity\LmsQuestao;
use App\Model\Entity\LmsRoleplayCenario;

class EadCursos extends Page {

	private static function json(array $data): string {
		return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}

	public static function index($request) {
		$content = View::render('admin/modules/ead/index', []);
		return parent::getPanel('Cursos Online', $content, 'pedagogico', $request);
	}

	public static function editor($request, $idTrilha) {
		$idAdmin = TenantHelper::getIdAdmin();
		$idTrilha = (int)$idTrilha;
		$trilha = EntityTrilhas::getTrilha('id = '.$idTrilha.' AND id_admin = '.$idAdmin)->fetchObject(EntityTrilhas::class);
		if (!$trilha) {
			$request->getRouter()->redirect('/painel/ead');
			return '';
		}
		$content = View::render('admin/modules/ead/editor', [
			'id_trilha' => $idTrilha,
			'nome_trilha' => htmlspecialchars((string)$trilha->nome, ENT_QUOTES, 'UTF-8'),
		]);
		return parent::getPanel('Cursos Online', $content, 'pedagogico', $request);
	}

	public static function getInfo($request) {
		$post = $request->getPostVars();
		$acao = $post['acao'] ?? '';

		if (!LmsHelper::tabelasExistem()) {
			return self::json([
				'success' => false,
				'sql_ok' => false,
				'message' => 'Execute o SQL database/lms_ead.sql no phpMyAdmin.',
			]);
		}

		$map = [
			'listar' => 'listar',
			'carregar_curso' => 'carregarCurso',
			'salvar_geral' => 'salvarGeral',
			'listar_aulas' => 'listarAulas',
			'salvar_aula' => 'salvarAula',
			'excluir_aula' => 'excluirAula',
			'salvar_video' => 'salvarVideo',
			'excluir_video' => 'excluirVideo',
			'salvar_material' => 'salvarMaterial',
			'excluir_material' => 'excluirMaterial',
			'salvar_atividade' => 'salvarAtividade',
			'excluir_atividade' => 'excluirAtividade',
			'salvar_questao' => 'salvarQuestao',
			'excluir_questao' => 'excluirQuestao',
			'salvar_roleplay' => 'salvarRoleplay',
			'excluir_roleplay' => 'excluirRoleplay',
			'carregar_aula' => 'carregarAula',
		];

		if (!isset($map[$acao])) {
			return self::json(['success' => false, 'message' => 'Ação inválida.']);
		}

		$method = $map[$acao];
		return self::$method($post);
	}

	private static function listar(array $post): string {
		$idAdmin = TenantHelper::getIdAdmin();
		$results = EntityTrilhas::getTrilha('id_admin = '.$idAdmin, 'nome ASC');
		$itens = [];
		while ($t = $results->fetchObject(EntityTrilhas::class)) {
			$curso = LmsCurso::getByTrilha((int)$t->id, $idAdmin);
			$status = LmsHelper::statusEad($curso instanceof LmsCurso ? $curso : null, $idAdmin);
			$itens[] = [
				'id_trilha' => (int)$t->id,
				'nome' => $t->nome,
				'carga_h' => $t->carga_h,
				'status' => $status,
				'publicado' => $curso instanceof LmsCurso ? (int)$curso->publicado : 0,
				'aulas' => $curso instanceof LmsCurso ? LmsHelper::contagemAulasCurso((int)$curso->id, $idAdmin) : 0,
				'id_curso' => $curso instanceof LmsCurso ? (int)$curso->id : null,
			];
		}
		return self::json([
			'success' => true,
			'sql_ok' => true,
			'xp_ok' => \App\Common\Helpers\LmsXpHelper::tabelasExistem(),
			'itens' => $itens,
		]);
	}

	private static function carregarCurso(array $post): string {
		$idAdmin = TenantHelper::getIdAdmin();
		$idTrilha = (int)($post['id_trilha'] ?? 0);
		$trilha = EntityTrilhas::getTrilha('id = '.$idTrilha.' AND id_admin = '.$idAdmin)->fetchObject(EntityTrilhas::class);
		if (!$trilha) {
			return self::json(['success' => false, 'message' => 'Trilha não encontrada.']);
		}
		$curso = LmsHelper::garantirCursoTrilha($idTrilha, $idAdmin, (string)$trilha->nome);
		if (!$curso) {
			return self::json(['success' => false, 'message' => 'Não foi possível criar o curso EAD.']);
		}
		$objectives = json_decode((string)($curso->objectives ?? '[]'), true);
		if (!is_array($objectives)) {
			$objectives = [];
		}
		return self::json([
			'success' => true,
			'curso' => [
				'id' => (int)$curso->id,
				'id_trilha' => (int)$curso->id_trilha,
				'slug' => $curso->slug,
				'short_description' => $curso->short_description,
				'cover_url' => $curso->cover_url,
				'banner_url' => $curso->banner_url,
				'level' => $curso->level,
				'objectives' => $objectives,
				'objectives_text' => implode("\n", $objectives),
				'instructor_name' => $curso->instructor_name,
				'instructor_title' => $curso->instructor_title,
				'instructor_bio' => $curso->instructor_bio,
				'instructor_avatar_url' => $curso->instructor_avatar_url,
				'publicado' => (int)$curso->publicado,
			],
			'trilha' => ['id' => (int)$trilha->id, 'nome' => $trilha->nome],
		]);
	}

	private static function salvarGeral(array $post): string {
		$idAdmin = TenantHelper::getIdAdmin();
		$idTrilha = (int)($post['id_trilha'] ?? 0);
		$trilha = EntityTrilhas::getTrilha('id = '.$idTrilha.' AND id_admin = '.$idAdmin)->fetchObject(EntityTrilhas::class);
		if (!$trilha) {
			return self::json(['success' => false, 'message' => 'Trilha não encontrada.']);
		}
		$curso = LmsHelper::garantirCursoTrilha($idTrilha, $idAdmin, (string)$trilha->nome);
		if (!$curso) {
			return self::json(['success' => false, 'message' => 'Curso inválido.']);
		}

		$slugIn = trim((string)($post['slug'] ?? ''));
		$slug = LmsHelper::slugify($slugIn !== '' ? $slugIn : (string)$trilha->nome);
		$curso->slug = LmsHelper::slugUnico($slug, $idAdmin, (int)$curso->id);
		$curso->short_description = trim((string)($post['short_description'] ?? ''));
		$curso->cover_url = trim((string)($post['cover_url'] ?? ''));
		$curso->banner_url = trim((string)($post['banner_url'] ?? ''));
		$level = (string)($post['level'] ?? 'Iniciante');
		$curso->level = in_array($level, ['Iniciante', 'Intermediário', 'Avançado'], true) ? $level : 'Iniciante';
		$objText = (string)($post['objectives_text'] ?? '');
		$objs = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $objText) ?: [])));
		$curso->objectives = json_encode($objs, JSON_UNESCAPED_UNICODE);
		$curso->instructor_name = trim((string)($post['instructor_name'] ?? ''));
		$curso->instructor_title = trim((string)($post['instructor_title'] ?? ''));
		$curso->instructor_bio = trim((string)($post['instructor_bio'] ?? ''));
		$curso->instructor_avatar_url = trim((string)($post['instructor_avatar_url'] ?? ''));
		$curso->publicado = !empty($post['publicado']) ? 1 : 0;
		$curso->salvar();

		return self::json(['success' => true, 'message' => 'Dados gerais salvos.', 'id_curso' => (int)$curso->id, 'slug' => $curso->slug]);
	}

	private static function listarAulas(array $post): string {
		$idAdmin = TenantHelper::getIdAdmin();
		$idCurso = (int)($post['id_curso'] ?? 0);
		$curso = LmsCurso::getByIdAdmin($idCurso, $idAdmin);
		if (!$curso) {
			return self::json(['success' => false, 'message' => 'Curso não encontrado.']);
		}
		$mod = LmsHelper::garantirModuloPadrao($idCurso, $idAdmin);
		$aulas = [];
		foreach (LmsAula::listByModulo((int)$mod->id, $idAdmin) as $a) {
			$aulas[] = [
				'id' => (int)$a->id,
				'titulo' => $a->titulo,
				'descricao' => $a->descricao,
				'ordem' => (int)$a->ordem,
				'bloqueado' => (int)$a->bloqueado,
				'videos' => count(LmsVideo::listByAula((int)$a->id, $idAdmin)),
				'materiais' => count(LmsMaterial::listByAula((int)$a->id, $idAdmin)),
				'atividades' => count(LmsAtividade::listByAula((int)$a->id, $idAdmin)),
			];
		}
		return self::json(['success' => true, 'id_modulo' => (int)$mod->id, 'aulas' => $aulas]);
	}

	private static function carregarAula(array $post): string {
		$idAdmin = TenantHelper::getIdAdmin();
		$idAula = (int)($post['id_aula'] ?? 0);
		$aula = LmsAula::getByIdAdmin($idAula, $idAdmin);
		if (!$aula) {
			return self::json(['success' => false, 'message' => 'Aula não encontrada.']);
		}
		$videos = array_map(static function ($v) {
			return [
				'id' => (int)$v->id,
				'titulo' => $v->titulo,
				'url' => $v->url,
				'provider' => $v->provider,
				'duracao_min' => (int)$v->duracao_min,
				'ordem' => (int)$v->ordem,
			];
		}, LmsVideo::listByAula($idAula, $idAdmin));

		$materiais = array_map(static function ($m) {
			return [
				'id' => (int)$m->id,
				'label' => $m->label,
				'url' => $m->url,
				'tipo' => $m->tipo,
				'ordem' => (int)$m->ordem,
			];
		}, LmsMaterial::listByAula($idAula, $idAdmin));

		$atividades = [];
		foreach (LmsAtividade::listByAula($idAula, $idAdmin) as $at) {
			$questoes = array_map(static function ($q) {
				$ops = json_decode((string)($q->opcoes ?? '[]'), true);
				return [
					'id' => (int)$q->id,
					'tipo' => $q->tipo,
					'enunciado' => $q->enunciado,
					'opcoes' => is_array($ops) ? $ops : [],
					'resposta_correta' => $q->resposta_correta,
					'ordem' => (int)$q->ordem,
				];
			}, LmsQuestao::listByAtividade((int)$at->id, $idAdmin));
			$atividades[] = [
				'id' => (int)$at->id,
				'titulo' => $at->titulo,
				'descricao' => $at->descricao,
				'duracao_min' => (int)$at->duracao_min,
				'tentativas_max' => (int)$at->tentativas_max,
				'ordem' => (int)$at->ordem,
				'questoes' => $questoes,
			];
		}

		$mod = LmsModulo::getByIdAdmin((int)$aula->id_modulo, $idAdmin);
		$idCurso = $mod ? (int)$mod->id_curso : 0;
		$roleplays = [];
		foreach (LmsRoleplayCenario::listByCurso($idCurso, $idAdmin) as $rp) {
			if ((int)($rp->id_aula ?? 0) !== $idAula) {
				continue;
			}
			$obj = json_decode((string)($rp->objectives ?? '[]'), true);
			$crit = json_decode((string)($rp->criteria ?? '[]'), true);
			$roleplays[] = [
				'id' => (int)$rp->id,
				'titulo' => $rp->titulo,
				'tema' => $rp->tema,
				'cenario' => $rp->cenario,
				'user_role' => $rp->user_role,
				'ai_role' => $rp->ai_role,
				'ai_character_name' => $rp->ai_character_name,
				'difficulty' => $rp->difficulty,
				'min_score' => (int)$rp->min_score,
				'base_prompt' => $rp->base_prompt,
				'initial_personality' => $rp->initial_personality,
				'initial_message' => $rp->initial_message,
				'estimated_minutes' => (int)$rp->estimated_minutes,
				'objectives' => is_array($obj) ? $obj : [],
				'criteria' => is_array($crit) ? $crit : [],
			];
		}

		return self::json([
			'success' => true,
			'aula' => [
				'id' => (int)$aula->id,
				'id_modulo' => (int)$aula->id_modulo,
				'titulo' => $aula->titulo,
				'descricao' => $aula->descricao,
				'ordem' => (int)$aula->ordem,
				'bloqueado' => (int)$aula->bloqueado,
			],
			'videos' => $videos,
			'materiais' => $materiais,
			'atividades' => $atividades,
			'roleplays' => $roleplays,
			'id_curso' => $idCurso,
		]);
	}

	private static function salvarAula(array $post): string {
		$idAdmin = TenantHelper::getIdAdmin();
		$idCurso = (int)($post['id_curso'] ?? 0);
		$curso = LmsCurso::getByIdAdmin($idCurso, $idAdmin);
		if (!$curso) {
			return self::json(['success' => false, 'message' => 'Curso não encontrado.']);
		}
		$mod = LmsHelper::garantirModuloPadrao($idCurso, $idAdmin);
		$idAula = (int)($post['id_aula'] ?? 0);
		$aula = $idAula > 0 ? LmsAula::getByIdAdmin($idAula, $idAdmin) : new LmsAula();
		if ($idAula > 0 && !$aula) {
			return self::json(['success' => false, 'message' => 'Aula não encontrada.']);
		}
		$aula->id_modulo = (int)$mod->id;
		$aula->id_admin = $idAdmin;
		$aula->titulo = trim((string)($post['titulo'] ?? 'Nova aula'));
		if ($aula->titulo === '') {
			$aula->titulo = 'Nova aula';
		}
		$aula->descricao = trim((string)($post['descricao'] ?? ''));
		$aula->ordem = (int)($post['ordem'] ?? 0);
		$aula->bloqueado = !empty($post['bloqueado']) ? 1 : 0;
		$id = $aula->salvar();
		return self::json(['success' => true, 'message' => 'Aula salva.', 'id_aula' => $id]);
	}

	private static function excluirAula(array $post): string {
		$idAdmin = TenantHelper::getIdAdmin();
		$idAula = (int)($post['id_aula'] ?? 0);
		$aula = LmsAula::getByIdAdmin($idAula, $idAdmin);
		if (!$aula) {
			return self::json(['success' => false, 'message' => 'Aula não encontrada.']);
		}
		foreach (LmsVideo::listByAula($idAula, $idAdmin) as $v) {
			$v->excluir();
		}
		foreach (LmsMaterial::listByAula($idAula, $idAdmin) as $m) {
			$m->excluir();
		}
		foreach (LmsAtividade::listByAula($idAula, $idAdmin) as $at) {
			foreach (LmsQuestao::listByAtividade((int)$at->id, $idAdmin) as $q) {
				$q->excluir();
			}
			$at->excluir();
		}
		$aula->excluir();
		return self::json(['success' => true, 'message' => 'Aula excluída.']);
	}

	private static function salvarVideo(array $post): string {
		$idAdmin = TenantHelper::getIdAdmin();
		$idAula = (int)($post['id_aula'] ?? 0);
		$aula = LmsAula::getByIdAdmin($idAula, $idAdmin);
		if (!$aula) {
			return self::json(['success' => false, 'message' => 'Aula não encontrada.']);
		}
		$url = trim((string)($post['url'] ?? ''));
		if ($url === '') {
			return self::json(['success' => false, 'message' => 'URL do vídeo obrigatória.']);
		}
		$id = (int)($post['id'] ?? 0);
		$v = $id > 0 ? LmsVideo::getByIdAdmin($id, $idAdmin) : new LmsVideo();
		if ($id > 0 && !$v) {
			return self::json(['success' => false, 'message' => 'Vídeo não encontrado.']);
		}
		$v->id_aula = $idAula;
		$v->id_admin = $idAdmin;
		$v->titulo = trim((string)($post['titulo'] ?? ''));
		$provider = (string)($post['provider'] ?? 'youtube') === 'private' ? 'private' : 'youtube';
		$v->url = LmsHelper::normalizeVideoUrl($url, $provider);
		$v->provider = $provider;
		$v->duracao_min = (int)($post['duracao_min'] ?? 0);
		$v->ordem = (int)($post['ordem'] ?? 0);
		$newId = $v->salvar();
		return self::json(['success' => true, 'message' => 'Vídeo salvo.', 'id' => $newId]);
	}

	private static function excluirVideo(array $post): string {
		$idAdmin = TenantHelper::getIdAdmin();
		$id = (int)($post['id'] ?? 0);
		$v = LmsVideo::getByIdAdmin($id, $idAdmin);
		if (!$v) {
			return self::json(['success' => false, 'message' => 'Vídeo não encontrado.']);
		}
		$v->excluir();
		return self::json(['success' => true, 'message' => 'Vídeo excluído.']);
	}

	private static function salvarMaterial(array $post): string {
		$idAdmin = TenantHelper::getIdAdmin();
		$idAula = (int)($post['id_aula'] ?? 0);
		$aula = LmsAula::getByIdAdmin($idAula, $idAdmin);
		if (!$aula) {
			return self::json(['success' => false, 'message' => 'Aula não encontrada.']);
		}
		$url = trim((string)($post['url'] ?? ''));
		$label = trim((string)($post['label'] ?? ''));
		if ($url === '' || $label === '') {
			return self::json(['success' => false, 'message' => 'Label e URL obrigatórios.']);
		}
		$id = (int)($post['id'] ?? 0);
		$m = $id > 0 ? LmsMaterial::getByIdAdmin($id, $idAdmin) : new LmsMaterial();
		if ($id > 0 && !$m) {
			return self::json(['success' => false, 'message' => 'Material não encontrado.']);
		}
		$tipo = (string)($post['tipo'] ?? 'link');
		$m->id_aula = $idAula;
		$m->id_admin = $idAdmin;
		$m->label = $label;
		$m->url = $url;
		$m->tipo = in_array($tipo, ['pdf', 'link', 'file'], true) ? $tipo : 'link';
		$m->ordem = (int)($post['ordem'] ?? 0);
		$newId = $m->salvar();
		return self::json(['success' => true, 'message' => 'Material salvo.', 'id' => $newId]);
	}

	private static function excluirMaterial(array $post): string {
		$idAdmin = TenantHelper::getIdAdmin();
		$id = (int)($post['id'] ?? 0);
		$m = LmsMaterial::getByIdAdmin($id, $idAdmin);
		if (!$m) {
			return self::json(['success' => false, 'message' => 'Material não encontrado.']);
		}
		$m->excluir();
		return self::json(['success' => true, 'message' => 'Material excluído.']);
	}

	private static function salvarAtividade(array $post): string {
		$idAdmin = TenantHelper::getIdAdmin();
		$idCurso = (int)($post['id_curso'] ?? 0);
		$idAula = (int)($post['id_aula'] ?? 0);
		$curso = LmsCurso::getByIdAdmin($idCurso, $idAdmin);
		if (!$curso) {
			return self::json(['success' => false, 'message' => 'Curso não encontrado.']);
		}
		if ($idAula > 0 && !LmsAula::getByIdAdmin($idAula, $idAdmin)) {
			return self::json(['success' => false, 'message' => 'Aula não encontrada.']);
		}
		$id = (int)($post['id'] ?? 0);
		$at = $id > 0 ? LmsAtividade::getByIdAdmin($id, $idAdmin) : new LmsAtividade();
		if ($id > 0 && !$at) {
			return self::json(['success' => false, 'message' => 'Atividade não encontrada.']);
		}
		$at->id_curso = $idCurso;
		$at->id_aula = $idAula > 0 ? $idAula : null;
		$at->id_admin = $idAdmin;
		$at->titulo = trim((string)($post['titulo'] ?? 'Atividade'));
		$at->descricao = trim((string)($post['descricao'] ?? ''));
		$at->duracao_min = (int)($post['duracao_min'] ?? 30);
		$at->tentativas_max = max(1, min(10, (int)($post['tentativas_max'] ?? 3)));
		$at->ordem = (int)($post['ordem'] ?? 0);
		$newId = $at->salvar();
		return self::json(['success' => true, 'message' => 'Atividade salva.', 'id' => $newId]);
	}

	private static function excluirAtividade(array $post): string {
		$idAdmin = TenantHelper::getIdAdmin();
		$id = (int)($post['id'] ?? 0);
		$at = LmsAtividade::getByIdAdmin($id, $idAdmin);
		if (!$at) {
			return self::json(['success' => false, 'message' => 'Atividade não encontrada.']);
		}
		foreach (LmsQuestao::listByAtividade($id, $idAdmin) as $q) {
			$q->excluir();
		}
		$at->excluir();
		return self::json(['success' => true, 'message' => 'Atividade excluída.']);
	}

	private static function salvarQuestao(array $post): string {
		$idAdmin = TenantHelper::getIdAdmin();
		$idAtividade = (int)($post['id_atividade'] ?? 0);
		$at = LmsAtividade::getByIdAdmin($idAtividade, $idAdmin);
		if (!$at) {
			return self::json(['success' => false, 'message' => 'Atividade não encontrada.']);
		}
		$id = (int)($post['id'] ?? 0);
		$q = $id > 0 ? LmsQuestao::getByIdAdmin($id, $idAdmin) : new LmsQuestao();
		if ($id > 0 && !$q) {
			return self::json(['success' => false, 'message' => 'Questão não encontrada.']);
		}
		$tipo = (string)($post['tipo'] ?? 'multiple');
		$tipo = in_array($tipo, ['multiple', 'boolean', 'essay'], true) ? $tipo : 'multiple';
		$opcoesRaw = $post['opcoes'] ?? '[]';
		if (is_string($opcoesRaw)) {
			$decoded = json_decode($opcoesRaw, true);
			$opcoes = is_array($decoded) ? $decoded : [];
		} else {
			$opcoes = is_array($opcoesRaw) ? $opcoesRaw : [];
		}
		$resposta = trim((string)($post['resposta_correta'] ?? ''));
		if ($tipo === 'boolean') {
			$opcoes = [
				['id' => 'true', 'label' => 'Verdadeiro'],
				['id' => 'false', 'label' => 'Falso'],
			];
			$t = strtolower($resposta);
			$resposta = in_array($t, ['0', 'false', 'f', 'falso', 'nao', 'não', 'n'], true) ? 'false' : 'true';
		} elseif ($tipo === 'essay') {
			$opcoes = [];
			$resposta = '';
		}
		$q->id_atividade = $idAtividade;
		$q->id_admin = $idAdmin;
		$q->tipo = $tipo;
		$q->enunciado = trim((string)($post['enunciado'] ?? ''));
		$q->opcoes = json_encode($opcoes, JSON_UNESCAPED_UNICODE);
		$q->resposta_correta = $resposta;
		$q->ordem = (int)($post['ordem'] ?? 0);
		if ($q->enunciado === '') {
			return self::json(['success' => false, 'message' => 'Enunciado obrigatório.']);
		}
		$newId = $q->salvar();
		return self::json(['success' => true, 'message' => 'Questão salva.', 'id' => $newId]);
	}

	private static function excluirQuestao(array $post): string {
		$idAdmin = TenantHelper::getIdAdmin();
		$id = (int)($post['id'] ?? 0);
		$q = LmsQuestao::getByIdAdmin($id, $idAdmin);
		if (!$q) {
			return self::json(['success' => false, 'message' => 'Questão não encontrada.']);
		}
		$q->excluir();
		return self::json(['success' => true, 'message' => 'Questão excluída.']);
	}

	private static function salvarRoleplay(array $post): string {
		$idAdmin = TenantHelper::getIdAdmin();
		$idCurso = (int)($post['id_curso'] ?? 0);
		$idAula = (int)($post['id_aula'] ?? 0);
		$curso = LmsCurso::getByIdAdmin($idCurso, $idAdmin);
		if (!$curso) {
			return self::json(['success' => false, 'message' => 'Curso não encontrado.']);
		}
		$id = (int)($post['id'] ?? 0);
		$rp = $id > 0 ? LmsRoleplayCenario::getByIdAdmin($id, $idAdmin) : new LmsRoleplayCenario();
		if ($id > 0 && !$rp) {
			return self::json(['success' => false, 'message' => 'Cenário não encontrado.']);
		}
		$objs = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string)($post['objectives_text'] ?? '')) ?: [])));
		$rp->id_curso = $idCurso;
		$rp->id_aula = $idAula > 0 ? $idAula : null;
		$rp->id_admin = $idAdmin;
		$rp->titulo = trim((string)($post['titulo'] ?? 'Role play'));
		$rp->tema = trim((string)($post['tema'] ?? ''));
		$rp->cenario = trim((string)($post['cenario'] ?? ''));
		$rp->user_role = trim((string)($post['user_role'] ?? ''));
		$rp->ai_role = trim((string)($post['ai_role'] ?? ''));
		$rp->ai_character_name = trim((string)($post['ai_character_name'] ?? ''));
		$rp->difficulty = (string)($post['difficulty'] ?? 'medium');
		$rp->min_score = (int)($post['min_score'] ?? 70);
		$rp->base_prompt = trim((string)($post['base_prompt'] ?? ''));
		$rp->initial_personality = trim((string)($post['initial_personality'] ?? ''));
		$rp->initial_message = trim((string)($post['initial_message'] ?? ''));
		$rp->estimated_minutes = (int)($post['estimated_minutes'] ?? 15);
		$rp->objectives = json_encode($objs, JSON_UNESCAPED_UNICODE);
		$rp->criteria = '[]';
		$newId = $rp->salvar();
		return self::json(['success' => true, 'message' => 'Role play salvo.', 'id' => $newId]);
	}

	private static function excluirRoleplay(array $post): string {
		$idAdmin = TenantHelper::getIdAdmin();
		$id = (int)($post['id'] ?? 0);
		$rp = LmsRoleplayCenario::getByIdAdmin($id, $idAdmin);
		if (!$rp) {
			return self::json(['success' => false, 'message' => 'Cenário não encontrado.']);
		}
		$rp->excluir();
		return self::json(['success' => true, 'message' => 'Role play excluído.']);
	}
}

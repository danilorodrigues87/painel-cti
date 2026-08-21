<?php

namespace App\Controller\Master;

use App\Common\Helpers\BrandingHelper;
use App\Model\Entity\CjDepoimento;
use App\Utils\View;

class ConectDepoimentos extends Page {

	public static function index($request): string {
		if (!CjDepoimento::tabelaExiste()) {
			$content = View::render('master/modules/conect-depoimentos/sql', []);
			return parent::getPanel('Conecta Jovem — Depoimentos', $content, 'conect-depoimentos');
		}

		$rows = '';
		foreach (CjDepoimento::listarTodos() as $d) {
			$ativo = !empty($d['ativo']);
			$badge = $ativo
				? '<span class="badge bg-success">Ativo</span>'
				: '<span class="badge bg-secondary">Inativo</span>';
			$tipo = htmlspecialchars((string)($d['tipo_autor'] ?? 'manual'));
			$rows .= '<tr>'
				.'<td>'.htmlspecialchars((string)($d['nome_exibicao'] ?? '')).'</td>'
				.'<td class="small">'.htmlspecialchars(mb_substr((string)($d['texto'] ?? ''), 0, 80)).'…</td>'
				.'<td>'.$tipo.'</td>'
				.'<td>'.$badge.'</td>'
				.'<td>'.(int)($d['ordem'] ?? 0).'</td>'
				.'<td class="text-nowrap">'
				.'<a href="'.URL.'/master/conect-depoimentos/editar/'.(int)$d['id'].'" class="btn btn-sm btn-outline-primary me-1">Editar</a>'
				.'<form method="post" action="'.URL.'/master/conect-depoimentos/'.(int)$d['id'].'/excluir" class="d-inline" onsubmit="return confirm(\'Excluir depoimento?\');">'
				.'<button type="submit" class="btn btn-sm btn-outline-danger">Excluir</button></form>'
				.'</td></tr>';
		}
		if ($rows === '') {
			$rows = '<tr><td colspan="6" class="text-muted">Nenhum depoimento cadastrado.</td></tr>';
		}

		$content = View::render('master/modules/conect-depoimentos/index', ['rows' => $rows]);
		return parent::getPanel('Conecta Jovem — Depoimentos', $content, 'conect-depoimentos');
	}

	public static function editar($request, int $id): string {
		if (!CjDepoimento::tabelaExiste()) {
			header('Location: '.URL.'/master/conect-depoimentos');
			exit;
		}
		$row = $id > 0 ? CjDepoimento::getById($id) : null;
		$tipo = (string)($row['tipo_autor'] ?? 'manual');
		$avatarUrl = BrandingHelper::urlConectDepoimentoAvatar($row['avatar_arquivo'] ?? null);
		if (!$avatarUrl && $tipo === 'candidato' && !empty($row['candidato_foto'])) {
			$avatarUrl = \App\Common\Helpers\UserFotoHelper::urlPublica((string)$row['candidato_foto']);
		}
		if (!$avatarUrl && $tipo === 'empresa' && !empty($row['empresa_logo'])) {
			$avatarUrl = BrandingHelper::urlConectEmpresaLogo($row['empresa_logo'] ?? null);
		}

		$content = View::render('master/modules/conect-depoimentos/edit', [
			'id'              => $id,
			'texto'           => htmlspecialchars((string)($row['texto'] ?? ''), ENT_QUOTES, 'UTF-8'),
			'nome_exibicao'   => htmlspecialchars((string)($row['nome_exibicao'] ?? ''), ENT_QUOTES, 'UTF-8'),
			'cargo'           => htmlspecialchars((string)($row['cargo'] ?? ''), ENT_QUOTES, 'UTF-8'),
			'tipo_autor'      => $tipo,
			'tipo_manual'     => $tipo === 'manual' ? 'selected' : '',
			'tipo_candidato'  => $tipo === 'candidato' ? 'selected' : '',
			'tipo_empresa'    => $tipo === 'empresa' ? 'selected' : '',
			'id_candidato'    => (int)($row['id_candidato'] ?? 0),
			'id_empresa'      => (int)($row['id_empresa'] ?? 0),
			'ordem'           => (int)($row['ordem'] ?? 0),
			'ativo'           => !isset($row['ativo']) || !empty($row['ativo']) ? 'checked' : '',
			'avatar_preview'  => $avatarUrl ? htmlspecialchars($avatarUrl, ENT_QUOTES, 'UTF-8') : '',
			'avatar_style'    => $avatarUrl ? '' : 'display:none',
		]);
		return parent::getPanel($id > 0 ? 'Editar depoimento' : 'Novo depoimento', $content, 'conect-depoimentos');
	}

	public static function salvar($request): string {
		if (!CjDepoimento::tabelaExiste()) {
			return json_encode(['success' => false, 'message' => 'Execute database/conect_jovem_depoimentos.sql.']);
		}
		$post = $request->getPostVars() ?: [];
		$files = $request->getFileVars();
		$id = (int)($post['id'] ?? 0);
		$texto = trim((string)($post['texto'] ?? ''));
		$nome = trim((string)($post['nome_exibicao'] ?? ''));
		if ($texto === '' || $nome === '') {
			return json_encode(['success' => false, 'message' => 'Texto e nome são obrigatórios.']);
		}

		$tipo = (string)($post['tipo_autor'] ?? 'manual');
		if (!in_array($tipo, ['manual', 'candidato', 'empresa'], true)) {
			$tipo = 'manual';
		}

		$atual = $id > 0 ? CjDepoimento::getById($id) : null;
		$avatar = $atual['avatar_arquivo'] ?? null;
		if (!empty($post['remover_avatar'])) {
			$avatar = null;
		} elseif ($tipo === 'manual') {
			$avatar = BrandingHelper::processarUploadConectDepoimentoAvatar($files['avatar'] ?? null, $avatar);
		} else {
			$avatar = null;
		}

		$idCandidato = null;
		$idEmpresa = null;
		if ($tipo === 'candidato') {
			$idCandidato = (int)($post['id_candidato'] ?? 0) ?: null;
		} elseif ($tipo === 'empresa') {
			$idEmpresa = (int)($post['id_empresa'] ?? 0) ?: null;
		}

		$dados = [
			'texto'          => mb_substr($texto, 0, 5000),
			'nome_exibicao'  => mb_substr($nome, 0, 120),
			'cargo'          => mb_substr(trim((string)($post['cargo'] ?? '')), 0, 191),
			'tipo_autor'     => $tipo,
			'id_candidato'   => $idCandidato,
			'id_empresa'     => $idEmpresa,
			'avatar_arquivo' => $avatar,
			'ordem'          => max(0, (int)($post['ordem'] ?? 0)),
			'ativo'          => !empty($post['ativo']) ? 1 : 0,
		];

		if ($id > 0 && $atual) {
			CjDepoimento::atualizar($id, $dados);
		} else {
			$id = CjDepoimento::inserir($dados);
		}

		return json_encode([
			'success'  => true,
			'message'  => 'Depoimento salvo.',
			'redirect' => URL.'/master/conect-depoimentos/editar/'.$id,
		], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}

	public static function buscar($request): string {
		$post = $request->getPostVars() ?: [];
		$tipo = (string)($post['tipo'] ?? '');
		$q = trim((string)($post['q'] ?? ''));
		$items = [];
		if ($tipo === 'candidato') {
			foreach (CjDepoimento::buscarCandidatos($q) as $r) {
				$items[] = [
					'id'    => (int)$r['id'],
					'label' => (string)($r['nome'] ?? '').' · '.(string)($r['email'] ?? ''),
					'nome'  => (string)($r['nome'] ?? ''),
					'cargo' => 'Candidato',
					'avatarUrl' => \App\Common\Helpers\UserFotoHelper::urlPublica($r['foto'] ?? null),
				];
			}
		} elseif ($tipo === 'empresa') {
			foreach (CjDepoimento::buscarEmpresas($q) as $r) {
				$nome = (string)($r['nome_fantasia'] ?: $r['razao_social'] ?? '');
				$items[] = [
					'id'    => (int)$r['id'],
					'label' => $nome,
					'nome'  => $nome,
					'cargo' => 'Empresa parceira',
					'avatarUrl' => BrandingHelper::urlConectEmpresaLogo($r['logo'] ?? null),
				];
			}
		}
		return json_encode(['success' => true, 'items' => $items], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}

	public static function excluir($request, int $id): void {
		if ($id > 0 && CjDepoimento::tabelaExiste()) {
			CjDepoimento::excluir($id);
		}
		header('Location: '.URL.'/master/conect-depoimentos');
		exit;
	}
}

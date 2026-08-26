<?php

namespace App\Controller\Master;

use App\Common\Helpers\BrandingHelper;
use App\Common\Helpers\ConectAnuncioHelper;
use App\Common\Helpers\ConectEnderecoHelper;
use App\Model\Entity\CjAnuncio;
use App\Model\Entity\CjAnuncioAssinatura;
use App\Model\Entity\CjAnuncioConfig;
use App\Model\Entity\CjAnuncioEvento;
use App\Model\Entity\CjAnuncioPlano;
use App\Model\Entity\EstadoCidades;
use App\Session\User\Login as SessionUser;
use App\Utils\View;

class ConectAnuncios extends Page {

	public static function index($request): string {
		if (!CjAnuncio::tabelaExiste()) {
			$content = View::render('master/modules/conect-anuncios/sql', []);
			return parent::getPanel('Conecta Jovem — Anúncios', $content, 'conect-anuncios');
		}

		$config = CjAnuncioConfig::get();
		$pendentes = CjAnuncio::listarMaster(['status' => 'pendente', 'limit' => 50]);
		$todos = CjAnuncio::listarMaster(['limit' => 200]);
		$ids = array_map(static fn($r) => (int)($r['id'] ?? 0), $todos);
		$metricas = CjAnuncioEvento::resumoPorAnuncios($ids);

		$rowsPend = self::renderRows($pendentes, $metricas, true);
		$rows = self::renderRows($todos, $metricas, false);

		$slotsOpts = '';
		foreach (ConectAnuncioHelper::SLOTS as $k => $lbl) {
			$sel = in_array($k, $config['slots_habilitados'] ?? [], true) ? 'checked' : '';
			$slotsOpts .= '<div class="form-check form-check-inline">'
				.'<input class="form-check-input slot-cfg" type="checkbox" value="'.htmlspecialchars($k).'" id="slot_'.htmlspecialchars($k).'" '.$sel.'>'
				.'<label class="form-check-label small" for="slot_'.htmlspecialchars($k).'">'.htmlspecialchars($lbl).'</label></div>';
		}

		$content = View::render('master/modules/conect-anuncios/index', [
			'rows_pendentes'   => $rowsPend ?: '<tr><td colspan="8" class="text-muted">Nenhum anúncio pendente.</td></tr>',
			'rows'             => $rows ?: '<tr><td colspan="8" class="text-muted">Nenhum anúncio cadastrado.</td></tr>',
			'preco_minimo'     => number_format((float)($config['preco_minimo_mensal'] ?? 99), 2, ',', '.'),
			'max_empresa'      => (int)($config['max_anuncios_por_empresa'] ?? 3),
			'requer_aprovacao' => !empty($config['requer_aprovacao_master']) ? 'checked' : '',
			'slots_opts'       => $slotsOpts,
			'planos_rows'      => self::renderPlanosRows(),
			'assinaturas_rows' => self::renderAssinaturasRows(),
			'planos_sql_aviso' => CjAnuncioPlano::tabelaExiste() ? '' : '<div class="alert alert-warning small">Execute <code>database/cj_anuncio_assinatura.sql</code> para habilitar planos e cobrança PIX.</div>',
		]);
		return parent::getPanel('Conecta Jovem — Anúncios', $content, 'conect-anuncios');
	}

	/** @param array<int,array<string,mixed>> $rows */
	private static function renderRows(array $rows, array $metricas, bool $comAcoesMod): string {
		$html = '';
		foreach ($rows as $row) {
			$id = (int)($row['id'] ?? 0);
			$map = ConectAnuncioHelper::mapAdmin($row, $metricas[$id] ?? null);
			$status = (string)($row['status'] ?? '');
			$badge = match ($status) {
				'ativo'     => 'success',
				'pendente'  => 'warning text-dark',
				'pausado'   => 'secondary',
				'rejeitado' => 'danger',
				default     => 'light text-dark',
			};
			$owner = ($row['owner_tipo'] ?? '') === 'empresa'
				? htmlspecialchars((string)($row['empresa_nome'] ?? 'Empresa'))
				: 'Master / CTI';
			$geo = ($row['uf'] ?? '') !== '' || !empty($row['cidade_id'])
				? htmlspecialchars(trim(($row['cidade_nome'] ?? '').' '.($row['uf'] ?? '')))
				: 'Nacional';
			$acoes = '<a href="'.URL.'/master/conect-anuncios/editar/'.$id.'" class="btn btn-sm btn-outline-primary me-1">Editar</a>';
			if ($comAcoesMod && $status === 'pendente') {
				$acoes .= '<form method="post" action="'.URL.'/master/conect-anuncios/'.$id.'/aprovar" class="d-inline me-1">'
					.'<button type="submit" class="btn btn-sm btn-success">Aprovar</button></form>';
				$acoes .= '<button type="button" class="btn btn-sm btn-outline-danger btn-rejeitar" data-id="'.$id.'">Rejeitar</button>';
			}
			$acoes .= '<form method="post" action="'.URL.'/master/conect-anuncios/'.$id.'/excluir" class="d-inline ms-1" onsubmit="return confirm(\'Excluir anúncio?\');">'
				.'<button type="submit" class="btn btn-sm btn-outline-danger">Excluir</button></form>';

			$html .= '<tr>'
				.'<td><strong>'.htmlspecialchars($map['titulo']).'</strong><br><small class="text-muted">'.htmlspecialchars($map['nomeAnunciante']).'</small></td>'
				.'<td>'.$owner.'</td>'
				.'<td class="small">'.htmlspecialchars($map['slotLabel']).'</td>'
				.'<td class="small">'.$geo.'</td>'
				.'<td><span class="badge bg-'.$badge.'">'.htmlspecialchars($map['statusLabel']).'</span></td>'
				.'<td class="small">'.$map['impressoes'].' / '.$map['cliques'].' <span class="text-muted">('.$map['ctr'].'%)</span></td>'
				.'<td class="text-nowrap small">'.htmlspecialchars(substr((string)($row['created_at'] ?? ''), 0, 16)).'</td>'
				.'<td class="text-nowrap">'.$acoes.'</td>'
				.'</tr>';
		}
		return $html;
	}

	public static function editar($request, int $id): string {
		if (!CjAnuncio::tabelaExiste()) {
			header('Location: '.URL.'/master/conect-anuncios');
			exit;
		}
		$row = $id > 0 ? CjAnuncio::getById($id) : null;
		$imagemUrl = BrandingHelper::urlConectAnuncioImagem($row['imagem_arquivo'] ?? null);

		$cidadeId = (int)($row['cidade_id'] ?? 0);
		$estadoId = 0;
		if ($cidadeId > 0) {
			$loc = ConectEnderecoHelper::localPorCidadeId($cidadeId);
			$estadoId = (int)($loc['estadoId'] ?? 0);
		} elseif (!empty($row['uf'])) {
			$ufBusca = strtoupper(trim((string)$row['uf']));
			$est = EstadoCidades::getEstados("sigla = '".$ufBusca."'", null, '1')->fetch(\PDO::FETCH_ASSOC);
			if (is_array($est)) {
				$estadoId = (int)($est['id'] ?? 0);
			}
		}

		$slotOpts = '';
		foreach (ConectAnuncioHelper::SLOTS as $k => $lbl) {
			$rowSlot = ConectAnuncioHelper::slotLegado((string)($row['slot'] ?? 'footer_carousel'));
			$sel = $rowSlot === $k ? 'selected' : '';
			$dim = ConectAnuncioHelper::dimensaoSlot($k);
			$slotOpts .= '<option value="'.htmlspecialchars($k).'" data-sugestao="'.htmlspecialchars($dim['sugestao']).'" data-hint="'.htmlspecialchars($dim['hint']).'" '.$sel.'>'.htmlspecialchars($lbl).'</option>';
		}
		$linkTipo = (string)($row['link_tipo'] ?? 'url');
		$statusOpts = '';
		foreach (['rascunho', 'pendente', 'ativo', 'pausado', 'rejeitado'] as $st) {
			$sel = ($row['status'] ?? 'ativo') === $st ? 'selected' : '';
			$statusOpts .= '<option value="'.$st.'" '.$sel.'>'.htmlspecialchars(ConectAnuncioHelper::STATUS_LABELS[$st] ?? $st).'</option>';
		}

		$content = View::render('master/modules/conect-anuncios/edit', [
			'id'              => $id,
			'titulo_pagina'   => $id > 0 ? 'Editar anúncio' : 'Novo anúncio global',
			'titulo'          => htmlspecialchars((string)($row['titulo'] ?? ''), ENT_QUOTES, 'UTF-8'),
			'nome_anunciante' => htmlspecialchars((string)($row['nome_anunciante'] ?? ''), ENT_QUOTES, 'UTF-8'),
			'slot_opts'       => $slotOpts,
			'link_tipo_url'   => $linkTipo === 'url' ? 'selected' : '',
			'link_tipo_insta' => $linkTipo === 'instagram' ? 'selected' : '',
			'link_tipo_wa'    => $linkTipo === 'whatsapp' ? 'selected' : '',
			'link_destino'    => htmlspecialchars((string)($row['link_destino'] ?? ''), ENT_QUOTES, 'UTF-8'),
			'whatsapp'        => htmlspecialchars((string)($row['whatsapp'] ?? ''), ENT_QUOTES, 'UTF-8'),
			'estado_id'       => $estadoId,
			'cidade_id'       => $cidadeId,
			'estados_json'    => json_encode(self::listarEstados(), JSON_UNESCAPED_UNICODE),
			'ordem'           => (int)($row['ordem'] ?? 0),
			'inicio_em'       => !empty($row['inicio_em']) ? date('Y-m-d\TH:i', strtotime($row['inicio_em'])) : '',
			'fim_em'          => !empty($row['fim_em']) ? date('Y-m-d\TH:i', strtotime($row['fim_em'])) : '',
			'valor_mensal'    => isset($row['valor_mensal']) ? htmlspecialchars((string)$row['valor_mensal'], ENT_QUOTES, 'UTF-8') : '',
			'status_opts'     => $statusOpts,
			'imagem_preview'  => $imagemUrl ? htmlspecialchars($imagemUrl, ENT_QUOTES, 'UTF-8') : '',
			'imagem_style'    => $imagemUrl ? '' : 'display:none',
		]);
		return parent::getPanel($id > 0 ? 'Editar anúncio' : 'Novo anúncio global', $content, 'conect-anuncios');
	}

	public static function salvar($request): string {
		if (!CjAnuncio::tabelaExiste()) {
			return json_encode(['success' => false, 'message' => 'Execute database/cj_anuncios.sql.']);
		}
		$post = $request->getPostVars() ?: [];
		$files = $request->getFileVars();
		$id = (int)($post['id'] ?? 0);
		$atual = $id > 0 ? CjAnuncio::getById($id) : null;

		$upload = BrandingHelper::processarUploadConectAnuncioDetalhe(
			$files['imagem'] ?? null,
			$atual['imagem_arquivo'] ?? null
		);
		if (!empty($upload['error'])) {
			return json_encode(['success' => false, 'message' => $upload['error']]);
		}
		$imagem = $upload['filename'];
		if (!empty($post['remover_imagem'])) {
			$imagem = null;
		}

		$estadoId = (int)($post['estado_id'] ?? 0);
		$cidadeId = (int)($post['cidade_id'] ?? 0);
		$ufPost = '';
		if ($estadoId > 0) {
			$est = EstadoCidades::getEstados('id = '.$estadoId, null, '1')->fetch(\PDO::FETCH_ASSOC);
			if (is_array($est)) {
				$ufPost = strtoupper(trim((string)($est['sigla'] ?? '')));
			}
		}

		$valid = ConectAnuncioHelper::validarPayload(array_merge($post, [
			'imagem_arquivo' => $imagem ?: ($atual['imagem_arquivo'] ?? ''),
			'uf'             => $ufPost,
			'cidade_id'      => $cidadeId,
		]), !$imagem && empty($atual['imagem_arquivo']), true);
		if (empty($valid['ok'])) {
			return json_encode(['success' => false, 'message' => $valid['message'] ?? 'Dados inválidos.']);
		}

		$dados = $valid['data'];
		$dados['publisher'] = 'conecta_jovem';
		$dados['owner_tipo'] = 'master';
		$dados['id_empresa'] = null;
		$dados['id_admin'] = null;
		if ($imagem) {
			$dados['imagem_arquivo'] = $imagem;
		} elseif (!empty($post['remover_imagem'])) {
			return json_encode(['success' => false, 'message' => 'Imagem obrigatória.']);
		}

		$status = (string)($post['status'] ?? 'ativo');
		if (!in_array($status, ['rascunho', 'pendente', 'ativo', 'pausado', 'rejeitado', 'expirado'], true)) {
			$status = 'ativo';
		}
		$dados['status'] = $status;

		if ($id > 0) {
			CjAnuncio::atualizar($id, $dados);
		} else {
			if (empty($dados['imagem_arquivo'])) {
				return json_encode(['success' => false, 'message' => 'Envie a imagem do banner.']);
			}
			$id = CjAnuncio::inserir($dados);
		}

		return json_encode(['success' => true, 'message' => 'Salvo.', 'redirect' => URL.'/master/conect-anuncios']);
	}

	public static function salvarConfig($request): string {
		if (!CjAnuncioConfig::tabelaExiste()) {
			return json_encode(['success' => false, 'message' => 'Execute database/cj_anuncios.sql.']);
		}
		$post = $request->getPostVars() ?: [];
		$slots = $post['slots'] ?? [];
		if (!is_array($slots)) {
			$slots = [];
		}
		$preco = str_replace(',', '.', preg_replace('/[^\d,.-]/', '', (string)($post['preco_minimo_mensal'] ?? '99')));

		CjAnuncioConfig::salvar([
			'preco_minimo_mensal'      => (float)$preco,
			'max_anuncios_por_empresa' => (int)($post['max_anuncios_por_empresa'] ?? 3),
			'requer_aprovacao_master'  => !empty($post['requer_aprovacao_master']),
			'slots_habilitados'        => array_values(array_filter($slots, static fn($s) => isset(ConectAnuncioHelper::SLOTS[$s]))),
		]);

		return json_encode(['success' => true, 'message' => 'Configuração salva.']);
	}

	public static function salvarPlano($request): string {
		if (!CjAnuncioPlano::tabelaExiste()) {
			return json_encode(['success' => false, 'message' => 'Execute database/cj_anuncio_assinatura.sql.']);
		}
		$post = $request->getPostVars() ?: [];
		$id = (int)($post['id'] ?? 0);
		$valor = str_replace(',', '.', preg_replace('/[^\d,.-]/', '', (string)($post['valor_mensal'] ?? '0')));

		if ($id > 0) {
			$plano = CjAnuncioPlano::getById($id);
			if (!$plano instanceof CjAnuncioPlano) {
				return json_encode(['success' => false, 'message' => 'Plano não encontrado.']);
			}
		} else {
			$plano = new CjAnuncioPlano();
			$plano->slug = trim((string)($post['slug'] ?? ''));
		}

		$plano->nome = trim((string)($post['nome'] ?? ''));
		$plano->descricao = trim((string)($post['descricao'] ?? ''));
		$plano->max_anuncios = max(1, (int)($post['max_anuncios'] ?? 1));
		$plano->valor_mensal = (float)$valor;
		$plano->ordem = (int)($post['ordem'] ?? 0);
		$plano->ativo = !empty($post['ativo']) ? 1 : 0;

		if ($plano->nome === '') {
			return json_encode(['success' => false, 'message' => 'Informe o nome do plano.']);
		}
		if ($id <= 0 && ($plano->slug === '' || !preg_match('/^[a-z0-9_-]+$/', $plano->slug))) {
			return json_encode(['success' => false, 'message' => 'Slug inválido (use letras minúsculas, números, _ ou -).']);
		}

		$ok = $id > 0 ? $plano->atualizar() : $plano->cadastrar();
		return json_encode(['success' => (bool)$ok, 'message' => $ok ? 'Plano salvo.' : 'Falha ao salvar.']);
	}

	private static function renderPlanosRows(): string {
		if (!CjAnuncioPlano::tabelaExiste()) {
			return '<tr><td colspan="6" class="text-muted">Tabela de planos não instalada.</td></tr>';
		}
		$rows = CjAnuncioPlano::listarTodos();
		if ($rows === []) {
			return '<tr><td colspan="6" class="text-muted">Nenhum plano cadastrado.</td></tr>';
		}
		$html = '';
		foreach ($rows as $row) {
			$id = (int)($row['id'] ?? 0);
			$ativo = !empty($row['ativo']) ? 'Sim' : 'Não';
			$valor = number_format((float)($row['valor_mensal'] ?? 0), 2, ',', '.');
			$html .= '<tr data-plano-id="'.$id.'">'
				.'<td><strong>'.htmlspecialchars((string)($row['nome'] ?? '')).'</strong><br><code class="small">'.htmlspecialchars((string)($row['slug'] ?? '')).'</code></td>'
				.'<td class="small">'.htmlspecialchars((string)($row['descricao'] ?? '')).'</td>'
				.'<td>'.(int)($row['max_anuncios'] ?? 0).'</td>'
				.'<td>R$ '.$valor.'</td>'
				.'<td>'.$ativo.'</td>'
				.'<td><button type="button" class="btn btn-sm btn-outline-primary btn-edit-plano" '
				.'data-id="'.$id.'" data-slug="'.htmlspecialchars((string)($row['slug'] ?? '')).'" '
				.'data-nome="'.htmlspecialchars((string)($row['nome'] ?? '')).'" '
				.'data-desc="'.htmlspecialchars((string)($row['descricao'] ?? '')).'" '
				.'data-max="'.(int)($row['max_anuncios'] ?? 1).'" '
				.'data-valor="'.$valor.'" '
				.'data-ordem="'.(int)($row['ordem'] ?? 0).'" '
				.'data-ativo="'.(!empty($row['ativo']) ? '1' : '0').'">Editar</button></td>'
				.'</tr>';
		}
		return $html;
	}

	private static function renderAssinaturasRows(): string {
		if (!CjAnuncioAssinatura::tabelaExiste()) {
			return '<tr><td colspan="5" class="text-muted">Tabela de assinaturas não instalada.</td></tr>';
		}
		$rows = CjAnuncioAssinatura::listarMaster(100);
		if ($rows === []) {
			return '<tr><td colspan="5" class="text-muted">Nenhuma assinatura ainda.</td></tr>';
		}
		$html = '';
		foreach ($rows as $row) {
			$badge = match ((string)($row['status'] ?? '')) {
				'ativa'     => 'success',
				'pendente'  => 'warning text-dark',
				'cancelada' => 'secondary',
				'expirada'  => 'danger',
				default     => 'light text-dark',
			};
			$html .= '<tr>'
				.'<td>'.htmlspecialchars((string)($row['empresa_nome'] ?? 'Empresa #'.($row['id_empresa'] ?? ''))).'</td>'
				.'<td>'.htmlspecialchars((string)($row['plano_nome'] ?? '')).'</td>'
				.'<td><span class="badge bg-'.$badge.'">'.htmlspecialchars((string)($row['status'] ?? '')).'</span></td>'
				.'<td class="small">'.htmlspecialchars((string)($row['proximo_vencimento'] ?? '—')).'</td>'
				.'<td class="small">'.htmlspecialchars(substr((string)($row['created_at'] ?? ''), 0, 16)).'</td>'
				.'</tr>';
		}
		return $html;
	}

	public static function aprovar($request, int $id): void {
		if ($id > 0 && CjAnuncio::tabelaExiste()) {
			$user = SessionUser::getUserLogedData();
			CjAnuncio::atualizar($id, [
				'status'       => 'ativo',
				'aprovado_em'  => date('Y-m-d H:i:s'),
				'aprovado_por' => (int)($user['usuario']['id'] ?? 0),
				'motivo_rejeicao' => null,
			]);
		}
		header('Location: '.URL.'/master/conect-anuncios');
		exit;
	}

	public static function reprovar($request, int $id): string {
		if ($id <= 0 || !CjAnuncio::tabelaExiste()) {
			return json_encode(['success' => false, 'message' => 'Anúncio inválido.']);
		}
		$post = $request->getPostVars() ?: [];
		$motivo = trim((string)($post['motivo'] ?? ''));
		CjAnuncio::atualizar($id, [
			'status'          => 'rejeitado',
			'motivo_rejeicao' => mb_substr($motivo, 0, 500),
		]);
		return json_encode(['success' => true, 'message' => 'Anúncio rejeitado.']);
	}

	public static function excluir($request, int $id): void {
		if ($id > 0 && CjAnuncio::tabelaExiste()) {
			CjAnuncio::excluir($id);
		}
		header('Location: '.URL.'/master/conect-anuncios');
		exit;
	}

	public static function cidades($request): string {
		$post = $request->getPostVars() ?: [];
		$estadoId = (int)($post['estado'] ?? $post['estado_id'] ?? 0);
		$lista = [];
		if ($estadoId > 0) {
			$results = EstadoCidades::getCidades('estados_id = '.$estadoId, 'nome ASC');
			while ($c = $results->fetchObject()) {
				$lista[] = [
					'id'   => (int)$c->id,
					'nome' => (string)$c->nome,
				];
			}
		}
		return json_encode(['success' => true, 'cidades' => $lista], JSON_UNESCAPED_UNICODE);
	}

	/** @return array<int, array{id:int,nome:string,sigla:string}> */
	private static function listarEstados(): array {
		$out = [];
		$results = EstadoCidades::getEstados(null, 'nome ASC');
		while ($e = $results->fetchObject()) {
			$out[] = [
				'id'    => (int)$e->id,
				'nome'  => (string)$e->nome,
				'sigla' => (string)($e->sigla ?? ''),
			];
		}
		return $out;
	}
}

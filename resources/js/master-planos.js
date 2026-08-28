const MASTER_PLANOS_URL = 'master/planos';
const MASTER_EAD_CTI_URL = 'master/ead-cursos';

/** @type {number|null} */
let editingPlanoId = null;

const $modalPlano = () => $('#modalPlanoMaster');

function esc(s){
	return $('<div>').text(s == null ? '' : String(s)).html();
}

window.MASTER_CURSOS_CTI = window.MASTER_CURSOS_CTI || [];

function renderCursosChecks(selecionados) {
	const cursos = window.MASTER_CURSOS_CTI || [];
	const selOrder = (selecionados || []).map(function (id) { return String(id); });
	const sel = {};
	selOrder.forEach(function (id) { sel[id] = true; });

	const ordered = [];
	selOrder.forEach(function (id) {
		const c = cursos.find(function (x) { return String(x.id) === id; });
		if (c) ordered.push(c);
	});
	cursos.forEach(function (c) {
		if (!sel[String(c.id)]) ordered.push(c);
	});

	const $box = $('#lista-cursos-plano').empty();
	if (!cursos.length) {
		$box.append('<p class="text-muted small mb-0">Nenhum curso CTI cadastrado. Crie em Cursos CTI.</p>');
		return;
	}

	ordered.forEach(function (c) {
		const id = 'pcurso-' + c.id;
		const checked = !!sel[String(c.id)];
		const pub = c.publicado ? '' : ' <span class="badge bg-secondary">rascunho</span>';
		const thumb = c.cover_url
			? '<img src="' + esc(c.cover_url) + '" alt="" class="rounded me-2" style="width:40px;height:40px;object-fit:cover">'
			: '<span class="rounded bg-light d-inline-flex align-items-center justify-content-center me-2 text-muted" style="width:40px;height:40px"><i class="fas fa-book"></i></span>';
		const desc = c.short_description
			? '<div class="small text-muted text-truncate" style="max-width:320px">' + esc(c.short_description) + '</div>'
			: '';
		const meta = (c.aulas != null ? c.aulas + ' aula(s)' : '');

		$box.append(
			'<div class="curso-plano-row border rounded p-2 mb-1" data-id="' + c.id + '">'
			+ '<div class="d-flex align-items-start gap-1">'
			+ '<div class="btn-group-vertical btn-group-sm curso-ordem-btns">'
			+ '<button type="button" class="btn btn-outline-secondary btn-curso-up py-0" title="Subir"><i class="fas fa-chevron-up"></i></button>'
			+ '<button type="button" class="btn btn-outline-secondary btn-curso-down py-0" title="Descer"><i class="fas fa-chevron-down"></i></button>'
			+ '</div>'
			+ thumb
			+ '<div class="form-check flex-grow-1 mb-0">'
			+ '<input class="form-check-input chk-curso-plano" type="checkbox" id="' + id + '" value="' + c.id + '" ' + (checked ? 'checked' : '') + '>'
			+ '<label class="form-check-label w-100" for="' + id + '">'
			+ '<strong>' + esc(c.nome) + '</strong>' + pub
			+ (meta ? '<span class="small text-muted ms-1">· ' + esc(meta) + '</span>' : '')
			+ desc
			+ '</label></div></div></div>'
		);
	});
}

function coletarCursosIds() {
	const ids = [];
	$('.curso-plano-row').each(function () {
		const $chk = $(this).find('.chk-curso-plano');
		if ($chk.is(':checked')) {
			ids.push(parseInt($chk.val(), 10));
		}
	});
	return ids.filter(function (n) { return n > 0; });
}

function carregarCursosCti(callback) {
	$.post(url_base + MASTER_EAD_CTI_URL, { acao: 'listar_para_planos' }, function (res) {
		window.MASTER_CURSOS_CTI = (res && res.success && res.cursos) ? res.cursos : [];
		if (typeof callback === 'function') callback();
	}, 'json').fail(function () {
		window.MASTER_CURSOS_CTI = [];
		if (typeof callback === 'function') callback();
	});
}

function renderChecks(selecionados, todos){
	const mods = window.MASTER_MODULOS || [];
	const sel = {};
	(selecionados || []).forEach(function(s){ sel[s] = true; });
	const $box = $('#lista-modulos-plano').empty();
	mods.forEach(function(m){
		const id = 'pmod-'+m.slug;
		const checked = todos || !!sel[m.slug];
		$box.append(
			'<div class="col-md-4 col-sm-6"><div class="form-check">'
			+'<input class="form-check-input chk-mod-plano" type="checkbox" id="'+id+'" value="'+esc(m.slug)+'" '+(checked?'checked':'')+'>'
			+'<label class="form-check-label" for="'+id+'">'+esc(m.label)+'</label>'
			+'</div></div>'
		);
	});
	aplicarTodos();
}

function aplicarTodos(){
	const todos = $modalPlano().find('#plano_assinatura_todos_modulos').is(':checked');
	$('.chk-mod-plano').prop('disabled', todos);
	if(todos) $('.chk-mod-plano').prop('checked', true);
}

function coletarSlugs(){
	const slugs = [];
	$('.chk-mod-plano:checked').each(function(){ slugs.push($(this).val()); });
	return slugs;
}

function limpar(){
	editingPlanoId = null;
	const $m = $modalPlano();
	$m.find('#plano_assinatura_id').val('');
	$m.find('#plano_assinatura_nome, #plano_assinatura_descricao, #plano_assinatura_descricao_detalhada, #plano_assinatura_valor_mensal').val('');
	$m.find('#plano_assinatura_ordem').val('0');
	$m.find('#plano_assinatura_ativo').val('1');
	$m.find('#plano_assinatura_todos_modulos').prop('checked', false);
	$('#titulo-modal-plano').text('Novo plano');
	$('#badge-editando-plano').addClass('d-none').text('');
	renderChecks([], false);
	renderCursosChecks([]);
}

function renderLista(planos){
	const $tb = $('#lista-planos-master').empty();
	if(!planos || !planos.length){
		$tb.append('<tr><td colspan="6" class="text-center text-muted py-4">Nenhum plano ainda.</td></tr>');
		return;
	}
	planos.forEach(function(p){
		const badge = p.ativo ? '<span class="badge bg-success">Ativo</span>' : '<span class="badge bg-secondary">Inativo</span>';
		const mods = p.todos_modulos ? 'Todos' : ((p.modulos_qtd||0)+' módulos');
		const cti = (p.cursos_qtd || 0) > 0 ? (' · ' + p.cursos_qtd + ' curso(s) CTI') : '';
		const valor = (p.valor_br != null) ? ('R$ '+p.valor_br) : '—';
		const det = (p.descricao_detalhada || '').trim();
		const detBadge = det
			? ' <span class="badge bg-info text-dark" title="'+esc(det.substring(0, 200))+'">contrato</span>'
			: '';
		$tb.append(
			'<tr>'
			+'<td>'+esc(p.ordem)+'</td>'
			+'<td><strong>'+esc(p.nome)+'</strong>'+detBadge+'<br><small class="text-muted">'+esc(p.descricao||'')+'</small></td>'
			+'<td>'+esc(valor)+'</td>'
			+'<td>'+esc(mods)+esc(cti)+'</td>'
			+'<td>'+badge+'</td>'
			+'<td class="text-end">'
			+'<button type="button" class="btn btn-sm btn-outline-primary me-1 btn-editar-plano" data-id="'+p.id+'"><i class="fas fa-edit"></i></button>'
			+'<button type="button" class="btn btn-sm btn-outline-danger btn-excluir-plano" data-id="'+p.id+'"><i class="fas fa-trash"></i></button>'
			+'</td></tr>'
		);
	});
}

function carregar(){
	$.post(url_base + MASTER_PLANOS_URL, { acao: 'listar' }, function(res){
		if(!res || !res.success){
			Swal.fire('Erro', (res && res.message) || 'Falha.', 'error');
			return;
		}
		renderLista(res.planos || []);
	}, 'json');
}

function abrir(id){
	const planoId = parseInt(id, 10);
	if (!planoId) return;

	limpar();
	editingPlanoId = planoId;

	$.post(url_base + MASTER_PLANOS_URL, { acao: 'detalhes', id: planoId }, function(res){
		if(!res || !res.success){
			Swal.fire('Erro', (res && res.message) || 'Falha.', 'error');
			return;
		}
		const p = res.plano;
		const $m = $modalPlano();
		editingPlanoId = parseInt(p.id, 10) || planoId;
		$m.find('#plano_assinatura_id').val(editingPlanoId);
		$m.find('#plano_assinatura_nome').val(p.nome || '');
		$m.find('#plano_assinatura_descricao').val(p.descricao || '');
		$m.find('#plano_assinatura_descricao_detalhada').val(p.descricao_detalhada || '');
		$m.find('#plano_assinatura_valor_mensal').val(p.valor_br || '0,00');
		$m.find('#plano_assinatura_ordem').val(p.ordem || 0);
		$m.find('#plano_assinatura_ativo').val(p.ativo ? '1' : '0');
		$m.find('#plano_assinatura_todos_modulos').prop('checked', !!p.todos_modulos);
		$('#titulo-modal-plano').text('Editar plano');
		$('#badge-editando-plano').removeClass('d-none').text('#' + editingPlanoId);
		renderChecks(p.modulos || [], !!p.todos_modulos);
		renderCursosChecks(p.cursos_ids || []);
		$m.modal('show');
	}, 'json');
}

function salvar(){
	const $m = $modalPlano();
	const idFromField = parseInt($m.find('#plano_assinatura_id').val(), 10) || 0;
	const id = editingPlanoId || idFromField;
	const isEdit = editingPlanoId !== null && editingPlanoId > 0;

	if (isEdit && id <= 0) {
		Swal.fire('Erro', 'ID do plano inválido. Feche o modal e tente editar novamente.', 'error');
		return;
	}

	const dados = {
		acao: 'salvar',
		modo: isEdit ? 'editar' : 'criar',
		id: id,
		nome: $m.find('#plano_assinatura_nome').val(),
		descricao: $m.find('#plano_assinatura_descricao').val(),
		descricao_detalhada: $m.find('#plano_assinatura_descricao_detalhada').val(),
		valor_mensal: $m.find('#plano_assinatura_valor_mensal').val(),
		ordem: $m.find('#plano_assinatura_ordem').val(),
		ativo: $m.find('#plano_assinatura_ativo').val(),
		todos_modulos: $m.find('#plano_assinatura_todos_modulos').is(':checked') ? 1 : 0,
		modulos_json: JSON.stringify(coletarSlugs()),
		cursos_json: JSON.stringify(coletarCursosIds())
	};
	if(!String(dados.nome||'').trim()){
		Swal.fire('Atenção', 'Informe o nome do plano.', 'warning');
		return;
	}
	$.post(url_base + MASTER_PLANOS_URL, dados, function(res){
		if(!res || !res.success){
			Swal.fire('Erro', (res && res.message) || 'Falha.', 'error');
			return;
		}
		$m.modal('hide');
		Swal.fire('OK', res.message, 'success');
		carregar();
	}, 'json');
}

$(function(){
	carregarCursosCti(function () {
		renderChecks([], false);
		renderCursosChecks([]);
	});
	carregar();
	$('#btn-novo-plano-assinatura').on('click', function(){
		limpar();
		$modalPlano().modal('show');
	});
	$('#btn-salvar-plano-assinatura').on('click', salvar);
	$modalPlano().find('#plano_assinatura_todos_modulos').on('change', aplicarTodos);
	$(document).on('click', '.btn-editar-plano', function(){ abrir($(this).data('id')); });
	$(document).on('click', '.btn-excluir-plano', function(){
		const id = $(this).data('id');
		Swal.fire({ title: 'Excluir plano?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Excluir' })
			.then(function(r){
				if(!r.isConfirmed) return;
				$.post(url_base + MASTER_PLANOS_URL, { acao: 'excluir', id: id }, function(res){
					if(!res || !res.success){
						Swal.fire('Erro', (res && res.message) || 'Falha.', 'error');
						return;
					}
					carregar();
				}, 'json');
			});
	});
	$(document).on('click', '.btn-curso-up', function () {
		const $row = $(this).closest('.curso-plano-row');
		const $prev = $row.prev('.curso-plano-row');
		if ($prev.length) $row.insertBefore($prev);
	});
	$(document).on('click', '.btn-curso-down', function () {
		const $row = $(this).closest('.curso-plano-row');
		const $next = $row.next('.curso-plano-row');
		if ($next.length) $row.insertAfter($next);
	});
	$modalPlano().on('hidden.bs.modal', limpar);
});

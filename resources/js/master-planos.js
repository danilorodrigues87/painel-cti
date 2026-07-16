const MASTER_PLANOS_URL = 'master/planos';

function esc(s){
	return $('<div>').text(s == null ? '' : String(s)).html();
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
	const todos = $('#plano_todos_modulos').is(':checked');
	$('.chk-mod-plano').prop('disabled', todos);
	if(todos) $('.chk-mod-plano').prop('checked', true);
}

function coletarSlugs(){
	const slugs = [];
	$('.chk-mod-plano:checked').each(function(){ slugs.push($(this).val()); });
	return slugs;
}

function limpar(){
	$('#plano_id').val('');
	$('#plano_nome, #plano_descricao').val('');
	$('#plano_ordem').val('0');
	$('#plano_ativo').val('1');
	$('#plano_todos_modulos').prop('checked', false);
	$('#titulo-modal-plano').text('Novo plano');
	renderChecks([], false);
}

function renderLista(planos){
	const $tb = $('#lista-planos-master').empty();
	if(!planos || !planos.length){
		$tb.append('<tr><td colspan="5" class="text-center text-muted py-4">Nenhum plano ainda.</td></tr>');
		return;
	}
	planos.forEach(function(p){
		const badge = p.ativo ? '<span class="badge bg-success">Ativo</span>' : '<span class="badge bg-secondary">Inativo</span>';
		const mods = p.todos_modulos ? 'Todos' : ((p.modulos_qtd||0)+' módulos');
		$tb.append(
			'<tr>'
			+'<td>'+esc(p.ordem)+'</td>'
			+'<td><strong>'+esc(p.nome)+'</strong><br><small class="text-muted">'+esc(p.descricao||'')+'</small></td>'
			+'<td>'+esc(mods)+'</td>'
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
	$.post(url_base + MASTER_PLANOS_URL, { acao: 'detalhes', id: id }, function(res){
		if(!res || !res.success){
			Swal.fire('Erro', (res && res.message) || 'Falha.', 'error');
			return;
		}
		const p = res.plano;
		$('#plano_id').val(p.id);
		$('#plano_nome').val(p.nome || '');
		$('#plano_descricao').val(p.descricao || '');
		$('#plano_ordem').val(p.ordem || 0);
		$('#plano_ativo').val(p.ativo ? '1' : '0');
		$('#plano_todos_modulos').prop('checked', !!p.todos_modulos);
		$('#titulo-modal-plano').text('Editar plano');
		renderChecks(p.modulos || [], !!p.todos_modulos);
		$('#modalPlanoMaster').modal('show');
	}, 'json');
}

function salvar(){
	const dados = {
		acao: 'salvar',
		id: $('#plano_id').val(),
		nome: $('#plano_nome').val(),
		descricao: $('#plano_descricao').val(),
		ordem: $('#plano_ordem').val(),
		ativo: $('#plano_ativo').val(),
		todos_modulos: $('#plano_todos_modulos').is(':checked') ? 1 : 0,
		modulos_json: JSON.stringify(coletarSlugs())
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
		$('#modalPlanoMaster').modal('hide');
		Swal.fire('OK', res.message, 'success');
		carregar();
	}, 'json');
}

$(function(){
	renderChecks([], false);
	carregar();
	$('#btn-novo-plano').on('click', function(){
		limpar();
		$('#modalPlanoMaster').modal('show');
	});
	$('#btn-salvar-plano').on('click', salvar);
	$('#plano_todos_modulos').on('change', aplicarTodos);
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
	$('#modalPlanoMaster').on('hidden.bs.modal', limpar);
});

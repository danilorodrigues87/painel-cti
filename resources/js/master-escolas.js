const MASTER_ESCOLAS_URL = 'master/escolas';
let masterEscolasCache = [];

function esc(s){
	return $('<div>').text(s == null ? '' : String(s)).html();
}

function temPlanId(){
	return String(window.MASTER_TEM_PLAN_ID) === '1';
}

function popularSelectPlanos(selected){
	const $sel = $('#escola_plan_id').empty();
	$sel.append('<option value="">Personalizado (módulos manuais)</option>');
	(window.MASTER_PLANOS || []).forEach(function(p){
		$sel.append('<option value="'+p.id+'">'+esc(p.nome)+'</option>');
	});
	if(selected) $sel.val(String(selected));
}

function renderModulosChecks(selecionados, todos){
	const mods = window.MASTER_MODULOS || [];
	const sel = {};
	(selecionados || []).forEach(function(s){ sel[s] = true; });
	const $box = $('#lista-modulos-master').empty();
	mods.forEach(function(m){
		const id = 'mod-'+m.slug;
		const checked = todos || !!sel[m.slug];
		$box.append(
			'<div class="col-md-4 col-sm-6">'
			+'<div class="form-check">'
			+'<input class="form-check-input chk-mod-master" type="checkbox" id="'+id+'" value="'+esc(m.slug)+'" '+(checked?'checked':'')+'>'
			+'<label class="form-check-label" for="'+id+'">'+esc(m.label)+'</label>'
			+'</div></div>'
		);
	});
	aplicarUiPlanoModulos();
}

function aplicarUiPlanoModulos(){
	const planId = $('#escola_plan_id').val();
	const comPlano = !!planId;
	$('#todos_modulos, .chk-mod-master').prop('disabled', comPlano);
	if(comPlano){
		const plano = (window.MASTER_PLANOS || []).find(function(p){ return String(p.id) === String(planId); });
		if(plano){
			if(plano.todos_modulos){
				$('#todos_modulos').prop('checked', true);
				$('.chk-mod-master').prop('checked', true);
			} else {
				$('#todos_modulos').prop('checked', false);
				const set = {};
				(plano.modulos || []).forEach(function(s){ set[s] = true; });
				$('.chk-mod-master').each(function(){
					$(this).prop('checked', !!set[$(this).val()]);
				});
			}
		}
		$('#hint-modulos-plano').text('Módulos definidos pelo plano selecionado.');
	} else {
		const todos = $('#todos_modulos').is(':checked');
		$('.chk-mod-master').prop('disabled', todos);
		if(todos) $('.chk-mod-master').prop('checked', true);
		$('#hint-modulos-plano').text('Sem plano: escolha módulos manualmente.');
	}
}

function coletarSlugs(){
	const slugs = [];
	$('.chk-mod-master:checked').each(function(){ slugs.push($(this).val()); });
	return slugs;
}

function limparForm(){
	$('#escola_id').val('');
	$('#escola_nome, #escola_email, #escola_telefone, #escola_cpf_cnpj, #escola_site').val('');
	$('#escola_cep, #escola_endereco, #escola_numero, #escola_bairro, #escola_cidade, #escola_estado').val('');
	$('#diretor_nome, #diretor_email').val('');
	$('#escola_ativo').val('1');
	$('#todos_modulos').prop('checked', true);
	popularSelectPlanos('');
	$('#wrap-diretor-novo').show();
	$('#titulo-modal-escola').text('Nova escola');
	renderModulosChecks([], true);
}

function mostrarSenhaDiretor(titulo, diretor){
	Swal.fire({
		icon: 'success',
		title: titulo,
		html: '<p class="small mb-1"><strong>Diretor:</strong> '+esc(diretor.nome)+'</p>'
			+'<p class="small mb-1"><strong>E-mail:</strong> '+esc(diretor.email)+'</p>'
			+'<p class="small mb-0"><strong>Senha temporária:</strong> <code>'+esc(diretor.senha)+'</code></p>'
			+'<p class="text-danger small mt-2 mb-0">Anote agora — não será exibida novamente.</p>',
		width: 520
	});
}

function renderLista(escolas){
	const filtro = ($('#filtro-escola').val() || '').toLowerCase().trim();
	const $tb = $('#lista-escolas-master').empty();
	const lista = (escolas || []).filter(function(e){
		if(!filtro) return true;
		return String(e.nome || '').toLowerCase().indexOf(filtro) !== -1
			|| String(e.email || '').toLowerCase().indexOf(filtro) !== -1;
	});

	if(!lista.length){
		$tb.append('<tr><td colspan="6" class="text-center text-muted py-4">Nenhuma escola encontrada.</td></tr>');
		return;
	}

	lista.forEach(function(e){
		const badge = e.ativo
			? '<span class="badge bg-success">Ativa</span>'
			: '<span class="badge bg-secondary">Inativa</span>';
		const plano = e.plano_nome ? esc(e.plano_nome) : '<span class="text-muted">Personalizado</span>';
		$tb.append(
			'<tr>'
			+'<td>'+esc(e.id)+'</td>'
			+'<td><strong>'+esc(e.nome)+'</strong></td>'
			+'<td class="small">'+plano+'</td>'
			+'<td class="small">'+esc(e.email || '—')+'<br>'+esc(e.telefone || '')+'</td>'
			+'<td>'+badge+'</td>'
			+'<td class="text-end text-nowrap">'
			+'<button type="button" class="btn btn-sm btn-outline-success me-1 btn-impersonar" data-id="'+e.id+'" title="Entrar na escola"><i class="fas fa-sign-in-alt"></i></button>'
			+'<button type="button" class="btn btn-sm btn-outline-warning me-1 btn-reset-diretor" data-id="'+e.id+'" title="Reset senha Diretor"><i class="fas fa-key"></i></button>'
			+'<button type="button" class="btn btn-sm btn-outline-primary me-1 btn-editar-escola" data-id="'+e.id+'"><i class="fas fa-edit"></i></button>'
			+'<button type="button" class="btn btn-sm btn-outline-'+(e.ativo?'secondary':'success')+' btn-toggle-escola" data-id="'+e.id+'">'
			+(e.ativo?'Off':'On')
			+'</button>'
			+'</td>'
			+'</tr>'
		);
	});
}

function carregarEscolas(){
	$.post(url_base + MASTER_ESCOLAS_URL, { acao: 'listar' }, function(res){
		if(!res || !res.success){
			Swal.fire('Erro', (res && res.message) || 'Falha ao listar.', 'error');
			return;
		}
		masterEscolasCache = res.escolas || [];
		renderLista(masterEscolasCache);
	}, 'json').fail(function(){
		Swal.fire('Erro', 'Falha ao carregar escolas.', 'error');
	});
}

function abrirEdicao(id){
	$.post(url_base + MASTER_ESCOLAS_URL, { acao: 'detalhes', id: id }, function(res){
		if(!res || !res.success){
			Swal.fire('Erro', (res && res.message) || 'Falha ao carregar.', 'error');
			return;
		}
		const e = res.escola;
		$('#escola_id').val(e.id);
		$('#escola_nome').val(e.nome || '');
		$('#escola_email').val(e.email || '');
		$('#escola_telefone').val(e.telefone || '');
		$('#escola_cpf_cnpj').val(e.cpf_cnpj || '');
		$('#escola_site').val(e.site || '');
		$('#escola_cep').val(e.cep || '');
		$('#escola_endereco').val(e.endereco || '');
		$('#escola_numero').val(e.numero || '');
		$('#escola_bairro').val(e.bairro || '');
		$('#escola_cidade').val(e.cidade || '');
		$('#escola_estado').val(e.estado || '');
		$('#escola_ativo').val(e.ativo ? '1' : '0');
		$('#todos_modulos').prop('checked', !!e.todos_modulos);
		popularSelectPlanos(e.plan_id || '');
		$('#wrap-diretor-novo').hide();
		$('#titulo-modal-escola').text('Editar escola #'+e.id);
		renderModulosChecks(e.modulos || [], !!e.todos_modulos);
		$('#modalEscolaMaster').modal('show');
	}, 'json');
}

function salvarEscola(){
	const id = $('#escola_id').val();
	const dados = {
		acao: 'salvar',
		id: id,
		nome: $('#escola_nome').val(),
		email: $('#escola_email').val(),
		telefone: $('#escola_telefone').val(),
		cpf_cnpj: $('#escola_cpf_cnpj').val(),
		site: $('#escola_site').val(),
		cep: $('#escola_cep').val(),
		endereco: $('#escola_endereco').val(),
		numero: $('#escola_numero').val(),
		bairro: $('#escola_bairro').val(),
		cidade: $('#escola_cidade').val(),
		estado: $('#escola_estado').val(),
		ativo: $('#escola_ativo').val(),
		plan_id: $('#escola_plan_id').val() || '',
		todos_modulos: $('#todos_modulos').is(':checked') ? 1 : 0,
		modulos_json: JSON.stringify(coletarSlugs()),
		diretor_nome: $('#diretor_nome').val(),
		diretor_email: $('#diretor_email').val()
	};

	if(!String(dados.nome || '').trim()){
		Swal.fire('Atenção', 'Informe o nome da escola.', 'warning');
		return;
	}
	if(!id && (!String(dados.diretor_nome||'').trim() || !String(dados.diretor_email||'').trim())){
		Swal.fire('Atenção', 'Informe nome e e-mail do Diretor.', 'warning');
		return;
	}

	$('#btn-salvar-escola').prop('disabled', true);
	$.post(url_base + MASTER_ESCOLAS_URL, dados, function(res){
		$('#btn-salvar-escola').prop('disabled', false);
		if(!res || !res.success){
			Swal.fire('Erro', (res && res.message) || 'Não foi possível salvar.', 'error');
			return;
		}
		$('#modalEscolaMaster').modal('hide');
		carregarEscolas();
		if(res.diretor && res.diretor.senha){
			mostrarSenhaDiretor('Escola criada', res.diretor);
			return;
		}
		Swal.fire('OK', res.message, 'success');
	}, 'json').fail(function(){
		$('#btn-salvar-escola').prop('disabled', false);
		Swal.fire('Erro', 'Falha ao salvar.', 'error');
	});
}

$(function(){
	if(!temPlanId()){
		$('#alert-plan-id').removeClass('d-none');
	}
	popularSelectPlanos('');
	renderModulosChecks([], true);
	carregarEscolas();

	$('#btn-nova-escola').on('click', function(){
		limparForm();
		$('#modalEscolaMaster').modal('show');
	});
	$('#btn-salvar-escola').on('click', salvarEscola);
	$('#todos_modulos').on('change', aplicarUiPlanoModulos);
	$('#escola_plan_id').on('change', aplicarUiPlanoModulos);
	$('#filtro-escola').on('input', function(){ renderLista(masterEscolasCache); });

	$(document).on('click', '.btn-editar-escola', function(){
		abrirEdicao($(this).data('id'));
	});
	$(document).on('click', '.btn-toggle-escola', function(){
		$.post(url_base + MASTER_ESCOLAS_URL, { acao: 'toggle_ativo', id: $(this).data('id') }, function(res){
			if(!res || !res.success){
				Swal.fire('Erro', (res && res.message) || 'Falha.', 'error');
				return;
			}
			carregarEscolas();
		}, 'json');
	});
	$(document).on('click', '.btn-reset-diretor', function(){
		const id = $(this).data('id');
		Swal.fire({
			title: 'Resetar senha do Diretor?',
			text: 'Uma senha temporária será gerada.',
			icon: 'question',
			showCancelButton: true,
			confirmButtonText: 'Sim, resetar'
		}).then(function(r){
			if(!r.isConfirmed) return;
			$.post(url_base + MASTER_ESCOLAS_URL, { acao: 'reset_diretor', id: id }, function(res){
				if(!res || !res.success){
					Swal.fire('Erro', (res && res.message) || 'Falha.', 'error');
					return;
				}
				mostrarSenhaDiretor('Senha redefinida', res.diretor);
			}, 'json');
		});
	});
	$(document).on('click', '.btn-impersonar', function(){
		const id = $(this).data('id');
		Swal.fire({
			title: 'Entrar nesta escola?',
			text: 'Você acessará o painel como o Diretor (modo suporte).',
			icon: 'question',
			showCancelButton: true,
			confirmButtonText: 'Entrar'
		}).then(function(r){
			if(!r.isConfirmed) return;
			$.post(url_base + MASTER_ESCOLAS_URL, { acao: 'impersonar', id: id }, function(res){
				if(!res || !res.success){
					Swal.fire('Erro', (res && res.message) || 'Falha.', 'error');
					return;
				}
				window.location.href = res.redirect || (url_base + 'painel');
			}, 'json');
		});
	});

	$('#modalEscolaMaster').on('hidden.bs.modal', limparForm);
});

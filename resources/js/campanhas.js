const CAMPANHAS_URL = 'painel/campanhas';

function badgeStatus(status){
	const mapa = {
		rascunho: 'secondary',
		agendada: 'info',
		enviando: 'primary',
		concluida: 'success',
		pausada: 'warning',
		cancelada: 'dark'
	};
	return mapa[status] || 'secondary';
}

function badgeCanal(canal){
	return canal === 'whatsapp' ? 'success' : 'secondary';
}

function atualizarUiCanal(){
	const wa = $('#campanha_canal').val() === 'whatsapp';
	$('#wrap-assunto').toggle(!wa);
	$('#label-mensagem').text(wa ? 'Mensagem WhatsApp *' : 'Mensagem (HTML simples) *');
	$('#hint-mensagem').text(wa
		? 'Texto para WhatsApp. Variáveis: {nome}, {whatsapp}, {curso}, {escola}.'
		: 'No e-mail pode usar HTML simples. Variáveis: {nome}, {email}, {curso}, {escola}.');
	$('#campanha_assunto').prop('required', !wa);
}

function renderizarLista(campanhas){
	const $tbody = $('#lista-campanhas');
	$tbody.empty();

	if(!campanhas || !campanhas.length){
		$tbody.append('<tr><td colspan="6" class="text-center text-muted py-4">Nenhuma campanha ainda.</td></tr>');
		return;
	}

	campanhas.forEach(function(c){
		const progresso = c.total > 0
			? Math.round(((c.enviados + c.erros) / c.total) * 100)
			: 0;

		let acoes = '<button class="btn btn-sm btn-outline-secondary me-1 btn-detalhes" data-id="'+c.id+'"><i class="fas fa-eye"></i></button>';

		if(c.status === 'rascunho' || c.status === 'pausada'){
			acoes += '<button class="btn btn-sm btn-success me-1 btn-iniciar" data-id="'+c.id+'"><i class="fas fa-paper-plane"></i></button>';
			acoes += '<button class="btn btn-sm btn-outline-primary btn-editar" data-id="'+c.id+'"><i class="fas fa-edit"></i></button>';
		}
		if(c.status === 'enviando'){
			acoes += '<button class="btn btn-sm btn-warning me-1 btn-pausar" data-id="'+c.id+'"><i class="fas fa-pause"></i></button>';
		}
		if(c.status !== 'concluida' && c.status !== 'cancelada'){
			acoes += '<button class="btn btn-sm btn-outline-danger btn-cancelar" data-id="'+c.id+'"><i class="fas fa-times"></i></button>';
		}

		const sub = c.canal === 'whatsapp' ? (c.titulo || '') : (c.assunto || '');
		$tbody.append(`
			<tr>
				<td><strong>${escHtml(c.titulo)}</strong><br><small class="text-muted">${escHtml(sub)}</small></td>
				<td><span class="badge bg-${badgeCanal(c.canal)}">${escHtml(c.canal_label || c.canal)}</span></td>
				<td><span class="badge bg-${badgeStatus(c.status)}">${escHtml(c.status_label)}</span></td>
				<td>
					<div class="small">${c.enviados} enviados · ${c.erros} erros · ${c.pendentes} pendentes</div>
					<div class="progress" style="height:6px;">
						<div class="progress-bar" style="width:${progresso}%"></div>
					</div>
				</td>
				<td>${escHtml(c.criada_em)}</td>
				<td class="text-end">${acoes}</td>
			</tr>
		`);
	});
}

function escHtml(s){
	return $('<div>').text(s == null ? '' : String(s)).html();
}

function coletarFormulario(){
	return {
		acao: 'salvar',
		id: $('#campanha_id').val(),
		canal: $('#campanha_canal').val(),
		titulo: $('#campanha_titulo').val(),
		assunto: $('#campanha_assunto').val(),
		mensagem: $('#campanha_mensagem').val(),
		segmento_tipo: $('#segmento_tipo').val(),
		status_lead: $('#status_lead').val()
	};
}

function limparFormulario(){
	$('#campanha_id').val('');
	$('#campanha_canal').val('email');
	$('#campanha_titulo').val('');
	$('#campanha_assunto').val('');
	$('#campanha_mensagem').val('');
	$('#segmento_tipo').val('alunos_matriculados');
	$('#status_lead').val('');
	$('#preview-resultado').text('');
	$('#titulo-modal-campanha').text('Nova campanha');
	$('#wrap-status-lead').hide();
	atualizarUiCanal();
}

function carregarCampanhas(){
	$.post(url_base + CAMPANHAS_URL, {
		acao: 'listar',
		canal: $('#filtro-canal').val() || ''
	}, function(res){
		if(!res || !res.success){
			Swal.fire('Erro', (res && res.message) ? res.message : 'Falha ao listar.', 'error');
			return;
		}
		renderizarLista(res.campanhas);
	}, 'json').fail(function(){
		Swal.fire('Erro', 'Falha ao carregar campanhas.', 'error');
	});
}

function salvarCampanha(){
	const dados = coletarFormulario();
	$.post(url_base + CAMPANHAS_URL, dados, function(res){
		if(!res || !res.success){
			Swal.fire('Erro', (res && res.message) ? res.message : 'Não foi possível salvar.', 'error');
			return;
		}
		Swal.fire('Salvo', res.message, 'success');
		$('#modalCampanha').modal('hide');
		carregarCampanhas();
	}, 'json');
}

function previewPublico(){
	const dados = coletarFormulario();
	dados.acao = 'preview';
	$('#btn-preview-campanha').prop('disabled', true);
	$.post(url_base + CAMPANHAS_URL, dados, function(res){
		$('#btn-preview-campanha').prop('disabled', false);
		if(!res || !res.success){
			Swal.fire('Erro', (res && res.message) ? res.message : 'Falha no preview.', 'error');
			return;
		}
		const rotulo = res.canal === 'whatsapp' ? 'WhatsApp válido' : 'e-mail válido';
		let txt = res.total + ' destinatário(s) com '+rotulo+'.';
		if(res.amostra && res.amostra.length){
			txt += ' Ex.: ' + res.amostra.map(function(a){
				return a.nome + (a.contato ? ' ('+a.contato+')' : '');
			}).join(', ');
		}
		$('#preview-resultado').text(txt);
	}, 'json');
}

function acaoCampanha(acao, id, confirmar){
	const executar = function(){
		$.post(url_base + CAMPANHAS_URL, { acao: acao, id: id }, function(res){
			if(!res || !res.success){
				Swal.fire('Erro', (res && res.message) ? res.message : 'Falha na operação.', 'error');
				return;
			}
			Swal.fire('OK', res.message, 'success');
			carregarCampanhas();
		}, 'json');
	};

	if(confirmar){
		Swal.fire({
			title: 'Confirmar?',
			icon: 'question',
			showCancelButton: true,
			confirmButtonText: 'Sim'
		}).then(function(r){ if(r.isConfirmed) executar(); });
		return;
	}
	executar();
}

function abrirDetalhes(id){
	$.post(url_base + CAMPANHAS_URL, { acao: 'detalhes', id: id }, function(res){
		if(!res || !res.success){
			Swal.fire('Erro', (res && res.message) ? res.message : 'Falha ao carregar.', 'error');
			return;
		}
		const c = res.campanha;
		let errosHtml = '';
		if(res.erros && res.erros.length){
			errosHtml = '<hr><h6>Últimos erros</h6><ul class="small">';
			res.erros.forEach(function(e){
				errosHtml += '<li><strong>'+escHtml(e.nome)+'</strong> ('+escHtml(e.contato)+'): '+escHtml(e.erro)+'</li>';
			});
			errosHtml += '</ul>';
		}

		$('#body-detalhes-campanha').html(`
			<p><strong>Canal:</strong> ${escHtml(c.canal_label || c.canal)}</p>
			<p><strong>Assunto:</strong> ${escHtml(res.assunto || '—')}</p>
			<p><strong>Status:</strong> ${escHtml(c.status_label)}</p>
			<p><strong>Progresso:</strong> ${c.enviados} enviados, ${c.erros} erros, ${c.pendentes} pendentes de ${c.total}</p>
			<div class="border rounded p-3 bg-light small" style="white-space:pre-wrap;">${escHtml(res.mensagem)}</div>
			${errosHtml}
		`);
		$('#modalDetalhesCampanha').modal('show');
	}, 'json');
}

function editarCampanha(id){
	$.post(url_base + CAMPANHAS_URL, { acao: 'detalhes', id: id }, function(res){
		if(!res || !res.success){
			Swal.fire('Erro', (res && res.message) ? res.message : 'Falha ao carregar.', 'error');
			return;
		}
		const c = res.campanha;
		const seg = c.segmento || {};
		$('#campanha_id').val(c.id);
		$('#campanha_canal').val(c.canal || 'email');
		$('#campanha_titulo').val(c.titulo || '');
		$('#campanha_assunto').val(res.assunto || c.assunto || '');
		$('#campanha_mensagem').val(res.mensagem || c.mensagem || '');
		$('#segmento_tipo').val(seg.tipo || 'alunos_matriculados');
		$('#status_lead').val(seg.status_lead || '');
		$('#wrap-status-lead').toggle(seg.tipo === 'leads');
		$('#titulo-modal-campanha').text('Editar campanha');
		atualizarUiCanal();
		$('#modalCampanha').modal('show');
	}, 'json');
}

function processarFila(){
	$('#btn-processar-fila').prop('disabled', true);
	Swal.fire({ title: 'Processando fila...', allowOutsideClick: false, didOpen: function(){ Swal.showLoading(); } });
	$.post(url_base + CAMPANHAS_URL, { acao: 'processar', limite: 5 }, function(res){
		$('#btn-processar-fila').prop('disabled', false);
		Swal.close();
		if(!res || !res.success){
			Swal.fire('Erro', (res && res.message) ? res.message : 'Falha ao processar.', 'error');
			return;
		}
		Swal.fire('Fila', res.message, 'info');
		carregarCampanhas();
	}, 'json');
}

$(function(){
	carregarCampanhas();
	atualizarUiCanal();

	$('#campanha_canal').on('change', atualizarUiCanal);
	$('#filtro-canal').on('change', carregarCampanhas);

	$('#segmento_tipo').on('change', function(){
		$('#wrap-status-lead').toggle($(this).val() === 'leads');
	});

	$('#btn-salvar-campanha').on('click', salvarCampanha);
	$('#btn-preview-campanha').on('click', previewPublico);
	$('#btn-processar-fila').on('click', processarFila);

	$('#modalCampanha').on('hidden.bs.modal', limparFormulario);

	$(document).on('click', '.btn-iniciar', function(){
		acaoCampanha('iniciar', $(this).data('id'), true);
	});
	$(document).on('click', '.btn-pausar', function(){
		acaoCampanha('pausar', $(this).data('id'), false);
	});
	$(document).on('click', '.btn-cancelar', function(){
		acaoCampanha('cancelar', $(this).data('id'), true);
	});
	$(document).on('click', '.btn-detalhes', function(){
		abrirDetalhes($(this).data('id'));
	});
	$(document).on('click', '.btn-editar', function(){
		editarCampanha($(this).data('id'));
	});
});

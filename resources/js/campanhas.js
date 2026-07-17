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
	$('#wrap-emoji-campanha').toggleClass('d-none', !wa);
	$('#wrap-midia-campanha').toggleClass('d-none', !wa);
	$('#label-mensagem').text(wa ? 'Mensagem WhatsApp' : 'Mensagem (HTML simples) *');
	$('#hint-mensagem').text(wa
		? 'Texto e/ou mídia. Variáveis: {nome}, {whatsapp}, {curso}, {escola}. Em áudio, o texto vai como mensagem separada.'
		: 'No e-mail pode usar HTML simples. Variáveis: {nome}, {email}, {curso}, {escola}.');
	$('#campanha_assunto').prop('required', !wa);
	if(!wa){
		limparMidiaSelecionada(false);
	}
	atualizarUiSegmento();
}

function atualizarUiSegmento(){
	const tipo = $('#segmento_tipo').val();
	const grupos = tipo === 'whatsapp_grupos';
	$('#wrap-status-lead').toggle(tipo === 'leads');
	$('#wrap-grupos-wa').toggleClass('d-none', !grupos);
	if(grupos){
		$('#campanha_canal').val('whatsapp');
		atualizarUiCanalSemSegmento();
	}
}

function atualizarUiCanalSemSegmento(){
	const wa = $('#campanha_canal').val() === 'whatsapp';
	$('#wrap-assunto').toggle(!wa);
	$('#wrap-emoji-campanha').toggleClass('d-none', !wa);
	$('#wrap-midia-campanha').toggleClass('d-none', !wa);
	$('#label-mensagem').text(wa ? 'Mensagem WhatsApp' : 'Mensagem (HTML simples) *');
	$('#campanha_assunto').prop('required', !wa);
}

function inserirEmojiCampanha(emoji){
	const el = document.getElementById('campanha_mensagem');
	if(!el) return;
	const $t = $(el);
	const val = $t.val() || '';
	const start = el.selectionStart != null ? el.selectionStart : val.length;
	const end = el.selectionEnd != null ? el.selectionEnd : val.length;
	$t.val(val.substring(0, start) + emoji + val.substring(end));
	el.focus();
	const pos = start + emoji.length;
	if(el.setSelectionRange) el.setSelectionRange(pos, pos);
}

function limparMidiaSelecionada(marcarRemocao){
	$('#campanha_arquivo_img, #campanha_arquivo_doc, #campanha_arquivo_audio').val('');
	$('#campanha_midia_tipo').val('');
	window._campanhaArquivo = null;
	window._campanhaMidiaExistente = null;
	if(marcarRemocao){
		$('#campanha_remover_midia').val('1');
	} else {
		$('#campanha_remover_midia').val('0');
	}
	atualizarInfoMidia();
}

function atualizarInfoMidia(){
	const $info = $('#campanha-midia-info');
	const $btnRem = $('#btn-remover-midia-campanha');
	const arquivo = window._campanhaArquivo;
	const existente = window._campanhaMidiaExistente;
	const remover = $('#campanha_remover_midia').val() === '1';

	if(arquivo && arquivo.file){
		const tipoLabel = { image: 'Imagem', document: 'Documento', audio: 'Áudio' }[arquivo.tipo] || 'Arquivo';
		$info.html('<span class="text-success"><i class="fas fa-check-circle"></i> '+escHtml(tipoLabel)+': '+escHtml(arquivo.file.name)+'</span>');
		$btnRem.removeClass('d-none');
		return;
	}
	if(existente && !remover){
		const tipoLabel = { image: 'Imagem', document: 'Documento', audio: 'Áudio' }[existente.tipo] || 'Mídia';
		const nome = existente.nome || 'arquivo';
		let extra = '';
		if(existente.url && existente.tipo === 'image'){
			extra = ' <a href="'+escHtml(existente.url)+'" target="_blank" rel="noopener">ver</a>';
		}
		$info.html('<span class="text-primary"><i class="fas fa-paperclip"></i> '+escHtml(tipoLabel)+' já salva: '+escHtml(nome)+'</span>'+extra);
		$btnRem.removeClass('d-none');
		return;
	}
	$info.text('Nenhuma mídia anexada. A mensagem vira legenda (imagem/documento) ou texto após o áudio.');
	$btnRem.addClass('d-none');
}

function selecionarArquivoCampanha(tipo, input){
	const file = input.files && input.files[0] ? input.files[0] : null;
	$('#campanha_arquivo_img, #campanha_arquivo_doc, #campanha_arquivo_audio').not(input).val('');
	if(!file){
		window._campanhaArquivo = null;
		$('#campanha_midia_tipo').val('');
		atualizarInfoMidia();
		return;
	}
	window._campanhaArquivo = { tipo: tipo, file: file };
	window._campanhaMidiaExistente = null;
	$('#campanha_midia_tipo').val(tipo);
	$('#campanha_remover_midia').val('0');
	atualizarInfoMidia();
}

function coletarDestinosGrupos(){
	const destinos = [];
	$('#lista-grupos-wa input.chk-destino-wa:checked').each(function(){
		destinos.push({
			jid: $(this).val(),
			nome: $(this).data('nome') || $(this).val(),
			kind: $(this).data('kind') || 'grupo'
		});
	});
	return destinos;
}

function renderGruposWa(itens, selecionados){
	selecionados = selecionados || {};
	const $box = $('#lista-grupos-wa').empty();
	if(!itens || !itens.length){
		$box.append('<div class="text-muted small">Nenhum grupo/lista encontrado.</div>');
		return;
	}
	itens.forEach(function(it){
		const id = 'wa-dest-'+String(it.jid).replace(/[^a-zA-Z0-9]/g,'_').substring(0, 60);
		const badge = it.kind === 'lista' ? 'Lista' : 'Grupo';
		const $chk = $('<input class="form-check-input chk-destino-wa" type="checkbox">')
			.attr({ id: id, 'data-nome': it.nome, 'data-kind': it.kind })
			.val(it.jid)
			.prop('checked', !!selecionados[it.jid]);
		const $label = $('<label class="form-check-label"></label>').attr('for', id);
		$label.append(
			$('<span class="badge me-1"></span>').addClass(it.kind === 'lista' ? 'bg-info' : 'bg-success').text(badge)
		);
		$label.append(document.createTextNode(' ' + (it.nome || it.jid)));
		$label.append('<br>');
		$label.append($('<code class="small"></code>').text(it.jid));
		$box.append($('<div class="form-check"></div>').append($chk).append($label));
	});
}

function syncGruposWa(){
	$('#btn-sync-grupos-wa').prop('disabled', true);
	$.post(url_base + CAMPANHAS_URL, { acao: 'listar_grupos_wa' }, function(res){
		$('#btn-sync-grupos-wa').prop('disabled', false);
		if(!res || !res.success){
			Swal.fire('Erro', (res && res.message) ? res.message : 'Não foi possível listar grupos.', 'error');
			return;
		}
		const sel = {};
		coletarDestinosGrupos().forEach(function(d){ sel[d.jid] = true; });
		renderGruposWa(res.itens || [], sel);
		if(res.message){
			$('#lista-grupos-wa').prepend('<div class="alert alert-light border small py-1 mb-2">'+escHtml(res.message)+'</div>');
		}
	}, 'json').fail(function(){
		$('#btn-sync-grupos-wa').prop('disabled', false);
		Swal.fire('Erro', 'Falha ao sincronizar.', 'error');
	});
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

		let itensMenu = '';
		itensMenu += '<li><button type="button" class="dropdown-item btn-detalhes" data-id="'+c.id+'"><i class="fas fa-eye me-1"></i> Detalhes</button></li>';

		if(c.status === 'rascunho'){
			itensMenu += '<li><button type="button" class="dropdown-item text-success btn-iniciar" data-id="'+c.id+'"><i class="fas fa-paper-plane me-1"></i> Iniciar envio</button></li>';
			itensMenu += '<li><button type="button" class="dropdown-item btn-editar" data-id="'+c.id+'"><i class="fas fa-edit me-1"></i> Editar</button></li>';
		}
		if(c.status === 'pausada'){
			itensMenu += '<li><button type="button" class="dropdown-item text-success btn-iniciar" data-id="'+c.id+'"><i class="fas fa-play me-1"></i> Retomar envio</button></li>';
			itensMenu += '<li><button type="button" class="dropdown-item btn-editar" data-id="'+c.id+'"><i class="fas fa-edit me-1"></i> Editar</button></li>';
		}
		if(c.status === 'enviando'){
			itensMenu += '<li><button type="button" class="dropdown-item text-warning btn-pausar" data-id="'+c.id+'"><i class="fas fa-pause me-1"></i> Pausar envio</button></li>';
		}
		if(c.status !== 'concluida' && c.status !== 'cancelada'){
			itensMenu += '<li><hr class="dropdown-divider"></li>';
			itensMenu += '<li><button type="button" class="dropdown-item text-danger btn-cancelar" data-id="'+c.id+'"><i class="fas fa-stop me-1"></i> Parar / cancelar</button></li>';
		}

		const acoes = ''
			+'<div class="btn-group">'
			+(c.status === 'enviando'
				? '<button type="button" class="btn btn-sm btn-warning btn-pausar" data-id="'+c.id+'" title="Pausar"><i class="fas fa-pause"></i> Pausar</button>'
				: '')
			+(c.status === 'rascunho' || c.status === 'pausada'
				? '<button type="button" class="btn btn-sm btn-success btn-iniciar" data-id="'+c.id+'" title="'+(c.status === 'pausada' ? 'Retomar' : 'Iniciar')+'"><i class="fas fa-'+(c.status === 'pausada' ? 'play' : 'paper-plane')+'"></i> '+(c.status === 'pausada' ? 'Retomar' : 'Iniciar')+'</button>'
				: '')
			+'<button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">'
			+'<span class="visually-hidden">Mais</span></button>'
			+'<ul class="dropdown-menu dropdown-menu-end">'+itensMenu+'</ul>'
			+'</div>';

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
				<td class="text-end text-nowrap">${acoes}</td>
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
		status_lead: $('#status_lead').val(),
		destinos_json: JSON.stringify(coletarDestinosGrupos()),
		midia_tipo: $('#campanha_midia_tipo').val() || '',
		remover_midia: $('#campanha_remover_midia').val() || '0'
	};
}

function montarFormDataSalvar(){
	const dados = coletarFormulario();
	const fd = new FormData();
	Object.keys(dados).forEach(function(k){
		fd.append(k, dados[k] == null ? '' : dados[k]);
	});
	if(dados.canal === 'whatsapp' && window._campanhaArquivo && window._campanhaArquivo.file){
		fd.append('arquivo', window._campanhaArquivo.file);
		fd.set('midia_tipo', window._campanhaArquivo.tipo);
		fd.set('remover_midia', '0');
	}
	return fd;
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
	$('#lista-grupos-wa').html('<div class="text-muted small">Clique em sincronizar com o WhatsApp conectado.</div>');
	limparMidiaSelecionada(false);
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
	const canal = $('#campanha_canal').val();
	const mensagem = ($('#campanha_mensagem').val() || '').trim();
	const temArquivo = !!(window._campanhaArquivo && window._campanhaArquivo.file);
	const temMidiaExistente = !!(window._campanhaMidiaExistente && $('#campanha_remover_midia').val() !== '1');
	if(canal === 'whatsapp' && !mensagem && !temArquivo && !temMidiaExistente){
		Swal.fire('Atenção', 'Informe uma mensagem e/ou anexe imagem, documento ou áudio.', 'warning');
		return;
	}
	if(canal === 'email' && (!($('#campanha_assunto').val() || '').trim() || !mensagem)){
		Swal.fire('Atenção', 'Preencha assunto e mensagem do e-mail.', 'warning');
		return;
	}

	$('#btn-salvar-campanha').prop('disabled', true);
	$.ajax({
		url: url_base + CAMPANHAS_URL,
		method: 'POST',
		data: montarFormDataSalvar(),
		processData: false,
		contentType: false,
		dataType: 'json'
	}).done(function(res){
		$('#btn-salvar-campanha').prop('disabled', false);
		if(!res || !res.success){
			Swal.fire('Erro', (res && res.message) ? res.message : 'Não foi possível salvar.', 'error');
			return;
		}
		Swal.fire('Salvo', res.message, 'success');
		$('#modalCampanha').modal('hide');
		carregarCampanhas();
	}).fail(function(){
		$('#btn-salvar-campanha').prop('disabled', false);
		Swal.fire('Erro', 'Falha ao salvar campanha.', 'error');
	});
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
		const textos = {
			iniciar: { title: 'Iniciar envio?', text: 'A campanha entrará na fila de envio.' },
			cancelar: { title: 'Parar campanha?', text: 'Os pendentes serão cancelados e não poderão ser retomados.' }
		};
		const t = textos[acao] || { title: 'Confirmar?', text: '' };
		Swal.fire({
			title: t.title,
			text: t.text,
			icon: acao === 'cancelar' ? 'warning' : 'question',
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

		let midiaHtml = '';
		const midia = c.midia || null;
		if(midia && midia.tipo){
			const tipoLabel = { image: 'Imagem', document: 'Documento', audio: 'Áudio' }[midia.tipo] || midia.tipo;
			const link = midia.url ? ' — <a href="'+escHtml(midia.url)+'" target="_blank" rel="noopener">abrir</a>' : '';
			midiaHtml = '<p><strong>Mídia:</strong> '+escHtml(tipoLabel)+' ('+escHtml(midia.nome || 'arquivo')+')'+link+'</p>';
			if(midia.tipo === 'image' && midia.url){
				midiaHtml += '<div class="mb-2"><img src="'+escHtml(midia.url)+'" alt="" class="img-fluid rounded border" style="max-height:180px;"></div>';
			}
		}

		$('#body-detalhes-campanha').html(`
			<p><strong>Canal:</strong> ${escHtml(c.canal_label || c.canal)}</p>
			<p><strong>Assunto:</strong> ${escHtml(res.assunto || '—')}</p>
			<p><strong>Status:</strong> <span class="badge bg-${badgeStatus(c.status)}">${escHtml(c.status_label)}</span></p>
			<p><strong>Progresso:</strong> ${c.enviados} enviados, ${c.erros} erros, ${c.pendentes} pendentes de ${c.total}</p>
			${midiaHtml}
			<div class="border rounded p-3 bg-light small" style="white-space:pre-wrap;">${escHtml(res.mensagem)}</div>
			${errosHtml}
		`);

		let footer = '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>';
		if(c.status === 'enviando'){
			footer += '<button type="button" class="btn btn-warning btn-pausar" data-id="'+c.id+'" data-bs-dismiss="modal"><i class="fas fa-pause"></i> Pausar envio</button>';
		}
		if(c.status === 'rascunho' || c.status === 'pausada'){
			footer += '<button type="button" class="btn btn-success btn-iniciar" data-id="'+c.id+'" data-bs-dismiss="modal"><i class="fas fa-'+(c.status === 'pausada' ? 'play' : 'paper-plane')+'"></i> '+(c.status === 'pausada' ? 'Retomar' : 'Iniciar')+' envio</button>';
		}
		if(c.status !== 'concluida' && c.status !== 'cancelada'){
			footer += '<button type="button" class="btn btn-outline-danger btn-cancelar" data-id="'+c.id+'" data-bs-dismiss="modal"><i class="fas fa-stop"></i> Parar</button>';
		}
		$('#footer-detalhes-campanha').html(footer);

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
		$('#titulo-modal-campanha').text('Editar campanha');
		window._campanhaArquivo = null;
		window._campanhaMidiaExistente = c.midia || seg.midia || null;
		$('#campanha_remover_midia').val('0');
		$('#campanha_midia_tipo').val(window._campanhaMidiaExistente ? (window._campanhaMidiaExistente.tipo || '') : '');
		$('#campanha_arquivo_img, #campanha_arquivo_doc, #campanha_arquivo_audio').val('');
		atualizarUiCanal();
		atualizarInfoMidia();
		if(seg.tipo === 'whatsapp_grupos'){
			const sel = {};
			(seg.destinos || []).forEach(function(d){ if(d.jid) sel[d.jid] = true; });
			renderGruposWa(seg.destinos || [], sel);
		}
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
	window._campanhaArquivo = null;
	window._campanhaMidiaExistente = null;

	carregarCampanhas();
	atualizarUiCanal();

	$('#campanha_canal').on('change', atualizarUiCanal);
	$('#filtro-canal').on('change', carregarCampanhas);
	$('#segmento_tipo').on('change', atualizarUiSegmento);
	$('#btn-sync-grupos-wa').on('click', syncGruposWa);

	$('#btn-salvar-campanha').on('click', salvarCampanha);
	$('#btn-preview-campanha').on('click', previewPublico);
	$('#btn-processar-fila').on('click', processarFila);

	$('#modalCampanha').on('hidden.bs.modal', limparFormulario);

	$(document).on('click', '.camp-emoji', function(){
		inserirEmojiCampanha($(this).text());
	});
	$('#campanha_arquivo_img').on('change', function(){
		selecionarArquivoCampanha('image', this);
	});
	$('#campanha_arquivo_doc').on('change', function(){
		selecionarArquivoCampanha('document', this);
	});
	$('#campanha_arquivo_audio').on('change', function(){
		selecionarArquivoCampanha('audio', this);
	});
	$('#btn-remover-midia-campanha').on('click', function(){
		limparMidiaSelecionada(true);
	});

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

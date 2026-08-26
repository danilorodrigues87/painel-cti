const CAMPANHAS_URL = 'painel/campanhas';
let campanhaPollFila = null;
let campanhaPollTick = null;
let campanhaPacingTimer = null;
let campanhaPacing = null;
let campanhaPacing1a1 = null;
let campanhaTemPendentes = false;
let campanhaProcessandoFila = false;
let campanhaPagina = 1;
let campanhaBibModal = null;
let campanhaBibFormato = 'feed';
let campanhaProgressoPoll = null;
let campanhaProgressoId = null;

function renderPaginacaoAjax($el, pag, onPage){
	pag = pag || {};
	const pages = parseInt(pag.pages, 10) || 1;
	const page = parseInt(pag.page, 10) || 1;
	const total = parseInt(pag.total, 10) || 0;
	if(pages <= 1){
		$el.empty();
		return;
	}
	let html = '<ul class="pagination pagination-sm mb-0 justify-content-end">';
	html += '<li class="page-item'+(page <= 1 ? ' disabled' : '')+'"><a class="page-link" href="#" data-p="'+(page-1)+'">«</a></li>';
	for(let i = 1; i <= pages; i++){
		if(pages > 9 && Math.abs(i - page) > 3 && i !== 1 && i !== pages){
			if(i === 2 || i === pages - 1) html += '<li class="page-item disabled"><span class="page-link">…</span></li>';
			continue;
		}
		html += '<li class="page-item'+(i === page ? ' active' : '')+'"><a class="page-link" href="#" data-p="'+i+'">'+i+'</a></li>';
	}
	html += '<li class="page-item'+(page >= pages ? ' disabled' : '')+'"><a class="page-link" href="#" data-p="'+(page+1)+'">»</a></li>';
	html += '</ul>';
	if(total) html = '<div class="d-flex justify-content-between align-items-center flex-wrap gap-2"><small class="text-muted">'+total+' registro(s)</small>'+html+'</div>';
	$el.html(html);
	$el.find('a.page-link[data-p]').on('click', function(e){
		e.preventDefault();
		const p = parseInt($(this).data('p'), 10);
		if(!p || p < 1 || p > pages || p === page) return;
		onPage(p);
	});
}

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
	$('#wrap-inadimplentes').toggle(tipo === 'inadimplentes');
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
	window._campanhaMidiaBiblioteca = null;
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
	const biblioteca = window._campanhaMidiaBiblioteca;
	const remover = $('#campanha_remover_midia').val() === '1';

	if(arquivo && arquivo.file){
		const tipoLabel = { image: 'Imagem', document: 'Documento', audio: 'Áudio' }[arquivo.tipo] || 'Arquivo';
		$info.html('<span class="text-success"><i class="fas fa-check-circle"></i> '+escHtml(tipoLabel)+': '+escHtml(arquivo.file.name)+'</span>');
		$btnRem.removeClass('d-none');
		return;
	}
	if(biblioteca && biblioteca.path && !remover){
		const fmt = biblioteca.formato === 'story' ? ' (story)' : (biblioteca.formato === 'feed' ? ' (quadrado)' : '');
		let extra = biblioteca.url ? ' <a href="'+escHtml(biblioteca.url)+'" target="_blank" rel="noopener">ver</a>' : '';
		$info.html('<span class="text-success"><i class="fas fa-photo-video"></i> Biblioteca: '+escHtml(biblioteca.nome || 'imagem')+escHtml(fmt)+'</span>'+extra);
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
	window._campanhaMidiaBiblioteca = null;
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
		const progresso = c.eh_grupos
			? (c.status === 'enviando' ? 100 : (c.enviados > 0 ? 100 : 0))
			: (c.total > 0 ? Math.round(((c.enviados + c.erros) / c.total) * 100) : 0);
		const barraExtra = (c.eh_grupos && c.status === 'enviando') ? ' progress-bar-striped progress-bar-animated' : '';

		let itensMenu = '';
		itensMenu += '<li><button type="button" class="dropdown-item btn-detalhes" data-id="'+c.id+'"><i class="fas fa-eye me-1"></i> Detalhes</button></li>';

		if(c.status === 'rascunho'){
			itensMenu += '<li><button type="button" class="dropdown-item text-success btn-iniciar" data-id="'+c.id+'"><i class="fas fa-paper-plane me-1"></i> Iniciar envio</button></li>';
			itensMenu += '<li><button type="button" class="dropdown-item btn-editar" data-id="'+c.id+'"><i class="fas fa-edit me-1"></i> Editar</button></li>';
		}
		if(c.status === 'pausada'){
			itensMenu += '<li><button type="button" class="dropdown-item text-success btn-iniciar" data-id="'+c.id+'"><i class="fas fa-play me-1"></i> Retomar envio</button></li>';
			itensMenu += '<li><button type="button" class="dropdown-item btn-editar" data-id="'+c.id+'"><i class="fas fa-edit me-1"></i> Editar mensagem/mídia</button></li>';
		}
		if(c.status === 'enviando'){
			itensMenu += '<li><button type="button" class="dropdown-item text-warning btn-pausar" data-id="'+c.id+'"><i class="fas fa-pause me-1"></i> Pausar envio</button></li>';
			itensMenu += '<li><button type="button" class="dropdown-item btn-editar" data-id="'+c.id+'"><i class="fas fa-edit me-1"></i> Editar mensagem/mídia</button></li>';
		}
		if(c.status !== 'concluida' && c.status !== 'cancelada'){
			itensMenu += '<li><hr class="dropdown-divider"></li>';
			itensMenu += '<li><button type="button" class="dropdown-item text-danger btn-cancelar" data-id="'+c.id+'"><i class="fas fa-stop me-1"></i> '+(c.eh_grupos ? 'Encerrar campanha' : 'Parar / cancelar')+'</button></li>';
		}

		const acoes = ''
			+'<div class="btn-group">'
			+(c.status === 'enviando'
				? '<button type="button" class="btn btn-sm btn-warning btn-pausar" data-id="'+c.id+'" title="Pausar"><i class="fas fa-pause"></i> Pausar</button>'
					+'<button type="button" class="btn btn-sm btn-outline-primary btn-editar" data-id="'+c.id+'" title="Editar mensagem/mídia"><i class="fas fa-edit"></i></button>'
					+'<button type="button" class="btn btn-sm btn-outline-danger btn-cancelar" data-id="'+c.id+'" title="Encerrar"><i class="fas fa-stop"></i></button>'
				: '')
			+(c.status === 'rascunho' || c.status === 'pausada'
				? '<button type="button" class="btn btn-sm btn-success btn-iniciar" data-id="'+c.id+'" title="'+(c.status === 'pausada' ? 'Retomar' : 'Iniciar')+'"><i class="fas fa-'+(c.status === 'pausada' ? 'play' : 'paper-plane')+'"></i> '+(c.status === 'pausada' ? 'Retomar' : 'Iniciar')+'</button>'
					+(c.status === 'pausada' ? '<button type="button" class="btn btn-sm btn-outline-primary btn-editar" data-id="'+c.id+'"><i class="fas fa-edit"></i></button>' : '')
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
					<div class="small">${c.eh_grupos
						? (c.enviados+' envios · recorrente até Encerrar')
						: (c.enviados+' enviados · '+c.erros+' erros · '+c.pendentes+' pendentes')}</div>
					<div class="progress" style="height:6px;">
						<div class="progress-bar${barraExtra}" style="width:${progresso}%"></div>
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
		parcelas_atraso_min: $('#parcelas_atraso_min').val() || '1',
		destinos_json: JSON.stringify(coletarDestinosGrupos()),
		midia_tipo: $('#campanha_midia_tipo').val() || '',
		midia_biblioteca_path: (window._campanhaMidiaBiblioteca && window._campanhaMidiaBiblioteca.path) ? window._campanhaMidiaBiblioteca.path : '',
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
		fd.set('midia_biblioteca_path', '');
	}
	return fd;
}

function renderCampBibGrid(itens){
	const $grid = $('#camp-bib-grid');
	if(!itens || !itens.length){
		$grid.html('<div class="col-12 text-muted">Nenhuma imagem nesta categoria. Envie na Redes sociais → Biblioteca.</div>');
		return;
	}
	$grid.html(itens.map(function(it){
		const u = it.url || '';
		const fmt = it.formato === 'story' ? '<span class="badge bg-info">Story</span> ' : '<span class="badge bg-secondary">Quadrado</span> ';
		return '<div class="col-6 col-md-3"><div class="border rounded p-1">'+
			'<img src="'+escHtml(u)+'" class="w-100" style="height:100px;object-fit:cover" alt="">'+
			'<div class="small text-truncate mt-1">'+fmt+escHtml(it.titulo || it.path || '')+'</div>'+
			'<button type="button" class="btn btn-sm btn-primary w-100 camp-bib-pick" data-path="'+escHtml(it.path)+'" data-url="'+escHtml(u)+'" data-nome="'+escHtml(it.titulo || it.path || '')+'" data-formato="'+escHtml(it.formato || 'feed')+'">Usar</button>'+
			'</div></div>';
	}).join(''));
}

function carregarCampanhaBiblioteca(){
	const payload = { acao: 'biblioteca_listar', tipo: 'image' };
	if(campanhaBibFormato) payload.formato = campanhaBibFormato;
	$.post(url_base + CAMPANHAS_URL, payload, function(res){
		if(!res || !res.success){
			$('#camp-bib-grid').html('<div class="col-12 text-warning">'+(res && res.message ? escHtml(res.message) : 'Biblioteca indisponível.')+'</div>');
			return;
		}
		renderCampBibGrid(res.itens || []);
	}, 'json');
}

function abrirModalCampanhaBiblioteca(){
	if(!campanhaBibModal){
		campanhaBibModal = new bootstrap.Modal(document.getElementById('modalCampanhaBiblioteca'));
	}
	carregarCampanhaBiblioteca();
	campanhaBibModal.show();
}

function selecionarMidiaBibliotecaCampanha(item){
	window._campanhaArquivo = null;
	window._campanhaMidiaExistente = null;
	window._campanhaMidiaBiblioteca = {
		path: item.path,
		url: item.url,
		nome: item.nome,
		formato: item.formato,
		tipo: 'image'
	};
	$('#campanha_midia_tipo').val('image');
	$('#campanha_remover_midia').val('0');
	$('#campanha_arquivo_img, #campanha_arquivo_doc, #campanha_arquivo_audio').val('');
	atualizarInfoMidia();
	if(campanhaBibModal) campanhaBibModal.hide();
}

function atualizarAlertaCron(cron){
	const $alert = $('#alert-cron-campanhas');
	if(!cron){
		$alert.addClass('d-none');
		return;
	}
	$alert.removeClass('d-none');
	let txt = cron.hint || '';
	if(cron.ultima && cron.ultima.created_at){
		const quando = String(cron.ultima.created_at).slice(0, 16).replace('T', ' ');
		txt += ' Última execução: '+quando+' ('+(cron.ultima.origem || 'cron')+').';
	} else if(cron.token_configurado){
		txt += ' Nenhuma execução registrada ainda — configure o cron no cPanel.';
	}
	$('#cron-campanhas-texto').text(txt);
	$('#cron-campanhas-url').text(cron.url_cron || '');
	if(!cron.tabela_ok){
		$('#cron-campanhas-texto').append(' Execute database/campanha_worker_runs.sql para ver o histórico.');
	}
}

function limparFormulario(){
	$('#campanha_id').val('');
	$('#campanha_canal').val('email');
	$('#campanha_titulo').val('');
	$('#campanha_assunto').val('');
	$('#campanha_mensagem').val('');
	$('#segmento_tipo').val('alunos_matriculados');
	$('#status_lead').val('');
	$('#parcelas_atraso_min').val('1');
	$('#preview-resultado').text('');
	$('#titulo-modal-campanha').text('Nova campanha');
	$('#btn-salvar-campanha').html('<i class="fas fa-save"></i> Salvar rascunho');
	$('#campanha_canal, #segmento_tipo, #status_lead, #parcelas_atraso_min').prop('disabled', false);
	$('#wrap-grupos-wa').find('input,button').prop('disabled', false);
	$('#wrap-status-lead').hide();
	$('#wrap-inadimplentes').hide();
	$('#lista-grupos-wa').html('<div class="text-muted small">Clique em sincronizar com o WhatsApp conectado.</div>');
	limparMidiaSelecionada(false);
	atualizarUiCanal();
}

function formatarEspera(seg){
	seg = Math.max(0, parseInt(seg, 10) || 0);
	if(seg <= 0) return 'agora';
	const m = Math.floor(seg / 60);
	const s = seg % 60;
	if(m <= 0) return s+'s';
	return m+'min'+(s > 0 ? ' '+s+'s' : '');
}

function atualizarTextoPacing(pacing){
	campanhaPacing = pacing || campanhaPacing;
	const p = campanhaPacing;
	if(!p){
		$('#pacing-grupos-texto').text('Intervalo de grupos indisponível.');
		return;
	}
	let txt = 'Intervalo entre grupos: <strong>'+escHtml(String(p.delay_minutos || 60))+' min</strong>.';
	if(p.coluna_ok === false){
		txt += ' <span class="text-danger">Execute o SQL whatsapp_grupo_delay_segundos para gravar o intervalo.</span>';
	}
	if(campanhaTemPendentes){
		if(p.pode_enviar){
			txt += ' Próximo reenvio: <strong class="text-success">liberado</strong>.';
		} else {
			txt += ' Próximo reenvio em: <strong class="text-warning" id="pacing-countdown">'+escHtml(formatarEspera(p.proximo_em_segundos))+'</strong>.';
		}
	} else {
		txt += ' Nenhuma campanha de grupos em envio no momento.';
	}
	$('#pacing-grupos-texto').html(txt);
}

function atualizarTextoPacing1a1(pacing){
	campanhaPacing1a1 = pacing || campanhaPacing1a1;
	const p = campanhaPacing1a1;
	if(!p){
		$('#pacing-1a1-texto').text('Intervalo 1:1 indisponível.');
		return;
	}
	let txt = 'Intervalo entre números: <strong>'+escHtml(String(p.delay_segundos || 30))+'s</strong> (mín. 30s).';
	if(p.pode_enviar){
		txt += ' Próximo envio 1:1: <strong class="text-success">liberado</strong>.';
	} else {
		txt += ' Próximo envio 1:1 em: <strong class="text-warning">'+escHtml(formatarEspera(p.proximo_em_segundos))+'</strong>.';
	}
	$('#pacing-1a1-texto').html(txt);
}

function limparPacingTimer(){
	if(campanhaPacingTimer){
		clearTimeout(campanhaPacingTimer);
		campanhaPacingTimer = null;
	}
}

function agendarProximoEnvioGrupo(){
	limparPacingTimer();
	if(!campanhaTemPendentes || !campanhaPacing) return;
	const espera = Math.max(0, parseInt(campanhaPacing.proximo_em_segundos, 10) || 0);
	// Dispara no fim do intervalo (+2s de folga) — não depende só do poll de 30s
	campanhaPacingTimer = setTimeout(function(){
		campanhaPacingTimer = null;
		processarFilaSilencioso();
	}, (espera + 2) * 1000);
}

function tickPacingCountdown(){
	if(!campanhaPacing || !campanhaTemPendentes) return;
	if(campanhaPacing.proximo_em_segundos > 0){
		campanhaPacing.proximo_em_segundos = Math.max(0, campanhaPacing.proximo_em_segundos - 1);
		if(campanhaPacing.proximo_em_segundos <= 0){
			campanhaPacing.pode_enviar = true;
			atualizarTextoPacing(campanhaPacing);
			processarFilaSilencioso();
			return;
		}
		atualizarTextoPacing(campanhaPacing);
	}
}

function processarFilaSilencioso(){
	if(!campanhaTemPendentes || campanhaProcessandoFila) return;
	campanhaProcessandoFila = true;
	$.post(url_base + CAMPANHAS_URL, { acao: 'processar', limite: 1, silencioso: 1 }, function(res){
		campanhaProcessandoFila = false;
		if(!res || !res.success) return;
		if(res.pacing){
			atualizarTextoPacing(res.pacing);
			agendarProximoEnvioGrupo();
		}
		if(res.pacing_1a1){
			atualizarTextoPacing1a1(res.pacing_1a1);
		}
		const enviados = res.resumo && res.resumo.enviados ? res.resumo.enviados : 0;
		carregarCampanhas({ silencioso: true });
		if(campanhaProgressoId){
			pollDetalhesProgressoCampanha();
		}
		if(enviados > 0){
			document.title = '('+enviados+' enviados) Campanhas';
			setTimeout(function(){ document.title = 'Campanhas'; }, 4000);
		}
	}, 'json').fail(function(){
		campanhaProcessandoFila = false;
	});
}

function iniciarAutoFila(){
	if(!campanhaPollFila){
		campanhaPollFila = setInterval(processarFilaSilencioso, 35000);
	}
	if(!campanhaPollTick){
		campanhaPollTick = setInterval(tickPacingCountdown, 1000);
	}
	agendarProximoEnvioGrupo();
}

function pararAutoFila(){
	if(campanhaPollFila){
		clearInterval(campanhaPollFila);
		campanhaPollFila = null;
	}
	if(campanhaPollTick){
		clearInterval(campanhaPollTick);
		campanhaPollTick = null;
	}
	limparPacingTimer();
}

function carregarCampanhas(opts){
	opts = opts || {};
	if(opts.page) campanhaPagina = opts.page;
	$.post(url_base + CAMPANHAS_URL, {
		acao: 'listar',
		canal: $('#filtro-canal').val() || '',
		page: campanhaPagina
	}, function(res){
		if(!res || !res.success){
			if(!opts.silencioso){
				Swal.fire('Erro', (res && res.message) ? res.message : 'Falha ao listar.', 'error');
			}
			return;
		}
		renderizarLista(res.campanhas);
		if(campanhaProgressoId && res.campanhas && res.campanhas.length){
			const emProgresso = res.campanhas.find(function(c){
				return parseInt(c.id, 10) === parseInt(campanhaProgressoId, 10);
			});
			if(emProgresso){
				atualizarModalProgressoCampanha(emProgresso);
			}
		}
		if(res.pagination){
			campanhaPagina = res.pagination.page || campanhaPagina;
			renderPaginacaoAjax($('#paginacao-campanhas'), res.pagination, function(p){
				carregarCampanhas({ page: p });
			});
		} else {
			$('#paginacao-campanhas').empty();
		}
		campanhaTemPendentes = (res.campanhas || []).some(function(c){
			// Grupos: recorrente enquanto "enviando" (mesmo com fila da rodada vazia)
			if(c.status === 'enviando' && c.eh_grupos) return true;
			return c.status === 'enviando' && (c.pendentes || 0) > 0;
		});
		if(res.pacing) atualizarTextoPacing(res.pacing);
		else atualizarTextoPacing(campanhaPacing);
		if(res.pacing_1a1) atualizarTextoPacing1a1(res.pacing_1a1);
		else if(campanhaPacing1a1) atualizarTextoPacing1a1(campanhaPacing1a1);
		atualizarAlertaCron(res.cron);
		if(campanhaTemPendentes){
			iniciarAutoFila();
			if(res.pacing) agendarProximoEnvioGrupo();
			// Só dispara na carga manual (não após o próprio processar, evita loop)
			if(!opts.silencioso && res.pacing && res.pacing.pode_enviar){
				processarFilaSilencioso();
			}
		} else {
			pararAutoFila();
		}
	}, 'json').fail(function(){
		if(!opts.silencioso){
			Swal.fire('Erro', 'Falha ao carregar campanhas.', 'error');
		}
	});
}

function salvarCampanha(){
	const canal = $('#campanha_canal').val();
	const mensagem = ($('#campanha_mensagem').val() || '').trim();
	const temArquivo = !!(window._campanhaArquivo && window._campanhaArquivo.file);
	const temMidiaExistente = !!(window._campanhaMidiaExistente && $('#campanha_remover_midia').val() !== '1');
	const temMidiaBiblioteca = !!(window._campanhaMidiaBiblioteca && window._campanhaMidiaBiblioteca.path && $('#campanha_remover_midia').val() !== '1');
	if(canal === 'whatsapp' && !mensagem && !temArquivo && !temMidiaExistente && !temMidiaBiblioteca){
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

function pararPollingProgressoCampanha(){
	if(campanhaProgressoPoll){
		clearInterval(campanhaProgressoPoll);
		campanhaProgressoPoll = null;
	}
}

function fecharModalProgressoCampanha(){
	pararPollingProgressoCampanha();
	campanhaProgressoId = null;
	$('#modalCampanhaProgresso').modal('hide');
}

function abrirModalProgressoPreparando(titulo, id){
	pararPollingProgressoCampanha();
	campanhaProgressoId = id || null;
	$('#campanha-progresso-titulo').text(titulo || 'Iniciando campanha');
	$('#campanha-progresso-texto').text('Montando lista de destinatários…');
	$('#campanha-progresso-detalhe').text('Aguarde, isso pode levar alguns instantes em listas grandes.');
	$('#campanha-progresso-stats').text('');
	const $bar = $('#campanha-progresso-bar');
	$bar.addClass('progress-bar-striped progress-bar-animated').removeClass('bg-success');
	$bar.css('width', '100%').attr('aria-valuenow', 0).text('');
	$('#campanha-progresso-footer').addClass('d-none');
	$('#modalCampanhaProgresso').modal({ backdrop: 'static', keyboard: false });
	$('#modalCampanhaProgresso').modal('show');
	if(id){
		iniciarPollingProgressoCampanha(id);
	}
}

function pctProgressoCampanha(c){
	if(!c || c.eh_grupos) return null;
	if(c.progresso_pct != null) return Math.min(100, parseInt(c.progresso_pct, 10) || 0);
	const total = parseInt(c.total, 10) || 0;
	if(total <= 0) return 0;
	const pendentes = parseInt(c.pendentes, 10) || 0;
	return Math.min(100, Math.round(((total - pendentes) / total) * 100));
}

function campanhaEnvioConcluido(c){
	if(!c || c.eh_grupos) return false;
	if(c.status === 'concluida') return true;
	const pendentes = parseInt(c.pendentes, 10) || 0;
	const total = parseInt(c.total, 10) || 0;
	return pendentes <= 0 && total > 0;
}

function finalizarProgressoCampanha(c, mensagem){
	pararPollingProgressoCampanha();
	const concluida = campanhaEnvioConcluido(c) || (c && c.status === 'concluida');
	if(concluida){
		$('#campanha-progresso-titulo').text('Campanha concluída');
		$('#campanha-progresso-texto').text(mensagem || 'Todos os destinatários foram processados.');
		$('#campanha-progresso-bar').removeClass('progress-bar-striped progress-bar-animated').addClass('bg-success');
		$('#campanha-progresso-bar').css('width', '100%').attr('aria-valuenow', 100).text('100%');
		$('#campanha-progresso-footer').removeClass('d-none');
		setTimeout(function(){
			fecharModalProgressoCampanha();
			Swal.fire({
				toast: true,
				position: 'top-end',
				icon: 'success',
				title: mensagem || 'Campanha concluída',
				showConfirmButton: false,
				timer: 3500
			});
		}, 1500);
		return;
	}
	if(c && (c.status === 'pausada' || c.status === 'cancelada')){
		$('#campanha-progresso-titulo').text(c.status === 'pausada' ? 'Campanha pausada' : 'Campanha encerrada');
		$('#campanha-progresso-texto').text(mensagem || '');
		$('#campanha-progresso-footer').removeClass('d-none');
	}
}

function atualizarModalProgressoCampanha(c){
	if(!c) return;
	campanhaProgressoId = c.id;
	const $bar = $('#campanha-progresso-bar');

	if(c.status === 'rascunho'){
		return;
	}

	if(c.eh_grupos){
		$('#campanha-progresso-titulo').text('Campanha recorrente ativa');
		$('#campanha-progresso-texto').text('Reenvios periódicos nos grupos selecionados.');
		$('#campanha-progresso-detalhe').text('Você pode fechar esta janela; o envio continua em segundo plano.');
		$bar.removeClass('progress-bar-striped progress-bar-animated').addClass('bg-success');
		$bar.css('width', '100%').attr('aria-valuenow', 100).text('Ativa');
		$('#campanha-progresso-stats').text((c.enviados || 0)+' reenvio(s) realizados');
		$('#campanha-progresso-footer').removeClass('d-none');
		pararPollingProgressoCampanha();
		return;
	}

	const total = parseInt(c.total, 10) || 0;
	const enviados = parseInt(c.enviados, 10) || 0;
	const erros = parseInt(c.erros, 10) || 0;
	const pendentes = parseInt(c.pendentes, 10) || 0;
	const pct = pctProgressoCampanha(c);

	$('#campanha-progresso-titulo').text('Enviando campanha');
	$('#campanha-progresso-texto').text(pct >= 100 ? 'Envio concluído!' : 'Enviando mensagens…');
	$('#campanha-progresso-detalhe').text('O progresso é atualizado automaticamente. Você pode navegar no painel; o envio continua.');
	$bar.removeClass('progress-bar-striped progress-bar-animated');
	$bar.toggleClass('bg-success', pct >= 100);
	$bar.css('width', pct+'%').attr('aria-valuenow', pct).text(pct+'%');
	$('#campanha-progresso-stats').text(enviados+' enviados · '+erros+' erros · '+pendentes+' pendentes de '+total);

	if(campanhaEnvioConcluido(c) || c.status === 'concluida'){
		finalizarProgressoCampanha(c);
		return;
	}
	if(c.status === 'pausada' || c.status === 'cancelada'){
		finalizarProgressoCampanha(c);
	}
}

function pollDetalhesProgressoCampanha(){
	if(!campanhaProgressoId) return;
	$.post(url_base + CAMPANHAS_URL, { acao: 'detalhes', id: campanhaProgressoId }, function(res){
		if(!res || !res.success || !res.campanha) return;
		atualizarModalProgressoCampanha(res.campanha);
	}, 'json');
}

function iniciarPollingProgressoCampanha(id){
	if(campanhaProgressoPoll && campanhaProgressoId === id) return;
	pararPollingProgressoCampanha();
	campanhaProgressoId = id;
	pollDetalhesProgressoCampanha();
	campanhaProgressoPoll = setInterval(pollDetalhesProgressoCampanha, 2000);
}

function acaoCampanha(acao, id, confirmar){
	const executar = function(){
		const ehIniciar = acao === 'iniciar';
		if(ehIniciar){
			abrirModalProgressoPreparando('Iniciando campanha', id);
		}
		$.post(url_base + CAMPANHAS_URL, { acao: acao, id: id }, function(res){
			if(ehIniciar){
				if(!res || !res.success){
					fecharModalProgressoCampanha();
					Swal.fire('Erro', (res && res.message) ? res.message : 'Falha na operação.', 'error');
					return;
				}
				carregarCampanhas();
				if(res.campanha){
					atualizarModalProgressoCampanha(res.campanha);
					if(!res.campanha.eh_grupos && !campanhaEnvioConcluido(res.campanha) && res.campanha.status === 'enviando'){
						iniciarPollingProgressoCampanha(res.campanha.id);
					}
				} else {
					fecharModalProgressoCampanha();
					Swal.fire('OK', res.message, 'success');
				}
				return;
			}
			if((acao === 'pausar' || acao === 'cancelar') && campanhaProgressoId === id){
				fecharModalProgressoCampanha();
			}
			if(!res || !res.success){
				Swal.fire('Erro', (res && res.message) ? res.message : 'Falha na operação.', 'error');
				return;
			}
			Swal.fire('OK', res.message, 'success');
			carregarCampanhas();
		}, 'json').fail(function(){
			if(ehIniciar) fecharModalProgressoCampanha();
			Swal.fire('Erro', 'Falha na comunicação com o servidor.', 'error');
		});
	};

	if(confirmar){
		const textos = {
			iniciar: { title: 'Iniciar envio?', text: 'Em grupos, a mensagem será reenviada periodicamente nos mesmos grupos até você Encerrar.' },
			cancelar: { title: 'Encerrar campanha?', text: 'Para o reenvio recorrente. O histórico de envios já feitos é mantido.' }
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

		const seg = c.segmento || {};
		let segExtra = '';
		if(seg.tipo === 'inadimplentes'){
			const n = parseInt(seg.parcelas_atraso_min, 10) || 1;
			segExtra = '<p><strong>Filtro:</strong> '+n+' ou mais parcela'+(n === 1 ? '' : 's')+' em atraso</p>';
		}

		$('#body-detalhes-campanha').html(`
			<p><strong>Canal:</strong> ${escHtml(c.canal_label || c.canal)}</p>
			<p><strong>Assunto:</strong> ${escHtml(res.assunto || '—')}</p>
			<p><strong>Status:</strong> <span class="badge bg-${badgeStatus(c.status)}">${escHtml(c.status_label)}</span></p>
			${segExtra}
			<p><strong>Progresso:</strong> ${c.eh_grupos
				? (c.enviados+' reenvios realizados (recorrente até Encerrar)')
				: (c.enviados+' enviados, '+c.erros+' erros, '+c.pendentes+' pendentes de '+c.total)}</p>
			${midiaHtml}
			<div class="border rounded p-3 bg-light small" style="white-space:pre-wrap;">${escHtml(res.mensagem)}</div>
			${errosHtml}
		`);

		let footer = '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>';
		if(c.status === 'enviando'){
			footer += '<button type="button" class="btn btn-warning btn-pausar" data-id="'+c.id+'" data-bs-dismiss="modal"><i class="fas fa-pause"></i> Pausar envio</button>';
			footer += '<button type="button" class="btn btn-outline-primary btn-editar" data-id="'+c.id+'" data-bs-dismiss="modal"><i class="fas fa-edit"></i> Editar mensagem/mídia</button>';
		}
		if(c.status === 'rascunho' || c.status === 'pausada'){
			footer += '<button type="button" class="btn btn-success btn-iniciar" data-id="'+c.id+'" data-bs-dismiss="modal"><i class="fas fa-'+(c.status === 'pausada' ? 'play' : 'paper-plane')+'"></i> '+(c.status === 'pausada' ? 'Retomar' : 'Iniciar')+' envio</button>';
			footer += '<button type="button" class="btn btn-outline-primary btn-editar" data-id="'+c.id+'" data-bs-dismiss="modal"><i class="fas fa-edit"></i> Editar</button>';
		}
		if(c.status !== 'concluida' && c.status !== 'cancelada'){
			footer += '<button type="button" class="btn btn-outline-danger btn-cancelar" data-id="'+c.id+'" data-bs-dismiss="modal"><i class="fas fa-stop"></i> '+(c.eh_grupos ? 'Encerrar' : 'Parar')+'</button>';
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
		const emCurso = c.status === 'enviando' || c.status === 'pausada';
		$('#campanha_id').val(c.id);
		$('#campanha_canal').val(c.canal || 'email');
		$('#campanha_titulo').val(c.titulo || '');
		$('#campanha_assunto').val(res.assunto || c.assunto || '');
		$('#campanha_mensagem').val(res.mensagem || c.mensagem || '');
		$('#segmento_tipo').val(seg.tipo || 'alunos_matriculados');
		$('#status_lead').val(seg.status_lead || '');
		$('#parcelas_atraso_min').val(String(seg.parcelas_atraso_min || 1));
		$('#titulo-modal-campanha').text(emCurso
			? 'Ajustar mensagem/mídia ('+(c.status === 'pausada' ? 'pausada' : 'em envio')+')'
			: 'Editar campanha');
		$('#btn-salvar-campanha').html(emCurso
			? '<i class="fas fa-save"></i> Salvar mensagem/mídia'
			: '<i class="fas fa-save"></i> Salvar rascunho');
		$('#campanha_canal, #segmento_tipo, #status_lead, #parcelas_atraso_min').prop('disabled', emCurso);
		window._campanhaArquivo = null;
		window._campanhaMidiaExistente = c.midia || seg.midia || null;
		window._campanhaMidiaBiblioteca = null;
		if(window._campanhaMidiaExistente && (window._campanhaMidiaExistente.origem === 'biblioteca' || String(window._campanhaMidiaExistente.path || '').indexOf('uploads/social/') === 0)){
			window._campanhaMidiaBiblioteca = {
				path: window._campanhaMidiaExistente.path,
				url: window._campanhaMidiaExistente.url,
				nome: window._campanhaMidiaExistente.nome,
				tipo: window._campanhaMidiaExistente.tipo || 'image'
			};
			window._campanhaMidiaExistente = null;
		}
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
		$('#wrap-grupos-wa').find('input,button').prop('disabled', emCurso);
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
		if(res.pacing) atualizarTextoPacing(res.pacing);
		Swal.fire('Fila', res.message, 'info');
		carregarCampanhas();
	}, 'json');
}

$(function(){
	window._campanhaArquivo = null;
	window._campanhaMidiaExistente = null;
	window._campanhaMidiaBiblioteca = null;

	carregarCampanhas();
	atualizarUiCanal();

	$('#campanha_canal').on('change', atualizarUiCanal);
	$('#filtro-canal').on('change', function(){ campanhaPagina = 1; carregarCampanhas(); });
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

	$('#btn-campanha-biblioteca').on('click', abrirModalCampanhaBiblioteca);
	$('#camp-bib-filtro .nav-link').on('click', function(){
		campanhaBibFormato = String($(this).data('formato') || '');
		$('#camp-bib-filtro .nav-link').removeClass('active');
		$(this).addClass('active');
		carregarCampanhaBiblioteca();
	});
	$(document).on('click', '.camp-bib-pick', function(){
		selecionarMidiaBibliotecaCampanha({
			path: String($(this).data('path') || ''),
			url: String($(this).data('url') || ''),
			nome: String($(this).data('nome') || ''),
			formato: String($(this).data('formato') || 'feed')
		});
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

	$('#btn-fechar-progresso-campanha').on('click', fecharModalProgressoCampanha);
	$('#modalCampanhaProgresso').on('hidden.bs.modal', function(){
		pararPollingProgressoCampanha();
		campanhaProgressoId = null;
	});
});

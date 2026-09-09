const ANIV_URL = 'painel/marketing/aniversariantes';
let anivPeriodo = 'mes';
let anivCanalEnvio = 'email';
let anivTemplate = { assunto: '', mensagem: '' };
let anivWaOk = false;
let anivBuscaTimer = null;

function anivPost(data, cb){
	$.post(url_base + ANIV_URL, data, cb, 'json').fail(function(){
		Swal.fire('Erro', 'Falha na requisição.', 'error');
	});
}

function escAniv(s){
	return $('<div>').text(s == null ? '' : String(s)).html();
}

function idsSelecionadosAniv(){
	const ids = [];
	$('#aniv-corpo input.aniv-check:checked').each(function(){
		const id = parseInt($(this).val(), 10);
		if(id > 0) ids.push(id);
	});
	return ids;
}

function atualizarUiPeriodoAniv(){
	const ehMes = anivPeriodo === 'mes';
	$('#wrap-aniv-mes').toggleClass('d-none', !ehMes);
	$('#aniv-filtros-periodo button').removeClass('active');
	$('#aniv-filtros-periodo button[data-periodo="'+anivPeriodo+'"]').addClass('active');
}

function carregarAniversariantes(){
	const mes = parseInt($('#aniv-mes').val(), 10) || new Date().getMonth() + 1;
	anivPost({
		acao: 'listar',
		periodo: anivPeriodo,
		mes: mes,
		busca: ($('#aniv-busca').val() || '').trim()
	}, function(res){
		if(!res || !res.success){
			$('#aniv-corpo').html('<tr><td colspan="8" class="text-danger p-3">'+
				escAniv((res && res.message) || 'Falha ao carregar.')+'</td></tr>');
			return;
		}
		$('#aniv-total').text((res.total || 0)+' aniversariante(s)');
		const $body = $('#aniv-corpo').empty();
		const lista = res.lista || [];
		if(!lista.length){
			$body.append('<tr><td colspan="8" class="text-muted p-3">Nenhum aniversariante neste filtro.</td></tr>');
			$('#aniv-check-all').prop('checked', false);
			return;
		}
		lista.forEach(function(a){
			const mat = a.matricula_ativa ? '<span class="badge bg-success">Ativa</span>' : '<span class="badge bg-secondary">—</span>';
			const env = a.enviado_ano
				? '<span class="badge bg-info text-dark">Sim</span>'
				: '<span class="text-muted">Não</span>';
			$body.append(
				'<tr>'
				+'<td><input type="checkbox" class="form-check-input aniv-check" value="'+a.id+'"></td>'
				+'<td>'+escAniv(a.nome)+'</td>'
				+'<td>'+escAniv(a.nascimento_fmt)+'</td>'
				+'<td>'+(a.idade != null ? escAniv(a.idade) : '—')+'</td>'
				+'<td class="small">'+escAniv(a.email || '—')+'</td>'
				+'<td class="small">'+escAniv(a.whatsapp || '—')+'</td>'
				+'<td>'+mat+'</td>'
				+'<td>'+env+'</td>'
				+'</tr>'
			);
		});
		$('#aniv-check-all').prop('checked', false);
	});
}

function carregarMetaAniv(cb){
	anivPost({ acao: 'meta' }, function(res){
		if(res && res.success){
			anivTemplate = res.template || { assunto: '', mensagem: '' };
			anivWaOk = !!res.whatsapp_conectado;
		}
		if(typeof cb === 'function') cb();
	});
}

function abrirModalEnvioAniv(canal){
	const ids = idsSelecionadosAniv();
	if(!ids.length){
		Swal.fire('Atenção', 'Selecione ao menos um aniversariante.', 'warning');
		return;
	}
	if(canal === 'whatsapp' && !anivWaOk){
		Swal.fire('WhatsApp', 'WhatsApp não conectado. Configure em Comunicação.', 'warning');
		return;
	}
	anivCanalEnvio = canal;
	$('#modal-aniv-envio-titulo').text(canal === 'whatsapp' ? 'Enviar WhatsApp' : 'Enviar e-mail');
	$('#wrap-aniv-assunto').toggleClass('d-none', canal === 'whatsapp');
	$('#aniv-envio-resumo').text(ids.length+' destinatário(s) selecionado(s).');
	$('#aniv-assunto').val(anivTemplate.assunto || '');
	$('#aniv-mensagem').val(
		canal === 'whatsapp'
			? $('<div>').html(anivTemplate.mensagem || '').text()
			: (anivTemplate.mensagem || '')
	);
	$('#modal-aniv-envio').modal('show');
}

function confirmarEnvioAniv(){
	const ids = idsSelecionadosAniv();
	if(!ids.length) return;
	$('#btn-aniv-confirmar-envio').prop('disabled', true);
	anivPost({
		acao: 'enviar',
		canal: anivCanalEnvio,
		ids: ids,
		assunto: $('#aniv-assunto').val(),
		mensagem: $('#aniv-mensagem').val()
	}, function(res){
		$('#btn-aniv-confirmar-envio').prop('disabled', false);
		$('#modal-aniv-envio').modal('hide');
		if(!res || (!res.success && !(res.enviados > 0))){
			Swal.fire('Erro', (res && res.message) || 'Falha no envio.', 'error');
			return;
		}
		Swal.fire('Envio', (res.message || 'Concluído.'), res.erros ? 'warning' : 'success');
		carregarAniversariantes();
	});
}

function criarCampanhaAniv(){
	let segmento = 'aniversariantes_mes';
	if(anivPeriodo === 'hoje'){
		segmento = 'aniversariantes_dia';
	}
	const canal = anivWaOk ? 'whatsapp' : 'email';
	window.location.href = url_base + 'painel/campanhas?segmento=' + encodeURIComponent(segmento)
		+ '&canal=' + encodeURIComponent(canal) + '&titulo=' + encodeURIComponent('Aniversariantes');
}

$(function(){
	const mesAtual = new Date().getMonth() + 1;
	$('#aniv-mes').val(String(mesAtual));
	atualizarUiPeriodoAniv();
	carregarMetaAniv(carregarAniversariantes);

	$('#aniv-filtros-periodo').on('click', 'button[data-periodo]', function(){
		anivPeriodo = $(this).data('periodo') || 'mes';
		atualizarUiPeriodoAniv();
		carregarAniversariantes();
	});
	$('#aniv-mes').on('change', carregarAniversariantes);
	$('#aniv-busca').on('input', function(){
		clearTimeout(anivBuscaTimer);
		anivBuscaTimer = setTimeout(carregarAniversariantes, 350);
	});
	$('#btn-aniv-atualizar').on('click', carregarAniversariantes);

	$('#aniv-check-all').on('change', function(){
		const ck = $(this).prop('checked');
		$('#aniv-corpo .aniv-check').prop('checked', ck);
	});

	$('#btn-aniv-email').on('click', function(){ abrirModalEnvioAniv('email'); });
	$('#btn-aniv-wa').on('click', function(){ abrirModalEnvioAniv('whatsapp'); });
	$('#btn-aniv-confirmar-envio').on('click', confirmarEnvioAniv);
	$('#btn-aniv-campanha').on('click', criarCampanhaAniv);
});

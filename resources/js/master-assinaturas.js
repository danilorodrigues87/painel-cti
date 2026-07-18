const MASTER_ASSINATURAS_URL = 'master/assinaturas';
const pixCache = {};

function esc(s){
	return $('<div>').text(s == null ? '' : String(s)).html();
}

function badgeStatus(st){
	const map = {
		aberta: 'warning',
		pago: 'success',
		vencida: 'danger',
		cancelada: 'secondary'
	};
	const cls = map[st] || 'secondary';
	return '<span class="badge bg-'+cls+'">'+esc(st)+'</span>';
}

function popularEscolas(){
	const $sel = $('#filtro_escola');
	const cur = $sel.val();
	$sel.find('option:not(:first)').remove();
	(window.MASTER_ESCOLAS_SAAS || []).forEach(function(e){
		$sel.append('<option value="'+e.id+'">'+esc(e.nome)+'</option>');
	});
	if(cur) $sel.val(cur);
}

function renderLista(faturas){
	const $tb = $('#lista-faturas-saas').empty();
	if(!faturas || !faturas.length){
		$tb.append('<tr><td colspan="6" class="text-center text-muted py-4">Nenhuma fatura.</td></tr>');
		return;
	}
	faturas.forEach(function(f){
		pixCache[f.id] = f.pix_copia_cola || '';
		const acoes = [];
		if(f.tem_pix){
			acoes.push('<button type="button" class="btn btn-sm btn-outline-success me-1 btn-ver-pix" data-id="'+f.id+'" data-info="'+esc(f.escola_nome+' · '+f.competencia+' · R$ '+f.valor_br)+'"><i class="fas fa-qrcode"></i></button>');
		}
		if(f.status !== 'pago'){
			acoes.push('<button type="button" class="btn btn-sm btn-outline-primary me-1 btn-reenviar-pix" data-id="'+f.id+'" title="Gerar/atualizar PIX"><i class="fas fa-sync"></i></button>');
			acoes.push('<button type="button" class="btn btn-sm btn-outline-dark btn-marcar-paga" data-id="'+f.id+'" title="Marcar paga manual"><i class="fas fa-check"></i></button>');
		}
		$tb.append(
			'<tr>'
			+'<td><strong>'+esc(f.escola_nome)+'</strong><br><small class="text-muted">#'+f.id_admin+'</small></td>'
			+'<td>'+esc(f.competencia)+'</td>'
			+'<td>R$ '+esc(f.valor_br)+'</td>'
			+'<td>'+esc(f.vencimento)+'</td>'
			+'<td>'+badgeStatus(f.status)+'</td>'
			+'<td class="text-end">'+acoes.join('')+'</td>'
			+'</tr>'
		);
	});
}

function carregar(){
	$.post(url_base + MASTER_ASSINATURAS_URL, {
		acao: 'listar',
		competencia: $('#filtro_competencia').val() || '',
		id_admin: $('#filtro_escola').val() || '',
		status: $('#filtro_status').val() || ''
	}, function(res){
		if(!res || !res.success){
			Swal.fire('Erro', (res && res.message) || 'Falha.', 'error');
			return;
		}
		renderLista(res.faturas || []);
	}, 'json');
}

function gerarMes(){
	const comp = $('#filtro_competencia').val() || '';
	Swal.fire({
		title: 'Gerar faturas?',
		text: 'Competência '+comp+' para escolas com plano e preço.',
		icon: 'question',
		showCancelButton: true,
		confirmButtonText: 'Gerar'
	}).then(function(r){
		if(!r.isConfirmed) return;
		$.post(url_base + MASTER_ASSINATURAS_URL, { acao: 'gerar_mes', competencia: comp }, function(res){
			if(!res || !res.success){
				Swal.fire('Erro', (res && res.message) || 'Falha.', 'error');
				return;
			}
			let extra = '';
			if(res.erros && res.erros.length){
				extra = '<br><small class="text-muted">'+esc(res.erros.slice(0,5).join(' | '))+'</small>';
			}
			Swal.fire({ title: 'OK', html: esc(res.message)+extra, icon: 'success' });
			carregar();
		}, 'json');
	});
}

function gerarEscola(){
	const id = $('#filtro_escola').val();
	if(!id){
		Swal.fire('Atenção', 'Selecione uma escola no filtro.', 'warning');
		return;
	}
	$.post(url_base + MASTER_ASSINATURAS_URL, {
		acao: 'gerar',
		id_admin: id,
		competencia: $('#filtro_competencia').val() || ''
	}, function(res){
		if(!res || !res.success){
			Swal.fire('Erro', (res && res.message) || 'Falha.', 'error');
			return;
		}
		Swal.fire('OK', res.message, 'success');
		carregar();
	}, 'json');
}

function processar(){
	$.post(url_base + MASTER_ASSINATURAS_URL, {
		acao: 'processar',
		id_admin: $('#filtro_escola').val() || ''
	}, function(res){
		if(!res || !res.success){
			Swal.fire('Erro', (res && res.message) || 'Falha.', 'error');
			return;
		}
		const r = res.resumo || {};
		Swal.fire({
			title: 'Worker',
			html: 'Geradas/atualizadas: '+(r.geradas||0)+'<br>Suspensas: '+(r.suspensas||0)
				+(r.mp_ok ? '' : '<br><span class="text-danger">MP CTI não configurado</span>'),
			icon: 'info'
		});
		carregar();
	}, 'json');
}

$(function(){
	popularEscolas();
	if(window.MASTER_WEBHOOK_SAAS){
		$('#webhook-url').text(window.MASTER_WEBHOOK_SAAS);
		$('#webhook-hint').removeClass('d-none');
	}
	carregar();
	$('#btn-filtrar').on('click', carregar);
	$('#btn-gerar-mes').on('click', gerarMes);
	$('#btn-gerar-escola').on('click', gerarEscola);
	$('#btn-processar').on('click', processar);

	$(document).on('click', '.btn-ver-pix', function(){
		const id = $(this).data('id');
		$('#pix-saas-info').text($(this).data('info') || '');
		$('#pix-saas-copia').val(pixCache[id] || '');
		$('#modalPixSaas').modal('show');
	});
	$('#btn-copiar-pix').on('click', function(){
		const t = document.getElementById('pix-saas-copia');
		t.select();
		document.execCommand('copy');
		Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Copiado', showConfirmButton: false, timer: 1500 });
	});
	$(document).on('click', '.btn-reenviar-pix', function(){
		const id = $(this).data('id');
		$.post(url_base + MASTER_ASSINATURAS_URL, { acao: 'reenviar_pix', id: id }, function(res){
			if(!res || !res.success){
				Swal.fire('Erro', (res && res.message) || 'Falha.', 'error');
				return;
			}
			Swal.fire('OK', res.message, 'success');
			carregar();
		}, 'json');
	});
	$(document).on('click', '.btn-marcar-paga', function(){
		const id = $(this).data('id');
		Swal.fire({ title: 'Marcar como paga?', icon: 'question', showCancelButton: true, confirmButtonText: 'Sim' })
			.then(function(r){
				if(!r.isConfirmed) return;
				$.post(url_base + MASTER_ASSINATURAS_URL, { acao: 'marcar_paga', id: id }, function(res){
					if(!res || !res.success){
						Swal.fire('Erro', (res && res.message) || 'Falha.', 'error');
						return;
					}
					carregar();
				}, 'json');
			});
	});
});

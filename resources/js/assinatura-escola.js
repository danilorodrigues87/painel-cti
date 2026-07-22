const ASSINATURA_ESCOLA_URL = 'painel/assinatura';
let faturaAbertaId = null;

function esc(s){
	return $('<div>').text(s == null ? '' : String(s)).html();
}

function badgeStatus(st){
	const map = { aberta: 'warning', pago: 'success', vencida: 'danger', cancelada: 'secondary' };
	return '<span class="badge bg-'+(map[st]||'secondary')+'">'+esc(st)+'</span>';
}

function setAberta(f){
	faturaAbertaId = f ? f.id : null;
	if(!f){
		$('#box-sem-aberta').removeClass('d-none');
		$('#box-com-aberta').addClass('d-none');
		$('#badge-fatura-status').attr('class', 'badge bg-secondary').text('—');
		return;
	}
	$('#box-sem-aberta').addClass('d-none');
	$('#box-com-aberta').removeClass('d-none');
	$('#badge-fatura-status').attr('class', 'badge bg-'+(f.status === 'vencida' ? 'danger' : 'warning')).text(f.status);
	$('#fat-competencia').text(f.competencia || '—');
	$('#fat-valor').text('R$ '+(f.valor_br || '0,00'));
	$('#fat-vencimento').text(f.vencimento || '—');
	const pix = f.pix_copia_cola || '';
	$('#fat-pix').val(pix);
	const qrSrc = f.pix_qr_src || (pix
		? ('https://api.qrserver.com/v1/create-qr-code/?size=220x220&ecc=M&margin=8&data='+encodeURIComponent(pix))
		: '');
	if(qrSrc){
		$('#fat-qr-img').attr('src', qrSrc).removeClass('d-none');
		$('#fat-qr-placeholder').addClass('d-none');
	} else {
		$('#fat-qr-img').addClass('d-none').attr('src', '');
		$('#fat-qr-placeholder').removeClass('d-none');
	}
	$('#btn-copiar-pix-escola').prop('disabled', !pix);
	$('#btn-atualizar-pix').prop('disabled', false);
	$('#btn-verificar-pag').prop('disabled', !f.mp_payment_id && !pix);
}

function renderHistorico(faturas){
	const $tb = $('#lista-faturas-escola').empty();
	if(!faturas || !faturas.length){
		$tb.append('<tr><td colspan="5" class="text-center text-muted py-4">Nenhuma fatura ainda.</td></tr>');
		return;
	}
	faturas.forEach(function(f){
		$tb.append(
			'<tr>'
			+'<td>'+esc(f.competencia)+'</td>'
			+'<td>R$ '+esc(f.valor_br)+'</td>'
			+'<td>'+esc(f.vencimento)+'</td>'
			+'<td>'+badgeStatus(f.status)+'</td>'
			+'<td>'+esc(f.pago_em || '—')+'</td>'
			+'</tr>'
		);
	});
}

function carregar(){
	$.post(url_base + ASSINATURA_ESCOLA_URL, { acao: 'carregar' }, function(res){
		if(!res || !res.success){
			Swal.fire('Erro', (res && res.message) || 'Falha ao carregar.', 'error');
			return;
		}
		if(!res.tabela_ok){
			$('#alert-assinatura').removeClass('d-none').text(res.message || 'Assinatura indisponível.');
			$('#lista-faturas-escola').html('<tr><td colspan="5" class="text-center text-muted py-4">—</td></tr>');
			setAberta(null);
			return;
		}

		const r = res.resumo || {};
		$('#ass-plano-nome').text(r.plano_nome || 'Personalizado / sem plano');
		$('#ass-valor-mensal').text(r.valor_mensal_br ? ('R$ '+r.valor_mensal_br) : '—');
		$('#ass-dia-venc').text(r.dia_vencimento || '—');

		if(r.em_trial){
			$('#alert-assinatura').removeClass('d-none alert-warning').addClass('alert-info')
				.text('Você está em período de trial'+(r.trial_ate ? (' até '+r.trial_ate) : '')+'. Cobrança começa após o trial.');
		} else if(r.bloqueada || r.assinatura_status === 'suspensa' || !r.escola_ativa){
			$('#alert-assinatura').removeClass('d-none alert-info').addClass('alert-warning')
				.text('Assinatura suspensa. Regularize o pagamento abaixo para liberar o painel.');
		} else {
			$('#alert-assinatura').addClass('d-none');
		}

		const soLeitura = !!r.so_leitura;
		$('#btn-atualizar-pix, #btn-verificar-pag, #btn-copiar-pix-escola').toggleClass('d-none', soLeitura);
		if(soLeitura){
			$('#alert-somente-diretor').removeClass('d-none');
		} else {
			$('#alert-somente-diretor').addClass('d-none');
		}

		setAberta(res.aberta || null);
		renderHistorico(res.faturas || []);
	}, 'json').fail(function(){
		Swal.fire('Erro', 'Falha de comunicação.', 'error');
	});
}

$(function(){
	carregar();

	$('#btn-copiar-pix-escola').on('click', function(){
		const t = document.getElementById('fat-pix');
		if(!t.value) return;
		t.select();
		document.execCommand('copy');
		if(navigator.clipboard && navigator.clipboard.writeText){
			navigator.clipboard.writeText(t.value);
		}
		Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'PIX copiado', showConfirmButton: false, timer: 1500 });
	});

	$('#btn-atualizar-pix').on('click', function(){
		if(!faturaAbertaId) return;
		const $btn = $(this).prop('disabled', true);
		$.post(url_base + ASSINATURA_ESCOLA_URL, { acao: 'atualizar_pix', id: faturaAbertaId }, function(res){
			$btn.prop('disabled', false);
			if(!res || !res.success){
				Swal.fire('Erro', (res && res.message) || 'Falha.', 'error');
				return;
			}
			Swal.fire('OK', res.message, 'success');
			carregar();
		}, 'json').fail(function(){
			$btn.prop('disabled', false);
			Swal.fire('Erro', 'Falha de comunicação.', 'error');
		});
	});

	$('#btn-verificar-pag').on('click', function(){
		if(!faturaAbertaId) return;
		const $btn = $(this).prop('disabled', true);
		$.post(url_base + ASSINATURA_ESCOLA_URL, { acao: 'verificar', id: faturaAbertaId }, function(res){
			$btn.prop('disabled', false);
			if(!res || !res.success){
				Swal.fire('Erro', (res && res.message) || 'Falha.', 'error');
				return;
			}
			Swal.fire(res.fatura && res.fatura.status === 'pago' ? 'Pago' : 'Status', res.message, res.fatura && res.fatura.status === 'pago' ? 'success' : 'info');
			carregar();
		}, 'json').fail(function(){
			$btn.prop('disabled', false);
			Swal.fire('Erro', 'Falha de comunicação.', 'error');
		});
	});
});

function escHtml(s) {
	return String(s || '')
		.replace(/&/g, '&amp;')
		.replace(/</g, '&lt;')
		.replace(/>/g, '&gt;')
		.replace(/"/g, '&quot;');
}

function moedaBrJs(n) {
	var v = parseFloat(n);
	if (isNaN(v)) return '0,00';
	return v.toFixed(2).replace('.', ',');
}

function idsParcelasMarcadas() {
	var ids = [];
	$('.chk-parc-cobrar:checked').each(function () {
		var id = parseInt($(this).val(), 10);
		if (id > 0) ids.push(id);
	});
	return ids;
}

function parcelasCobrarPayload() {
	var ids = idsParcelasMarcadas();
	return ids.length ? ids.join(',') : '';
}

function recalcularSimulacao(id, callback) {
	var simUrl =
		typeof cancelarSimular !== 'undefined' && cancelarSimular
			? cancelarSimular
			: 'painel/matriculas/cancelar/simular';
	$('#box-totais-cancel').css('opacity', '0.55');
	$.ajax({
		url: url_base + simUrl,
		method: 'post',
		data: { id: id, parcelas_cobrar: parcelasCobrarPayload() },
		dataType: 'json',
		success: function (sim) {
			$('#box-totais-cancel').css('opacity', '1');
			if (sim && sim.ok && typeof callback === 'function') callback(sim);
		},
		error: function () {
			$('#box-totais-cancel').css('opacity', '1');
		},
	});
}

function renderBlocoMulta(sim) {
	var multa = parseFloat(sim.multa_rescisoria) || 0;
	if (multa <= 0) {
		return '<div id="box-multa-cancel" class="d-none"></div>';
	}
	return (
		'<p id="box-multa-cancel" class="mb-2 border-start border-3 border-warning ps-2">' +
		'<strong>Multa rescisória:</strong> R$ ' +
		moedaBrJs(sim.multa_rescisoria) +
		' <span class="text-muted">(' +
		moedaBrJs((sim.params && sim.params.multa_cancelamento_pct) || 10) +
		'% sobre parcelas futuras que serão canceladas)</span> — ' +
		'será gerado <u>título em aberto</u> para quitação no carnê ou extrato do aluno.</p>'
	);
}

function renderTotaisCancelamento(sim) {
	var html =
		'<div class="border rounded p-2 mb-2 bg-light small" id="box-totais-cancel">' +
		'<div>Parcelas a cobrar: <strong>' +
		(sim.qtd_cobrar || 0) +
		'</strong> · Baixa admin: <strong>' +
		(sim.qtd_baixar || 0) +
		'</strong></div>' +
		'<div>Total a cobrar (face): <strong>R$ ' +
		moedaBrJs(sim.total_cobrar_face) +
		'</strong> · com encargos: <strong>R$ ' +
		moedaBrJs(sim.total_cobrar_com_encargos) +
		'</strong></div>' +
		'<div>Multa rescisória (' +
		moedaBrJs((sim.params && sim.params.multa_cancelamento_pct) || 10) +
		'% sobre futuras não cobradas): <strong>R$ ' +
		moedaBrJs(sim.multa_rescisoria) +
		'</strong></div>' +
		'<div class="fw-semibold mt-1">Dívida estimada (cobrança + multa): R$ ' +
		moedaBrJs(sim.total_geral_com_encargos) +
		'</div>' +
		'</div>';
	return html;
}

function renderListaParcelas(sim) {
	var parcelas = sim.parcelas || [].concat(sim.vencidas || [], sim.futuras || []);
	if (!parcelas.length) {
		return '<p class="text-muted mb-2">Nenhuma parcela em aberto nesta matrícula.</p>';
	}
	var html =
		'<p class="fw-semibold mb-1">Selecione as parcelas que <u>permanecem em aberto</u> para cobrança. As demais recebem baixa administrativa (R$ 0).</p>' +
		'<div class="table-responsive mb-2" style="max-height:220px;overflow:auto">' +
		'<table class="table table-sm table-bordered mb-0"><thead class="table-light">' +
		'<tr><th style="width:32px"></th><th>Venc.</th><th>Descrição</th><th>Face</th><th>Com enc.</th></tr></thead><tbody>';
	parcelas.forEach(function (p) {
		var checked = p.cobrar !== undefined ? p.cobrar : !!p.cobrar_default;
		var badge =
			p.grupo === 'vencida'
				? '<span class="badge bg-danger ms-1">Vencida</span>'
				: '<span class="badge bg-secondary ms-1">Futura</span>';
		html +=
			'<tr><td class="text-center">' +
			'<input type="checkbox" class="form-check-input chk-parc-cobrar" value="' +
			p.id +
			'"' +
			(checked ? ' checked' : '') +
			'></td>' +
			'<td class="text-nowrap">' +
			escHtml(p.vencimento_br) +
			badge +
			'</td>' +
			'<td class="small">' +
			escHtml(p.descricao) +
			'</td>' +
			'<td>R$ ' +
			moedaBrJs(p.valor_face) +
			'</td>' +
			'<td>R$ ' +
			moedaBrJs(p.total_com_encargos) +
			'</td></tr>';
	});
	html += '</tbody></table></div>';
	return html;
}

function cancelar_contrato(id) {
	var simUrl =
		typeof cancelarSimular !== 'undefined' && cancelarSimular
			? cancelarSimular
			: 'painel/matriculas/cancelar/simular';

	$.ajax({
		url: url_base + simUrl,
		method: 'post',
		data: { id: id },
		dataType: 'json',
		success: function (sim) {
			if (!sim || !sim.ok) {
				Swal.fire('Erro', (sim && sim.message) || 'Não foi possível simular o cancelamento.', 'error');
				return;
			}
			abrirModalCancelamento(id, sim);
		},
		error: function () {
			Swal.fire('Erro', 'Falha ao simular cancelamento.', 'error');
		},
	});
}

function abrirModalCancelamento(id, sim) {
	var params = sim.params || {};
	var html = '<div class="text-start small" id="wrap-cancel-modal">';
	html +=
		'<p class="mb-2">Carência: <strong>' +
		(params.carencia_dias || 7) +
		' dias</strong> · Multa atraso: <strong>' +
		moedaBrJs(params.multa_atraso_pct || 2) +
		'%</strong> · Juros: <strong>' +
		moedaBrJs(params.juros_mora_pct_mes || 1) +
		'% a.m.</strong></p>';
	html += renderTotaisCancelamento(sim);
	html += renderListaParcelas(sim);
	html += renderBlocoMulta(sim);
	html +=
		'<p class="text-muted mb-0 small">Encargos de atraso continuam sendo calculados até a data do pagamento de cada título. A multa rescisória recalcula ao marcar/desmarcar parcelas futuras.</p>';
	html += '</div>';

	Swal.fire({
		title: 'Cancelar contrato?',
		html: html,
		icon: 'warning',
		width: 720,
		showCancelButton: true,
		confirmButtonColor: '#3085d6',
		cancelButtonColor: '#d33',
		confirmButtonText: 'Confirmar cancelamento',
		cancelButtonText: 'Voltar',
		didOpen: function () {
			var $popup = $(Swal.getHtmlContainer());
			$popup
				.off('change.cancelParc', '.chk-parc-cobrar')
				.on('change.cancelParc', '.chk-parc-cobrar', function () {
					recalcularSimulacao(id, function (novoSim) {
						$popup.find('#box-totais-cancel').replaceWith(renderTotaisCancelamento(novoSim));
						$popup.find('#box-multa-cancel').replaceWith(renderBlocoMulta(novoSim));
					});
				});
		},
		willClose: function () {
			var $popup = Swal.getHtmlContainer();
			if ($popup) {
				$($popup).off('change.cancelParc', '.chk-parc-cobrar');
			}
		},
		preConfirm: function () {
			return {
				parcelas_cobrar: idsParcelasMarcadas(),
			};
		},
	}).then(function (result) {
		if (!result.isConfirmed) return;

		var flags = result.value || {};
		$.ajax({
			url: url_base + (typeof cancelar !== 'undefined' ? cancelar : 'painel/matriculas/cancelar'),
			method: 'post',
			data: {
				id: id,
				parcelas_cobrar: (flags.parcelas_cobrar || []).join(','),
			},
			dataType: 'json',
			success: function (res) {
				var ok = res && res.ok;
				Swal.fire({
					title: ok ? 'Cancelado!' : 'Atenção',
					text: (res && res.message) || (ok ? 'Contrato cancelado.' : 'Erro ao cancelar.'),
					icon: ok ? 'success' : 'error',
				});
				if (typeof listar === 'function') listar(null, 1);
			},
			error: function () {
				Swal.fire({ title: 'Erro', text: 'Falha ao cancelar o contrato.', icon: 'error' });
			},
		});
	});
}

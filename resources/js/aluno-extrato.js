(function () {
	var idAluno = window.EXTRATO_ALUNO_ID;
	var estado = { titulos: [], podeRenegociar: false, aluno: null, totais: null };

	function toastOk(msg) {
		Swal.fire({
			toast: true,
			position: 'top-end',
			icon: 'success',
			title: msg || 'OK',
			showConfirmButton: false,
			timer: 2200,
		});
	}

	function toastErr(msg) {
		Swal.fire({
			icon: 'error',
			title: 'Atenção',
			text: msg || 'Ocorreu um erro.',
			confirmButtonText: 'OK',
		});
	}

	function toastInfo(msg) {
		Swal.fire({
			icon: 'info',
			title: 'Atenção',
			text: msg,
			confirmButtonText: 'OK',
		});
	}

	function post(data) {
		return $.ajax({
			url: url_base + 'painel/alunos/' + idAluno + '/extrato',
			method: 'POST',
			data: data,
			dataType: 'json',
		});
	}

	function esc(s) {
		return $('<div>').text(s == null ? '' : String(s)).html();
	}

	function moeda(v) {
		var n = Number(v) || 0;
		return n.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
	}

	function fmtData(d) {
		if (!d || d === '0000-00-00') return '—';
		var p = String(d).slice(0, 10).split('-');
		if (p.length !== 3) return esc(d);
		return p[2] + '/' + p[1] + '/' + p[0];
	}

	function badgeStatus(st) {
		if (st === 'pago') return '<span class="badge bg-success">Pago</span>';
		if (st === 'vencido') return '<span class="badge bg-danger">Vencido</span>';
		return '<span class="badge bg-warning text-dark">Em aberto</span>';
	}

	function renderTotais(t) {
		t = t || {};
		$('#totais-extrato').html(
			'<div class="col-6 col-md-3"><div class="border rounded p-2 small"><div class="text-muted">Pago</div><strong class="text-success">' +
				moeda(t.pago) +
				'</strong></div></div>' +
				'<div class="col-6 col-md-3"><div class="border rounded p-2 small"><div class="text-muted">Em aberto</div><strong class="text-primary">' +
				moeda(t.aberto) +
				'</strong></div></div>' +
				'<div class="col-6 col-md-3"><div class="border rounded p-2 small"><div class="text-muted">Vencido</div><strong class="text-danger">' +
				moeda(t.vencido) +
				'</strong></div></div>' +
				'<div class="col-6 col-md-3"><div class="border rounded p-2 small"><div class="text-muted">Títulos</div><strong>' +
				(t.titulos || 0) +
				'</strong></div></div>'
		);
	}

	function render(res) {
		estado.titulos = res.titulos || [];
		estado.podeRenegociar = !!res.pode_renegociar;
		estado.aluno = res.aluno;
		estado.totais = res.totais;

		renderTotais(res.totais);
		if (estado.podeRenegociar) {
			$('#btn-renegociar').removeClass('d-none');
		} else {
			$('#btn-renegociar').addClass('d-none');
		}

		var html = '';

		// —— Acordos com parcelas e baixa em lote ——
		if ((res.acordos || []).length) {
			html += '<h5 class="mt-2 mb-2">Acordos de renegociação</h5>';
			(res.acordos || []).forEach(function (a) {
				var temAberto = (a.parcelas || []).some(function (p) {
					return p.selecionavel;
				});
				var rowsA = '';
				(a.parcelas || []).forEach(function (t) {
					var chk = t.selecionavel
						? '<input type="checkbox" class="form-check-input chk-titulo chk-acordo-' +
							a.id +
							'" value="' +
							t.id +
							'">'
						: '';
					var btnBaixa = t.selecionavel
						? '<button type="button" class="btn btn-sm btn-outline-success btn-dar-baixa" data-id="' +
							t.id +
							'" data-valor="' +
							t.valor +
							'" data-desc="' +
							esc(t.descricao) +
							'">Só esta</button>'
						: '—';
					rowsA +=
						'<tr>' +
						'<td class="text-center">' +
						chk +
						'</td>' +
						'<td class="small">' +
						esc(t.descricao) +
						'</td>' +
						'<td>' +
						fmtData(t.vencimento) +
						'</td>' +
						'<td>' +
						moeda(t.valor) +
						'</td>' +
						'<td>' +
						badgeStatus(t.status) +
						'</td>' +
						'<td class="small text-muted">' +
						(t.tipo_pagamento ? esc(t.tipo_pagamento) : '—') +
						'</td>' +
						'<td>' +
						btnBaixa +
						'</td>' +
						'</tr>';
				});
				html +=
					'<div class="card mb-3 border-primary" data-acordo="' +
					a.id +
					'">' +
					'<div class="card-header bg-primary bg-opacity-10 d-flex flex-wrap justify-content-between align-items-center gap-2">' +
					'<div><strong>' +
					esc(a.label) +
					'</strong> · ' +
					a.qtd_parcelas +
					' parcelas · total ' +
					moeda(a.valor_total) +
					(a.observacao ? '<div class="small text-muted">' + esc(a.observacao) + '</div>' : '') +
					'</div>' +
					'<div class="small">Pago ' +
					moeda(a.total_pago) +
					' · Aberto ' +
					moeda(a.total_aberto) +
					(a.total_vencido > 0 ? ' · <span class="text-danger">Vencido ' + moeda(a.total_vencido) + '</span>' : '') +
					'</div></div>' +
					'<div class="card-body p-0">' +
					(temAberto
						? '<div class="px-3 py-2 border-bottom d-flex flex-wrap gap-2 align-items-center">' +
							'<div class="form-check mb-0">' +
							'<input class="form-check-input chk-acordo-todos" type="checkbox" data-acordo="' +
							a.id +
							'" id="chk-ac-todos-' +
							a.id +
							'">' +
							'<label class="form-check-label small" for="chk-ac-todos-' +
							a.id +
							'">Selecionar abertas deste acordo</label></div>' +
							'<button type="button" class="btn btn-sm btn-success btn-baixa-acordo" data-acordo="' +
							a.id +
							'"><i class="fa-solid fa-check-double"></i> Dar baixa nas selecionadas</button>' +
							'</div>'
						: '') +
					'<div class="table-responsive"><table class="table table-sm table-hover mb-0">' +
					'<thead class="table-light"><tr><th style="width:36px"></th><th>Parcela</th><th>Vencimento</th><th>Valor</th><th>Status</th><th>Pgto</th><th></th></tr></thead>' +
					'<tbody>' +
					(rowsA || '<tr><td colspan="7" class="text-muted p-3">Sem parcelas</td></tr>') +
					'</tbody></table></div></div></div>';
			});
		}

		// —— Resumo matrículas ——
		if ((res.matriculas || []).length) {
			html += '<h5 class="mt-3 mb-2">Matrículas</h5>';
			(res.matriculas || []).forEach(function (m) {
				html +=
					'<div class="card mb-2"><div class="card-body py-2 px-3 small d-flex flex-wrap justify-content-between gap-2">' +
					'<div><strong>Matrícula #' +
					m.id +
					'</strong> · ' +
					esc(m.curso) +
					' <span class="text-muted">(' +
					esc(m.status) +
					')</span></div>' +
					'<div>Pago ' +
					moeda(m.total_pago) +
					' · Aberto ' +
					moeda(m.total_aberto) +
					(m.total_vencido > 0 ? ' · <span class="text-danger">Vencido ' + moeda(m.total_vencido) + '</span>' : '') +
					'</div></div></div>';
			});
		}

		// —— Tabela geral (matrícula + acordo) p/ PDF e renegociação ——
		var rows = '';
		(res.titulos || []).forEach(function (t) {
			var chk = t.selecionavel
				? '<input type="checkbox" class="form-check-input chk-titulo chk-geral" value="' + t.id + '">'
				: '';
			var btnBaixa = t.selecionavel
				? '<button type="button" class="btn btn-sm btn-outline-success btn-dar-baixa" data-id="' +
					t.id +
					'" data-valor="' +
					t.valor +
					'" data-desc="' +
					esc(t.descricao) +
					'">Só esta</button>'
				: '<span class="small text-muted">—</span>';
			rows +=
				'<tr>' +
				'<td class="text-center">' +
				chk +
				'</td>' +
				'<td class="small">' +
				esc(t.origem_label) +
				'</td>' +
				'<td class="small">' +
				esc(t.descricao) +
				'</td>' +
				'<td>' +
				fmtData(t.vencimento) +
				'</td>' +
				'<td>' +
				moeda(t.valor) +
				'</td>' +
				'<td>' +
				badgeStatus(t.status) +
				'</td>' +
				'<td class="small text-muted">' +
				(t.tipo_pagamento ? esc(t.tipo_pagamento) : '—') +
				'</td>' +
				'<td>' +
				btnBaixa +
				'</td>' +
				'</tr>';
		});

		html +=
			'<h5 class="mt-3 mb-2">Todos os títulos</h5>' +
			'<p class="small text-muted">Use os checkboxes aqui para renegociar ou baixar várias de origens diferentes. Nos acordos acima, a baixa em lote já fica no card do acordo.</p>' +
			'<div class="card" id="card-titulos-pdf"><div class="card-body p-0">' +
			'<div class="table-responsive"><table class="table table-sm table-hover mb-0">' +
			'<thead class="table-light"><tr>' +
			'<th style="width:36px"><input type="checkbox" class="form-check-input" id="chk-todos" title="Selecionar abertas"></th>' +
			'<th>Origem</th><th>Descrição</th><th>Vencimento</th><th>Valor</th><th>Status</th><th>Pgto</th><th></th>' +
			'</tr></thead><tbody>' +
			(rows || '<tr><td colspan="8" class="text-muted p-3">Nenhum título encontrado.</td></tr>') +
			'</tbody></table></div></div></div>';

		$('#box-extrato').html(html);
		$('#box-extrato')
			.off('click', '.btn-dar-baixa')
			.on('click', '.btn-dar-baixa', function () {
				abrirBaixa($(this).data('id'), parseFloat($(this).data('valor')), String($(this).data('desc') || ''));
			});
		$('#box-extrato')
			.off('click', '.btn-baixa-acordo')
			.on('click', '.btn-baixa-acordo', function () {
				var idAc = $(this).data('acordo');
				abrirBaixaLote('.chk-acordo-' + idAc + ':checked');
			});
		$('#box-extrato')
			.off('change', '.chk-acordo-todos')
			.on('change', '.chk-acordo-todos', function () {
				var idAc = $(this).data('acordo');
				$('.chk-acordo-' + idAc).prop('checked', $(this).is(':checked'));
			});
		$('#chk-todos')
			.off('change')
			.on('change', function () {
				$('.chk-geral').prop('checked', $(this).is(':checked'));
			});
	}

	function abrirBaixa(idTitulo, valor, desc) {
		var hoje = new Date();
		var yyyy = hoje.getFullYear();
		var mm = String(hoje.getMonth() + 1).padStart(2, '0');
		var dd = String(hoje.getDate()).padStart(2, '0');
		Swal.fire({
			title: 'Dar baixa',
			width: 480,
			html:
				'<p class="small text-start mb-2">' +
				esc(desc) +
				'</p>' +
				'<div class="text-start">' +
				'<label class="form-label small mb-0">Valor pago (R$)</label>' +
				'<input id="bx-valor" type="number" step="0.01" min="0.01" class="form-control form-control-sm mb-2" value="' +
				(Number(valor) || 0).toFixed(2) +
				'">' +
				'<label class="form-label small mb-0">Forma de pagamento</label>' +
				'<select id="bx-tipo" class="form-select form-select-sm mb-2">' +
				'<option value="">Selecione</option>' +
				'<option value="Dinheiro">Dinheiro</option>' +
				'<option value="Pix">Pix</option>' +
				'<option value="Cartão">Cartão</option>' +
				'<option value="Transferência">Transferência</option>' +
				'<option value="Boleto">Boleto</option>' +
				'</select>' +
				'<label class="form-label small mb-0">Data do pagamento</label>' +
				'<input id="bx-data" type="date" class="form-control form-control-sm" value="' +
				yyyy +
				'-' +
				mm +
				'-' +
				dd +
				'">' +
				'</div>',
			showCancelButton: true,
			confirmButtonText: 'Confirmar baixa',
			preConfirm: function () {
				var tipo = ($('#bx-tipo').val() || '').trim();
				var v = parseFloat($('#bx-valor').val());
				var data = $('#bx-data').val();
				if (!tipo) {
					Swal.showValidationMessage('Selecione a forma de pagamento.');
					return false;
				}
				if (!(v > 0)) {
					Swal.showValidationMessage('Informe o valor pago.');
					return false;
				}
				if (!data) {
					Swal.showValidationMessage('Informe a data.');
					return false;
				}
				return { valor_pago: v, tipo_pagamento: tipo, data_pagamento: data };
			},
		}).then(function (r) {
			if (!r.isConfirmed) return;
			post({
				acao: 'dar_baixa',
				id_titulo: idTitulo,
				valor_pago: r.value.valor_pago,
				tipo_pagamento: r.value.tipo_pagamento,
				data_pagamento: r.value.data_pagamento,
			}).done(function (res) {
				if (!res || !res.success) {
					toastErr((res && res.message) || 'Falha ao dar baixa.');
					return;
				}
				carregar();
			});
		});
	}

	function carregar() {
		$('#alert-extrato').addClass('d-none');
		post({ acao: 'extrato' })
			.done(function (res) {
				if (!res || !res.success) {
					$('#alert-extrato').removeClass('d-none').text((res && res.message) || 'Falha ao carregar.');
					$('#box-extrato').html('');
					return;
				}
				render(res);
			})
			.fail(function () {
				$('#alert-extrato').removeClass('d-none').text('Erro de rede.');
			});
	}

	function idsSelecionados(selector) {
		var ids = [];
		$(selector || '.chk-titulo:checked').each(function () {
			ids.push(parseInt($(this).val(), 10));
		});
		return ids;
	}

	function somaSelecionados(selector) {
		var ids = idsSelecionados(selector);
		var sum = 0;
		(estado.titulos || []).forEach(function (t) {
			if (ids.indexOf(t.id) >= 0) sum += Number(t.valor) || 0;
		});
		return sum;
	}

	function abrirRenegociar() {
		var ids = idsSelecionados();
		if (!ids.length) {
			toastInfo('Selecione os títulos em aberto/vencidos que entrarão no acordo.');
			return;
		}
		var sugerido = somaSelecionados();
		var hoje = new Date();
		var yyyy = hoje.getFullYear();
		var mm = String(hoje.getMonth() + 1).padStart(2, '0');
		var dd = String(hoje.getDate()).padStart(2, '0');
		Swal.fire({
			title: 'Renegociar débitos',
			width: 560,
			html:
				'<p class="small text-start mb-2">' +
				ids.length +
				' título(s) · saldo selecionado <strong>' +
				moeda(sugerido) +
				'</strong></p>' +
				'<div class="text-start">' +
				'<label class="form-label small mb-0">Valor total do acordo (R$)</label>' +
				'<input id="rng-valor" type="number" step="0.01" min="0.01" class="form-control form-control-sm mb-2" value="' +
				sugerido.toFixed(2) +
				'">' +
				'<label class="form-label small mb-0">Quantidade de parcelas</label>' +
				'<input id="rng-qtd" type="number" min="1" max="120" class="form-control form-control-sm mb-2" value="3">' +
				'<label class="form-label small mb-0">Primeiro vencimento</label>' +
				'<input id="rng-venc" type="date" class="form-control form-control-sm mb-2" value="' +
				yyyy +
				'-' +
				mm +
				'-' +
				dd +
				'">' +
				'<label class="form-label small mb-0">Observação (opcional)</label>' +
				'<input id="rng-obs" class="form-control form-control-sm" maxlength="500">' +
				'<p class="small text-muted mt-2 mb-0">Os títulos antigos ficam como “Renegociação” no histórico. O novo acordo gera parcelas sem liberar curso EAD.</p>' +
				'</div>',
			showCancelButton: true,
			confirmButtonText: 'Criar acordo',
			preConfirm: function () {
				var valor = parseFloat($('#rng-valor').val());
				var qtd = parseInt($('#rng-qtd').val(), 10);
				var venc = $('#rng-venc').val();
				if (!(valor > 0)) {
					Swal.showValidationMessage('Informe o valor total.');
					return false;
				}
				if (!(qtd >= 1)) {
					Swal.showValidationMessage('Informe a quantidade de parcelas.');
					return false;
				}
				if (!venc) {
					Swal.showValidationMessage('Informe o primeiro vencimento.');
					return false;
				}
				return {
					valor_total: valor,
					qtd_parcelas: qtd,
					primeiro_vencimento: venc,
					observacao: ($('#rng-obs').val() || '').trim(),
				};
			},
		}).then(function (r) {
			if (!r.isConfirmed) return;
			post({
				acao: 'renegociar',
				ids_titulos: JSON.stringify(ids),
				valor_total: r.value.valor_total,
				qtd_parcelas: r.value.qtd_parcelas,
				primeiro_vencimento: r.value.primeiro_vencimento,
				observacao: r.value.observacao,
			}).done(function (res) {
				if (!res || !res.success) {
					toastErr((res && res.message) || 'Falha ao renegociar.');
					return;
				}
				toastOk(res.message || 'Acordo criado.');
				carregar();
			});
		});
	}

	function baixarPdf() {
		var el = document.getElementById('card-titulos-pdf');
		if (!el || typeof html2pdf === 'undefined') {
			toastErr('Não foi possível gerar o PDF.');
			return;
		}
		var nome = (estado.aluno && estado.aluno.nome) || 'aluno';
		var opt = {
			margin: 10,
			filename: 'extrato-' + nome.replace(/\s+/g, '-').toLowerCase() + '.pdf',
			image: { type: 'jpeg', quality: 0.95 },
			html2canvas: { scale: 2 },
			jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
		};
		html2pdf().set(opt).from(el).save();
	}

	function abrirBaixaLote(selectorChk) {
		var sel = selectorChk || '.chk-geral:checked, .chk-titulo.chk-geral:checked';
		// botão global: preferir checkboxes da tabela geral; se vazio, qualquer chk marcado
		if (!selectorChk) {
			sel = '.chk-geral:checked';
			if (!$(sel).length) sel = '.chk-titulo:checked';
		}
		var ids = idsSelecionados(sel);
		if (!ids.length) {
			toastInfo('Marque as parcelas em aberto/vencidas que deseja baixar.');
			return;
		}
		var total = somaSelecionados(sel);
		var hoje = new Date();
		var yyyy = hoje.getFullYear();
		var mm = String(hoje.getMonth() + 1).padStart(2, '0');
		var dd = String(hoje.getDate()).padStart(2, '0');
		Swal.fire({
			title: 'Dar baixa em ' + ids.length + ' parcela(s)',
			width: 480,
			html:
				'<p class="small text-start mb-2">Total: <strong>' +
				moeda(total) +
				'</strong> (cada parcela pelo valor integral)</p>' +
				'<div class="text-start">' +
				'<label class="form-label small mb-0">Forma de pagamento</label>' +
				'<select id="bx-lote-tipo" class="form-select form-select-sm mb-2">' +
				'<option value="">Selecione</option>' +
				'<option value="Dinheiro">Dinheiro</option>' +
				'<option value="Pix">Pix</option>' +
				'<option value="Cartão">Cartão</option>' +
				'<option value="Transferência">Transferência</option>' +
				'<option value="Boleto">Boleto</option>' +
				'</select>' +
				'<label class="form-label small mb-0">Data do pagamento</label>' +
				'<input id="bx-lote-data" type="date" class="form-control form-control-sm" value="' +
				yyyy +
				'-' +
				mm +
				'-' +
				dd +
				'">' +
				'</div>',
			showCancelButton: true,
			confirmButtonText: 'Confirmar baixas',
			preConfirm: function () {
				var tipo = ($('#bx-lote-tipo').val() || '').trim();
				var data = $('#bx-lote-data').val();
				if (!tipo) {
					Swal.showValidationMessage('Selecione a forma de pagamento.');
					return false;
				}
				if (!data) {
					Swal.showValidationMessage('Informe a data.');
					return false;
				}
				return { tipo_pagamento: tipo, data_pagamento: data };
			},
		}).then(function (r) {
			if (!r.isConfirmed) return;
			post({
				acao: 'dar_baixa_lote',
				ids_titulos: JSON.stringify(ids),
				tipo_pagamento: r.value.tipo_pagamento,
				data_pagamento: r.value.data_pagamento,
			}).done(function (res) {
				if (!res || !res.success) {
					toastErr((res && res.message) || 'Falha ao dar baixa.');
					return;
				}
				toastOk(res.message || 'Baixas registradas.');
				carregar();
			});
		});
	}

	$('#btn-renegociar').on('click', abrirRenegociar);
	$('#btn-pdf').on('click', baixarPdf);
	$('#btn-baixa-lote').on('click', abrirBaixaLote);
	carregar();
})();

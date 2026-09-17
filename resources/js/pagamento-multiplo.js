/**
 * Pagamento combinado / parcial — blocos .pagamento-multiplo
 */
(function () {
	function parseMoney(str) {
		if (str == null || str === '') return 0;
		var s = String(str).replace(/[^\d.,-]/g, '').replace(',', '.');
		var n = parseFloat(s);
		return isNaN(n) ? 0 : n;
	}

	function formatBr(n) {
		return (Number(n) || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
	}

	function readData($wrap, camelKey, dashedKey) {
		var v = $wrap.data(camelKey);
		if (v !== undefined && v !== null && v !== '') {
			return v;
		}
		if (dashedKey) {
			v = $wrap.attr('data-' + dashedKey);
			if (v !== undefined && v !== null && v !== '') {
				return v;
			}
		}
		return undefined;
	}

	function getTotalDevido($wrap) {
		var id = readData($wrap, 'totalInput', 'total-input');
		var $inp = id ? $('#' + id) : $();
		if (!$inp.length) {
			$inp = $wrap.closest('form').find('[name="valor_pagar"], [name="valor_pagar_total"]').first();
		}
		if (!$inp.length) return 0;
		return parseMoney($inp.val());
	}

	function getJaPago($wrap) {
		var raw = readData($wrap, 'jaPago', 'ja-pago');
		var v = parseFloat(raw);
		return isNaN(v) ? 0 : v;
	}

	function coletarLinhas($wrap) {
		var linhas = [];
		$wrap.find('.pm-linhas .pm-row').each(function () {
			var tipo = ($(this).find('.pm-tipo').val() || '').trim();
			var valor = parseMoney($(this).find('.pm-valor').val());
			if (tipo && valor > 0) {
				linhas.push({ tipo_pagamento: tipo, valor: Math.round(valor * 100) / 100 });
			}
		});
		return linhas;
	}

	function atualizar($wrap) {
		var linhas = coletarLinhas($wrap);
		var soma = linhas.reduce(function (a, l) {
			return a + l.valor;
		}, 0);
		var devido = getTotalDevido($wrap);
		var ja = getJaPago($wrap);
		var saldoAntes = Math.max(0, devido - ja);
		var saldoDepois = Math.max(0, saldoAntes - soma);
		$wrap.find('.pm-soma').text('R$ ' + formatBr(soma));
		$wrap.find('.pm-devido').text('R$ ' + formatBr(saldoAntes));
		$wrap.find('.pm-saldo').text('R$ ' + formatBr(saldoDepois));
		$wrap.find('.pm-json').val(JSON.stringify(linhas));
	}

	function addRow($wrap) {
		var tpl = $wrap.find('.pm-row-template').html();
		if (!tpl) return;
		$wrap.find('.pm-linhas').append(tpl);
		atualizar($wrap);
	}

	window.refreshPagamentoMultiplo = function (root) {
		var $root = root ? $(root) : $(document);
		$root.find('.pagamento-multiplo').each(function () {
			atualizar($(this));
		});
	};

	window.validatePagamentoMultiplo = function (root) {
		var $wrap =
			root && root.jquery
				? root.hasClass('pagamento-multiplo')
					? root
					: root.find('.pagamento-multiplo').first()
				: $(root).closest('.pagamento-multiplo');
		if (!$wrap.length) {
			return { ok: false, message: 'Formas de pagamento não carregadas.' };
		}
		atualizar($wrap);
		var linhas = coletarLinhas($wrap);
		if (!linhas.length) {
			return { ok: false, message: 'Informe ao menos uma forma de pagamento com valor.' };
		}
		var devido = getTotalDevido($wrap);
		var ja = getJaPago($wrap);
		var saldoAntes = Math.max(0, devido - ja);
		var soma = linhas.reduce(function (a, l) {
			return a + l.valor;
		}, 0);
		soma = Math.round(soma * 100) / 100;
		var tol = 0.05;
		if (soma > saldoAntes + tol) {
			return {
				ok: false,
				message: 'Valor informado (R$ ' + formatBr(soma) + ') é maior que o saldo (R$ ' + formatBr(saldoAntes) + ').',
			};
		}
		var permitirParcial = String(readData($wrap, 'permitirParcial', 'permitir-parcial')) === '1';
		if (!permitirParcial && Math.abs(soma - saldoAntes) > tol) {
			return { ok: false, message: 'A soma das formas deve fechar o total a pagar (R$ ' + formatBr(saldoAntes) + ').' };
		}
		if (soma <= tol) {
			return { ok: false, message: 'Informe um valor maior que zero.' };
		}
		return { ok: true, linhas: linhas, pagamentos_json: JSON.stringify(linhas), soma: soma };
	};

	window.initPagamentoMultiplo = function (root) {
		var $root = root ? $(root) : $(document);
		$root.find('.pagamento-multiplo').each(function () {
			var $wrap = $(this);
			if ($wrap.data('pm-init')) return;
			$wrap.data('pm-init', 1);
			if ($wrap.find('.pm-linhas .pm-row').length === 0) {
				addRow($wrap);
				addRow($wrap);
			}
			atualizar($wrap);
		});
	};

	$(document).on('click', '.pagamento-multiplo .btn-pm-add', function () {
		addRow($(this).closest('.pagamento-multiplo'));
	});

	$(document).on('click', '.pagamento-multiplo .btn-pm-rm', function () {
		var $wrap = $(this).closest('.pagamento-multiplo');
		var $rows = $wrap.find('.pm-linhas .pm-row');
		if ($rows.length <= 1) {
			$(this).closest('.pm-row').find('input, select').val('');
		} else {
			$(this).closest('.pm-row').remove();
		}
		atualizar($wrap);
	});

	$(document).on('input change', '.pagamento-multiplo .pm-valor, .pagamento-multiplo .pm-tipo', function () {
		atualizar($(this).closest('.pagamento-multiplo'));
	});

	$(document).on('submit', 'form', function () {
		var $form = $(this);
		$form.find('.pagamento-multiplo').each(function () {
			atualizar($(this));
		});
	});

	$(function () {
		initPagamentoMultiplo(document);
	});
})();

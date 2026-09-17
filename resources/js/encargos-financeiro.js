function moedaEncargos(n) {
	var v = parseFloat(n);
	if (isNaN(v)) return '0,00';
	return v.toFixed(2).replace('.', ',');
}

function jaPagoTituloModal() {
	var ja = parseFloat($('#ja_pago_titulo').val());
	if (!isNaN(ja) && ja > 0) {
		return ja;
	}
	return 0;
}

function atualizarTotalEncargosPagamento() {
	var $cb = $('#cobrar_encargos');
	var face = parseFloat($('#enc_valor_face').val()) || 0;
	var multa = parseFloat($('#enc_valor_multa').val()) || 0;
	var juros = parseFloat($('#enc_valor_juros').val()) || 0;
	var comEnc = parseFloat($('#enc_total_com_encargos').val()) || face + multa + juros;
	var devido = face;
	if ($cb.length) {
		devido = $cb.is(':checked') ? comEnc : face;
	} else if ($('#valor_devido_titulo').length) {
		devido = parseFloat($('#valor_devido_titulo').val()) || parseFloat($('#valor_pagar').val()) || face || comEnc;
	} else if ($('#valor_pagar').length) {
		devido = parseFloat($('#valor_pagar').val()) || face || comEnc;
	}

	var ja = jaPagoTituloModal();
	var saldo = Math.max(0, devido - ja);

	if ($('#valor_devido_titulo').length) {
		$('#valor_devido_titulo').val(devido.toFixed(2));
	}
	if ($('#valor_pagar').length) {
		$('#valor_pagar').val(devido.toFixed(2));
	}
	if ($('#valor_saldo_receber').length) {
		$('#valor_saldo_receber').val(saldo.toFixed(2));
	}
	if ($('#bx-valor').length) {
		$('#bx-valor').val(saldo.toFixed(2));
	}
	if ($('#enc_total_label').length) {
		$('#enc_total_label').text('R$ ' + moedaEncargos(saldo));
	}
	if (typeof calcularTroco === 'function') {
		calcularTroco();
	}
	if (typeof refreshPagamentoMultiplo === 'function') {
		refreshPagamentoMultiplo(
			'#body_dar_baixa .pagamento-multiplo, #modalDarBaixa .pagamento-multiplo, #formModal .pagamento-multiplo'
		);
	}
}

$(document).on('change', '#cobrar_encargos', atualizarTotalEncargosPagamento);

$(document).on('shown.bs.modal', '#formModal, #modalDarBaixa', function () {
	atualizarTotalEncargosPagamento();
	if (typeof initPagamentoMultiplo === 'function') {
		initPagamentoMultiplo(this);
	}
});

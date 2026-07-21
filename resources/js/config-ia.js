function postIa(data) {
	return $.ajax({
		url: url_base + 'painel/config/ia',
		method: 'POST',
		dataType: 'json',
		data: data
	});
}

function carregarIa() {
	postIa({ acao: 'carregar' }).done(function (res) {
		if (!res || !res.success) {
			Swal.fire('Erro', (res && res.message) || 'Falha', 'error');
			return;
		}
		if (!res.coluna_ok) {
			$('#alert-sql-ia').removeClass('d-none');
			$('#btn-salvar-ia').prop('disabled', true);
		} else {
			$('#alert-sql-ia').addClass('d-none');
			$('#btn-salvar-ia').prop('disabled', false);
		}
		$('#ai_ativo').prop('checked', !!res.ai_ativo);
		$('#ai_provider').val(res.ai_provider || '');
		$('#ai_model').val(res.ai_model || '');
		if (res.key_salva) {
			$('#ai_key_hint').text('Chave salva: ' + (res.key_mask || '********') + '. Cole uma nova só para trocar.');
		}
		var $b = $('#badge-ia-status');
		if (res.ai_pronto) {
			$b.removeClass('bg-secondary bg-warning').addClass('bg-success').text('Pronta');
		} else if (res.ai_ativo) {
			$b.removeClass('bg-secondary bg-success').addClass('bg-warning text-dark').text('Ativa sem chave');
		} else {
			$b.removeClass('bg-success bg-warning').addClass('bg-secondary').text('Desativada');
		}
	});
}

$(function () {
	carregarIa();
	$('#btn-salvar-ia').on('click', function () {
		postIa({
			acao: 'salvar',
			ai_ativo: $('#ai_ativo').is(':checked') ? '1' : '0',
			ai_provider: $('#ai_provider').val(),
			ai_model: $('#ai_model').val(),
			ai_api_key: $('#ai_api_key').val() || ''
		}).done(function (res) {
			if (!res || !res.success) {
				Swal.fire('Erro', (res && res.message) || 'Falha', 'error');
				return;
			}
			$('#ai_api_key').val('');
			Swal.fire('OK', res.message, 'success');
			carregarIa();
		});
	});
});

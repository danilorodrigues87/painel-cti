function postAssistente(data) {
	return $.ajax({
		url: url_base + 'painel/config/assistente',
		method: 'POST',
		dataType: 'json',
		data: data
	});
}

function okBadge(ok, labelOk, labelNo) {
	return ok
		? '<span class="text-success">' + labelOk + '</span>'
		: '<span class="text-muted">' + labelNo + '</span>';
}

function carregar() {
	postAssistente({ acao: 'carregar' }).done(function (res) {
		if (!res || !res.success) {
			if (res && res.coluna_ok === false) {
				$('#btn-salvar-assistente').prop('disabled', true);
			}
			Swal.fire('Atenção', (res && res.message) || 'Falha ao carregar', 'warning');
			return;
		}

		var c = res.config || {};
		$('#llm_ativo').prop('checked', !!c.llm_ativo);
		$('#llm_provider').val(c.llm_provider || '');
		$('#llm_model').val(c.llm_model || '');
		$('#telegram_bot_username').val(c.telegram_bot_username || '');
		$('#telegram_chat_id').val(c.telegram_chat_id || '');
		$('#telegram_notas').val(c.telegram_notas || '');

		if (c.llm_key_salva) {
			$('#llm_key_hint').text('Chave salva: ' + (c.llm_key_mask || '********') + '. Cole uma nova só para trocar.');
		}
		if (c.telegram_token_salvo) {
			$('#tg_token_hint').text('Token salvo: ' + (c.telegram_token_mask || '********') + '. Cole um novo só para trocar.');
		}

		$('#st-agent').html(okBadge(res.agent_api_pronta, 'ativa (Master)', 'aguardando Master'));
		$('#st-llm').html(okBadge(c.llm_pronto, 'pronta', 'pendente'));
		$('#st-tg').html(okBadge(c.telegram_pronto, 'token salvo', 'pendente'));

		var $b = $('#badge-status');
		if (res.agent_api_pronta && c.llm_pronto && c.telegram_pronto) {
			$b.removeClass('bg-secondary bg-warning').addClass('bg-success').text('Pronta p/ OpenClaw');
		} else if (c.llm_pronto || c.telegram_pronto) {
			$b.removeClass('bg-secondary bg-success').addClass('bg-warning text-dark').text('Parcial');
		} else {
			$b.removeClass('bg-success bg-warning').addClass('bg-secondary').text('Aguardando dados');
		}

		if (res.tem_ia_pedagogica) {
			$('#btn-copiar-pedagogica').removeClass('d-none');
		} else {
			$('#btn-copiar-pedagogica').addClass('d-none');
		}
	});
}

$(function () {
	carregar();

	$('#btn-copiar-pedagogica').on('click', function () {
		postAssistente({ acao: 'pedagogica_ref' }).done(function (res) {
			if (!res || !res.success) {
				Swal.fire('Erro', (res && res.message) || 'Falha', 'error');
				return;
			}
			$('#llm_ativo').prop('checked', true);
			$('#llm_provider').val(res.provider || '');
			$('#llm_model').val(res.model || '');
			$('#llm_api_key').val(res.api_key || '');
			Swal.fire('OK', res.message || 'Preenchido. Clique em Salvar para gravar no OpenClaw.', 'info');
		});
	});

	$('#btn-salvar-assistente').on('click', function () {
		postAssistente({
			acao: 'salvar',
			llm_ativo: $('#llm_ativo').is(':checked') ? '1' : '0',
			llm_provider: $('#llm_provider').val(),
			llm_model: $('#llm_model').val(),
			llm_api_key: $('#llm_api_key').val() || '',
			telegram_bot_token: $('#telegram_bot_token').val() || '',
			telegram_bot_username: $('#telegram_bot_username').val(),
			telegram_chat_id: $('#telegram_chat_id').val(),
			telegram_notas: $('#telegram_notas').val()
		}).done(function (res) {
			if (!res || !res.success) {
				Swal.fire('Erro', (res && res.message) || 'Falha', 'error');
				return;
			}
			$('#llm_api_key').val('');
			$('#telegram_bot_token').val('');
			Swal.fire('OK', res.message, 'success');
			carregar();
		});
	});
});

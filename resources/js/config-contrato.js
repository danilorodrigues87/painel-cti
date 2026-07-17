const CONFIG_CONTRATO_URL = 'painel/config/contrato';

function carregarModeloContrato(){
	$.post(url_base + CONFIG_CONTRATO_URL, { acao: 'carregar' }, function(res){
		if(!res || !res.success){
			Swal.fire('Erro', (res && res.message) ? res.message : 'Falha ao carregar.', 'error');
			return;
		}

		if(!res.coluna_ok){
			$('#alert-sql-contrato').removeClass('d-none').html(
				'Execute o SQL <code>database/escolas_modelo_contrato.sql</code> no phpMyAdmin para salvar modelos por escola. Enquanto isso, o contrato impresso continua no padrão CTI.'
			);
			$('#btn-salvar-contrato, #btn-restaurar-contrato').prop('disabled', true);
		} else {
			$('#alert-sql-contrato').addClass('d-none');
			$('#btn-salvar-contrato, #btn-restaurar-contrato').prop('disabled', false);
		}

		$('#modelo_contrato_html').val(res.html || '');
		$('#badge-padrao-contrato')
			.removeClass('bg-secondary bg-success bg-warning')
			.addClass(res.usando_padrao ? 'bg-secondary' : 'bg-success')
			.text(res.usando_padrao ? 'Usando padrão CTI' : 'Modelo customizado');

		const $ul = $('#lista-vars-contrato').empty();
		(res.variaveis || []).forEach(function(v){
			$ul.append('<li><code>{{'+escHtml(v.chave)+'}}</code> — '+escHtml(v.descricao)+'</li>');
		});

		if(res.frase_coluna_ok === false){
			$('#certificado_frase_conclusao, #btn-salvar-frase-cert').prop('disabled', true);
		} else {
			$('#certificado_frase_conclusao, #btn-salvar-frase-cert').prop('disabled', false);
			$('#certificado_frase_conclusao').val((res.certificado && res.certificado.frase_conclusao) || '');
		}
	}, 'json').fail(function(){
		Swal.fire('Erro', 'Falha ao carregar modelo de contrato.', 'error');
	});
}

function escHtml(s){
	return $('<div>').text(s == null ? '' : String(s)).html();
}

function salvarModeloContrato(){
	$('#btn-salvar-contrato').prop('disabled', true);
	$.post(url_base + CONFIG_CONTRATO_URL, {
		acao: 'salvar',
		html: $('#modelo_contrato_html').val()
	}, function(res){
		$('#btn-salvar-contrato').prop('disabled', false);
		if(!res || !res.success){
			Swal.fire('Erro', (res && res.message) ? res.message : 'Falha ao salvar.', 'error');
			return;
		}
		Swal.fire('Salvo', res.message, 'success');
		carregarModeloContrato();
	}, 'json').fail(function(){
		$('#btn-salvar-contrato').prop('disabled', false);
		Swal.fire('Erro', 'Falha ao salvar.', 'error');
	});
}

function restaurarModeloContrato(){
	Swal.fire({
		title: 'Restaurar padrão CTI?',
		text: 'Volta ao texto atual da escola 1 (Capão Bonito). Seu HTML customizado será descartado.',
		icon: 'question',
		showCancelButton: true,
		confirmButtonText: 'Restaurar'
	}).then(function(r){
		if(!r.isConfirmed) return;
		$.post(url_base + CONFIG_CONTRATO_URL, { acao: 'restaurar' }, function(res){
			if(!res || !res.success){
				Swal.fire('Erro', (res && res.message) ? res.message : 'Falha.', 'error');
				return;
			}
			Swal.fire('OK', res.message, 'success');
			carregarModeloContrato();
		}, 'json');
	});
}

function salvarFraseCertificado(){
	$('#btn-salvar-frase-cert').prop('disabled', true);
	$.post(url_base + CONFIG_CONTRATO_URL, {
		acao: 'salvar_certificado',
		frase_conclusao: $('#certificado_frase_conclusao').val()
	}, function(res){
		$('#btn-salvar-frase-cert').prop('disabled', false);
		if(!res || !res.success){
			Swal.fire('Erro', (res && res.message) ? res.message : 'Falha ao salvar.', 'error');
			return;
		}
		Swal.fire('Salvo', res.message, 'success');
		carregarModeloContrato();
	}, 'json').fail(function(){
		$('#btn-salvar-frase-cert').prop('disabled', false);
		Swal.fire('Erro', 'Falha ao salvar.', 'error');
	});
}

$(function(){
	carregarModeloContrato();
	$('#btn-salvar-contrato').on('click', salvarModeloContrato);
	$('#btn-restaurar-contrato').on('click', restaurarModeloContrato);
	$('#btn-salvar-frase-cert').on('click', salvarFraseCertificado);
});

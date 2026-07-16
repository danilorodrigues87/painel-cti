const CONFIG_EMAIL_URL = 'painel/config/comunicacao';

function aplicarPreset(preset){
	if(preset === 'gmail'){
		$('#smtp_host').val('smtp.gmail.com');
		$('#smtp_port').val(587);
		$('#smtp_encryption').val('tls');
	}
	if(preset === 'outlook'){
		$('#smtp_host').val('smtp.office365.com');
		$('#smtp_port').val(587);
		$('#smtp_encryption').val('tls');
	}
	if(preset === 'corp'){
		$('#smtp_host').val('');
		$('#smtp_port').val(587);
		$('#smtp_encryption').val('tls');
	}
}

function atualizarAlertaModo(data){
	const sistema = data.sistema || {};
	const modo = data.modo_envio || 'sistema';

	if(modo === 'escola'){
		$('#alert-modo-envio')
			.removeClass('alert-info alert-warning')
			.addClass('alert-success')
			.html('<i class="fas fa-check-circle"></i> Envios da escola usarão o <strong>SMTP configurado</strong> abaixo.');
		return;
	}

	if(sistema.configurado){
		$('#alert-modo-envio')
			.removeClass('alert-success alert-warning')
			.addClass('alert-info')
			.html('<i class="fas fa-info-circle"></i> Envios da escola usarão o e-mail padrão do sistema: <strong>'+sistema.from_email+'</strong>.');
		return;
	}

	$('#alert-modo-envio')
		.removeClass('alert-info alert-success')
		.addClass('alert-warning')
		.html('<i class="fas fa-exclamation-triangle"></i> Nenhum SMTP da escola ativo e o e-mail do sistema não está configurado no servidor (.env).');
}

function preencherFormulario(data){
	const cfg = data.config || {};

	$('#smtp_ativo').prop('checked', parseInt(cfg.smtp_ativo, 10) === 1);
	$('#smtp_host').val(cfg.smtp_host || '');
	$('#smtp_port').val(cfg.smtp_port || 587);
	$('#smtp_user').val(cfg.smtp_user || '');
	$('#smtp_from_email').val(cfg.smtp_from_email || '');
	$('#smtp_from_name').val(cfg.smtp_from_name || '');
	$('#smtp_encryption').val(cfg.smtp_encryption || 'tls');
	$('#email_delay_segundos').val(cfg.email_delay_segundos || 3);
	$('#email_max_hora').val(cfg.email_max_hora || 80);
	$('#smtp_pass').val('');

	if(cfg.tem_senha){
		$('#hint-senha').text('Senha já cadastrada. Deixe em branco para manter.');
	} else {
		$('#hint-senha').text('');
	}

	const sistema = data.sistema || {};
	if(sistema.configurado){
		$('#info-sistema').html(
			'<div><strong>Remetente:</strong> '+sistema.from_email+'</div>'
			+'<div><strong>Nome:</strong> '+(sistema.from_name || 'CTI Educacional')+'</div>'
		);
	} else {
		$('#info-sistema').html('<span class="text-warning">Não configurado no .env do servidor.</span>');
	}

	atualizarAlertaModo(data);
	preencherCobranca(data);
	preencherAniversario(data);
	preencherWhatsapp(data.whatsapp || {});

	if(data.aviso_smtp){
		Swal.fire('SMTP da escola', data.aviso_smtp, 'warning');
	}
}

function badgeStatusWa(status, conectado){
	if(conectado) return '<span class="badge bg-success">conectado</span>';
	const s = (status || 'unknown').toLowerCase();
	if(s === 'connecting' || s === 'qr') return '<span class="badge bg-warning text-dark">'+s+'</span>';
	if(s === 'not_created') return '<span class="badge bg-secondary">não criada</span>';
	return '<span class="badge bg-secondary">'+s+'</span>';
}

function normalizarSrcQr(qr){
	if(!qr) return null;
	qr = String(qr).trim();
	if(!qr) return null;
	if(qr.indexOf('data:image') === 0 || qr.indexOf('http://') === 0 || qr.indexOf('https://') === 0){
		return qr;
	}
	// Texto bruto do WhatsApp (ex.: 2@...) → gera imagem do QR
	return 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&ecc=M&margin=10&data='
		+ encodeURIComponent(qr);
}

let waPairingTimer = null;
let waLastQrAt = 0;
let waPareando = false;

function pararPareamentoWa(){
	waPareando = false;
	if(waPairingTimer){
		clearInterval(waPairingTimer);
		waPairingTimer = null;
	}
}

function atualizarBadgeWa(w){
	if(!w) return;
	if(w.status !== undefined){
		$('#wa-status-label').replaceWith('<span id="wa-status-label">'+badgeStatusWa(w.status, w.conectado)+'</span>');
	}
	if(w.instance) $('#wa-instance').text(w.instance);
	if(w.numero !== undefined && w.numero !== null && w.numero !== ''){
		$('#wa-numero').text(w.numero);
	}
}

function iniciarPareamentoWa(){
	pararPareamentoWa();
	waPareando = true;
	waLastQrAt = Date.now();
	waPairingTimer = setInterval(function(){
		if(!waPareando) return;
		$.post(url_base + CONFIG_EMAIL_URL, { acao: 'whatsapp_status' }, function(res){
			if(!res || !res.success || !waPareando) return;
			const w = res.whatsapp || {};
			// Só badge/número — NUNCA limpa o QR nem acumula alerta (status não traz qrcode)
			atualizarBadgeWa(w);
			if(w.conectado){
				pararPareamentoWa();
				mostrarQr(null, false);
				Swal.fire('Conectado!', 'WhatsApp pareado com sucesso.', 'success');
				whatsappStatus();
				return;
			}
			const st = String(w.status || '').toLowerCase();
			// Renova QR só se ainda estiver pareando e o código já tiver ~35s
			if(st !== 'not_created' && (Date.now() - waLastQrAt > 35000)){
				waLastQrAt = Date.now();
				$.post(url_base + CONFIG_EMAIL_URL, { acao: 'whatsapp_qr' }, function(r2){
					if(!r2 || !r2.success || !waPareando) return;
					const w2 = r2.whatsapp || r2;
					atualizarBadgeWa(w2);
					if(w2.qrcode){
						mostrarQr(w2.qrcode, false);
					}
					if(w2.conectado){
						pararPareamentoWa();
						mostrarQr(null, false);
						Swal.fire('Conectado!', 'WhatsApp pareado com sucesso.', 'success');
					}
				}, 'json');
			}
		}, 'json');
	}, 5000);
}

function mostrarQr(qr, iniciarPoll){
	if(iniciarPoll === undefined) iniciarPoll = true;
	const src = normalizarSrcQr(qr);
	if(src){
		$('#wa-qrcode').attr('src', src).removeClass('d-none');
		$('#wa-qr-placeholder').addClass('d-none');
		if(iniciarPoll){
			iniciarPareamentoWa();
		} else {
			waPareando = true;
		}
	} else {
		$('#wa-qrcode').addClass('d-none').attr('src', '');
		$('#wa-qr-placeholder').removeClass('d-none').text('Nenhum QR carregado. Use “Trocar número” se estiver travado em Connecting.');
	}
}

function preencherWhatsapp(w, opts){
	opts = opts || {};
	const msgs = [];
	if(!w.colunas_ok || !w.tabelas_ok || !w.configurado_env){
		if(!w.configurado_env) msgs.push('Configure EVOLUTION_URL e EVOLUTION_API_KEY no .env.');
		if(!w.colunas_ok || !w.tabelas_ok) msgs.push('Execute o SQL de WhatsApp/Evolution no phpMyAdmin.');
	}
	// Durante o pareamento, ignora "instância não existe" (falso positivo / corrida com o poll)
	if(w.erro && !(waPareando && String(w.status || '') === 'not_created')){
		msgs.push(w.erro);
	}
	if(msgs.length){
		$('#alert-whatsapp-sql').removeClass('d-none').html(msgs.join('<br>'));
	} else {
		$('#alert-whatsapp-sql').addClass('d-none').empty();
	}

	atualizarBadgeWa(w);
	$('#wa-webhook').text(w.webhook_url || '—');
	$('#evolution_ativo').prop('checked', parseInt(w.ativo, 10) === 1);
	$('#whatsapp_delay_segundos').val(w.delay || 5);
	$('#whatsapp_max_hora').val(w.max_hora || 40);

	// Só altera o QR se a resposta trouxe um código novo (status sempre manda null)
	if(w.qrcode){
		mostrarQr(w.qrcode, opts.iniciarPoll !== false);
	} else if(!waPareando && opts.limparQr){
		mostrarQr(null, false);
	}
}

function aplicarRespostaWhatsapp(res){
	const w = res.whatsapp || res;
	preencherWhatsapp(Object.assign({}, {
		colunas_ok: true,
		tabelas_ok: true,
		configurado_env: true
	}, w), { iniciarPoll: true });
	if(w.qrcode){
		mostrarQr(w.qrcode, true);
	}
}

function whatsappStatus(){
	$.post(url_base + CONFIG_EMAIL_URL, { acao: 'whatsapp_status' }, function(res){
		if(!res || !res.success){
			Swal.fire('Erro', (res && res.message) ? res.message : 'Falha ao consultar status.', 'error');
			return;
		}
		preencherWhatsapp(res.whatsapp || {}, { limparQr: !waPareando, iniciarPoll: false });
	}, 'json');
}

function whatsappConectar(){
	$('#btn-wa-conectar').prop('disabled', true);
	Swal.fire({ title: 'Preparando QR...', allowOutsideClick: false, didOpen: function(){ Swal.showLoading(); } });
	$.post(url_base + CONFIG_EMAIL_URL, { acao: 'whatsapp_conectar' }, function(res){
		$('#btn-wa-conectar').prop('disabled', false);
		Swal.close();
		if(!res || !res.success){
			pararPareamentoWa();
			Swal.fire('Erro', (res && res.message) ? res.message : 'Falha ao conectar.', 'error');
			return;
		}
		aplicarRespostaWhatsapp(res);
		if(res.whatsapp && res.whatsapp.conectado){
			pararPareamentoWa();
			Swal.fire('WhatsApp', res.message || 'Já conectado.', 'success');
		} else {
			Swal.fire({
				title: 'Escaneie o QR',
				html: (res.message || 'Abra o WhatsApp → Aparelhos conectados → Conectar um aparelho.')
					+ '<br><small class="text-muted">Escaneie em até ~40 segundos. A tela acompanha sozinha.</small>',
				icon: 'info'
			});
		}
	}, 'json').fail(function(){
		$('#btn-wa-conectar').prop('disabled', false);
		Swal.close();
		Swal.fire('Erro', 'Falha na requisição.', 'error');
	});
}

function whatsappQr(){
	$.post(url_base + CONFIG_EMAIL_URL, { acao: 'whatsapp_qr' }, function(res){
		if(!res || !res.success){
			Swal.fire('Erro', (res && res.message) ? res.message : 'Falha ao obter QR.', 'error');
			return;
		}
		aplicarRespostaWhatsapp(res);
	}, 'json');
}

function whatsappSalvar(){
	$.post(url_base + CONFIG_EMAIL_URL, {
		acao: 'whatsapp_salvar',
		evolution_ativo: $('#evolution_ativo').is(':checked') ? 1 : 0,
		whatsapp_delay_segundos: $('#whatsapp_delay_segundos').val(),
		whatsapp_max_hora: $('#whatsapp_max_hora').val()
	}, function(res){
		if(!res || !res.success){
			Swal.fire('Erro', (res && res.message) ? res.message : 'Falha ao salvar.', 'error');
			return;
		}
		Swal.fire('Salvo', res.message, 'success');
	}, 'json');
}

function whatsappTestar(){
	$('#btn-wa-testar').prop('disabled', true);
	$.post(url_base + CONFIG_EMAIL_URL, {
		acao: 'whatsapp_testar',
		whatsapp_teste: $('#whatsapp_teste').val(),
		whatsapp_msg_teste: $('#whatsapp_msg_teste').val()
	}, function(res){
		$('#btn-wa-testar').prop('disabled', false);
		if(!res || !res.success){
			Swal.fire('Falha', (res && res.message) ? res.message : 'Não enviou.', 'error');
			return;
		}
		Swal.fire('Enviado', res.message, 'success');
	}, 'json').fail(function(){
		$('#btn-wa-testar').prop('disabled', false);
		Swal.fire('Erro', 'Falha ao testar.', 'error');
	});
}

function whatsappDesconectar(){
	Swal.fire({
		title: 'Desconectar WhatsApp?',
		html: 'Desconecta a sessão do aparelho.<br><small class="text-muted">Para trocar de número com certeza, use o botão <strong>Trocar número</strong>.</small>',
		icon: 'warning',
		showCancelButton: true,
		confirmButtonText: 'Só desconectar',
		cancelButtonText: 'Cancelar'
	}).then(function(r){
		if(!r.isConfirmed) return;
		$.post(url_base + CONFIG_EMAIL_URL, { acao: 'whatsapp_desconectar', apagar_instancia: 0 }, function(res){
			if(!res || !res.success){
				Swal.fire('Erro', (res && res.message) ? res.message : 'Falha.', 'error');
				return;
			}
			mostrarQr(null);
			whatsappStatus();
			Swal.fire('OK', res.message, 'success');
		}, 'json');
	});
}

function whatsappRecriar(){
	Swal.fire({
		title: 'Trocar número / recriar?',
		html: 'Isso <strong>apaga a instância</strong> na Evolution e gera um QR novo para parear outro WhatsApp.<br>Use também se você excluiu a instância pelo painel da Evolution.',
		icon: 'warning',
		showCancelButton: true,
		confirmButtonText: 'Recriar e gerar QR',
		cancelButtonText: 'Cancelar'
	}).then(function(r){
		if(!r.isConfirmed) return;
		$('#btn-wa-recriar, #btn-wa-conectar').prop('disabled', true);
		Swal.fire({ title: 'Recriando instância...', allowOutsideClick: false, didOpen: function(){ Swal.showLoading(); } });
		$.post(url_base + CONFIG_EMAIL_URL, { acao: 'whatsapp_recriar' }, function(res){
			$('#btn-wa-recriar, #btn-wa-conectar').prop('disabled', false);
			Swal.close();
			if(!res || !res.success){
				pararPareamentoWa();
				Swal.fire({
					title: 'Erro',
					html: '<div style="text-align:left;word-break:break-word;font-size:13px;">'
						+ $('<div>').text((res && res.message) ? res.message : 'Falha ao recriar.').html()
						+ '</div>',
					icon: 'error',
					width: 640
				});
				return;
			}
			aplicarRespostaWhatsapp(res);
			if(res.whatsapp && res.whatsapp.conectado){
				pararPareamentoWa();
				Swal.fire('WhatsApp', res.message || 'Já conectado.', 'success');
			} else {
				Swal.fire({
					title: 'Escaneie o QR agora',
					html: 'Instância recriada. No celular: <strong>WhatsApp → Aparelhos conectados → Conectar</strong>.<br>'
						+ '<small class="text-muted">Faça o scan em até ~40s. Não use o painel da Evolution ao mesmo tempo.</small>',
					icon: 'info'
				});
			}
		}, 'json').fail(function(){
			$('#btn-wa-recriar, #btn-wa-conectar').prop('disabled', false);
			Swal.close();
			Swal.fire('Erro', 'Falha na requisição.', 'error');
		});
	});
}

function preencherAniversario(data){
	const a = data.aniversario || {};
	const tpl = data.templates_aniversario || a.templates || {};

	if(!a.colunas_ok || !a.log_ok){
		$('#alert-aniversario-sql').removeClass('d-none').html(
			'Execute o SQL de aniversário no phpMyAdmin para habilitar esta função.'
		);
	} else {
		$('#alert-aniversario-sql').addClass('d-none');
	}

	$('#aniversario_ativo').prop('checked', parseInt(a.aniversario_ativo, 10) === 1);
	$('#aniversario_apenas_matriculados').prop('checked', parseInt(a.aniversario_apenas_matriculados, 10) === 1);
	$('#aniversario_assunto').val(a.aniversario_assunto || tpl.assunto || '');
	$('#aniversario_mensagem').val(a.aniversario_mensagem || tpl.mensagem || '');
}

function preencherCobranca(data){
	const c = data.cobranca || {};
	const tpl = data.templates_padrao || {};

	if(!c.colunas_ok || !c.log_ok){
		$('#alert-cobranca-sql').removeClass('d-none').html(
			'Execute o SQL de cobrança automática no phpMyAdmin para habilitar esta função.'
		);
	} else {
		$('#alert-cobranca-sql').addClass('d-none');
	}

	$('#cobranca_ativo').prop('checked', parseInt(c.cobranca_ativo, 10) === 1);
	$('#cobranca_dias_antes').val(c.cobranca_dias_antes || '3,5');
	$('#cobranca_aviso_vencimento').prop('checked', parseInt(c.cobranca_aviso_vencimento, 10) === 1);
	$('#cobranca_dias_depois').val(c.cobranca_dias_depois || '1,3,7');
	$('#cobranca_enviar_responsavel').prop('checked', parseInt(c.cobranca_enviar_responsavel, 10) === 1);

	$('#cobranca_assunto_antes').val(c.cobranca_assunto_antes || tpl.assunto_antes || '');
	$('#cobranca_assunto_vencimento').val(c.cobranca_assunto_vencimento || tpl.assunto_vencimento || '');
	$('#cobranca_assunto_atraso').val(c.cobranca_assunto_atraso || tpl.assunto_atraso || '');
	$('#cobranca_msg_antes').val(c.cobranca_msg_antes || tpl.msg_antes || '');
	$('#cobranca_msg_vencimento').val(c.cobranca_msg_vencimento || tpl.msg_vencimento || '');
	$('#cobranca_msg_atraso').val(c.cobranca_msg_atraso || tpl.msg_atraso || '');
}

function coletarDadosFormulario(){
	return {
		acao: 'salvar',
		smtp_ativo: $('#smtp_ativo').is(':checked') ? 1 : 0,
		smtp_host: $('#smtp_host').val(),
		smtp_port: $('#smtp_port').val(),
		smtp_user: $('#smtp_user').val(),
		smtp_pass: $('#smtp_pass').val(),
		smtp_from_email: $('#smtp_from_email').val(),
		smtp_from_name: $('#smtp_from_name').val(),
		smtp_encryption: $('#smtp_encryption').val(),
		email_delay_segundos: $('#email_delay_segundos').val(),
		email_max_hora: $('#email_max_hora').val(),
		cobranca_ativo: $('#cobranca_ativo').is(':checked') ? 1 : 0,
		cobranca_dias_antes: $('#cobranca_dias_antes').val(),
		cobranca_aviso_vencimento: $('#cobranca_aviso_vencimento').is(':checked') ? 1 : 0,
		cobranca_dias_depois: $('#cobranca_dias_depois').val(),
		cobranca_enviar_responsavel: $('#cobranca_enviar_responsavel').is(':checked') ? 1 : 0,
		cobranca_assunto_antes: $('#cobranca_assunto_antes').val(),
		cobranca_assunto_vencimento: $('#cobranca_assunto_vencimento').val(),
		cobranca_assunto_atraso: $('#cobranca_assunto_atraso').val(),
		cobranca_msg_antes: $('#cobranca_msg_antes').val(),
		cobranca_msg_vencimento: $('#cobranca_msg_vencimento').val(),
		cobranca_msg_atraso: $('#cobranca_msg_atraso').val(),
		aniversario_ativo: $('#aniversario_ativo').is(':checked') ? 1 : 0,
		aniversario_apenas_matriculados: $('#aniversario_apenas_matriculados').is(':checked') ? 1 : 0,
		aniversario_assunto: $('#aniversario_assunto').val(),
		aniversario_mensagem: $('#aniversario_mensagem').val()
	};
}

function carregarConfiguracao(){
	$.post(url_base + CONFIG_EMAIL_URL, { acao: 'carregar' }, function(res){
		if(!res || !res.success){
			Swal.fire('Erro', (res && res.message) ? res.message : 'Não foi possível carregar.', 'error');
			return;
		}
		preencherFormulario(res);
		if(res.aviso){
			Swal.fire('Atenção', res.aviso, 'warning');
		}
	}, 'json').fail(function(xhr){
		let msg = 'Falha ao carregar configurações.';
		if(xhr && xhr.responseText && xhr.responseText.indexOf('escola_integracoes') !== -1){
			msg = 'A tabela escola_integracoes não existe. Execute o SQL de criação no phpMyAdmin.';
		}
		Swal.fire('Erro', msg, 'error');
	});
}

function salvarConfiguracao(){
	const dados = coletarDadosFormulario();

	$.post(url_base + CONFIG_EMAIL_URL, dados, function(res){
		if(!res || !res.success){
			Swal.fire('Erro', (res && res.message) ? res.message : 'Não foi possível salvar.', 'error');
			return;
		}
		Swal.fire('Salvo', res.message, 'success');
		carregarConfiguracao();
	}, 'json').fail(function(){
		Swal.fire('Erro', 'Falha ao salvar configurações.', 'error');
	});
}

function testarEmail(){
	const dados = coletarDadosFormulario();
	dados.acao = 'testar';
	dados.email_teste = $('#email_teste').val();

	$('#btn-testar-email').prop('disabled', true);

	$.post(url_base + CONFIG_EMAIL_URL, dados, function(res){
		$('#btn-testar-email').prop('disabled', false);

		if(!res || !res.success){
			Swal.fire('Falha no teste', (res && res.message) ? res.message : 'Não foi possível enviar.', 'error');
			return;
		}

		Swal.fire('Enviado', res.message, 'success');
	}, 'json').fail(function(){
		$('#btn-testar-email').prop('disabled', false);
		Swal.fire('Erro', 'Falha ao testar envio.', 'error');
	});
}

function previewCobranca(){
	const dados = coletarDadosFormulario();
	dados.acao = 'preview_cobranca';

	$('#btn-preview-cobranca').prop('disabled', true);
	$('#preview-cobranca-resultado').html('<span class="text-muted">Simulando...</span>');

	$.post(url_base + CONFIG_EMAIL_URL, dados, function(res){
		$('#btn-preview-cobranca').prop('disabled', false);

		if(!res || !res.success){
			Swal.fire('Erro', (res && res.message) ? res.message : 'Falha na simulação.', 'error');
			$('#preview-cobranca-resultado').html('');
			return;
		}

		const p = res.preview || {};

		if(!p.ok){
			const msg = p.message || 'Não foi possível simular. Verifique o SQL de cobrança.';
			$('#preview-cobranca-resultado').html('<span class="text-danger">'+msg+'</span>');
			Swal.fire('Atenção', msg, 'warning');
			return;
		}

		let html = '<strong>Hoje seriam enviados:</strong> '+(p.total || 0)+' e-mail(s).';
		if(!p.cobranca_ativa){
			html += ' <span class="text-muted">(simulação — cobrança ainda desativada)</span>';
		}
		if(p.enviados_hoje){
			html += '<br><small>Já enviados hoje (log): '+p.enviados_hoje+'.</small>';
		}
		if(p.itens && p.itens.length){
			html += '<ul class="mb-0 mt-2">';
			p.itens.slice(0, 10).forEach(function(i){
				const emails = (i.emails && i.emails.length) ? i.emails.join(', ') : 'sem e-mail';
				html += '<li>'+i.nome+' — '+i.label+' <small>('+emails+')</small></li>';
			});
			if(p.itens.length > 10){
				html += '<li>... e mais '+(p.itens.length - 10)+'</li>';
			}
			html += '</ul>';
		} else {
			html += '<p class="mb-0 mt-2 text-muted">Nenhum título em aberto coincide com as regras de hoje (ou já foram avisados).</p>';
		}

		$('#preview-cobranca-resultado').html(html);

		Swal.fire({
			title: 'Simulação de hoje',
			html: html,
			icon: (p.total > 0) ? 'info' : 'question',
			confirmButtonText: 'OK'
		});
	}, 'json').fail(function(xhr){
		$('#btn-preview-cobranca').prop('disabled', false);
		$('#preview-cobranca-resultado').html('');
		let msg = 'Falha na simulação.';
		if(xhr && xhr.responseText && xhr.responseText.indexOf('cobranca_') !== -1){
			msg = 'Colunas de cobrança não existem. Execute o SQL no phpMyAdmin.';
		}
		Swal.fire('Erro', msg, 'error');
	});
}

function executarCobranca(){
	Swal.fire({
		title: 'Enviar cobranças agora?',
		text: 'Serão enviados os avisos pendentes de hoje.',
		icon: 'question',
		showCancelButton: true,
		confirmButtonText: 'Enviar'
	}).then(function(r){
		if(!r.isConfirmed) return;
		$('#btn-executar-cobranca').prop('disabled', true);
		Swal.fire({ title: 'Enviando...', allowOutsideClick: false, didOpen: function(){ Swal.showLoading(); } });
		$.post(url_base + CONFIG_EMAIL_URL, { acao: 'executar_cobranca' }, function(res){
			$('#btn-executar-cobranca').prop('disabled', false);
			Swal.close();
			if(!res || !res.success){
				Swal.fire('Erro', (res && res.message) ? res.message : 'Falha no envio.', 'error');
				return;
			}
			Swal.fire('Concluído', res.message, 'success');
			previewCobranca();
		}, 'json');
	});
}

function auditarEmails(){
	$('#btn-auditar-emails').prop('disabled', true);
	$.post(url_base + CONFIG_EMAIL_URL, { acao: 'auditar_emails' }, function(res){
		$('#btn-auditar-emails').prop('disabled', false);

		if(!res || !res.success){
			Swal.fire('Erro', (res && res.message) ? res.message : 'Falha na auditoria.', 'error');
			return;
		}

		const a = res.auditoria || {};
		let html = '<p><strong>'+(a.total || 0)+'</strong> e-mail(s) inválido(s) ou fictício(s) encontrado(s).</p>';

		if(a.por_motivo){
			html += '<p class="small text-muted mb-2">Por motivo:</p><ul class="small text-start">';
			Object.keys(a.por_motivo).forEach(function(m){
				html += '<li>'+m+': <strong>'+a.por_motivo[m]+'</strong></li>';
			});
			html += '</ul>';
		}

		if(a.itens && a.itens.length){
			html += '<div class="table-responsive" style="max-height:280px;"><table class="table table-sm table-striped text-start mb-0">';
			html += '<thead><tr><th>Tipo</th><th>Nome</th><th>E-mail</th><th>Motivo</th></tr></thead><tbody>';
			a.itens.forEach(function(i){
				html += '<tr><td>'+i.tipo+'</td><td>'+i.nome+'</td><td><code>'+i.email+'</code></td><td class="small">'+i.motivo+'</td></tr>';
			});
			html += '</tbody></table></div>';
		} else {
			html += '<p class="text-success mb-0">Nenhum e-mail fictício detectado na amostra.</p>';
		}

		if(a.truncado){
			html += '<p class="small text-muted mt-2">Lista limitada a 150 registros. Corrija nos cadastros de Alunos, Responsáveis e Leads.</p>';
		}

		Swal.fire({
			title: 'Auditoria de e-mails',
			html: html,
			width: 720,
			confirmButtonText: 'OK'
		});
	}, 'json').fail(function(){
		$('#btn-auditar-emails').prop('disabled', false);
		Swal.fire('Erro', 'Falha ao auditar e-mails.', 'error');
	});
}

function previewAniversario(){
	const dados = coletarDadosFormulario();
	dados.acao = 'preview_aniversario';

	$('#btn-preview-aniversario').prop('disabled', true);
	$.post(url_base + CONFIG_EMAIL_URL, dados, function(res){
		$('#btn-preview-aniversario').prop('disabled', false);
		if(!res || !res.success){
			Swal.fire('Erro', (res && res.message) ? res.message : 'Falha na simulação.', 'error');
			return;
		}
		const p = res.preview || {};
		if(!p.ok){
			Swal.fire('Atenção', p.message || 'SQL pendente.', 'warning');
			return;
		}
		let html = '<strong>Aniversariantes hoje:</strong> '+(p.total || 0);
		if(!p.ativo){
			html += ' <span class="text-muted">(simulação — automação desativada)</span>';
		}
		if(p.itens && p.itens.length){
			html += '<ul class="mb-0 mt-2 text-start">';
			p.itens.slice(0, 10).forEach(function(i){
				html += '<li>'+i.nome+' &lt;'+i.contato+'&gt;</li>';
			});
			html += '</ul>';
		}
		$('#preview-aniversario-resultado').html(html);
		Swal.fire({ title: 'Aniversariantes de hoje', html: html, icon: 'info' });
	}, 'json');
}

function executarAniversario(){
	Swal.fire({
		title: 'Enviar aniversários agora?',
		text: 'Serão enviados os e-mails pendentes de hoje.',
		icon: 'question',
		showCancelButton: true,
		confirmButtonText: 'Enviar'
	}).then(function(r){
		if(!r.isConfirmed) return;
		$('#btn-executar-aniversario').prop('disabled', true);
		Swal.fire({ title: 'Enviando...', allowOutsideClick: false, didOpen: function(){ Swal.showLoading(); } });
		$.post(url_base + CONFIG_EMAIL_URL, { acao: 'executar_aniversario' }, function(res){
			$('#btn-executar-aniversario').prop('disabled', false);
			Swal.close();
			if(!res || !res.success){
				Swal.fire('Erro', (res && res.message) ? res.message : 'Falha no envio.', 'error');
				return;
			}
			Swal.fire('Concluído', res.message, 'success');
			previewAniversario();
		}, 'json');
	});
}

$(function(){
	carregarConfiguracao();

	$('#btn-preset-gmail').on('click', function(){ aplicarPreset('gmail'); });
	$('#btn-preset-outlook').on('click', function(){ aplicarPreset('outlook'); });
	$('#btn-preset-corp').on('click', function(){ aplicarPreset('corp'); });
	$('#btn-salvar-smtp').on('click', salvarConfiguracao);
	$('#btn-testar-email').on('click', testarEmail);
	$('#btn-preview-cobranca').on('click', previewCobranca);
	$('#btn-executar-cobranca').on('click', executarCobranca);
	$('#btn-auditar-emails').on('click', auditarEmails);
	$('#btn-preview-aniversario').on('click', previewAniversario);
	$('#btn-executar-aniversario').on('click', executarAniversario);
	$('#btn-wa-status').on('click', whatsappStatus);
	$('#btn-wa-conectar').on('click', whatsappConectar);
	$('#btn-wa-recriar').on('click', whatsappRecriar);
	$('#btn-wa-qr').on('click', whatsappQr);
	$('#btn-wa-salvar').on('click', whatsappSalvar);
	$('#btn-wa-testar').on('click', whatsappTestar);
	$('#btn-wa-desconectar').on('click', whatsappDesconectar);
});

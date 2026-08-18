function postSocialCfg(data) {
	return $.ajax({
		url: url_base + 'painel/config/social',
		method: 'POST',
		dataType: 'json',
		data: data
	});
}

function esc(s) {
	return $('<div>').text(s == null ? '' : String(s)).html();
}

function carregar() {
	postSocialCfg({ acao: 'carregar' }).done(function (res) {
		if (!res || !res.success) {
			return Swal.fire('Erro', (res && res.message) || 'Falha', 'error');
		}
		if (!res.coluna_ok) $('#alert-sql-meta').removeClass('d-none');
		else $('#alert-sql-meta').addClass('d-none');
		if (!res.auto_sql_ok) $('#alert-sql-auto').removeClass('d-none');
		else $('#alert-sql-auto').addClass('d-none');
		if (!res.app_ok) $('#alert-app-meta').removeClass('d-none');
		else $('#alert-app-meta').addClass('d-none');

		$('#meta_fb_ativo').prop('checked', !!res.meta_fb_ativo);
		$('#meta_ig_ativo').prop('checked', !!res.meta_ig_ativo);
		$('#meta_auto_ativo').prop('checked', !!res.meta_auto_ativo);
		$('#meta_page_id').val(res.meta_page_id || '');
		$('#meta_page_name').val(res.meta_page_name || '');
		$('#meta_ig_user_id').val(res.meta_ig_user_id || '');
		$('#meta_ig_username').val(res.meta_ig_username || '');
		$('#meta_page_token').val('');
		$('#token_mask_txt').text(res.token_salvo ? ('Token salvo: ' + (res.token_mask || '********')) : 'Nenhum token salvo.');
		$('#webhook_url').text(res.webhook_url || 'Salve a config uma vez para gerar o token do webhook.');
		$('#webhook_url_global').text(res.webhook_url_global || '—');
		var st = res.meta_pronto ? 'Pronto para publicar.' : 'Incompleto — conecte Page/token e ative FB e/ou IG.';
		if (res.meta_conectado_em) st += ' Conectado em ' + res.meta_conectado_em;
		if (res.meta_page_name) st += ' · ' + res.meta_page_name;
		if (res.meta_ig_username) st += ' · @' + res.meta_ig_username;
		if (res.meta_auto_ativo) st += ' · Automações ON';
		$('#meta-status-txt').text(st);

		carregarRegras();
		carregarLog();
	});
}

var regrasCache = [];

function carregarRegras() {
	postSocialCfg({ acao: 'listar_automacoes' }).done(function (res) {
		if (!res || !res.success) {
			$('#tbody-regras').html('<tr><td colspan="5" class="text-danger small p-3">' + esc((res && res.message) || 'Erro') + '</td></tr>');
			return;
		}
		regrasCache = res.itens || [];
		if (!regrasCache.length) {
			$('#tbody-regras').html('<tr><td colspan="5" class="text-muted small p-3">Nenhuma regra. Ex.: palavra "quero" → link de matrícula.</td></tr>');
			return;
		}
		var html = '';
		regrasCache.forEach(function (a) {
			html += '<tr>' +
				'<td><code>' + esc(a.palavra_chave) + '</code></td>' +
				'<td class="small">' + esc(a.match_modo) + '</td>' +
				'<td class="small">' + esc(a.canais) + '</td>' +
				'<td>' + (Number(a.ativo) === 1 ? '<span class="badge bg-success">on</span>' : '<span class="badge bg-secondary">off</span>') + '</td>' +
				'<td class="text-nowrap">' +
				'<button type="button" class="btn btn-sm btn-outline-primary btn-edit-regra" data-id="' + a.id + '">Editar</button> ' +
				'<button type="button" class="btn btn-sm btn-outline-danger btn-del-regra" data-id="' + a.id + '">Excluir</button>' +
				'</td></tr>';
		});
		$('#tbody-regras').html(html);
	});
}

function renderWebhookDebugStatus(st) {
	if (!st) {
		$('#webhook-debug-status').html('<span class="text-warning">Status indisponível — arquivos antigos no servidor?</span>');
		return;
	}
	var ok = st.file_ok || st.db_ok;
	var html = '<div><strong>Versão debug:</strong> ' + esc(st.code_version || '?') + '</div>';
	html += '<div><strong>Log arquivo:</strong> ' + (st.file_ok ? '<span class="text-success">OK</span>' : '<span class="text-danger">sem permissão</span>');
	html += ' · <code class="small">' + esc(st.file_path || '') + '</code>';
	if (st.file_size) html += ' · ' + st.file_size + ' bytes';
	html += '</div>';
	html += '<div><strong>Log banco (meta_webhook_log):</strong> ' + (st.db_ok ? '<span class="text-success">tabela OK</span>' : '<span class="text-danger">tabela ausente — rode meta_messaging.sql</span>') + '</div>';
	if (!ok) {
		html += '<div class="text-danger mt-1">Envie os arquivos PHP novos ao servidor e crie a pasta <code>uploads/logs/</code> gravável.</div>';
	}
	$('#webhook-debug-status').html(html);
}

function carregarLog() {
	postSocialCfg({ acao: 'log_automacoes' }).done(function (res) {
		renderWebhookDebugStatus(res && res.debug_status);
		var itens = (res && res.itens) || [];
		if (!itens.length) {
			$('#lista-log-auto').html('<li class="list-group-item text-muted">Sem eventos ainda.</li>');
		} else {
			var html = '';
			itens.forEach(function (l) {
				html += '<li class="list-group-item py-2">' +
					'<span class="badge bg-' + (l.status === 'ok' ? 'success' : (l.status === 'erro' ? 'danger' : 'secondary')) + '">' + esc(l.status) + '</span> ' +
					'<span class="text-muted">' + esc(l.canal) + '</span> · ' +
					esc((l.comentario_txt || '').slice(0, 60)) +
					(l.erro_msg ? '<div class="text-danger">' + esc(l.erro_msg) + '</div>' : '') +
					'<div class="text-muted" style="font-size:0.75rem">' + esc(l.created_at || '') + '</div></li>';
			});
			$('#lista-log-auto').html(html);
		}
		renderWebhookDebug((res && res.webhook_debug) || []);
	});
}

function renderWebhookDebug(itens) {
	if (!itens.length) {
		$('#lista-webhook-debug').html(
			'<li class="list-group-item text-muted">Nenhum evento registrado. '
			+ 'Confirme que a tabela <code>meta_webhook_log</code> existe (SQL meta_messaging.sql) '
			+ 'e que os arquivos novos foram enviados ao servidor.</li>'
		);
		return;
	}
	var html = '';
	itens.forEach(function (l) {
		var ev = l.evento || l.status || '';
		var badge = 'secondary';
		if (ev.indexOf('mensagem') >= 0 || ev === 'mensagem') badge = 'info';
		if (ev.indexOf('comentario') >= 0 || ev === 'recebido' || ev === 'extraido') badge = 'primary';
		if (ev === 'sem_parse' || ev === 'sem_valores') badge = 'warning';
		if (ev === 'escola_nao_encontrada') badge = 'danger';
		if (l.tipo === 'webhook_inbound' && ev.indexOf('comentario') >= 0) badge = 'primary';
		html += '<li class="list-group-item py-2">' +
			'<span class="badge bg-' + badge + '">' + esc(l.tipo + ': ' + ev) + '</span> ' +
			esc(l.payload_resumo || '') +
			(l.detalhe ? '<pre class="mt-1 mb-0 p-2 bg-light border rounded" style="font-size:0.7rem;max-height:180px;overflow:auto;white-space:pre-wrap;">' + esc(l.detalhe) + '</pre>' : '') +
			'<div class="text-muted" style="font-size:0.75rem">' + esc(l.created_at || '') + '</div></li>';
	});
	$('#lista-webhook-debug').html(html);
}

function showModal(id) {
	var el = document.getElementById(id);
	if (window.bootstrap && bootstrap.Modal) bootstrap.Modal.getOrCreateInstance(el).show();
	else $(el).modal('show');
}

function hideModal(id) {
	var el = document.getElementById(id);
	if (window.bootstrap && bootstrap.Modal) bootstrap.Modal.getOrCreateInstance(el).hide();
	else $(el).modal('hide');
}

$(function () {
	var params = new URLSearchParams(window.location.search);
	if (params.get('oauth') === 'ok') {
		$('#alert-oauth').removeClass('d-none').addClass('alert-success')
			.text('Conexão realizada. Página do Facebook vinculada' + (params.get('pages') ? ' (' + params.get('pages') + '). Se o suporte liberar novas permissões, conecte novamente.' : '.') );
	} else if (params.get('oauth') === 'erro') {
		$('#alert-oauth').removeClass('d-none').addClass('alert-danger')
			.text(params.get('msg') || 'Não foi possível conectar. Tente de novo ou fale com o suporte.');
	}

	carregar();

	$('#btn-recarregar-debug').on('click', function () {
		carregarLog();
	});

	$('#btn-ping-debug').on('click', function () {
		postSocialCfg({ acao: 'webhook_debug_status' }).done(function (res) {
			if (!res || !res.success) {
				return Swal.fire('Erro', (res && res.message) || 'Falha', 'error');
			}
			renderWebhookDebugStatus(res.status);
			var merged = (res.status && res.status.recent_file) || [];
			if (res.status && res.status.recent_db && res.status.recent_db.length) {
				merged = merged.concat(res.status.recent_db);
			}
			renderWebhookDebug(merged.slice(0, 40));
			Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Teste gravado no log', showConfirmButton: false, timer: 2000 });
		});
	});

	$('#btn-salvar').on('click', function () {
		postSocialCfg({
			acao: 'salvar',
			meta_fb_ativo: $('#meta_fb_ativo').is(':checked') ? 1 : 0,
			meta_ig_ativo: $('#meta_ig_ativo').is(':checked') ? 1 : 0,
			meta_auto_ativo: $('#meta_auto_ativo').is(':checked') ? 1 : 0,
			meta_page_id: $('#meta_page_id').val(),
			meta_page_name: $('#meta_page_name').val(),
			meta_ig_user_id: $('#meta_ig_user_id').val(),
			meta_ig_username: $('#meta_ig_username').val(),
			meta_page_token: $('#meta_page_token').val()
		}).done(function (res) {
			if (!res || !res.success) return Swal.fire('Erro', (res && res.message) || 'Falha', 'error');
			Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: res.message, showConfirmButton: false, timer: 2000 });
			carregar();
		});
	});

	$('#btn-testar').on('click', function () {
		postSocialCfg({ acao: 'testar' }).done(function (res) {
			var html = '<div class="text-start small" style="white-space:pre-wrap;text-align:left;">';
			html += esc((res && res.message) || 'Sem resposta.');
			if (res && res.scopes_faltando && res.scopes_faltando.length) {
				html += '\n\nReconecte com Facebook para solicitar os escopos novos (ex.: após App Review).';
			}
			html += '</div>';
			Swal.fire({
				title: res && res.success ? 'Diagnóstico Meta' : 'Falha na conexão',
				html: html,
				width: 620,
				icon: res && res.success ? 'info' : 'error',
				confirmButtonText: 'Fechar'
			});
		});
	});

	$('#btn-subscribe').on('click', function () {
		postSocialCfg({ acao: 'subscribe_webhooks' }).done(function (res) {
			var icon = res && res.success ? (res.partial ? 'warning' : 'success') : 'error';
			var title = res && res.success ? (res.partial ? 'Parcial' : 'OK') : 'Falha';
			Swal.fire(title, (res && res.message) || '', icon);
		});
	});

	$('#btn-oauth').on('click', function () {
		postSocialCfg({ acao: 'oauth_url' }).done(function (res) {
			if (!res || !res.success) return Swal.fire('OAuth', (res && res.message) || 'Indisponível', 'warning');
			window.location.href = res.url;
		});
	});

	$('#btn-desconectar').on('click', function () {
		Swal.fire({ title: 'Desconectar conta Meta?', showCancelButton: true, confirmButtonText: 'Sim' }).then(function (r) {
			if (!r.isConfirmed) return;
			postSocialCfg({ acao: 'desconectar' }).done(function () { carregar(); });
		});
	});

	$('#btn-nova-regra').on('click', function () {
		$('#regra_id').val('');
		$('#regra_kw').val('');
		$('#regra_modo').val('contem');
		$('#regra_canais').val('ambos');
		$('#regra_msg').val('');
		$('#regra_ativo').prop('checked', true);
		showModal('modalRegra');
	});

	$(document).on('click', '.btn-edit-regra', function () {
		var id = Number($(this).data('id'));
		var a = regrasCache.find(function (x) { return Number(x.id) === id; });
		if (!a) return;
		$('#regra_id').val(a.id);
		$('#regra_kw').val(a.palavra_chave);
		$('#regra_modo').val(a.match_modo || 'contem');
		$('#regra_canais').val(a.canais || 'ambos');
		$('#regra_msg').val(a.mensagem_dm);
		$('#regra_ativo').prop('checked', Number(a.ativo) === 1);
		showModal('modalRegra');
	});

	$(document).on('click', '.btn-del-regra', function () {
		var id = $(this).data('id');
		Swal.fire({ title: 'Excluir regra?', showCancelButton: true, confirmButtonText: 'Excluir' }).then(function (r) {
			if (!r.isConfirmed) return;
			postSocialCfg({ acao: 'excluir_automacao', id: id }).done(function () {
				carregarRegras();
				carregarLog();
			});
		});
	});

	$('#btn-salvar-regra').on('click', function () {
		postSocialCfg({
			acao: 'salvar_automacao',
			id: $('#regra_id').val(),
			palavra_chave: $('#regra_kw').val(),
			match_modo: $('#regra_modo').val(),
			canais: $('#regra_canais').val(),
			mensagem_dm: $('#regra_msg').val(),
			ativo: $('#regra_ativo').is(':checked') ? 1 : 0
		}).done(function (res) {
			if (!res || !res.success) return Swal.fire('Erro', (res && res.message) || 'Falha', 'error');
			hideModal('modalRegra');
			carregarRegras();
		});
	});
});

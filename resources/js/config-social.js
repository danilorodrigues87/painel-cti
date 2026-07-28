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

function carregarLog() {
	postSocialCfg({ acao: 'log_automacoes' }).done(function (res) {
		var itens = (res && res.itens) || [];
		if (!itens.length) {
			$('#lista-log-auto').html('<li class="list-group-item text-muted">Sem eventos ainda.</li>');
			return;
		}
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
	});
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
			Swal.fire(res && res.success ? 'OK' : 'Falha', (res && res.message) || '', res && res.success ? 'success' : 'error');
		});
	});

	$('#btn-subscribe').on('click', function () {
		postSocialCfg({ acao: 'subscribe_webhooks' }).done(function (res) {
			Swal.fire(res && res.success ? 'OK' : 'Falha', (res && res.message) || '', res && res.success ? 'success' : 'error');
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

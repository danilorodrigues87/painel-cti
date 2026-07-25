function postSocialCfg(data) {
	return $.ajax({
		url: url_base + 'painel/config/social',
		method: 'POST',
		dataType: 'json',
		data: data
	});
}

function carregar() {
	postSocialCfg({ acao: 'carregar' }).done(function (res) {
		if (!res || !res.success) {
			return Swal.fire('Erro', (res && res.message) || 'Falha', 'error');
		}
		if (!res.coluna_ok) $('#alert-sql-meta').removeClass('d-none');
		else $('#alert-sql-meta').addClass('d-none');
		if (!res.app_ok) $('#alert-app-meta').removeClass('d-none');
		else $('#alert-app-meta').addClass('d-none');

		$('#meta_fb_ativo').prop('checked', !!res.meta_fb_ativo);
		$('#meta_ig_ativo').prop('checked', !!res.meta_ig_ativo);
		$('#meta_page_id').val(res.meta_page_id || '');
		$('#meta_page_name').val(res.meta_page_name || '');
		$('#meta_ig_user_id').val(res.meta_ig_user_id || '');
		$('#meta_ig_username').val(res.meta_ig_username || '');
		$('#meta_page_token').val('');
		$('#token_mask_txt').text(res.token_salvo ? ('Token salvo: ' + (res.token_mask || '********')) : 'Nenhum token salvo.');
		$('#webhook_url').text(res.webhook_url || 'Salve a config uma vez para gerar o token do webhook.');
		var st = res.meta_pronto ? 'Pronto para publicar.' : 'Incompleto — conecte Page/token e ative FB e/ou IG.';
		if (res.meta_conectado_em) st += ' Conectado em ' + res.meta_conectado_em;
		if (res.meta_page_name) st += ' · ' + res.meta_page_name;
		if (res.meta_ig_username) st += ' · @' + res.meta_ig_username;
		$('#meta-status-txt').text(st);
	});
}

$(function () {
	var params = new URLSearchParams(window.location.search);
	if (params.get('oauth') === 'ok') {
		$('#alert-oauth').removeClass('d-none').addClass('alert-success')
			.text('OAuth OK. Page conectada' + (params.get('pages') ? ' (' + params.get('pages') + ' page(s) na conta — usamos a primeira).' : '.'));
	} else if (params.get('oauth') === 'erro') {
		$('#alert-oauth').removeClass('d-none').addClass('alert-danger')
			.text(params.get('msg') || 'Falha no OAuth.');
	}

	carregar();

	$('#btn-salvar').on('click', function () {
		postSocialCfg({
			acao: 'salvar',
			meta_fb_ativo: $('#meta_fb_ativo').is(':checked') ? 1 : 0,
			meta_ig_ativo: $('#meta_ig_ativo').is(':checked') ? 1 : 0,
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

	$('#btn-oauth').on('click', function () {
		postSocialCfg({ acao: 'oauth_url' }).done(function (res) {
			if (!res || !res.success) return Swal.fire('OAuth', (res && res.message) || 'Indisponível', 'warning');
			window.location.href = res.url;
		});
	});

	$('#btn-desconectar').on('click', function () {
		Swal.fire({ title: 'Desconectar conta Meta?', showCancelButton: true, confirmButtonText: 'Sim' }).then(function (r) {
			if (!r.isConfirmed) return;
			postSocialCfg({ acao: 'desconectar' }).done(function (res) {
				carregar();
				Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: (res && res.message) || 'OK', showConfirmButton: false, timer: 1800 });
			});
		});
	});
});

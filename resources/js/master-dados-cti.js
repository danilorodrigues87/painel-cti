const MASTER_DADOS_CTI_URL = 'master/dados-cti';

let usuariosMasterCache = [];

function popularEstados(selected) {
	const $sel = $('#cti_estado').empty().append('<option value="">—</option>');
	(window.CTI_ESTADOS || []).forEach(function (e) {
		$sel.append('<option value="' + e.id + '">' + $('<div>').text(e.nome).html() + '</option>');
	});
	if (selected) $sel.val(String(selected));
}

function carregarCidades(estadoId, cidadeId) {
	const $cid = $('#cti_cidade').empty().append('<option value="">—</option>');
	if (!estadoId) return $.Deferred().resolve().promise();
	return $.post(url_base + MASTER_DADOS_CTI_URL, { acao: 'cidades', estado: estadoId }, function (res) {
		(res.cidades || []).forEach(function (c) {
			$cid.append('<option value="' + c.id + '">' + $('<div>').text(c.nome).html() + '</option>');
		});
		if (cidadeId) $cid.val(String(cidadeId));
	}, 'json');
}

function popularSelectRepresentantes(usuarios, selectedId) {
	usuariosMasterCache = usuarios || [];
	const $sel = $('#cti_rep_usuario').empty().append('<option value="">— Selecione —</option>');
	usuariosMasterCache.forEach(function (u) {
		const label = u.nome + (u.cpf ? ' · CPF ' + u.cpf : '') + (u.is_super ? ' (super)' : '');
		$sel.append('<option value="' + u.id + '">' + $('<div>').text(label).html() + '</option>');
	});
	if (selectedId) $sel.val(String(selectedId));
	atualizarPreviewRepresentante();
}

function atualizarPreviewRepresentante() {
	const id = parseInt($('#cti_rep_usuario').val(), 10);
	const u = usuariosMasterCache.find(function (x) { return parseInt(x.id, 10) === id; });
	const $prev = $('#cti_rep_preview');
	if (!u) {
		$prev.text('Selecione um usuário Master com CPF cadastrado.');
		return;
	}
	const parts = [u.nome];
	if (u.cpf) parts.push('CPF ' + u.cpf);
	if (u.email) parts.push(u.email);
	$prev.text(parts.join(' · '));
}

function preencherForm(d) {
	d = d || {};
	$('#cti_razao').val(d.razao_social || '');
	$('#cti_fantasia').val(d.nome_fantasia || '');
	$('#cti_cnpj').val(d.cnpj || '');
	$('#cti_email').val(d.email || '');
	$('#cti_telefone').val(d.telefone || '');
	$('#cti_site').val(d.site || '');
	$('#cti_cep').val(d.cep || '');
	$('#cti_endereco').val(d.endereco || '');
	$('#cti_numero').val(d.numero || '');
	$('#cti_bairro').val(d.bairro || '');
	$('#cti_foro').val(d.foro_comarca || '');
	$('#cti_rep_cargo').val(d.rep_cargo || 'Administrador');
	popularEstados(d.estado || 0);
	return carregarCidades(d.estado || 0, d.cidade || 0).then(function () {
		popularSelectRepresentantes(window._usuariosMaster || [], d.rep_legal_usuario_id || 0);
	});
}

function atualizarBadge(completo, faltando) {
	const $b = $('#badge-dados-cti');
	const $alert = $('#alert-dados-incompletos');
	if (completo) {
		$b.removeClass('bg-secondary bg-warning').addClass('bg-success').text('Completo');
		$alert.addClass('d-none');
		return;
	}
	$b.removeClass('bg-secondary bg-success').addClass('bg-warning').text('Incompleto');
	$alert.removeClass('d-none').html(
		'<strong>Cadastro incompleto para o contrato:</strong> ' + (faltando || []).join(', ')
		+ '. <a href="' + url_base + 'master/dados-cti">Completar</a>.'
	);
}

function carregarDadosCti() {
	$.post(url_base + MASTER_DADOS_CTI_URL, { acao: 'carregar' }, function (res) {
		if (!res || !res.success) {
			Swal.fire('Erro', (res && res.message) || 'Falha ao carregar.', 'error');
			return;
		}
		window._usuariosMaster = res.usuarios_master || [];
		preencherForm(res.dados).then(function () {
			atualizarBadge(res.completo, res.faltando);
		});
	}, 'json');
}

function salvarDadosCti() {
	const payload = {
		acao: 'salvar',
		razao_social: $('#cti_razao').val(),
		nome_fantasia: $('#cti_fantasia').val(),
		cnpj: $('#cti_cnpj').val(),
		email: $('#cti_email').val(),
		telefone: $('#cti_telefone').val(),
		site: $('#cti_site').val(),
		cep: $('#cti_cep').val(),
		endereco: $('#cti_endereco').val(),
		numero: $('#cti_numero').val(),
		bairro: $('#cti_bairro').val(),
		estado: $('#cti_estado').val(),
		cidade: $('#cti_cidade').val(),
		foro_comarca: $('#cti_foro').val(),
		rep_legal_usuario_id: $('#cti_rep_usuario').val(),
		rep_cargo: $('#cti_rep_cargo').val(),
	};
	$('#btn-salvar-dados-cti').prop('disabled', true);
	$.post(url_base + MASTER_DADOS_CTI_URL, payload, function (res) {
		$('#btn-salvar-dados-cti').prop('disabled', false);
		if (!res || !res.success) {
			Swal.fire('Erro', (res && res.message) || 'Falha ao salvar.', 'error');
			return;
		}
		Swal.fire('Salvo', res.message, 'success');
		atualizarBadge(res.completo, res.faltando);
	}, 'json').fail(function () {
		$('#btn-salvar-dados-cti').prop('disabled', false);
		Swal.fire('Erro', 'Falha ao salvar.', 'error');
	});
}

$(function () {
	popularEstados();
	carregarDadosCti();
	$('#btn-salvar-dados-cti').on('click', salvarDadosCti);
	$('#cti_estado').on('change', function () {
		carregarCidades($(this).val(), 0);
	});
	$('#cti_rep_usuario').on('change', atualizarPreviewRepresentante);
});

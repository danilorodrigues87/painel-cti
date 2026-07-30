function postAgentApi(data) {
	return $.ajax({
		url: url_base + 'master/agent-api',
		method: 'POST',
		dataType: 'json',
		data: data
	});
}

var escolaAtualId = null;

function esc(s) {
	return $('<div>').text(s == null ? '' : String(s)).html();
}

function renderMasterKeys(keys) {
	var $tb = $('#keys-tbody').empty();
	if (!keys || !keys.length) {
		$tb.append('<tr><td colspan="5" class="text-muted">Nenhuma chave Master.</td></tr>');
		return;
	}
	keys.forEach(function (k) {
		var ativo = k.ativo
			? '<span class="badge bg-success">Sim</span>'
			: '<span class="badge bg-secondary">Revogada</span>';
		var btn = k.ativo
			? '<button type="button" class="btn btn-sm btn-outline-danger btn-revogar" data-id="' + k.id + '">Revogar</button>'
			: '—';
		$tb.append(
			'<tr><td>' + esc(k.nome) + '</td><td><code>' + esc(k.key_prefix) + '</code></td><td>' +
			ativo + '</td><td>' + esc(k.ultimo_uso_em || '—') + '</td><td>' + btn + '</td></tr>'
		);
	});
}

function renderEscolas(escolas) {
	var $tb = $('#escolas-tbody').empty();
	if (!escolas || !escolas.length) {
		$tb.append('<tr><td colspan="7" class="text-muted">Nenhuma escola com módulo Assistente IA.</td></tr>');
		return;
	}
	escolas.forEach(function (e) {
		var llm = e.llm_pronto ? '<span class="text-success">OK</span>' : '<span class="text-muted">—</span>';
		var tg = e.telegram_pronto ? '<span class="text-success">OK</span>' : '<span class="text-muted">—</span>';
		var keys = e.keys_ativas > 0
			? '<span class="badge bg-primary">' + e.keys_ativas + '</span> <code class="small">' + esc(e.key_prefix || '') + '</code>'
			: '<span class="text-muted">0</span>';
		var ativoBtn = e.agent_ativo
			? '<button type="button" class="btn btn-sm btn-success btn-toggle-ativo" data-id="' + e.id_admin + '" data-ativo="0">Ativo</button>'
			: '<button type="button" class="btn btn-sm btn-outline-secondary btn-toggle-ativo" data-id="' + e.id_admin + '" data-ativo="1">Inativo</button>';
		$tb.append(
			'<tr>' +
			'<td>' + esc(e.nome) + '</td>' +
			'<td>' + e.id_admin + '</td>' +
			'<td>' + llm + '</td>' +
			'<td>' + tg + '</td>' +
			'<td>' + keys + '</td>' +
			'<td>' + ativoBtn + '</td>' +
			'<td><button type="button" class="btn btn-sm btn-outline-primary btn-detalhe" data-id="' + e.id_admin + '">Abrir</button></td>' +
			'</tr>'
		);
	});
}

function carregarMaster() {
	postAgentApi({ acao: 'listar_master' }).done(function (res) {
		if (!res || !res.success) {
			Swal.fire('Erro', (res && res.message) || 'Falha', 'error');
			return;
		}
		if (res.api_base) $('#api-base').text(res.api_base);
		renderMasterKeys(res.keys || []);
	});
}

function carregarEscolas() {
	postAgentApi({ acao: 'listar_escolas' }).done(function (res) {
		if (!res || !res.success) {
			Swal.fire('Erro', (res && res.message) || 'Falha', 'error');
			return;
		}
		renderEscolas(res.escolas || []);
	});
}

function abrirDetalhe(id) {
	escolaAtualId = id;
	$('#modal-secrets').addClass('d-none');
	postAgentApi({ acao: 'detalhe_escola', id_admin: id }).done(function (res) {
		if (!res || !res.success) {
			Swal.fire('Erro', (res && res.message) || 'Falha', 'error');
			return;
		}
		$('#modal-escola-titulo').text(res.nome + ' (#' + res.id_admin + ')');
		var c = res.config || {};
		var keysHtml = (res.keys || []).map(function (k) {
			var st = k.ativo ? 'ativa' : 'revogada';
			var btn = k.ativo
				? ' <button type="button" class="btn btn-sm btn-link text-danger p-0 btn-revogar" data-id="' + k.id + '">revogar</button>'
				: '';
			return '<li><code>' + esc(k.key_prefix) + '</code> — ' + st + btn + '</li>';
		}).join('') || '<li class="text-muted">Nenhuma chave</li>';

		$('#modal-escola-body').html(
			'<p><strong>Agent ativo:</strong> ' + (c.agent_ativo ? 'sim' : 'não') + '</p>' +
			'<p><strong>LLM:</strong> ' + esc(c.llm_provider || '—') + ' / ' + esc(c.llm_model || '—') +
			' — ' + (c.llm_pronto ? 'chave OK (' + esc(c.llm_key_mask) + ')' : 'sem chave') + '</p>' +
			'<p><strong>Telegram:</strong> @' + esc(c.telegram_bot_username || '—') +
			' — ' + (c.telegram_pronto ? 'token OK (' + esc(c.telegram_token_mask) + ')' : 'sem token') + '</p>' +
			'<p class="mb-1"><strong>Chat ID:</strong> ' + esc(c.telegram_chat_id || '—') + '</p>' +
			'<p><strong>Notas:</strong> ' + esc(c.telegram_notas || '—') + '</p>' +
			'<p class="mb-1"><strong>Chaves Agent API:</strong></p><ul>' + keysHtml + '</ul>' +
			'<p class="text-muted mb-0">API: <code>' + esc(res.api_base) + '/escolas/' + res.id_admin + '/resumo</code></p>'
		);

		var el = document.getElementById('modalEscola');
		if (window.bootstrap && bootstrap.Modal) {
			bootstrap.Modal.getOrCreateInstance(el).show();
		} else {
			$(el).modal('show');
		}
	});
}

$(function () {
	carregarMaster();
	carregarEscolas();

	$('#btn-criar-master').on('click', function () {
		postAgentApi({
			acao: 'criar_master',
			nome: $('#key-nome').val() || 'Master OpenClaw'
		}).done(function (res) {
			if (!res || !res.success) {
				Swal.fire('Erro', (res && res.message) || 'Falha', 'error');
				return;
			}
			$('#plain-key').text(res.plain || '');
			$('#plain-box').removeClass('d-none');
			Swal.fire('Chave criada', 'Copie agora — não será exibida de novo.', 'success');
			carregarMaster();
		});
	});

	$(document).on('click', '.btn-revogar', function () {
		var id = $(this).data('id');
		Swal.fire({
			title: 'Revogar chave?',
			icon: 'warning',
			showCancelButton: true,
			confirmButtonText: 'Revogar'
		}).then(function (r) {
			if (!r.isConfirmed) return;
			postAgentApi({ acao: 'revogar', id: id }).done(function (res) {
				if (!res || !res.success) {
					Swal.fire('Erro', (res && res.message) || 'Falha', 'error');
					return;
				}
				$('#plain-box').addClass('d-none');
				carregarMaster();
				carregarEscolas();
				if (escolaAtualId) abrirDetalhe(escolaAtualId);
			});
		});
	});

	$(document).on('click', '.btn-toggle-ativo', function () {
		var id = $(this).data('id');
		var ativo = $(this).data('ativo');
		postAgentApi({ acao: 'set_ativo', id_admin: id, ativo: ativo }).done(function (res) {
			if (!res || !res.success) {
				Swal.fire('Erro', (res && res.message) || 'Falha', 'error');
				return;
			}
			carregarEscolas();
		});
	});

	$(document).on('click', '.btn-detalhe', function () {
		abrirDetalhe($(this).data('id'));
	});

	$('#btn-gerar-escola').on('click', function () {
		if (!escolaAtualId) return;
		postAgentApi({ acao: 'criar_escola', id_admin: escolaAtualId }).done(function (res) {
			if (!res || !res.success) {
				Swal.fire('Erro', (res && res.message) || 'Falha', 'error');
				return;
			}
			$('#plain-key').text(res.plain || '');
			$('#plain-box').removeClass('d-none');
			Swal.fire({
				title: 'Chave da escola',
				html: '<p>Copie agora:</p><code class="user-select-all">' + esc(res.plain) + '</code>',
				icon: 'success'
			});
			carregarEscolas();
			abrirDetalhe(escolaAtualId);
		});
	});

	$('#btn-revelar').on('click', function () {
		if (!escolaAtualId) return;
		Swal.fire({
			title: 'Revelar segredos?',
			text: 'Exibe LLM key e token do Telegram em texto claro (só nesta sessão).',
			icon: 'warning',
			showCancelButton: true,
			confirmButtonText: 'Revelar'
		}).then(function (r) {
			if (!r.isConfirmed) return;
			postAgentApi({ acao: 'revelar_segredos', id_admin: escolaAtualId }).done(function (res) {
				if (!res || !res.success) {
					Swal.fire('Erro', (res && res.message) || 'Falha', 'error');
					return;
				}
				$('#sec-llm').text(res.llm_api_key || '(vazio)');
				$('#sec-tg').text(res.telegram_bot_token || '(vazio)');
				$('#modal-secrets').removeClass('d-none');
			});
		});
	});
});

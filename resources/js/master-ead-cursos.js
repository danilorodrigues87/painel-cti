const MASTER_EAD_CTI_URL = 'master/ead-cursos';

function esc(s) {
	return $('<div>').text(s == null ? '' : String(s)).html();
}

function badgeStatus(status) {
	if (status === 'publicado') return '<span class="badge bg-success">Publicado</span>';
	if (status === 'rascunho') return '<span class="badge bg-warning text-dark">Rascunho</span>';
	return '<span class="badge bg-secondary">Sem conteúdo</span>';
}

function renderLista(itens) {
	const $tb = $('#lista-cursos-cti').empty();
	if (!itens || !itens.length) {
		$tb.append('<tr><td colspan="5" class="text-center text-muted py-4">Nenhum curso CTI. Clique em Novo curso.</td></tr>');
		return;
	}
	itens.forEach(function (c) {
		$tb.append(
			'<tr>'
			+ '<td><strong>' + esc(c.nome) + '</strong></td>'
			+ '<td>' + esc(c.carga_h || '—') + '</td>'
			+ '<td>' + esc(c.aulas || 0) + '</td>'
			+ '<td>' + badgeStatus(c.status) + '</td>'
			+ '<td class="text-end text-nowrap">'
			+ '<button type="button" class="btn btn-sm btn-outline-primary me-1 btn-editar-cti" data-id="' + c.id + '"><i class="fas fa-pen"></i> Editor</button>'
			+ '<button type="button" class="btn btn-sm btn-outline-secondary me-1 btn-toggle-pub-cti" data-id="' + c.id + '">' + (c.publicado ? 'Despublicar' : 'Publicar') + '</button>'
			+ '<button type="button" class="btn btn-sm btn-outline-danger btn-excluir-cti" data-id="' + c.id + '"><i class="fas fa-trash"></i></button>'
			+ '</td></tr>'
		);
	});
}

function carregar() {
	$.post(url_base + MASTER_EAD_CTI_URL, { acao: 'listar' }, function (res) {
		if (!res || !res.success) {
			Swal.fire('Erro', (res && res.message) || 'Falha.', 'error');
			return;
		}
		renderLista(res.itens || []);
	}, 'json');
}

$(function () {
	carregar();

	$('#btn-novo-curso-cti').on('click', function () {
		Swal.fire({
			title: 'Novo curso CTI',
			input: 'text',
			inputPlaceholder: 'Título do curso',
			inputValue: 'Novo curso CTI',
			showCancelButton: true,
			confirmButtonText: 'Criar'
		}).then(function (r) {
			if (!r.isConfirmed) return;
			$.post(url_base + MASTER_EAD_CTI_URL, { acao: 'criar', titulo: r.value || '' }, function (res) {
				if (!res || !res.success) {
					Swal.fire('Erro', (res && res.message) || 'Falha.', 'error');
					return;
				}
				Swal.fire('OK', res.message || 'Criado.', 'success');
				carregar();
			}, 'json');
		});
	});

	$(document).on('click', '.btn-editar-cti', function () {
		const id = $(this).data('id');
		$.post(url_base + MASTER_EAD_CTI_URL, { acao: 'abrir_editor', id: id }, function (res) {
			if (!res || !res.success || !res.redirect) {
				Swal.fire('Erro', (res && res.message) || 'Falha.', 'error');
				return;
			}
			window.location.href = res.redirect;
		}, 'json');
	});

	$(document).on('click', '.btn-toggle-pub-cti', function () {
		const id = $(this).data('id');
		$.post(url_base + MASTER_EAD_CTI_URL, { acao: 'toggle_publicado', id: id }, function (res) {
			if (!res || !res.success) {
				Swal.fire('Erro', (res && res.message) || 'Falha.', 'error');
				return;
			}
			carregar();
		}, 'json');
	});

	$(document).on('click', '.btn-excluir-cti', function () {
		const id = $(this).data('id');
		Swal.fire({
			title: 'Excluir curso CTI?',
			text: 'Escolas perderão o provisionamento deste curso.',
			icon: 'warning',
			showCancelButton: true,
			confirmButtonText: 'Excluir',
			confirmButtonColor: '#dc3545'
		}).then(function (r) {
			if (!r.isConfirmed) return;
			$.post(url_base + MASTER_EAD_CTI_URL, { acao: 'excluir', id: id }, function (res) {
				if (!res || !res.success) {
					Swal.fire('Erro', (res && res.message) || 'Falha.', 'error');
					return;
				}
				carregar();
			}, 'json');
		});
	});
});

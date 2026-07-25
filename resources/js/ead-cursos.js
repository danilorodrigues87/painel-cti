function postEad(data) {
	return $.ajax({
		url: url_base + 'painel/ead',
		method: 'POST',
		dataType: 'json',
		data: data
	});
}

function badgeStatus(status) {
	if (status === 'publicado') return '<span class="badge bg-success">Publicado</span>';
	if (status === 'rascunho') return '<span class="badge bg-warning text-dark">Rascunho</span>';
	return '<span class="badge bg-secondary">Sem conteúdo</span>';
}

function carregarListaEad() {
	postEad({ acao: 'listar' }).done(function (res) {
		if (!res || !res.success) {
			if (res && res.sql_ok === false) {
				$('#alert-sql-ead').removeClass('d-none');
			}
			$('#ead-tbody').html('<tr><td colspan="6" class="text-danger">' + ((res && res.message) || 'Falha ao listar') + '</td></tr>');
			return;
		}
		$('#alert-sql-ead').addClass('d-none');
		if (res.xp_ok === false) {
			$('#alert-sql-xp').removeClass('d-none');
		} else {
			$('#alert-sql-xp').addClass('d-none');
		}
		if (res.matricula_ead_ok === false) {
			$('#alert-sql-ead').removeClass('d-none');
		}
		if (!res.itens || !res.itens.length) {
			$('#ead-tbody').html('<tr><td colspan="6" class="text-muted">Nenhum curso EAD. Clique em Novo curso.</td></tr>');
			return;
		}
		var html = '';
		res.itens.forEach(function (item) {
			var vit = !item.vitrine_ativo
				? '<span class="text-muted">—</span>'
				: (Number(item.vitrine_preco_mensal || 0) <= 0
					? '<span class="badge bg-success">Gratuito</span>'
					: '<span class="badge bg-info text-dark">R$ ' + Number(item.vitrine_preco_mensal || 0).toFixed(2) + '/mês</span>');
			html += '<tr>' +
				'<td>' + $('<div>').text(item.nome).html() + '</td>' +
				'<td>' + (item.carga_h || '—') + '</td>' +
				'<td>' + (item.aulas || 0) + '</td>' +
				'<td>' + badgeStatus(item.status) + '</td>' +
				'<td>' + vit + '</td>' +
				'<td><a class="btn btn-sm btn-primary" href="' + url_base + 'painel/ead/curso/' + item.id_curso + '">Editar</a></td>' +
				'</tr>';
		});
		$('#ead-tbody').html(html);
	}).fail(function () {
		$('#ead-tbody').html('<tr><td colspan="6" class="text-danger">Erro de rede.</td></tr>');
	});
}

$(function () {
	carregarListaEad();
	$('#btn-novo-curso').on('click', function () {
		Swal.fire({
			title: 'Novo curso EAD',
			input: 'text',
			inputLabel: 'Título',
			inputValue: 'Novo curso',
			showCancelButton: true,
			confirmButtonText: 'Criar'
		}).then(function (r) {
			if (!r.isConfirmed) return;
			postEad({ acao: 'criar_curso', titulo: r.value || 'Novo curso' }).done(function (res) {
				if (!res || !res.success) {
					Swal.fire('Erro', (res && res.message) || 'Falha', 'error');
					return;
				}
				window.location.href = url_base + 'painel/ead/curso/' + res.id_curso;
			});
		});
	});
});

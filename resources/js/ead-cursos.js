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
			$('#ead-tbody').html('<tr><td colspan="5" class="text-danger">' + ((res && res.message) || 'Falha ao listar') + '</td></tr>');
			return;
		}
		$('#alert-sql-ead').addClass('d-none');
		if (res.xp_ok === false) {
			$('#alert-sql-xp').removeClass('d-none');
		} else {
			$('#alert-sql-xp').addClass('d-none');
		}
		if (!res.itens || !res.itens.length) {
			$('#ead-tbody').html('<tr><td colspan="5" class="text-muted">Nenhuma trilha cadastrada. Crie em Pedagógico → Trilhas.</td></tr>');
			return;
		}
		var html = '';
		res.itens.forEach(function (item) {
			html += '<tr>' +
				'<td>' + $('<div>').text(item.nome).html() + '</td>' +
				'<td>' + (item.carga_h || '—') + '</td>' +
				'<td>' + (item.aulas || 0) + '</td>' +
				'<td>' + badgeStatus(item.status) + '</td>' +
				'<td><a class="btn btn-sm btn-primary" href="' + url_base + 'painel/ead/' + item.id_trilha + '">Editar conteúdo</a></td>' +
				'</tr>';
		});
		$('#ead-tbody').html(html);
	}).fail(function () {
		$('#ead-tbody').html('<tr><td colspan="5" class="text-danger">Erro de rede.</td></tr>');
	});
}

$(function () {
	carregarListaEad();
});

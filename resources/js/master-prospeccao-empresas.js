(function () {
	'use strict';

	var API = 'master/prospeccao-empresas';
	var page = 1;
	var googlePageToken = null;
	var googleQuery = '';

	function esc(s) {
		return $('<div>').text(s == null ? '' : String(s)).html();
	}

	function filtros() {
		return {
			q: ($('#filt-q').val() || '').trim(),
			com_whatsapp: $('#filt-whatsapp').is(':checked') ? 1 : 0
		};
	}

	function initGoogleUi() {
		var ok = window.PROSP_GOOGLE_OK === 1 || window.PROSP_GOOGLE_OK === '1';
		if (!ok) {
			$('#google-q, #btn-google-buscar').prop('disabled', true);
		}
	}

	function carregarStats() {
		$.post(url_base + API, { acao: 'stats' }, function (res) {
			if (!res || !res.success || !res.stats) return;
			var s = res.stats;
			$('#stat-total').text(s.total != null ? s.total : '—');
			$('#stat-whatsapp').text(s.comWhatsapp != null ? s.comWhatsapp : '—');
			$('#stat-ultima').text(s.ultimaImportacaoBr || '—');
		}, 'json');
	}

	function carregarLista(p) {
		page = p || 1;
		var payload = $.extend({ acao: 'listar', page: page }, filtros());
		$('#tb-empresas').html('<tr><td colspan="8" class="text-muted text-center py-3">Carregando…</td></tr>');

		$.post(url_base + API, payload, function (res) {
			if (!res || !res.success) {
				var msg = (res && res.message) ? res.message : 'Erro ao carregar';
				$('#tb-empresas').html('<tr><td colspan="8" class="text-danger text-center py-3">' + esc(msg) + '</td></tr>');
				return;
			}
			var $tb = $('#tb-empresas').empty();
			if (!res.items || !res.items.length) {
				$tb.append('<tr><td colspan="8" class="text-muted text-center py-4">Nenhuma empresa salva. Importe do Google acima.</td></tr>');
			} else {
				res.items.forEach(function (r) {
					var wa = r.whatsappUrl
						? '<a href="' + esc(r.whatsappUrl) + '" target="_blank" rel="noopener" class="small">' + esc(r.telefone || r.whatsappDigits) + '</a>'
						: '<span class="text-muted">—</span>';
					var maps = r.mapsUrl
						? '<a href="' + esc(r.mapsUrl) + '" target="_blank" rel="noopener" class="small">Abrir</a>'
						: '<span class="text-muted">—</span>';
					var nota = r.nota != null && r.nota !== '' ? esc(String(r.nota)) : '—';
					$tb.append(
						'<tr>'
						+ '<td><div class="fw-medium">' + esc(r.nome) + '</div>'
						+ (r.queryOrigem ? '<div class="text-muted" style="font-size:11px">' + esc(r.queryOrigem) + '</div>' : '')
						+ '</td>'
						+ '<td class="small">' + esc(r.endereco || '—') + '</td>'
						+ '<td class="small text-nowrap">' + esc(r.telefone || '—') + '</td>'
						+ '<td>' + wa + '</td>'
						+ '<td>' + maps + '</td>'
						+ '<td>' + nota + '</td>'
						+ '<td class="small text-nowrap">' + esc(r.importadoEmBr || '—') + '</td>'
						+ '<td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger btn-excluir" data-id="' + esc(r.id) + '" title="Excluir"><i class="fas fa-trash"></i></button></td>'
						+ '</tr>'
					);
				});
			}
			var total = res.total || 0;
			var pages = res.pages || 1;
			$('#lista-info').text(total + ' registro(s) · página ' + page + ' de ' + pages);
			$('#btn-prev').prop('disabled', page <= 1);
			$('#btn-next').prop('disabled', page >= pages);
		}, 'json').fail(function () {
			$('#tb-empresas').html('<tr><td colspan="8" class="text-danger text-center py-3">Falha de rede</td></tr>');
		});
	}

	function buscarGoogle(more) {
		var q = ($('#google-q').val() || '').trim();
		if (!more) {
			googleQuery = q;
			googlePageToken = null;
		}
		if (!googleQuery || googleQuery.length < 3) {
			Swal.fire('Atenção', 'Informe pelo menos 3 caracteres na busca Google.', 'warning');
			return;
		}

		$('#btn-google-buscar, #btn-google-mais').prop('disabled', true);
		var payload = { acao: 'buscar', q: googleQuery };
		if (more && googlePageToken) {
			payload.pageToken = googlePageToken;
		}

		$.post(url_base + API, payload, function (res) {
			$('#btn-google-buscar, #btn-google-mais').prop('disabled', false);
			if (!res || !res.success) {
				Swal.fire('Erro', (res && res.message) || 'Falha na busca Google.', 'error');
				return;
			}
			googlePageToken = res.nextPageToken || null;
			if (googlePageToken) {
				$('#btn-google-mais').removeClass('d-none');
			} else {
				$('#btn-google-mais').addClass('d-none');
			}
			$('#google-info').removeClass('d-none').text(res.message || 'Importação concluída.');
			var icon = (res.totalPagina || 0) > 0 ? 'success' : 'info';
			var title = (res.totalPagina || 0) > 0 ? 'Importado' : 'Sem resultados nesta página';
			Swal.fire(title, res.message || 'Dados salvos no banco.', icon);
			carregarStats();
			carregarLista(1);
		}, 'json').fail(function () {
			$('#btn-google-buscar, #btn-google-mais').prop('disabled', false);
			Swal.fire('Erro', 'Falha de rede ao buscar no Google.', 'error');
		});
	}

	function excluir(id) {
		Swal.fire({
			title: 'Excluir registro?',
			text: 'Remove da base local (não afeta o Google).',
			icon: 'warning',
			showCancelButton: true,
			confirmButtonText: 'Excluir',
			cancelButtonText: 'Cancelar'
		}).then(function (result) {
			if (!result.isConfirmed) return;
			$.post(url_base + API, { acao: 'excluir', id: id }, function (res) {
				if (!res || !res.success) {
					Swal.fire('Erro', (res && res.message) || 'Falha ao excluir.', 'error');
					return;
				}
				carregarStats();
				carregarLista(page);
			}, 'json');
		});
	}

	function exportarCsv() {
		var $form = $('<form method="post" action="' + url_base + API + '/export" target="_blank"></form>');
		var data = filtros();
		Object.keys(data).forEach(function (k) {
			$form.append($('<input type="hidden">').attr('name', k).val(data[k]));
		});
		$('body').append($form);
		$form.trigger('submit');
		$form.remove();
	}

	$('#btn-filtrar').on('click', function () { carregarLista(1); });
	$('#filt-whatsapp').on('change', function () { carregarLista(1); });
	$('#btn-prev').on('click', function () { if (page > 1) carregarLista(page - 1); });
	$('#btn-next').on('click', function () { carregarLista(page + 1); });
	$('#btn-google-buscar').on('click', function () { buscarGoogle(false); });
	$('#btn-google-mais').on('click', function () { buscarGoogle(true); });
	$('#btn-export-csv').on('click', exportarCsv);
	$('#tb-empresas').on('click', '.btn-excluir', function () {
		excluir($(this).data('id'));
	});

	initGoogleUi();
	carregarStats();
	carregarLista(1);
})();

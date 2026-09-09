(function () {
	'use strict';

	var API = url_base + 'painel/marketing/biblioteca';
	var UPLOAD = url_base + 'painel/marketing/biblioteca/upload';
	var S = window.SocialBibShared;
	var bibFiltroFormato = 'feed';
	var bibFiltroTipo = 'image';

	function postApi(data, cb) {
		$.post(API, data, cb, 'json').fail(function () {
			Swal.fire('Erro', 'Falha na requisição.', 'error');
		});
	}

	function loadBiblioteca() {
		var payload = { acao: 'listar' };
		if (bibFiltroTipo) payload.tipo = bibFiltroTipo;
		if (bibFiltroFormato) payload.formato = bibFiltroFormato;
		postApi(payload, function (r) {
			if (!r || r.sql_ok === false) {
				$('#bib-grid').html('<div class="col-12 text-warning">' + S.esc((r && r.message) || 'SQL pendente') + '</div>');
				return;
			}
			if (r.stats) {
				$('#bib-stats').text(r.stats.total_itens + ' arquivo(s) · ' + (r.stats.total_bytes_fmt || S.formatBytes(r.stats.total_bytes)));
			}
			S.renderGrid('#bib-grid', (r && r.itens) || [], { pickMode: false });
		});
	}

	function uploadOne(file, formato) {
		var fd = new FormData();
		fd.append('arquivo', file);
		if (formato) fd.append('formato', formato);
		return $.ajax({
			url: UPLOAD,
			method: 'POST',
			data: fd,
			processData: false,
			contentType: false,
			dataType: 'json'
		});
	}

	function uploadFiles(files) {
		var list = Array.prototype.slice.call(files || []);
		var chain = $.Deferred().resolve(0).promise();
		var ok = 0;
		list.forEach(function (file) {
			chain = chain.then(function () {
				var fmt = S.guessTipo(file.type || file.name) === 'video' ? '' : ($('#bib-upload-formato').val() || 'feed');
				return uploadOne(file, fmt).then(function (r) {
					if (r && r.success) ok++;
					return ok;
				}, function () { return ok; });
			});
		});
		return chain;
	}

	$(function () {
		loadBiblioteca();

		$('#bib-filtro-formato .nav-link').on('click', function () {
			bibFiltroFormato = String($(this).data('formato') || '');
			bibFiltroTipo = String($(this).data('tipo') || '');
			$('#bib-filtro-formato .nav-link').removeClass('active');
			$(this).addClass('active');
			if (bibFiltroFormato === 'feed' || bibFiltroFormato === 'story') {
				$('#bib-upload-formato').val(bibFiltroFormato);
			}
			loadBiblioteca();
		});

		$('#bib-upload').on('change', function () {
			var files = this.files;
			if (!files || !files.length) return;
			$('#bib-upload').prop('disabled', true);
			uploadFiles(files).always(function (ok) {
				$('#bib-upload').prop('disabled', false).val('');
				loadBiblioteca();
				if (!ok) Swal.fire('Upload', 'Nenhum arquivo enviado com sucesso.', 'warning');
			});
		});

		$('#btn-bib-refresh').on('click', loadBiblioteca);

		$(document).on('click', '.bib-ver, .bib-thumb', function (e) {
			e.preventDefault();
			e.stopPropagation();
			S.abrirPreview($(this).data('url'), $(this).data('tipo'), $(this).data('titulo'));
		});

		$(document).on('click', '.bib-edit', function () {
			var id = parseInt($(this).data('id'), 10);
			var atual = String($(this).data('titulo') || '');
			Swal.fire({
				title: 'Editar título',
				input: 'text',
				inputValue: atual,
				showCancelButton: true,
				confirmButtonText: 'Salvar'
			}).then(function (r) {
				if (!r.isConfirmed) return;
				postApi({ acao: 'salvar', id: id, titulo: r.value || '' }, function (res) {
					if (!res || !res.success) {
						Swal.fire('Erro', (res && res.message) || 'Falha.', 'error');
						return;
					}
					loadBiblioteca();
				});
			});
		});

		$(document).on('click', '.bib-del', function () {
			var id = parseInt($(this).data('id'), 10);
			Swal.fire({
				title: 'Excluir mídia?',
				text: 'O arquivo será removido do servidor se não estiver em uso.',
				icon: 'warning',
				showCancelButton: true,
				confirmButtonColor: '#dc3545',
				confirmButtonText: 'Excluir'
			}).then(function (r) {
				if (!r.isConfirmed) return;
				postApi({ acao: 'excluir', id: id }, function (res) {
					if (!res || !res.success) {
						var extra = '';
						if (res && res.usos && res.usos.length) {
							extra = '<ul class="text-start small mt-2">' + res.usos.map(function (u) {
								return '<li>' + S.esc(u.detalhe || u.ref) + '</li>';
							}).join('') + '</ul>';
						}
						Swal.fire({
							title: 'Não foi possível',
							html: S.esc((res && res.message) || 'Falha.') + extra,
							icon: 'error'
						});
						return;
					}
					Swal.fire('OK', res.message || 'Removida.', 'success');
					loadBiblioteca();
				});
			});
		});

		$('#modalBibPreview').on('hidden.bs.modal', function () {
			$('#bib-preview-body').empty();
		});
	});
})();

var semanaInicio = null;
var postsCache = [];
var midiasPendentes = [];
var diasNomes = ['Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado', 'Domingo'];

function postSocial(data) {
	return $.ajax({
		url: url_base + 'painel/social',
		method: 'POST',
		dataType: 'json',
		data: data
	});
}

function ymd(d) {
	var y = d.getFullYear();
	var m = String(d.getMonth() + 1).padStart(2, '0');
	var day = String(d.getDate()).padStart(2, '0');
	return y + '-' + m + '-' + day;
}

function mondayOf(date) {
	var d = new Date(date.getFullYear(), date.getMonth(), date.getDate());
	var day = d.getDay();
	var diff = day === 0 ? -6 : 1 - day;
	d.setDate(d.getDate() + diff);
	return d;
}

function addDays(d, n) {
	var x = new Date(d.getTime());
	x.setDate(x.getDate() + n);
	return x;
}

function statusBadge(st) {
	var map = {
		rascunho: 'secondary',
		agendado: 'primary',
		publicando: 'warning',
		publicado: 'success',
		erro: 'danger',
		cancelado: 'dark'
	};
	return '<span class="badge bg-' + (map[st] || 'secondary') + '">' + st + '</span>';
}

function formatoBadge(f) {
	var map = { feed: 'Feed', story: 'Story', reel: 'Reel', carousel: 'Carrossel' };
	return '<span class="badge bg-info text-dark">' + (map[f] || f || 'Feed') + '</span>';
}

function esc(s) {
	return $('<div>').text(s == null ? '' : String(s)).html();
}

function syncFormatoUi() {
	var f = $('#post_formato').val();
	var igOnly = f === 'story' || f === 'reel';
	$('#aviso-formato-ig').toggleClass('d-none', !igOnly);
	if (igOnly) {
		$('#post_canais').val('instagram').prop('disabled', true);
	} else {
		$('#post_canais').prop('disabled', false);
	}
	var multi = f === 'carousel';
	$('#post_arquivo').prop('multiple', multi);
	if (f === 'reel') {
		$('#post_arquivo').attr('accept', 'video/mp4,video/quicktime');
	} else if (f === 'feed') {
		$('#post_arquivo').attr('accept', 'image/jpeg,image/png,image/webp');
	} else {
		$('#post_arquivo').attr('accept', 'image/jpeg,image/png,image/webp,video/mp4,video/quicktime');
	}
}

function renderPreviewMidias() {
	var html = '';
	midiasPendentes.forEach(function (m, i) {
		html += '<div class="border rounded p-1 position-relative" style="width:72px">';
		if (m.tipo === 'video') {
			html += '<div class="small text-center py-3">▶</div>';
		} else {
			html += '<img src="' + esc(m.url) + '" alt="" style="width:64px;height:64px;object-fit:cover" class="rounded">';
		}
		html += '<button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 py-0 px-1 btn-rm-midia" data-i="' + i + '">×</button></div>';
	});
	$('#post_preview_list').html(html);
}

function carregarSemana() {
	if (!semanaInicio) semanaInicio = mondayOf(new Date());
	var inicio = ymd(semanaInicio);
	$('#label-semana').text(inicio + ' → ' + ymd(addDays(semanaInicio, 6)));

	postSocial({ acao: 'semana', inicio: inicio }).done(function (res) {
		if (!res || !res.success) {
			if (res && res.sql_ok === false) $('#alert-sql-social').removeClass('d-none');
			$('#tbody-semana').html('<tr><td colspan="7" class="text-danger p-3">' + esc((res && res.message) || 'Erro') + '</td></tr>');
			return;
		}
		$('#alert-sql-social').addClass('d-none');
		postsCache = res.itens || [];
		renderGrade();
		renderLista();
	});

	postSocial({ acao: 'status_meta' }).done(function (res) {
		if (res && res.success && !res.pronto) $('#alert-meta-off').removeClass('d-none');
		else $('#alert-meta-off').addClass('d-none');
	});
}

function renderGrade() {
	var th = '<th style="width:4rem">Hora</th>';
	for (var i = 0; i < 7; i++) {
		var d = addDays(semanaInicio, i);
		th += '<th class="small">' + diasNomes[i] + '<br><span class="text-muted">' + ymd(d).slice(5) + '</span></th>';
	}
	$('#thead-dias').html(th);

	var byDay = [[], [], [], [], [], [], []];
	postsCache.forEach(function (p) {
		if (!p.agendado_em) return;
		var dt = p.agendado_em.replace(' ', 'T');
		var d = new Date(dt);
		var mon = mondayOf(d);
		if (ymd(mon) !== ymd(semanaInicio)) return;
		var idx = (d.getDay() + 6) % 7;
		byDay[idx].push(p);
	});

	var horas = {};
	postsCache.forEach(function (p) {
		if (!p.agendado_em) return;
		horas[p.agendado_em.substr(11, 5)] = true;
	});
	var listaH = Object.keys(horas).sort();
	if (!listaH.length) listaH = ['09:00', '12:00', '18:00'];

	var html = '';
	listaH.forEach(function (hora) {
		html += '<tr><td class="small text-muted">' + esc(hora) + '</td>';
		for (var i = 0; i < 7; i++) {
			var cell = byDay[i].filter(function (p) {
				return (p.agendado_em || '').substr(11, 5) === hora;
			});
			html += '<td class="p-1 align-top">';
			cell.forEach(function (p) {
				var thumb = (p.midias && p.midias[0] && p.midias[0].url && p.midias[0].tipo !== 'video')
					? '<img src="' + esc(p.midias[0].url) + '" alt="" style="width:40px;height:40px;object-fit:cover" class="rounded me-1">'
					: '';
				html += '<button type="button" class="btn btn-sm btn-light border w-100 text-start mb-1 btn-post-item" data-id="' + p.id + '">' +
					'<div class="d-flex align-items-start">' + thumb +
					'<div class="small flex-grow-1">' + statusBadge(p.status) + ' ' + formatoBadge(p.formato) +
					'<div class="text-truncate" style="max-width:9rem">' + esc((p.caption || '').slice(0, 40)) + '</div>' +
					'<div class="text-muted">' + esc(p.canais) + '</div></div></div></button>';
			});
			html += '</td>';
		}
		html += '</tr>';
	});
	$('#tbody-semana').html(html || '<tr><td colspan="8" class="text-muted p-3">Nenhum post nesta semana.</td></tr>');
}

function renderLista() {
	if (!postsCache.length) {
		$('#lista-posts').html('<li class="list-group-item text-muted small">Nenhum post.</li>');
		return;
	}
	var html = '';
	postsCache.forEach(function (p) {
		html += '<li class="list-group-item list-group-item-action btn-post-item py-2" data-id="' + p.id + '" style="cursor:pointer">' +
			'<div class="d-flex justify-content-between"><span class="small">' + esc(p.agendado_em || '—') + '</span>' + statusBadge(p.status) + '</div>' +
			'<div class="small">' + formatoBadge(p.formato) + ' · ' + esc(p.canais) + '</div>' +
			'<div class="small text-truncate">' + esc(p.caption || '(sem legenda)') + '</div></li>';
	});
	$('#lista-posts').html(html);
}

function mostrarDetalhe(id) {
	var p = postsCache.find(function (x) { return Number(x.id) === Number(id); });
	if (!p) {
		$('#painel-detalhe').html('<p class="text-muted small">Post não encontrado.</p>');
		return;
	}
	var imgs = '';
	(p.midias || []).forEach(function (m) {
		if (m.url && m.tipo !== 'video') {
			imgs += '<img src="' + esc(m.url) + '" class="img-fluid rounded border mb-1 me-1" style="max-height:100px" alt="">';
		} else if (m.tipo === 'video') {
			imgs += '<div class="badge bg-secondary mb-1">Vídeo</div> ';
		}
	});
	var html = imgs +
		'<div class="mb-2">' + statusBadge(p.status) + ' ' + formatoBadge(p.formato) +
		' <span class="small text-muted">' + esc(p.canais) + '</span></div>' +
		'<div class="small mb-2"><strong>Quando:</strong> ' + esc(p.agendado_em || '—') + '</div>' +
		'<p class="small">' + esc(p.caption || '') + '</p>';
	if (p.erro_msg) html += '<div class="alert alert-danger small py-2">' + esc(p.erro_msg) + '</div>';
	if (p.fb_post_id) html += '<div class="small text-muted">FB: ' + esc(p.fb_post_id) + '</div>';
	if (p.ig_media_id) html += '<div class="small text-muted">IG: ' + esc(p.ig_media_id) + '</div>';
	html += '<div class="d-flex flex-wrap gap-1 mt-3">';
	if (p.status === 'agendado' || p.status === 'erro' || p.status === 'rascunho') {
		html += '<button type="button" class="btn btn-sm btn-success" id="btn-pub-agora" data-id="' + p.id + '">Publicar agora</button>';
		html += '<button type="button" class="btn btn-sm btn-outline-danger" id="btn-cancelar-post" data-id="' + p.id + '">Cancelar</button>';
	}
	html += '</div>';
	$('#painel-detalhe').html(html);
}

function abrirModalNovo() {
	$('#post_id').val('');
	$('#post_caption').val('');
	$('#caption_count').text('0');
	$('#post_formato').val('feed');
	$('#post_canais').val('ambos').prop('disabled', false);
	$('#post_agendado').val('');
	$('#post_lote').val('');
	$('#post_url').val('');
	$('#post_arquivo').val('');
	midiasPendentes = [];
	renderPreviewMidias();
	syncFormatoUi();
	var el = document.getElementById('modalPost');
	if (window.bootstrap && bootstrap.Modal) bootstrap.Modal.getOrCreateInstance(el).show();
	else $(el).modal('show');
}

function uploadArquivo(file) {
	var fd = new FormData();
	fd.append('arquivo', file);
	return $.ajax({
		url: url_base + 'painel/social/upload',
		method: 'POST',
		data: fd,
		processData: false,
		contentType: false,
		dataType: 'json'
	});
}

$(function () {
	semanaInicio = mondayOf(new Date());
	carregarSemana();

	$('#btn-semana-prev').on('click', function () {
		semanaInicio = addDays(semanaInicio, -7);
		carregarSemana();
	});
	$('#btn-semana-next').on('click', function () {
		semanaInicio = addDays(semanaInicio, 7);
		carregarSemana();
	});
	$('#btn-novo-post').on('click', abrirModalNovo);
	$('#post_formato').on('change', syncFormatoUi);

	$('#post_caption').on('input', function () {
		$('#caption_count').text(String($(this).val() || '').length);
	});

	$('#post_arquivo').on('change', function () {
		var files = this.files ? Array.prototype.slice.call(this.files) : [];
		if (!files.length) return;
		var f = $('#post_formato').val();
		var max = f === 'carousel' ? 10 : 1;
		if (f === 'carousel' && midiasPendentes.length + files.length > 10) {
			return Swal.fire('Carrossel', 'Máximo 10 mídias.', 'warning');
		}
		if (f !== 'carousel') midiasPendentes = [];
		var chain = $.Deferred().resolve();
		files.slice(0, max).forEach(function (file) {
			chain = chain.then(function () {
				return uploadArquivo(file).then(function (res) {
					if (!res || !res.success) {
						Swal.fire('Upload', (res && res.message) || 'Falha', 'error');
						return;
					}
					midiasPendentes.push({ path: res.path, url: res.url, tipo: res.tipo || 'image' });
					$('#post_url').val('');
					renderPreviewMidias();
				});
			});
		});
		$(this).val('');
	});

	$(document).on('click', '.btn-rm-midia', function () {
		midiasPendentes.splice(Number($(this).data('i')), 1);
		renderPreviewMidias();
	});

	$('#btn-salvar-post').on('click', function () {
		var loteLines = ($('#post_lote').val() || '').split(/\r?\n/).map(function (s) { return s.trim(); }).filter(Boolean);
		var midias = midiasPendentes.map(function (m) {
			return { path: m.path, tipo: m.tipo };
		});
		var url = ($('#post_url').val() || '').trim();
		if (!midias.length && url) {
			var f = $('#post_formato').val();
			midias.push({ url_externa: url, tipo: f === 'reel' ? 'video' : 'image' });
		}
		postSocial({
			acao: 'salvar',
			caption: $('#post_caption').val(),
			formato: $('#post_formato').val(),
			canais: $('#post_canais').val(),
			agendado_em: $('#post_agendado').val(),
			status: 'agendado',
			midias: JSON.stringify(midias),
			lote_horarios: JSON.stringify(loteLines)
		}).done(function (res) {
			if (!res || !res.success) return Swal.fire('Erro', (res && res.message) || 'Falha', 'error');
			Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: res.message, showConfirmButton: false, timer: 2200 });
			var el = document.getElementById('modalPost');
			if (window.bootstrap && bootstrap.Modal) bootstrap.Modal.getOrCreateInstance(el).hide();
			else $(el).modal('hide');
			carregarSemana();
		});
	});

	$(document).on('click', '.btn-post-item', function () {
		mostrarDetalhe($(this).data('id'));
	});
	$(document).on('click', '#btn-cancelar-post', function () {
		var id = $(this).data('id');
		Swal.fire({ title: 'Cancelar post?', showCancelButton: true, confirmButtonText: 'Sim' }).then(function (r) {
			if (!r.isConfirmed) return;
			postSocial({ acao: 'cancelar', id: id }).done(function () {
				carregarSemana();
				$('#painel-detalhe').html('<p class="text-muted small">Cancelado.</p>');
			});
		});
	});
	$(document).on('click', '#btn-pub-agora', function () {
		var id = $(this).data('id');
		postSocial({ acao: 'publicar_agora', id: id }).done(function (res) {
			Swal.fire(res && res.success ? 'OK' : 'Falha', (res && res.message) || '', res && res.success ? 'success' : 'error');
			carregarSemana();
			mostrarDetalhe(id);
		});
	});
	$('#btn-worker').on('click', function () {
		postSocial({ acao: 'worker' }).done(function (res) {
			var r = (res && res.resumo) || {};
			Swal.fire('Worker', 'Processados: ' + (r.processados || 0) + ' · OK: ' + (r.ok || 0) + ' · Erro: ' + (r.erro || 0), 'info');
			carregarSemana();
		});
	});
});

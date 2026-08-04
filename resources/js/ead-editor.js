function postEad(data) {
	return $.ajax({
		url: url_base + 'painel/ead',
		method: 'POST',
		dataType: 'json',
		data: data
	});
}

function toastOk(msg) {
	Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: msg, showConfirmButton: false, timer: 1800 });
}

function toastErr(msg) {
	Swal.fire('Erro', msg || 'Falha', 'error');
}

var estadoAula = null;
var bunnyOk = false;
var bunnyMotivo = '';

function idCurso() {
	return parseInt($('#id_curso').val() || '0', 10);
}

function idAula() {
	return parseInt($('#aula_id').val() || '0', 10);
}

function carregarGeral() {
	postEad({ acao: 'carregar_curso', id_curso: idCurso() }).done(function (res) {
		if (!res || !res.success) {
			toastErr(res && res.message);
			return;
		}
		var c = res.curso;
		$('#id_curso').val(c.id);
		$('#titulo').val(c.titulo || '');
		$('#carga_h').val(c.carga_h || '');
		$('#slug').val(c.slug || '');
		$('#short_description').val(c.short_description || '');
		$('#cover_url').val(c.cover_url || '');
		$('#banner_url').val(c.banner_url || '');
		$('#level').val(c.level || 'Iniciante');
		$('#objectives_text').val(c.objectives_text || '');
		$('#instructor_name').val(c.instructor_name || '');
		$('#instructor_title').val(c.instructor_title || '');
		$('#instructor_bio').val(c.instructor_bio || '');
		$('#instructor_avatar_url').val(c.instructor_avatar_url || '');
		$('#publicado').prop('checked', !!c.publicado);
		if (c.vitrine_ok) {
			$('#bloco-vitrine').removeClass('d-none');
			$('#vitrine_ativo').prop('checked', !!c.vitrine_ativo);
			var preco = Number(c.vitrine_preco_mensal || 0);
			$('#vitrine_preco_mensal').val(preco);
			$('#vitrine_gratuito').prop('checked', !!c.vitrine_ativo && preco <= 0);
			$('#vitrine_descricao').val(c.vitrine_descricao || '');
			$('#vitrine_preco_mensal').prop('disabled', $('#vitrine_gratuito').is(':checked'));
		}
		listarAulas();
		listarMatriculasEad();
	});
}

function salvarGeral() {
	var gratuito = $('#vitrine_gratuito').is(':checked');
	if (gratuito) {
		$('#vitrine_preco_mensal').val(0);
	}
	postEad({
		acao: 'salvar_geral',
		id_curso: idCurso(),
		titulo: $('#titulo').val(),
		carga_h: $('#carga_h').val(),
		slug: $('#slug').val(),
		short_description: $('#short_description').val(),
		cover_url: $('#cover_url').val(),
		banner_url: $('#banner_url').val(),
		level: $('#level').val(),
		objectives_text: $('#objectives_text').val(),
		instructor_name: $('#instructor_name').val(),
		instructor_title: $('#instructor_title').val(),
		instructor_bio: $('#instructor_bio').val(),
		instructor_avatar_url: $('#instructor_avatar_url').val(),
		publicado: $('#publicado').is(':checked') ? '1' : '0',
		vitrine_ativo: $('#vitrine_ativo').is(':checked') ? '1' : '0',
		vitrine_preco_mensal: gratuito ? '0' : $('#vitrine_preco_mensal').val(),
		vitrine_descricao: $('#vitrine_descricao').val()
	}).done(function (res) {
		if (!res || !res.success) return toastErr(res && res.message);
		if (res.slug) $('#slug').val(res.slug);
		toastOk(res.message);
	});
}

function listarMatriculasEad() {
	postEad({ acao: 'listar_matriculas_ead', id_curso: idCurso() }).done(function (res) {
		if (!res || !res.success) {
			$('#tbody-matriculas-ead').html('<tr><td colspan="4" class="text-danger">' + ((res && res.message) || 'Erro') + '</td></tr>');
			return;
		}
		if (!res.itens || !res.itens.length) {
			$('#tbody-matriculas-ead').html('<tr><td colspan="4" class="text-muted">Nenhum aluno matriculado neste curso.</td></tr>');
			return;
		}
		var html = '';
		res.itens.forEach(function (a) {
			html += '<tr>' +
				'<td>' + $('<div>').text(a.nome).html() + '</td>' +
				'<td>' + $('<div>').text(a.email || '').html() + '</td>' +
				'<td>' + (a.inicio || '—') + '</td>' +
				'<td><button type="button" class="btn btn-sm btn-outline-danger btn-desmatricular" data-id="' + a.id_aluno + '">Remover</button></td>' +
				'</tr>';
		});
		$('#tbody-matriculas-ead').html(html);
	});
}

function listarAulas() {
	postEad({ acao: 'listar_aulas', id_curso: idCurso() }).done(function (res) {
		if (!res || !res.success) return;
		var html = '';
		(res.aulas || []).forEach(function (a) {
			var meta = '';
			if (a.tipo_conteudo === 'interativa') {
				meta = '<span class="badge bg-primary me-1">Interativa</span>' +
					'<small class="text-muted">' + (a.cenas || 0) + ' cena(s)' +
					(a.interativa_status ? ' · ' + a.interativa_status : '') + '</small>';
			} else {
				meta = '<small class="text-muted">' + a.videos + ' vídeos · ' + a.materiais + ' mats · ' + a.atividades + ' ativ.</small>';
			}
			html += '<button type="button" class="list-group-item list-group-item-action aula-item" data-id="' + a.id + '">' +
				'<div class="fw-semibold">' + $('<div>').text(a.titulo).html() + '</div>' +
				meta +
				'</button>';
		});
		$('#lista-aulas').html(html || '<div class="text-muted small p-2">Nenhuma aula ainda.</div>');
	});
}

function abrirAula(id) {
	postEad({ acao: 'carregar_aula', id_aula: id }).done(function (res) {
		if (!res || !res.success) return toastErr(res && res.message);
		estadoAula = res;
		bunnyOk = !!res.bunny_ok;
		bunnyMotivo = res.bunny_motivo || '';
		$('#aula-placeholder').addClass('d-none');
		$('#painel-aula').removeClass('d-none');
		var a = res.aula;
		$('#aula_id').val(a.id);
		$('#aula_titulo').val(a.titulo);
		$('#aula_descricao').val(a.descricao || '');
		$('#aula_ordem').val(a.ordem);
		$('#aula_bloqueado').prop('checked', !!a.bloqueado);
		renderVideos(res.videos || []);
		renderMateriais(res.materiais || []);
		renderAtividades(res.atividades || []);
		renderRoleplays(res.roleplays || []);
	});
}

function renderVideos(list) {
	var html = '';
	list.forEach(function (v) {
		var meta = '';
		if (v.provider === 'bunny') {
			var st = v.bunny_status || '—';
			meta = '<span class="badge bg-info text-dark me-1">Bunny</span>' +
				'<span class="badge bg-secondary">' + $('<div>').text(st).html() + '</span>';
			if (v.bunny_error) {
				meta += '<br><small class="text-danger">' + $('<div>').text(v.bunny_error).html() + '</small>';
			}
		} else {
			meta = '<span class="badge bg-danger me-1">YouTube</span><small class="text-break">' + $('<div>').text(v.url || '').html() + '</small>';
		}
		html += '<li class="list-group-item d-flex justify-content-between align-items-start gap-2">' +
			'<div class="flex-grow-1"><strong>' + $('<div>').text(v.titulo || 'Vídeo').html() + '</strong><br>' + meta + '</div>' +
			'<div class="btn-group-vertical">' +
			(v.provider === 'bunny' ? '' : '<button type="button" class="btn btn-sm btn-outline-secondary btn-edit-video" data-id="' + v.id + '">Editar</button>') +
			'<button type="button" class="btn btn-sm btn-outline-danger btn-del-video" data-id="' + v.id + '">Excluir</button></div></li>';
	});
	$('#lista-videos').html(html || '<li class="list-group-item text-muted">Nenhum vídeo.</li>');
}

function renderMateriais(list) {
	var html = '';
	list.forEach(function (m) {
		html += '<li class="list-group-item d-flex justify-content-between gap-2">' +
			'<div class="flex-grow-1"><span class="badge bg-light text-dark me-1">' + m.tipo + '</span> ' + $('<div>').text(m.label).html() +
			'<br><small class="text-break">' + $('<div>').text(m.url).html() + '</small></div>' +
			'<div class="btn-group-vertical">' +
			'<button type="button" class="btn btn-sm btn-outline-secondary btn-edit-material" data-id="' + m.id + '">Editar</button>' +
			'<button type="button" class="btn btn-sm btn-outline-danger btn-del-material" data-id="' + m.id + '">Excluir</button></div></li>';
	});
	$('#lista-materiais').html(html || '<li class="list-group-item text-muted">Nenhum material.</li>');
}

function findAtividade(id) {
	if (!estadoAula || !estadoAula.atividades) return null;
	return estadoAula.atividades.find(function (a) { return String(a.id) === String(id); }) || null;
}

function findQuestao(idAtiv, idQ) {
	var at = findAtividade(idAtiv);
	if (!at) return null;
	return (at.questoes || []).find(function (q) { return String(q.id) === String(idQ); }) || null;
}

function findRoleplay(id) {
	if (!estadoAula || !estadoAula.roleplays) return null;
	return estadoAula.roleplays.find(function (r) { return String(r.id) === String(id); }) || null;
}

function findVideo(id) {
	if (!estadoAula || !estadoAula.videos) return null;
	return estadoAula.videos.find(function (v) { return String(v.id) === String(id); }) || null;
}

function findMaterial(id) {
	if (!estadoAula || !estadoAula.materiais) return null;
	return estadoAula.materiais.find(function (m) { return String(m.id) === String(id); }) || null;
}

function renderAtividades(list) {
	var html = '';
	list.forEach(function (at) {
		html += '<div class="border rounded p-2 mb-2">' +
			'<div class="d-flex justify-content-between align-items-start gap-2">' +
			'<div><strong>' + $('<div>').text(at.titulo).html() + '</strong>' +
			'<br><small class="text-muted">' + (at.questoes || []).length + ' questão(ões) · ' +
			(at.tentativas_max || 3) + ' tentativas · ' + (at.duracao_min || 30) + ' min</small></div>' +
			'<div class="btn-group">' +
			'<button type="button" class="btn btn-sm btn-outline-info btn-preview-atividade" data-id="' + at.id + '">Preview</button>' +
			'<button type="button" class="btn btn-sm btn-outline-secondary btn-edit-atividade" data-id="' + at.id + '">Editar</button>' +
			'<button type="button" class="btn btn-sm btn-outline-danger btn-del-atividade" data-id="' + at.id + '">Excluir</button>' +
			'</div></div>' +
			'<div class="mt-2"><button type="button" class="btn btn-sm btn-outline-primary btn-add-questao" data-id="' + at.id + '">+ Questão</button></div>' +
			'<ul class="mt-2 mb-0 small list-unstyled">';
		(at.questoes || []).forEach(function (q) {
			html += '<li class="d-flex justify-content-between align-items-center border-top py-1">' +
				'<span><span class="badge bg-secondary me-1">' + q.tipo + '</span> ' +
				$('<div>').text(q.enunciado || '').html().slice(0, 90) + '</span>' +
				'<span class="text-nowrap">' +
				'<button type="button" class="btn btn-link btn-sm py-0 btn-edit-questao" data-ativ="' + at.id + '" data-id="' + q.id + '">editar</button>' +
				'<button type="button" class="btn btn-link btn-sm text-danger py-0 btn-del-questao" data-id="' + q.id + '">x</button>' +
				'</span></li>';
		});
		html += '</ul></div>';
	});
	$('#lista-atividades').html(html || '<p class="text-muted mb-0">Nenhuma atividade.</p>');
}

function renderRoleplays(list) {
	var html = '';
	list.forEach(function (rp) {
		html += '<div class="border rounded p-2 mb-2">' +
			'<div class="d-flex justify-content-between align-items-start gap-2">' +
			'<div><strong>' + $('<div>').text(rp.titulo).html() + '</strong>' +
			'<br><small>' + $('<div>').text(rp.tema || '').html() +
			' · personagem: ' + $('<div>').text(rp.ai_character_name || '—').html() +
			' · ' + (rp.estimated_minutes || 15) + ' min · mín. ' + (rp.min_score || 70) + '%</small></div>' +
			'<div class="btn-group">' +
			'<button type="button" class="btn btn-sm btn-outline-info btn-preview-roleplay" data-id="' + rp.id + '">Preview</button>' +
			'<button type="button" class="btn btn-sm btn-outline-secondary btn-edit-roleplay" data-id="' + rp.id + '">Editar</button>' +
			'<button type="button" class="btn btn-sm btn-outline-danger btn-del-roleplay" data-id="' + rp.id + '">Excluir</button>' +
			'</div></div></div>';
	});
	$('#lista-roleplays').html(html || '<p class="text-muted mb-0">Nenhum role play.</p>');
}

function opcoesToPipe(opcoes) {
	if (!Array.isArray(opcoes)) return '';
	return opcoes.map(function (o) {
		return (o && (o.label || o.text || o.id)) ? String(o.label || o.text || o.id) : '';
	}).filter(Boolean).join('|');
}

function uploadBunnyPut(file, upload, onProgress) {
	return new Promise(function (resolve, reject) {
		var xhr = new XMLHttpRequest();
		xhr.open('PUT', upload.putUrl, true);
		xhr.setRequestHeader('AccessKey', upload.accessKey);
		xhr.setRequestHeader('Content-Type', 'application/octet-stream');
		xhr.upload.onprogress = function (e) {
			if (!e.lengthComputable || typeof onProgress !== 'function') return;
			onProgress(Math.round((e.loaded / e.total) * 100));
		};
		xhr.onload = function () {
			if (xhr.status >= 200 && xhr.status < 300) resolve();
			else reject(new Error('Upload Bunny HTTP ' + xhr.status));
		};
		xhr.onerror = function () { reject(new Error('Falha de rede no upload Bunny')); };
		xhr.send(file);
	});
}

function pollBunnyStatus(videoDbId, tries) {
	tries = tries || 0;
	return postEad({ acao: 'bunny_status', id: videoDbId }).then(function (res) {
		if (!res || !res.success) {
			return $.Deferred().reject(res && res.message).promise();
		}
		if (res.bunny_status === 'ready') return res;
		if (res.bunny_status === 'error') {
			return $.Deferred().reject(res.bunny_error || 'Erro no processamento').promise();
		}
		if (tries > 60) {
			return $.Deferred().reject('Tempo esgotado aguardando processamento.').promise();
		}
		return new Promise(function (resolve) {
			setTimeout(function () { resolve(pollBunnyStatus(videoDbId, tries + 1)); }, 3000);
		});
	});
}

function abrirDialogoVideo(v) {
	var edit = !!v;
	if (edit && v.provider === 'bunny') {
		toastErr('Vídeos Bunny não são editáveis — exclua e envie de novo.');
		return;
	}
	if (edit && v.provider === 'youtube') {
		toastErr('YouTube foi desativado. Exclua este vídeo e envie pelo Bunny.');
		return;
	}
	if (!bunnyOk) {
		Swal.fire({
			icon: 'warning',
			title: 'Bunny Stream incompleto',
			html: (bunnyMotivo
				? '<p class="mb-2">' + $('<div>').text(bunnyMotivo).html() + '</p>'
				: '<p class="mb-2">Os vídeos das aulas usam <strong>Bunny Stream</strong> (não o Storage).</p>') +
				'<p class="mb-0 small text-muted">Configure em <strong>Master → Bunny</strong>: Ativar Stream, Library ID, CDN Hostname, API AccessKey e Token Authentication Key. O badge Stream deve ficar <strong>Pronto</strong>.</p>'
		});
		return;
	}
	Swal.fire({
		title: edit ? 'Editar vídeo' : 'Novo vídeo (Bunny)',
		html:
			'<p class="small text-muted mb-2">Somente upload Bunny (protegido). YouTube não é mais aceito.</p>' +
			'<input id="sw-v-titulo" class="swal2-input" placeholder="Título">' +
			'<input id="sw-v-file" class="swal2-file" type="file" accept="video/mp4,video/webm,video/*">' +
			'<div id="sw-v-progress" class="small text-muted mt-2" style="display:none"></div>',
		didOpen: function () {
			if (edit) {
				$('#sw-v-titulo').val(v.titulo || '');
			}
		},
		showCancelButton: true,
		confirmButtonText: 'Enviar',
		preConfirm: function () {
			var titulo = ($('#sw-v-titulo').val() || '').trim();
			var fileEl = document.getElementById('sw-v-file');
			var file = fileEl && fileEl.files && fileEl.files[0];
			if (!file) {
				Swal.showValidationMessage('Selecione o arquivo de vídeo.');
				return false;
			}
			if (file.size > 800 * 1024 * 1024) {
				Swal.showValidationMessage('Arquivo muito grande (máx. ~800 MB neste fluxo).');
				return false;
			}
			return { provider: 'bunny', titulo: titulo, file: file };
		}
	}).then(function (r) {
		if (!r.isConfirmed || !r.value) return;
		enviarVideoBunny(r.value.titulo, r.value.file);
	});
}

function enviarVideoBunny(titulo, file) {
	Swal.fire({
		title: 'Enviando para Bunny…',
		html: '<div id="bunny-up-pct">0%</div>',
		allowOutsideClick: false,
		didOpen: function () { Swal.showLoading(); }
	});
	postEad({
		acao: 'bunny_criar_video',
		id_aula: idAula(),
		titulo: titulo || file.name,
		ordem: $('#lista-videos li').length
	}).done(function (res) {
		if (!res || !res.success || !res.upload) {
			Swal.close();
			toastErr(res && res.message);
			return;
		}
		var videoDbId = res.id;
		uploadBunnyPut(file, res.upload, function (pct) {
			$('#bunny-up-pct').text(pct + '%');
		}).then(function () {
			return postEad({ acao: 'bunny_finalize', id: videoDbId });
		}).then(function () {
			$('#bunny-up-pct').text('Processando…');
			return pollBunnyStatus(videoDbId);
		}).then(function () {
			Swal.close();
			toastOk('Vídeo Bunny pronto.');
			abrirAula(idAula());
		}).catch(function (err) {
			Swal.close();
			toastErr(typeof err === 'string' ? err : (err && err.message) || 'Falha no upload Bunny');
			abrirAula(idAula());
		});
	}).fail(function () {
		Swal.close();
		toastErr('Falha ao criar vídeo no Bunny');
	});
}

function abrirDialogoMaterial(m) {
	var edit = !!m;
	Swal.fire({
		title: edit ? 'Editar material' : 'Novo material',
		html: '<input id="sw-m-label" class="swal2-input" placeholder="Nome">' +
			'<input id="sw-m-url" class="swal2-input" placeholder="URL do PDF ou link">' +
			'<select id="sw-m-tipo" class="swal2-select"><option value="pdf">PDF</option><option value="link">Link</option><option value="file">Arquivo</option></select>',
		didOpen: function () {
			if (!edit) return;
			$('#sw-m-label').val(m.label || '');
			$('#sw-m-url').val(m.url || '');
			$('#sw-m-tipo').val(m.tipo || 'link');
		},
		showCancelButton: true,
		preConfirm: function () {
			var label = ($('#sw-m-label').val() || '').trim();
			var url = ($('#sw-m-url').val() || '').trim();
			if (!label || !url) {
				Swal.showValidationMessage('Nome e URL são obrigatórios.');
				return false;
			}
			return { label: label, url: url, tipo: $('#sw-m-tipo').val() };
		}
	}).then(function (r) {
		if (!r.isConfirmed) return;
		var payload = $.extend({
			acao: 'salvar_material',
			id_aula: idAula(),
			ordem: edit ? (m.ordem || 0) : $('#lista-materiais li').length
		}, r.value);
		if (edit) payload.id = m.id;
		postEad(payload).done(function (res) {
			if (!res || !res.success) return toastErr(res && res.message);
			toastOk(res.message);
			abrirAula(idAula());
		});
	});
}

function abrirDialogoAtividade(at) {
	var edit = !!at;
	Swal.fire({
		title: edit ? 'Editar atividade' : 'Nova atividade',
		width: 640,
		customClass: { popup: 'swal-ead-wide' },
		html:
			'<div class="text-start px-1">' +
			'<label class="form-label small mb-0 fw-semibold" for="sw-at-titulo">Título</label>' +
			'<input id="sw-at-titulo" class="swal2-input mt-1 mb-2" style="width:100%;margin-left:0" placeholder="Ex.: Quiz da aula 3">' +
			'<label class="form-label small mb-0 fw-semibold" for="sw-at-desc">Descrição (opcional)</label>' +
			'<textarea id="sw-at-desc" class="swal2-textarea mt-1 mb-2" style="width:100%;margin-left:0" placeholder="Instruções para o aluno"></textarea>' +
			'<div class="row g-2">' +
			'<div class="col-6">' +
			'<label class="form-label small mb-0 fw-semibold" for="sw-at-tent">Tentativas por ciclo</label>' +
			'<input id="sw-at-tent" type="number" min="1" max="10" class="swal2-input mt-1" style="width:100%;margin-left:0" placeholder="3">' +
			'</div>' +
			'<div class="col-6">' +
			'<label class="form-label small mb-0 fw-semibold" for="sw-at-dur">Duração estimada (min)</label>' +
			'<input id="sw-at-dur" type="number" min="5" max="180" class="swal2-input mt-1" style="width:100%;margin-left:0" placeholder="30">' +
			'</div></div></div>',
		didOpen: function () {
			if (edit) {
				$('#sw-at-titulo').val(at.titulo || '');
				$('#sw-at-desc').val(at.descricao || '');
				$('#sw-at-tent').val(at.tentativas_max || 3);
				$('#sw-at-dur').val(at.duracao_min || 30);
			} else {
				$('#sw-at-tent').val(3);
				$('#sw-at-dur').val(30);
			}
		},
		showCancelButton: true,
		confirmButtonText: edit ? 'Salvar' : 'Criar',
		preConfirm: function () {
			var titulo = ($('#sw-at-titulo').val() || '').trim();
			if (!titulo) {
				Swal.showValidationMessage('Informe o título.');
				return false;
			}
			return {
				titulo: titulo,
				descricao: ($('#sw-at-desc').val() || '').trim(),
				tentativas_max: parseInt($('#sw-at-tent').val(), 10) || 3,
				duracao_min: parseInt($('#sw-at-dur').val(), 10) || 30
			};
		}
	}).then(function (r) {
		if (!r.isConfirmed) return;
		var payload = $.extend({
			acao: 'salvar_atividade',
			id_curso: idCurso(),
			id_aula: idAula()
		}, r.value);
		if (edit) payload.id = at.id;
		postEad(payload).done(function (res) {
			if (!res || !res.success) return toastErr(res && res.message);
			toastOk(res.message);
			abrirAula(idAula());
		});
	});
}

function abrirDialogoQuestao(idAtiv, q) {
	var edit = !!q;
	Swal.fire({
		title: edit ? 'Editar questão' : 'Nova questão',
		html: '<select id="sw-q-tipo" class="swal2-select">' +
			'<option value="multiple">Múltipla escolha</option>' +
			'<option value="boolean">Verdadeiro / Falso</option>' +
			'<option value="essay">Aberta (corrigida por IA)</option></select>' +
			'<textarea id="sw-q-enun" class="swal2-textarea" placeholder="Enunciado"></textarea>' +
			'<div id="sw-q-multiple-fields">' +
			'<input id="sw-q-ops" class="swal2-input" placeholder="Opções separadas por | (ex: Windows|Linux|macOS)">' +
			'<p class="text-muted small mb-0 px-1">Resposta correta = letra da opção: A, B, C…</p>' +
			'<input id="sw-q-resp" class="swal2-input" placeholder="Resposta correta (ex: A)">' +
			'</div>' +
			'<div id="sw-q-boolean-fields" style="display:none">' +
			'<select id="sw-q-bool" class="swal2-select"><option value="true">Verdadeiro</option><option value="false">Falso</option></select>' +
			'<p class="text-muted small mb-0 px-1">O aluno verá botões Verdadeiro e Falso.</p>' +
			'</div>' +
			'<div id="sw-q-essay-fields" style="display:none">' +
			'<p class="text-muted small mb-0 px-1">Sem gabarito — a IA avalia a resposta em percentual (0–100%).</p>' +
			'</div>',
		didOpen: function () {
			function syncTipo() {
				var t = $('#sw-q-tipo').val();
				$('#sw-q-multiple-fields').toggle(t === 'multiple');
				$('#sw-q-boolean-fields').toggle(t === 'boolean');
				$('#sw-q-essay-fields').toggle(t === 'essay');
			}
			$('#sw-q-tipo').on('change', syncTipo);
			if (edit) {
				$('#sw-q-tipo').val(q.tipo || 'multiple');
				$('#sw-q-enun').val(q.enunciado || '');
				if (q.tipo === 'boolean') {
					var rc = String(q.resposta_correta || 'true').toLowerCase();
					$('#sw-q-bool').val(rc === 'false' || rc === '0' || rc === 'falso' ? 'false' : 'true');
				} else if (q.tipo === 'multiple') {
					$('#sw-q-ops').val(opcoesToPipe(q.opcoes));
					$('#sw-q-resp').val(q.resposta_correta || '');
				}
			}
			syncTipo();
		},
		showCancelButton: true,
		preConfirm: function () {
			var tipo = $('#sw-q-tipo').val();
			var enunciado = ($('#sw-q-enun').val() || '').trim();
			if (!enunciado) {
				Swal.showValidationMessage('Informe o enunciado.');
				return false;
			}
			if (tipo === 'boolean') {
				return {
					tipo: 'boolean',
					enunciado: enunciado,
					opcoes: JSON.stringify([
						{ id: 'true', label: 'Verdadeiro' },
						{ id: 'false', label: 'Falso' }
					]),
					resposta_correta: $('#sw-q-bool').val() === 'false' ? 'false' : 'true'
				};
			}
			if (tipo === 'essay') {
				return { tipo: 'essay', enunciado: enunciado, opcoes: '[]', resposta_correta: '' };
			}
			var opsRaw = ($('#sw-q-ops').val() || '').split('|').map(function (s) { return s.trim(); }).filter(Boolean);
			if (opsRaw.length < 2) {
				Swal.showValidationMessage('Informe ao menos 2 opções separadas por |');
				return false;
			}
			var opcoes = opsRaw.map(function (label, i) {
				return { id: String.fromCharCode(65 + i), label: label };
			});
			var resp = ($('#sw-q-resp').val() || '').trim().toUpperCase();
			if (!resp || !opcoes.some(function (o) { return o.id === resp; })) {
				Swal.showValidationMessage('Resposta correta deve ser uma letra das opções (A, B, C…).');
				return false;
			}
			return {
				tipo: 'multiple',
				enunciado: enunciado,
				opcoes: JSON.stringify(opcoes),
				resposta_correta: resp
			};
		}
	}).then(function (r) {
		if (!r.isConfirmed) return;
		var payload = $.extend({
			acao: 'salvar_questao',
			id_atividade: idAtiv
		}, r.value);
		if (edit) payload.id = q.id;
		postEad(payload).done(function (res) {
			if (!res || !res.success) return toastErr(res && res.message);
			toastOk(res.message);
			abrirAula(idAula());
		});
	});
}

function abrirDialogoRoleplay(rp) {
	var edit = !!rp;
	Swal.fire({
		title: edit ? 'Editar role play' : 'Novo role play',
		html: '<input id="sw-rp-titulo" class="swal2-input" placeholder="Título (visto pelo aluno)">' +
			'<input id="sw-rp-tema" class="swal2-input" placeholder="Tema">' +
			'<textarea id="sw-rp-cenario" class="swal2-textarea" placeholder="Situação (resumo para o aluno)"></textarea>' +
			'<input id="sw-rp-user" class="swal2-input" placeholder="Papel do aluno (ex: Suporte técnico)">' +
			'<input id="sw-rp-char" class="swal2-input" placeholder="Nome do personagem IA (ex: Dona Maria)">' +
			'<input id="sw-rp-ai" class="swal2-input" placeholder="Papel curto da IA (ex: Cliente iniciante)">' +
			'<textarea id="sw-rp-prompt" class="swal2-textarea" placeholder="PROMPT só para a IA (aluno NÃO vê)"></textarea>' +
			'<textarea id="sw-rp-msg" class="swal2-textarea" placeholder="Mensagem inicial da IA (aluno vê)"></textarea>' +
			'<input id="sw-rp-min" type="number" min="5" max="120" value="15" class="swal2-input" placeholder="Tempo limite (minutos)">' +
			'<input id="sw-rp-score" type="number" min="0" max="100" value="70" class="swal2-input" placeholder="Nota mínima para aprovar">',
		didOpen: function () {
			if (!edit) return;
			$('#sw-rp-titulo').val(rp.titulo || '');
			$('#sw-rp-tema').val(rp.tema || '');
			$('#sw-rp-cenario').val(rp.cenario || '');
			$('#sw-rp-user').val(rp.user_role || '');
			$('#sw-rp-char').val(rp.ai_character_name || '');
			$('#sw-rp-ai').val(rp.ai_role || '');
			$('#sw-rp-prompt').val(rp.base_prompt || '');
			$('#sw-rp-msg').val(rp.initial_message || '');
			$('#sw-rp-min').val(rp.estimated_minutes || 15);
			$('#sw-rp-score').val(rp.min_score || 70);
		},
		showCancelButton: true,
		preConfirm: function () {
			var charName = ($('#sw-rp-char').val() || '').trim() || ($('#sw-rp-ai').val() || '').trim() || 'Personagem';
			return {
				titulo: $('#sw-rp-titulo').val(),
				tema: $('#sw-rp-tema').val(),
				cenario: $('#sw-rp-cenario').val(),
				user_role: $('#sw-rp-user').val(),
				ai_role: ($('#sw-rp-ai').val() || '').trim() || charName,
				ai_character_name: charName,
				base_prompt: $('#sw-rp-prompt').val(),
				initial_message: $('#sw-rp-msg').val(),
				estimated_minutes: parseInt($('#sw-rp-min').val(), 10) || 15,
				min_score: parseInt($('#sw-rp-score').val(), 10) || 70
			};
		}
	}).then(function (r) {
		if (!r.isConfirmed) return;
		var payload = $.extend({ acao: 'salvar_roleplay', id_curso: idCurso(), id_aula: idAula() }, r.value);
		if (edit) payload.id = rp.id;
		postEad(payload).done(function (res) {
			if (!res || !res.success) return toastErr(res && res.message);
			toastOk(res.message);
			abrirAula(idAula());
		});
	});
}

function previewAtividade(at) {
	var body = '<p class="text-start mb-2"><strong>' + $('<div>').text(at.titulo).html() + '</strong></p>' +
		'<p class="text-muted small text-start">O aluno vê: ' + (at.tentativas_max || 3) + ' tentativas por ciclo · respostas travadas após confirmar.</p><hr>';
	(at.questoes || []).forEach(function (q, i) {
		body += '<div class="text-start mb-3"><p class="mb-1"><strong>' + (i + 1) + '.</strong> ' +
			$('<div>').text(q.enunciado || '').html() +
			' <span class="badge bg-light text-dark">' + q.tipo + '</span></p>';
		if (q.tipo === 'boolean') {
			body += '<p class="small mb-0">○ Verdadeiro &nbsp; ○ Falso</p>';
		} else if (q.tipo === 'essay') {
			body += '<p class="small text-muted mb-0">[caixa de texto livre — corrigida por IA]</p>';
		} else {
			(q.opcoes || []).forEach(function (o) {
				body += '<p class="small mb-0">○ ' + $('<div>').text(o.label || o.id).html() + '</p>';
			});
		}
		body += '</div>';
	});
	if (!(at.questoes || []).length) body += '<p class="text-muted">Sem questões ainda.</p>';
	Swal.fire({ title: 'Preview — o que o aluno vê', html: body, width: 640, confirmButtonText: 'Fechar' });
}

function previewRoleplay(rp) {
	var html = '<div class="text-start">' +
		'<p><strong>' + $('<div>').text(rp.titulo || '').html() + '</strong></p>' +
		'<p class="small">' + $('<div>').text(rp.cenario || rp.tema || '').html() + '</p>' +
		'<p class="small">Você: <strong>' + $('<div>').text(rp.user_role || 'Aluno').html() +
		'</strong> · Personagem: <strong>' + $('<div>').text(rp.ai_character_name || 'IA').html() + '</strong></p>' +
		'<p class="small text-muted">Tempo: ' + (rp.estimated_minutes || 15) + ' min · Nota mínima: ' + (rp.min_score || 70) + '%</p>' +
		'<hr><p class="small"><em>Mensagem inicial:</em><br>' + $('<div>').text(rp.initial_message || '(vazia)').html() + '</p>' +
		'<p class="small text-warning mb-0">O prompt completo da IA <strong>não</strong> aparece para o aluno.</p></div>';
	Swal.fire({ title: 'Preview — role play', html: html, width: 640, confirmButtonText: 'Fechar' });
}

function salvarAula() {
	postEad({
		acao: 'salvar_aula',
		id_curso: idCurso(),
		id_aula: idAula() || '',
		titulo: $('#aula_titulo').val(),
		descricao: $('#aula_descricao').val(),
		ordem: $('#aula_ordem').val(),
		bloqueado: $('#aula_bloqueado').is(':checked') ? '1' : '0'
	}).done(function (res) {
		if (!res || !res.success) return toastErr(res && res.message);
		toastOk(res.message);
		$('#aula_id').val(res.id_aula);
		listarAulas();
		abrirAula(res.id_aula);
	});
}

$(function () {
	carregarGeral();

	$('#btn-salvar-geral').on('click', salvarGeral);

	$(document).on('change', '#vitrine_gratuito', function () {
		var on = $(this).is(':checked');
		$('#vitrine_preco_mensal').prop('disabled', on);
		if (on) {
			$('#vitrine_preco_mensal').val(0);
			$('#vitrine_ativo').prop('checked', true);
		}
	});
	$(document).on('change', '#vitrine_preco_mensal', function () {
		if (Number($(this).val() || 0) > 0) {
			$('#vitrine_gratuito').prop('checked', false);
			$('#vitrine_preco_mensal').prop('disabled', false);
		}
	});

	$('#btn-nova-aula').on('click', function () {
		postEad({
			acao: 'salvar_aula',
			id_curso: idCurso(),
			titulo: 'Nova aula',
			ordem: $('#lista-aulas .aula-item').length
		}).done(function (res) {
			if (!res || !res.success) return toastErr(res && res.message);
			listarAulas();
			abrirAula(res.id_aula);
		});
	});

	$('#lista-aulas').on('click', '.aula-item', function () {
		abrirAula($(this).data('id'));
	});

	$('#btn-salvar-aula').on('click', salvarAula);

	$('#btn-excluir-aula').on('click', function () {
		var id = idAula();
		if (!id) return;
		Swal.fire({ title: 'Excluir aula?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Sim' }).then(function (r) {
			if (!r.isConfirmed) return;
			postEad({ acao: 'excluir_aula', id_aula: id }).done(function (res) {
				if (!res || !res.success) return toastErr(res && res.message);
				toastOk(res.message);
				$('#painel-aula').addClass('d-none');
				$('#aula-placeholder').removeClass('d-none');
				listarAulas();
			});
		});
	});

	$('#btn-add-video').on('click', function () { abrirDialogoVideo(null); });
	$('#lista-videos').on('click', '.btn-edit-video', function () {
		abrirDialogoVideo(findVideo($(this).data('id')));
	});
	$('#lista-videos').on('click', '.btn-del-video', function () {
		postEad({ acao: 'excluir_video', id: $(this).data('id') }).done(function () { abrirAula(idAula()); });
	});

	$('#btn-add-material').on('click', function () { abrirDialogoMaterial(null); });
	$('#lista-materiais').on('click', '.btn-edit-material', function () {
		abrirDialogoMaterial(findMaterial($(this).data('id')));
	});
	$('#lista-materiais').on('click', '.btn-del-material', function () {
		postEad({ acao: 'excluir_material', id: $(this).data('id') }).done(function () { abrirAula(idAula()); });
	});

	$('#btn-add-atividade').on('click', function () { abrirDialogoAtividade(null); });
	$('#lista-atividades').on('click', '.btn-edit-atividade', function () {
		abrirDialogoAtividade(findAtividade($(this).data('id')));
	});
	$('#lista-atividades').on('click', '.btn-preview-atividade', function () {
		previewAtividade(findAtividade($(this).data('id')));
	});
	$('#lista-atividades').on('click', '.btn-del-atividade', function () {
		postEad({ acao: 'excluir_atividade', id: $(this).data('id') }).done(function () { abrirAula(idAula()); });
	});

	$('#lista-atividades').on('click', '.btn-add-questao', function () {
		abrirDialogoQuestao($(this).data('id'), null);
	});
	$('#lista-atividades').on('click', '.btn-edit-questao', function () {
		abrirDialogoQuestao($(this).data('ativ'), findQuestao($(this).data('ativ'), $(this).data('id')));
	});
	$('#lista-atividades').on('click', '.btn-del-questao', function () {
		postEad({ acao: 'excluir_questao', id: $(this).data('id') }).done(function () { abrirAula(idAula()); });
	});

	$('#btn-add-roleplay').on('click', function () { abrirDialogoRoleplay(null); });
	$('#lista-roleplays').on('click', '.btn-edit-roleplay', function () {
		abrirDialogoRoleplay(findRoleplay($(this).data('id')));
	});
	$('#lista-roleplays').on('click', '.btn-preview-roleplay', function () {
		previewRoleplay(findRoleplay($(this).data('id')));
	});
	$('#lista-roleplays').on('click', '.btn-del-roleplay', function () {
		postEad({ acao: 'excluir_roleplay', id: $(this).data('id') }).done(function () { abrirAula(idAula()); });
	});

	var buscaTimer = null;
	$('#busca-aluno').on('input', function () {
		var q = $(this).val();
		clearTimeout(buscaTimer);
		if (!q || q.length < 2) {
			$('#busca-aluno-resultados').empty();
			return;
		}
		buscaTimer = setTimeout(function () {
			postEad({ acao: 'buscar_alunos', q: q }).done(function (res) {
				var html = '';
				(res.itens || []).forEach(function (a) {
					html += '<button type="button" class="list-group-item list-group-item-action btn-matricular-aluno" data-id="' + a.id + '">' +
						$('<div>').text(a.nome + ' — ' + (a.email || '')).html() + '</button>';
				});
				$('#busca-aluno-resultados').html(html);
			});
		}, 300);
	});
	$('#busca-aluno-resultados').on('click', '.btn-matricular-aluno', function () {
		var idAluno = $(this).data('id');
		postEad({ acao: 'matricular_ead', id_curso: idCurso(), id_aluno: idAluno }).done(function (res) {
			if (!res || !res.success) return toastErr(res && res.message);
			toastOk(res.message);
			$('#busca-aluno').val('');
			$('#busca-aluno-resultados').empty();
			listarMatriculasEad();
		});
	});
	$('#tbody-matriculas-ead').on('click', '.btn-desmatricular', function () {
		var idAluno = $(this).data('id');
		postEad({ acao: 'desmatricular_ead', id_curso: idCurso(), id_aluno: idAluno }).done(function (res) {
			if (!res || !res.success) return toastErr(res && res.message);
			toastOk(res.message);
			listarMatriculasEad();
		});
	});
	$('#tab-alunos').on('shown.bs.tab', listarMatriculasEad);
});

const WA_URL = 'painel/whatsapp';
let waConversaId = null;
let waIsDiretor = false;
let waPoll = null;

function waPost(data, cb){
	$.post(url_base + WA_URL, data, cb, 'json').fail(function(){
		Swal.fire('Erro', 'Falha na requisição WhatsApp.', 'error');
	});
}

function esc(s){
	return $('<div>').text(s == null ? '' : String(s)).html();
}

function carregarConversas(){
	waPost({ acao: 'listar' }, function(res){
		if(!res || !res.success){
			$('#alert-wa-sql').removeClass('d-none').text((res && res.message) ? res.message : 'Não foi possível listar.');
			return;
		}
		const meta = res.meta || {};
		waIsDiretor = !!meta.is_diretor;
		if(!meta.chatbot_ok || !meta.setores_ok){
			$('#alert-wa-sql').removeClass('d-none').html(
				'Execute o SQL de WhatsApp (setores/chatbot) no phpMyAdmin — veja ARCHITECTURE.md.'
			);
		} else {
			$('#alert-wa-sql').addClass('d-none');
		}
		if(!waIsDiretor){
			$('#tab-config-li').addClass('d-none');
		}

		const lista = res.conversas || [];
		const $box = $('#wa-lista-conversas').empty();
		if(!lista.length){
			$box.append('<div class="p-3 text-muted small">Nenhuma conversa ainda.</div>');
			return;
		}
		lista.forEach(function(c){
			const ativo = waConversaId === parseInt(c.id, 10) ? ' active' : '';
			const nome = c.nome_contato || c.telefone;
			const setor = c.setor_nome ? ' · '+c.setor_nome : '';
			const st = c.chatbot_estado || c.status || '';
			$box.append(
				'<a href="#" class="list-group-item list-group-item-action'+ativo+'" data-id="'+c.id+'">'
				+'<div class="d-flex justify-content-between"><strong class="text-truncate">'+esc(nome)+'</strong>'
				+'<span class="badge bg-secondary">'+esc(st)+'</span></div>'
				+'<div class="small text-muted">'+esc(c.telefone)+esc(setor)+'</div>'
				+'</a>'
			);
		});
	});
}

function abrirConversa(id){
	waConversaId = parseInt(id, 10);
	waPost({ acao: 'mensagens', conversa_id: id }, function(res){
		if(!res || !res.success){
			Swal.fire('Erro', (res && res.message) || 'Falha.', 'error');
			return;
		}
		const c = res.conversa || {};
		$('#wa-chat-titulo').text(c.nome_contato || c.telefone || 'Conversa');
		$('#wa-chat-sub').text((c.telefone || '')+' · '+(c.chatbot_estado || c.status || ''));
		$('#btn-wa-assumir, #btn-wa-transferir, #btn-wa-fechar').removeClass('d-none');
		$('#wa-texto, #btn-wa-enviar').prop('disabled', false);

		const $m = $('#wa-mensagens').empty();
		(res.mensagens || []).forEach(function(m){
			const mine = m.direction === 'out';
			const align = mine ? 'text-end' : 'text-start';
			const bg = mine ? 'bg-success text-white' : 'bg-white';
			$m.append(
				'<div class="mb-2 '+align+'"><span class="d-inline-block rounded px-3 py-2 shadow-sm '+bg+'" style="max-width:80%;">'
				+esc(m.corpo || ('['+(m.tipo||'msg')+']'))
				+'</span><div class="small text-muted">'+esc(m.created_at || '')+'</div></div>'
			);
		});
		$m.scrollTop($m[0].scrollHeight);
		carregarConversas();
	});
}

function enviarMsg(){
	if(!waConversaId) return;
	const texto = $('#wa-texto').val();
	if(!String(texto).trim()) return;
	$('#btn-wa-enviar').prop('disabled', true);
	waPost({ acao: 'enviar', conversa_id: waConversaId, texto: texto }, function(res){
		$('#btn-wa-enviar').prop('disabled', false);
		if(!res || !res.success){
			Swal.fire('Erro', (res && res.message) || 'Não enviou.', 'error');
			return;
		}
		$('#wa-texto').val('');
		abrirConversa(waConversaId);
	});
}

function carregarSetoresConfig(){
	waPost({ acao: 'setores_listar' }, function(res){
		if(!res || !res.success) return;
		const $t = $('#tbl-setores').empty();
		const $sel = $('#at-setor').empty();
		(res.setores || []).forEach(function(s){
			$t.append(
				'<tr><td>'+esc(s.nome)+'</td><td>'+esc(s.ordem)+'</td><td>'+(parseInt(s.ativo,10)===1?'sim':'não')+'</td>'
				+'<td><button type="button" class="btn btn-link btn-sm btn-editar-setor" data-id="'+s.id+'">Editar</button></td></tr>'
			);
			if(parseInt(s.ativo,10)===1){
				$sel.append('<option value="'+s.id+'">'+esc(s.nome)+'</option>');
			}
		});
	});
	waPost({ acao: 'atendentes_listar' }, function(res){
		if(!res || !res.success) return;
		const $t = $('#tbl-atendentes').empty();
		(res.atendentes || []).forEach(function(a){
			$t.append(
				'<tr><td>'+esc(a.usuario_nome)+' <small class="text-muted">('+esc(a.usuario_nivel)+')</small></td>'
				+'<td>'+esc(a.setor_nome)+'</td>'
				+'<td><button type="button" class="btn btn-sm btn-outline-danger btn-at-del" data-id="'+a.id+'">×</button></td></tr>'
			);
		});
	});
	waPost({ acao: 'usuarios_lista' }, function(res){
		if(!res || !res.success) return;
		const $u = $('#at-usuario').empty();
		(res.usuarios || []).forEach(function(u){
			$u.append('<option value="'+u.id+'">'+esc(u.nome)+' ('+esc(u.nivel)+')</option>');
		});
	});
}

function promptSetor(s){
	s = s || { id: 0, nome: '', slug: '', ordem: 0, ativo: 1, mensagem_fila: '' };
	Swal.fire({
		title: s.id ? 'Editar setor' : 'Novo setor',
		html:
			'<input id="sw-nome" class="swal2-input" placeholder="Nome" value="'+esc(s.nome)+'">'
			+'<input id="sw-ordem" class="swal2-input" placeholder="Ordem" value="'+(s.ordem||0)+'">'
			+'<textarea id="sw-msg" class="swal2-textarea" placeholder="Mensagem ao entrar na fila">'+esc(s.mensagem_fila||'')+'</textarea>'
			+'<label class="d-block mt-2"><input type="checkbox" id="sw-ativo" '+(parseInt(s.ativo,10)===1?'checked':'')+'> Ativo</label>',
		showCancelButton: true,
		preConfirm: function(){
			return {
				id: s.id || 0,
				nome: $('#sw-nome').val(),
				ordem: $('#sw-ordem').val(),
				mensagem_fila: $('#sw-msg').val(),
				ativo: $('#sw-ativo').is(':checked') ? 1 : 0
			};
		}
	}).then(function(r){
		if(!r.isConfirmed) return;
		const d = r.value;
		d.acao = 'setor_salvar';
		waPost(d, function(res){
			if(!res || !res.success){
				Swal.fire('Erro', (res && res.message) || 'Falha.', 'error');
				return;
			}
			carregarSetoresConfig();
		});
	});
}

$(function(){
	carregarConversas();
	waPoll = setInterval(carregarConversas, 15000);

	$('#btn-wa-refresh').on('click', carregarConversas);
	$('#wa-lista-conversas').on('click', 'a[data-id]', function(e){
		e.preventDefault();
		abrirConversa($(this).data('id'));
	});
	$('#btn-wa-enviar').on('click', enviarMsg);
	$('#wa-texto').on('keydown', function(e){
		if(e.key === 'Enter'){ e.preventDefault(); enviarMsg(); }
	});
	$('#btn-wa-assumir').on('click', function(){
		if(!waConversaId) return;
		waPost({ acao: 'assumir', conversa_id: waConversaId }, function(res){
			Swal.fire(res && res.success ? 'OK' : 'Erro', (res && res.message) || '', res && res.success ? 'success' : 'error');
			abrirConversa(waConversaId);
		});
	});
	$('#btn-wa-fechar').on('click', function(){
		if(!waConversaId) return;
		waPost({ acao: 'fechar', conversa_id: waConversaId }, function(res){
			Swal.fire(res && res.success ? 'OK' : 'Erro', (res && res.message) || '', res && res.success ? 'success' : 'error');
			abrirConversa(waConversaId);
		});
	});
	$('#btn-wa-transferir').on('click', function(){
		if(!waConversaId) return;
		waPost({ acao: 'setores_listar' }, function(res){
			const opts = {};
			(res.setores || []).filter(function(s){ return parseInt(s.ativo,10)===1; }).forEach(function(s){
				opts[s.id] = s.nome;
			});
			Swal.fire({
				title: 'Transferir para setor',
				input: 'select',
				inputOptions: opts,
				showCancelButton: true
			}).then(function(r){
				if(!r.isConfirmed) return;
				waPost({ acao: 'transferir', conversa_id: waConversaId, setor_id: r.value }, function(res2){
					Swal.fire(res2 && res2.success ? 'OK' : 'Erro', (res2 && res2.message) || '', res2 && res2.success ? 'success' : 'error');
					abrirConversa(waConversaId);
				});
			});
		});
	});

	$('button[data-bs-target="#tab-config"]').on('shown.bs.tab', carregarSetoresConfig);
	$('#btn-setor-novo').on('click', function(){ promptSetor(null); });
	$('#tbl-setores').on('click', '.btn-editar-setor', function(){
		const id = parseInt($(this).data('id'), 10);
		waPost({ acao: 'setores_listar' }, function(res){
			const s = (res.setores || []).find(function(x){ return parseInt(x.id,10) === id; });
			if(s) promptSetor(s);
		});
	});
	$('#btn-at-vincular').on('click', function(){
		waPost({
			acao: 'atendente_vincular',
			usuario_id: $('#at-usuario').val(),
			setor_id: $('#at-setor').val()
		}, function(res){
			if(!res || !res.success){
				Swal.fire('Erro', (res && res.message) || 'Falha.', 'error');
				return;
			}
			carregarSetoresConfig();
		});
	});
	$('#tbl-atendentes').on('click', '.btn-at-del', function(){
		waPost({ acao: 'atendente_remover', id: $(this).data('id') }, function(){
			carregarSetoresConfig();
		});
	});
});

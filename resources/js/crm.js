const STATUS_COLUNAS = ['novo','em_atendimento','matriculado','perdido'];

let crmArrastando = false;
let crmPaginaAtual = 1;
let crmFiltroTimer = null;

function montarCardLead(lead){
	const classeEsquecido = lead.esquecido ? ' lead-esquecido' : '';
	const badgeEsquecido  = lead.esquecido ? '<span class="badge badge-esquecido ms-1">Sem contato +48h</span>' : '';
	const valorHtml       = lead.valor_estimado_br ? '<div class="lead-valor">R$ '+lead.valor_estimado_br+'</div>' : '';

	return `
	<div class="kanban-card${classeEsquecido}" data-id="${lead.id}" data-nome="${lead.nome.toLowerCase()}" data-curso="${(lead.curso_interesse || '').toLowerCase()}">
		<div class="lead-nome">${lead.nome}${badgeEsquecido}</div>
		<div class="lead-meta">
			${lead.curso_interesse ? lead.curso_interesse + '<br>' : ''}
			<small>${lead.data_cadastro}</small>
		</div>
	</div>`;
}

function atualizarContadores(colunas){
	STATUS_COLUNAS.forEach(function(status){
		const qtd = (colunas[status] || []).length;
		$('#count-'+status).text(qtd);
	});
}

function atualizarTotais(totais){
	STATUS_COLUNAS.forEach(function(status){
		const valor = totais[status] || '0,00';
		$('#total-'+status).text('R$ '+valor);
	});
}

function popularFiltroCursos(cursos){
	const $select = $('#filtro-curso');
	const valorAtual = $select.val();

	$select.find('option:not(:first)').remove();

	(cursos || []).forEach(function(curso){
		$select.append('<option value="'+curso.toLowerCase()+'">'+curso+'</option>');
	});

	if(valorAtual){
		$select.val(valorAtual);
	}
}

function renderizarKanban(colunas, totais){
	STATUS_COLUNAS.forEach(function(status){
		const $coluna = $('#coluna-'+status);
		$coluna.empty();

		(colunas[status] || []).forEach(function(lead){
			$coluna.append(montarCardLead(lead));
		});
	});

	atualizarContadores(colunas);
	if(totais){
		atualizarTotais(totais);
	}
}

function listar(filtro=null, page=1){
	carregarLeads(page);
}

function carregarLeads(page){
	if(page === undefined || page === null){
		page = crmPaginaAtual;
	}

	crmPaginaAtual = page;

	$.ajax({
		url: url_base + listagemCrm,
		method: 'post',
		data: {
			page: page,
			filtro_nome: ($('#filtro-busca-lead').val() || '').trim(),
			filtro_curso: ($('#filtro-curso').val() || '').trim()
		},
		dataType: 'json',
		success: function(result){
			if(result.colunas){
				popularFiltroCursos(result.cursos || []);
				renderizarKanban(result.colunas, result.totais || {});
			}

			if(result.pagination !== undefined){
				$('#pagination').html(result.pagination);
			}
		}
	});
}

function atualizarStatusLead(id, status, motivoPerda, callback){
	const dados = { id, status };

	if(motivoPerda){
		dados.motivo_perda = motivoPerda;
	}

	$.ajax({
		url: url_base + statusLead,
		method: 'post',
		data: dados,
		dataType: 'json',
		success: function(result){
			if(result.erro){
				Swal.fire({ title: 'Ops...', text: result.erro, icon: 'error' });
				carregarLeads();
			} else {
				carregarLeads();
				if(typeof callback === 'function'){
					callback();
				}
			}
		},
		error: function(){
			Swal.fire({ title: 'Erro', text: 'Não foi possível atualizar o status.', icon: 'error' });
			carregarLeads();
		}
	});
}

function abrirModalMotivoPerda(id){
	$('#perda-lead-id').val(id);
	$('#perda-motivo').val('');
	$('#response-motivo-perda').html('');
	$('.btn-motivo').removeClass('active');
	$('#btn-confirmar-perda').prop('disabled', true);
	$('#modalMotivoPerda').modal('show');
}

function preencherModalDetalhes(result){
	const lead = result.lead;

	$('#detalhe-lead-nome').text(lead.nome);
	$('#detalhe-lead-id').val(lead.id);
	$('#editar-lead-id').val(lead.id);
	$('#detalhe-status-badge').text(lead.status_label);
	$('#detalhe-data-resumo').text('Cadastrado em '+lead.data_cadastro);

	$('#editar-nome').val(lead.nome);
	$('#editar-whatsapp').val(lead.whatsapp);
	$('#editar-email').val(lead.email);
	$('#editar-idade').val(lead.idade);
	$('#editar-responsavel').val(lead.responsavel_nome);
	$('#editar-curso').val(lead.curso_interesse);
	$('#editar-valor').val(lead.valor_estimado);
	$('#editar-origem').val(lead.origem);
	$('#editar-bairro').val(lead.bairro);
	$('#editar-cidade').val(lead.cidade);
	$('#editar-motivo-perda').val(lead.motivo_perda !== '-' ? lead.motivo_perda : '');

	$('#detalhe-timeline').html(result.timeline);
	$('#detalhe-observacao').val('');
	$('#response-detalhe-lead').html('');

	$('#btn-whatsapp-lead').attr('href', result.whatsapp_link);
}

function abrirDetalhesLead(id){
	$.ajax({
		url: url_base + detalhesLead,
		method: 'post',
		data: { id },
		dataType: 'json',
		success: function(result){
			if(result.erro){
				Swal.fire({ title: 'Ops...', text: result.erro, icon: 'error' });
				return;
			}
			preencherModalDetalhes(result);
			$('#modalDetalhesLead').modal('show');
		}
	});
}

function inicializarSortable(){
	STATUS_COLUNAS.forEach(function(status){
		const el = document.getElementById('coluna-'+status);
		if(!el) return;

		new Sortable(el, {
			group: 'crm-kanban',
			animation: 150,
			ghostClass: 'kanban-ghost',
			delay: 120,
			delayOnTouchOnly: true,
			onStart: function(){
				crmArrastando = true;
			},
			onEnd: function(){
				setTimeout(function(){ crmArrastando = false; }, 150);
			},
			onAdd: function(evt){
				const id         = evt.item.getAttribute('data-id');
				const novoStatus = evt.to.getAttribute('data-status');
				const colunaOrigem = evt.from;

				if(novoStatus === 'perdido'){
					colunaOrigem.appendChild(evt.item);
					abrirModalMotivoPerda(id);
					return;
				}

				atualizarStatusLead(id, novoStatus);
			}
		});
	});
}

$(document).on('click', '.kanban-card', function(){
	if(crmArrastando) return;
	abrirDetalhesLead($(this).data('id'));
});

$(document).on('input', '#filtro-busca-lead', function(){
	clearTimeout(crmFiltroTimer);
	crmFiltroTimer = setTimeout(function(){
		carregarLeads(1);
	}, 400);
});

$(document).on('change', '#filtro-curso', function(){
	carregarLeads(1);
});

$(document).on('click', '#btn-limpar-filtros', function(){
	$('#filtro-busca-lead').val('').removeAttr('readonly');
	$('#filtro-curso').val('');
	carregarLeads(1);
});

$(document).on('click', '.btn-motivo', function(){
	const motivo = $(this).data('motivo');
	$('.btn-motivo').removeClass('active');
	$(this).addClass('active');
	$('#perda-motivo').val(motivo);
	$('#btn-confirmar-perda').prop('disabled', false);
});

$(document).on('submit', '#form-motivo-perda', function(event){
	event.preventDefault();

	const id     = $('#perda-lead-id').val();
	const motivo = $('#perda-motivo').val();

	if(!motivo){
		$('#response-motivo-perda').html('<div class="alert alert-danger py-1">Selecione um motivo.</div>');
		return;
	}

	atualizarStatusLead(id, 'perdido', motivo, function(){
		$('#modalMotivoPerda').modal('hide');
	});
});

$('#modalMotivoPerda').on('hidden.bs.modal', function(){
	if($('#perda-motivo').val() === ''){
		carregarLeads();
	}
});

$(document).on('submit', '#form-novo-lead', function(event){
	event.preventDefault();

	$.ajax({
		url: url_base + salvarLead,
		method: 'post',
		data: $(this).serialize(),
		dataType: 'json',
		success: function(result){
			if(result.erro){
				$('#response-lead').html('<div class="alert alert-danger">'+result.erro+'</div>');
			} else {
				$('#modalNovoLead').modal('hide');
				$('#form-novo-lead')[0].reset();
				$('#response-lead').html('');
				Swal.fire({ title: 'Lead cadastrado!', icon: 'success', timer: 1200, showConfirmButton: false });
				carregarLeads();
			}
		}
	});
});

$(document).on('submit', '#form-comentario-lead', function(event){
	event.preventDefault();

	$.ajax({
		url: url_base + comentarioLead,
		method: 'post',
		data: $(this).serialize(),
		dataType: 'json',
		success: function(result){
			if(result.erro){
				$('#response-detalhe-lead').html('<div class="alert alert-danger">'+result.erro+'</div>');
			} else {
				$('#detalhe-observacao').val('');
				$('#detalhe-timeline').html(result.timeline);
				$('#response-detalhe-lead').html('<div class="alert alert-success">Comentário salvo com sucesso.</div>');
				carregarLeads();
				setTimeout(function(){
					$('#response-detalhe-lead').html('');
				}, 2000);
			}
		}
	});
});

$(document).on('submit', '#form-editar-lead', function(event){
	event.preventDefault();

	$.ajax({
		url: url_base + atualizarLead,
		method: 'post',
		data: $(this).serialize(),
		dataType: 'json',
		success: function(result){
			if(result.erro){
				$('#response-detalhe-lead').html('<div class="alert alert-danger">'+result.erro+'</div>');
			} else {
				preencherModalDetalhes(result);
				$('#response-detalhe-lead').html('<div class="alert alert-success">Dados atualizados com sucesso.</div>');
				carregarLeads();
				setTimeout(function(){
					$('#response-detalhe-lead').html('');
				}, 2000);
			}
		}
	});
});

$(document).on('submit', '#form-importar-leads', function(event){
	event.preventDefault();

	const $form = $(this);
	const $btn  = $('#btn-importar-leads');
	const formData = new FormData(this);

	$('#response-importar').html('');
	$btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Importando...');

	$.ajax({
		url: url_base + importarLeads,
		method: 'post',
		data: formData,
		processData: false,
		contentType: false,
		dataType: 'json',
		success: function(result){
			if(result.erro){
				let html = '<div class="alert alert-danger">'+result.erro+'</div>';
				if(result.detalhes && result.detalhes.length){
					html += '<ul class="small text-danger mb-0">';
					result.detalhes.forEach(function(item){
						html += '<li>'+item+'</li>';
					});
					html += '</ul>';
				}
				$('#response-importar').html(html);
				$btn.prop('disabled', false).html('<i class="fas fa-upload"></i> Importar');
				return;
			}

			$('#modalImportarLeads').modal('hide');
			$form[0].reset();

			let texto = result.importados + ' lead(s) importado(s) com sucesso.';
			if(result.ignorados > 0){
				texto += ' '+result.ignorados+' linha(s) ignorada(s).';
			}

			Swal.fire({
				title: 'Importação concluída!',
				text: texto,
				icon: 'success'
			}).then(function(){
				carregarLeads();
			});
		},
		error: function(){
			$('#response-importar').html('<div class="alert alert-danger">Erro ao enviar a planilha. Tente novamente.</div>');
			$btn.prop('disabled', false).html('<i class="fas fa-upload"></i> Importar');
		}
	});
});

$('#modalImportarLeads').on('hidden.bs.modal', function(){
	$('#form-importar-leads')[0].reset();
	$('#response-importar').html('');
	$('#btn-importar-leads').prop('disabled', false).html('<i class="fas fa-upload"></i> Importar');
});

function limparFiltroBuscaLead(){
	const el = document.getElementById('filtro-busca-lead');
	if(!el) return;

	const valor = (el.value || '').trim();

	if(valor.indexOf('@') !== -1){
		el.value = '';
		el.setAttribute('value', '');
	}
}

function resetarFiltroBuscaLead(){
	const el = document.getElementById('filtro-busca-lead');
	if(!el) return;

	el.value = '';
	el.setAttribute('value', '');
	el.removeAttribute('readonly');
}

function iniciarAntiAutofillFiltro(){
	resetarFiltroBuscaLead();

	[50, 150, 400, 800, 1200].forEach(function(ms){
		setTimeout(limparFiltroBuscaLead, ms);
	});

	$(document).on('focus', '#filtro-busca-lead', function(){
		$(this).removeAttr('readonly');
		limparFiltroBuscaLead();
	});
}

$(document).ready(function(){
	if(typeof listagemCrm !== 'undefined'){
		iniciarAntiAutofillFiltro();
		$('#filtro-curso').val('');
		carregarLeads(1);
		inicializarSortable();
	}
});

$(window).on('load', function(){
	if(typeof listagemCrm !== 'undefined'){
		resetarFiltroBuscaLead();
		setTimeout(limparFiltroBuscaLead, 100);
	}
});

$(function(){
	$('#form-anuncio-config').on('submit', function(e){
		e.preventDefault();
		const slots = [];
		$('.slot-cfg:checked').each(function(){ slots.push($(this).val()); });
		const data = {
			preco_minimo_mensal: $('input[name=preco_minimo_mensal]').val(),
			max_anuncios_por_empresa: $('input[name=max_anuncios_por_empresa]').val(),
			requer_aprovacao_master: $('#req_aprov').is(':checked') ? 1 : 0,
			slots: slots
		};
		$.post(url_base + 'master/conect-anuncios/config', data, function(res){
			if(res && res.success){
				Swal.fire('OK', res.message, 'success');
			} else {
				Swal.fire('Erro', (res && res.message) ? res.message : 'Falha.', 'error');
			}
		}, 'json');
	});

	let rejModal = null;
	$(document).on('click', '.btn-rejeitar', function(){
		$('#rej_id').val($(this).data('id'));
		$('#rej_motivo').val('');
		rejModal = new bootstrap.Modal(document.getElementById('modalRejeitar'));
		rejModal.show();
	});

	$('#btn-confirmar-rejeitar').on('click', function(){
		const id = $('#rej_id').val();
		$.post(url_base + 'master/conect-anuncios/' + id + '/reprovar', { motivo: $('#rej_motivo').val() }, function(res){
			if(res && res.success){
				if(rejModal) rejModal.hide();
				location.reload();
			} else {
				Swal.fire('Erro', (res && res.message) ? res.message : 'Falha.', 'error');
			}
		}, 'json');
	});

	let planoModal = null;
	function abrirModalPlano(data){
		$('#plano_id').val(data.id || 0);
		$('#plano_slug').val(data.slug || '').prop('disabled', !!(data.id && data.id > 0));
		$('#plano_nome').val(data.nome || '');
		$('#plano_desc').val(data.desc || '');
		$('#plano_max').val(data.max || 1);
		$('#plano_valor').val(data.valor || '');
		$('#plano_ordem').val(data.ordem || 0);
		$('#plano_ativo').prop('checked', data.ativo !== '0');
		planoModal = new bootstrap.Modal(document.getElementById('modalPlano'));
		planoModal.show();
	}

	$('#btn-novo-plano').on('click', function(){
		abrirModalPlano({});
	});

	$(document).on('click', '.btn-edit-plano', function(){
		const $b = $(this);
		abrirModalPlano({
			id: $b.data('id'),
			slug: $b.data('slug'),
			nome: $b.data('nome'),
			desc: $b.data('desc'),
			max: $b.data('max'),
			valor: $b.data('valor'),
			ordem: $b.data('ordem'),
			ativo: String($b.data('ativo'))
		});
	});

	$('#form-plano').on('submit', function(e){
		e.preventDefault();
		const data = {
			id: $('#plano_id').val(),
			slug: $('#plano_slug').val(),
			nome: $('#plano_nome').val(),
			descricao: $('#plano_desc').val(),
			max_anuncios: $('#plano_max').val(),
			valor_mensal: $('#plano_valor').val(),
			ordem: $('#plano_ordem').val(),
			ativo: $('#plano_ativo').is(':checked') ? 1 : 0
		};
		$.post(url_base + 'master/conect-anuncios/planos/salvar', data, function(res){
			if(res && res.success){
				if(planoModal) planoModal.hide();
				location.reload();
			} else {
				Swal.fire('Erro', (res && res.message) ? res.message : 'Falha.', 'error');
			}
		}, 'json');
	});
});

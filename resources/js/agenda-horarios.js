$(document).ready(function(){
	listar(null, 1);

	var params = new URLSearchParams(window.location.search);
	var lab = params.get('lab');
	if(lab){
		horaForm('', 'novo', lab);
		history.replaceState({}, '', window.location.pathname);
	}
});

function listar(filtro, page) {
	page = page || 1;
	$.ajax({
		url: url_base + listagem,
		method: 'post',
		data: { filtro, page },
		dataType: 'json',
		success: function(result){
			$('#listar').html(result.itens);
			$('#pagination').html(result.pagination);
		}
	});
}

function horaForm(id, funcao, laboratorio_id) {
	var data = { id, funcao };
	if(laboratorio_id){
		data.laboratorio_id = laboratorio_id;
	}
	$.ajax({
		url: url_base + formulario,
		method: 'post',
		data: data,
		dataType: 'json',
		success: function(result){
			if(result.erro){
				Swal.fire({ title: 'Erro', text: result.erro, icon: 'error' });
				return;
			}
			$('#listar-dados').html(result.form);
			$('#formModal').modal('show');
		}
	});
}

function horaExcluir(id) {
	Swal.fire({
		title: 'Excluir horário?',
		text: 'Alunos agendados neste horário perderão o vínculo.',
		icon: 'warning',
		showCancelButton: true,
		confirmButtonText: 'Sim, excluir'
	}).then(function(result){
		if(!result.isConfirmed) return;
		$.ajax({
			url: url_base + deletar,
			method: 'post',
			data: { id },
			dataType: 'json',
			success: function(ok){
				Swal.fire({ title: ok ? 'Excluído!' : 'Erro', icon: ok ? 'success' : 'error' });
				listar(null, 1);
			}
		});
	});
}

$(document).on('submit', '#formHora', function(event){
	event.preventDefault();
	$.ajax({
		url: url_base + edicao,
		type: 'POST',
		data: $(this).serialize(),
		dataType: 'json',
		success: function(response){
			if(response.erro){
				$('#response').html('<div class="alert alert-danger">'+response.erro+'</div>');
			} else {
				$('#formModal').modal('hide');
				Swal.fire({ title: 'Salvo!', icon: 'success' });
				listar(null, 1);
			}
		}
	});
});

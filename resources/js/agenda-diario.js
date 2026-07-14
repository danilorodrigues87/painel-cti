$(document).ready(function(){
	var hoje = new Date().toISOString().slice(0, 10);
	$('#data-diario').val(hoje);
	carregarDiario();
});

function carregarDiario() {
	var data = $('#data-diario').val();
	var laboratorio_id = $('#lab-diario').val() || 0;

	$.ajax({
		url: url_base + listagem,
		method: 'post',
		data: { data, laboratorio_id },
		dataType: 'json',
		success: function(result){
			$('#listar').html(result.table);
			if(result.labs_options){
				$('#lab-diario').html(result.labs_options);
			}
		}
	});
}

$(document).on('submit', '#formDiario', function(event){
	event.preventDefault();
	$.ajax({
		url: url_base + salvarDiario,
		type: 'POST',
		data: $(this).serialize(),
		dataType: 'json',
		success: function(response){
			if(response.erro){
				Swal.fire({ title: 'Atenção', text: response.erro, icon: 'warning' });
			} else {
				Swal.fire({ title: 'Diário salvo!', icon: 'success' });
			}
		},
		error: function(){
			Swal.fire({ title: 'Erro', text: 'Não foi possível salvar.', icon: 'error' });
		}
	});
});

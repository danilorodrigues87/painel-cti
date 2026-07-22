// CHAMA A FUNÇÃO LISTAR AO CARREGAR A PAGINA
function diaAtualAgenda() {
 var d = new Date().getDay();
 return d === 0 ? 1 : d;
}

$(document).ready(function(){
 listar(diaAtualAgenda(), 1);
})

// FUNÇÃO LISTAR CONTEUDOS DA PAGINA
function listar(filtro=null,page=1) {

 $.ajax({
    url: url_base+listaAgenda,
    method: "post",
    data: {filtro,page},
    dataType: "json", 
    success: function(result){

     $('#listar').html(result.itens);
     $('#pagination').html(result.pagination);

     $('#fil-1').removeClass('active')
     $('#fil-2').removeClass('active')
     $('#fil-3').removeClass('active')
     $('#fil-4').removeClass('active')
     $('#fil-5').removeClass('active')
     $('#fil-6').removeClass('active')

     if(result.filtro == 1){
      $('#fil-1').addClass('active')
  } else if(result.filtro  == 2){
      $('#fil-2').addClass('active')
  } else if(result.filtro  == 3){
      $('#fil-3').addClass('active')
  } else if(result.filtro  == 4){
      $('#fil-4').addClass('active')
  } else if(result.filtro  == 5){
      $('#fil-5').addClass('active')
  } else if(result.filtro  == 6){
      $('#fil-6').addClass('active')
  } 
},

})
}

// FUNÇÃO QUE CARREGA A MODAL E OS DADOS
function ver_info(id, funcao) {

   $.ajax({
    url: url_base+formulario,
    method: "post",
    data: { id, funcao },
    dataType: "text",
    success: function(result) {

  	$('#listar-dados').html(result);
	 $('#formModal').modal('show');

},

});

}

// FUNÇÃO DE EXCLUSÃO (remove slot do plano semanal)
function excluir(id) {

    $('#formModal').modal('hide');
    $('#formAgendamento').modal('hide');
    Swal.fire({
      title: "Remover este horário do plano do aluno?",
      text: "O aluno deixará de frequentar este horário.",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#3085d6",
      cancelButtonColor: "#d33",
      confirmButtonText: "Sim, remover!"
  }).then((result) => {

      if (result.isConfirmed) {
   
       $.ajax({
        url: url_base+deletar,
        method: "post",
        data: {id},
        dataType: "text",
        success: function(result){

            if(result){
                Swal.fire({
              title: "Removido!",
              text: "Horário removido do plano.",
              icon: "success"
          });
            } else {
                Swal.fire({
              title: "Erro",
              text: "Não foi possível remover.",
              icon: "error"
          });
            }
            
            listar(diaAtualAgenda(), 1);

        },

    
    })

   }
});

}

function editar(id_agenda=null,funcao) {

   $.ajax({
    url: url_base+edicao,
    method: "post",
    data: {id_agenda,funcao},
    dataType: "json",
    success: function(result) {

    $('#body_agendamento').html(result.form);
    $('#formAgendamento').modal('show');
    select_dia_semana(result.id_horario || 0);

},

});

}

function infoAlunoPlano() {
  var id_aluno = document.getElementById("id_aluno").value;
  if(!id_aluno || id_aluno == 0){
    $('#info-plano').addClass('d-none');
    return;
  }

  $.ajax({
    url: url_base + 'painel/agenda/laboratorio/aluno',
    method: 'post',
    data: { id_aluno },
    dataType: 'json',
    success: function(info){
      var limite = info.aulas_semanais || 0;
      var atual = info.planos_ativos || 0;
      var txt = 'Aulas/semana na matrícula: <strong>'+limite+'</strong> · No plano: <strong>'+atual+'</strong>';
      if(limite > 0 && atual >= limite){
        txt += ' <span class="text-danger">(limite atingido)</span>';
      }
      $('#info-plano').html(txt).removeClass('d-none');

      if(info.id_trilha){
        $('#id_trilha').val(info.id_trilha);
      }
    }
  });
}

function select_dia_semana(id_horario=null) {
  var dia_semana = document.getElementById("dia_semana").value;
  var laboratorio_id = document.getElementById("laboratorio_id") ? document.getElementById("laboratorio_id").value : 0;
  
  $.ajax({
   type: 'POST',
   url: url_base+'painel/agenda/laboratorio/horarios',
   data: {dia_semana, id_horario, laboratorio_id},
   success: function(e) {
    document.getElementById("horarios").innerHTML = e;
  }
})
  
}

// FUNÇÃO QUE EXECUTA UM CREATE OU UPDATE DE DADOS
$(document).on("submit", "#form", function(event) {
    event.preventDefault();

    $.ajax({
        url: url_base + salvar,
        type: "POST",
        dataType: "text",
        data: $(this).serialize(),
        success: function(response) {

          if (response.trim() !== "salvo") {
                $("#response").html('<div class="alert alert-danger">' + response + '</div>');

            } else {

                Swal.fire({
                    title: "Ótimo!",
                    text: "Horário adicionado ao plano semanal.",
                    icon: "success"
               });
                $('#btn-fechar-ag').click();

                listar(diaAtualAgenda(), 1);

            }

        },
        error: function(xhr, status, error) {
            $("#response").html('<div class="alert alert-danger">Ocorreu um erro ao processar a solicitação.</div>');
            console.log("Erro:", error);
        }
    });
});

// ——— Reposição / avulso ———
function abrirAvulso() {
  $.ajax({
    url: url_base + 'painel/agenda/laboratorio/avulso/form',
    method: 'post',
    dataType: 'text',
    success: function (html) {
      $('#listar-dados').html(html);
      $('#formModal').modal('show');
      setTimeout(function () {
        carregarHorariosAvulso();
        listarAvulsosHoje();
      }, 200);
    }
  });
}

function infoAlunoAvulso() {
  var id_aluno = $('#av_id_aluno').val();
  if (!id_aluno || id_aluno == 0) return;
  $.ajax({
    url: url_base + 'painel/agenda/laboratorio/aluno',
    method: 'post',
    data: { id_aluno },
    dataType: 'json',
    success: function (info) {
      if (info.id_trilha) $('#av_id_trilha').val(info.id_trilha);
    }
  });
}

function carregarHorariosAvulso() {
  var data = $('#av_data').val();
  if (!data) return;
  var d = new Date(data + 'T12:00:00');
  var dia = d.getDay();
  if (dia === 0) dia = 1;
  $('#av_dia_semana').val(dia);
  var laboratorio_id = $('#av_laboratorio_id').val() || 0;
  $.ajax({
    type: 'POST',
    url: url_base + 'painel/agenda/laboratorio/horarios',
    data: { dia_semana: dia, laboratorio_id: laboratorio_id },
    success: function (e) {
      $('#av_horarios').html(e);
    }
  });
}

function listarAvulsosHoje() {
  var data = $('#av_data').val() || '';
  if (!$('#lista-avulsos-wrap').length) {
    $('#form-avulso .modal-body').append('<hr><h6 class="mt-2">Repos nesta data</h6><div id="lista-avulsos-wrap"></div>');
  }
  $.ajax({
    url: url_base + 'painel/agenda/laboratorio/avulso/listar',
    method: 'post',
    data: { data },
    dataType: 'json',
    success: function (res) {
      $('#lista-avulsos-wrap').html(res.html || '');
    }
  });
}

$(document).on('change', '#av_data', function () {
  carregarHorariosAvulso();
  listarAvulsosHoje();
});

$(document).on('submit', '#form-avulso', function (event) {
  event.preventDefault();
  $.ajax({
    url: url_base + 'painel/agenda/laboratorio/avulso/salvar',
    type: 'POST',
    dataType: 'text',
    data: $(this).serialize(),
    success: function (response) {
      if (response.trim() !== 'salvo') {
        $('#response-avulso').html('<div class="alert alert-danger">' + response + '</div>');
      } else {
        Swal.fire({ title: 'Ótimo!', text: 'Reposição agendada.', icon: 'success' });
        listarAvulsosHoje();
        $('#form-avulso')[0].reset();
        $('#av_data').val(new Date().toISOString().slice(0, 10));
        carregarHorariosAvulso();
      }
    },
    error: function () {
      $('#response-avulso').html('<div class="alert alert-danger">Erro ao salvar.</div>');
    }
  });
});

function excluirAvulso(id) {
  Swal.fire({
    title: 'Remover esta reposição?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Sim, remover'
  }).then(function (r) {
    if (!r.isConfirmed) return;
    $.ajax({
      url: url_base + 'painel/agenda/laboratorio/avulso/excluir',
      method: 'post',
      data: { id },
      success: function () {
        listarAvulsosHoje();
      }
    });
  });
}

// CHAMA A FUNÇÃO LISTAR AO CARREGAR A PAGINA
$(document).ready(function(){
 listar(null,1);

})


// FUNÇÃO LITSAR CONTEUDOS DA PAGINA
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


// FUNÇÃO DE EXCLUSÃO
function excluir(id) {

    $('#formAgendamento').modal('hide');
    Swal.fire({
      title: "Você tem certeza que quer excluir esse item?",
      text: "Isso não poderá ser recuperado!",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#3085d6",
      cancelButtonColor: "#d33",
      confirmButtonText: "Sim, excluir!"
  }).then((result) => {

      if (result.isConfirmed) {
   
       $.ajax({
        url: url_base+deletar,
        method: "post",
        data: {id},
        dataType: "text",
        success: function(result){

            if(result){
                result = "Item excluido com sucesso!"
                let status = "success"
                Swal.fire({
              title: "Excluido!",
              text: result,
              icon: status
          });
            } else {
                let status = "error"
            }
            
            listar(null,1);

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
    select_dia_semana(result.id_horario)
    $('#btn-fechar').click();

},

});

}


function select_dia_semana(id_horario=null) {
  var dia_semana = document.getElementById("dia_semana").value;
  
  $.ajax({
   type: 'POST',
   url: url_base+'painel/agenda/laboratorio/horarios',
   data: {dia_semana,id_horario},
   success: function(e) {
    document.getElementById("horarios").innerHTML = e;
  }
})
  
}



// FUNÇÃO QUE EXECUTA UM CREATE OU UPDATE DE DADOS
$(document).on("submit", "#form", function(event) {
    event.preventDefault(); // Evita o envio do formulário de forma tradicional

    $.ajax({
        url: url_base + salvar,
        type: "POST",
        dataType: "text",
        data: $(this).serialize(), // Serializa os dados do formulário
        success: function(response) {


          if (!response == "salvo") {
                $("#response").html('<div class="alert alert-danger">' + response + '</div>');

            } else {

                Swal.fire({
                    title: "Óttimo!",
                    text: "Os dados foram atualizados com sucesso.",
                    icon: "success"
               });
                $('#btn-fechar-ag').click();

                listar(null,1);

            }

        },
        error: function(xhr, status, error) {
            // Lida com erros de requisição
            $("#response").html('<div class="alert alert-danger">Ocorreu um erro ao processar a solicitação.</div>');
            console.log("Erro:", error);
        }
    });
});
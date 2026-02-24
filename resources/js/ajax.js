
// CHAMA A FUNÇÃO LISTAR AO CARREGAR A PAGINA
$(document).ready(function(){
 listar(null,1);

})



// FUNÇÃO LITSAR CONTEUDOS DA PAGINA
function listar(filtro=null,page=1) {


 $.ajax({
    url: url_base+listagem,
    method: "post",
    data: {filtro,page},
    dataType: "json", 
    success: function(result){

     $('#listar').html(result.itens);
     $('#pagination').html(result.pagination);

        // FILTROS QUE SÃO USADOS APENAS NA TELA DE USUÁRIOS
     $('#fil-todos').removeClass('active')
     $('#fil-diretor').removeClass('active')
     $('#fil-secretario').removeClass('active')
     $('#fil-financeiro').removeClass('active')
     $('#fil-parceiro').removeClass('active')
     $('#fil-cliente').removeClass('active')
     $('#fil-inativo').removeClass('active')

     if(filtro == null){
      $('#fil-todos').addClass('active')
  } else if(filtro == 'Diretor'){
      $('#fil-diretor').addClass('active')
  } else if(filtro == 'Secretario'){
      $('#fil-secretario').addClass('active')
  } else if(filtro == 'Financeiro'){
      $('#fil-financeiro').addClass('active')
  } else if(filtro == 'Comercial'){
      $('#fil-comercial').addClass('active')
  }else if(filtro == 'inativo'){
      $('#fil-inativo').addClass('active')
  } else {
      $('#fil-todos').addClass('active')
  }
        // FIM DOS FILTROS DE USUÁRIOS

},

})
}



// FUNÇÃO DE EXCLUSÃO
function excluir(id) {

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
        dataType: "json",
        success: function(result){
            if(result){
                result = "Item excluido com sucesso!"
                let status = "success"
            } else {
                let status = "error"
            }
            Swal.fire({
              title: "Excluido!",
              text: result,
              icon: status
          });
            listar(null,1);
        },

    })

   }
});

}


// FUNÇÃO DE EXCLUSÃO
function cancelar_contrato(id) {

    Swal.fire({
      title: "Você tem certeza que quer cancelar esse contrato?",
      text: "Isso não poderá ser recuperado!",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#3085d6",
      cancelButtonColor: "#d33",
      confirmButtonText: "Sim, cancelar!"
  }).then((result) => {

      if (result.isConfirmed) {

       $.ajax({
        url: url_base+cancelar,
        method: "post",
        data: {id},
        dataType: "json",
        success: function(result){
            if(result){
                result = "Contrato cancelado com sucesso!"
                let status = "success"
            } else {
                let status = "error"
            }
            Swal.fire({
              title: "Cancelado!",
              text: result,
              icon: status
          });
            listar(null,1);
        },

    })

   }
});

}


// FUNÇÃO QUE EXECUTA UM CREATE OU UPDATE DE DADOS COM ENVIO DE IMAGEM
$(document).on("submit", "#formEmpresa", function(event) {
    event.preventDefault(); // Evita o envio do formulário de forma tradicional

    // Criando um objeto FormData para enviar dados, incluindo arquivos
    var formData = new FormData(this);

    $.ajax({
       url: url_base+edicao,
       type: "POST",
       data: formData, // Enviando os dados como FormData
       contentType: false, // Impede que o jQuery defina automaticamente o Content-Type (é necessário para upload de arquivos)
       processData: false, // Impede que o jQuery processe os dados
       dataType: "json",
       success: function(response) {
        console.log(response)
            // Processar a resposta
            // Verifica se o JSON contém o campo 'erro'
            if (response.erro) {
                // Exibe o erro se existir
                $("#response").html('<div class="alert alert-danger">' + response.erro + '</div>');
            } else {
                // Fecha o modal e exibe a mensagem de sucesso
                $('#btn-fechar').click();
                Swal.fire({
                    title: "Muito bem!",
                    text: "Os dados foram atualizados com sucesso.",
                    icon: "success"
                });
                // Chama a função listar com o filtro retornado
                listar(response.filtro, 1);
            }
        },
        error: function(xhr, status, error) {
            // Lida com erros de requisição
            $("#response").html('<div class="alert alert-danger">Ocorreu um erro ao processar a solicitação.</div>');
            console.log("Erro:", error);
        }
    });
});

$(document).on("submit", "#form", function(event) {
    event.preventDefault();

    // === ADICIONE ISSO AQUI ===
    // Sincroniza o conteúdo do CKEditor com o textarea original
    if (typeof meuEditor !== 'undefined') {
        document.querySelector('#editor').value = meuEditor.getData();
    }
    // ==========================

    // Agora o FormData vai capturar o texto já atualizado com as tags HTML
    var formData = new FormData(this);

    $.ajax({
        url: url_base + edicao,
        type: "POST",
        dataType: "json",
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
     
            if (response.erro) {
                $("#response").html('<div class="alert alert-danger">' + response.erro + '</div>');
            } else {
                $('#btn-fechar').click();
                Swal.fire({
                    title: "Muito bem!",
                    text: "Os dados foram atualizados com sucesso.",
                    icon: "success"
                });
                listar(response.filtro, 1);
            }
        },
        error: function(xhr, status, error) {
            $("#response").html('<div class="alert alert-danger">Ocorreu um erro ao processar a solicitação.</div>');
            console.log("Erro:", error);
        }
    });
});


// FUNÇÃO QUE CARREGA A MODAL E OS DADOS
function list_itens(id, funcao) {

   $.ajax({
    url: url_base+formulario,
    method: "post",
    data: { id, funcao },
    dataType: "json",
    success: function(result) {

            // Verifica se o result é um objeto
      if (typeof result === 'object' && result !== null) {
            // Se for um objeto, assume que possui as propriedades form e cidade
        $('#listar-dados').html(result.form);

        if (result.cidade) {
          selectEstado(result.cidade);
      }
  } else {

    // Se não for um objeto, assume que é uma string
   $('#listar-dados').html(result);

   if(formulario == 'painel/matriculas/form'){
       selectAluno()
       //check()
   }
}

$('#formModal').modal('show');

},

});

}


// FUNÇÃO QUE CARREGA A MODAL E OS DADOS
function darBaixa(id) {

   $.ajax({
    url: url_base+formBaixa,
    method: "post",
    data: {id},
    dataType: "text",
    success: function(result) {

        $('#formModal').modal('hide');
        $('#modalDarBaixa').modal('show');
        $('#body_dar_baixa').html(result);

    },

});

}




function selectAluno(id_aluno) {
    // Verifica se id_aluno é numérico e diferente de 0
    if (!isNaN(id_aluno) && id_aluno != 0) {
        // Faz a requisição AJAX
        $.ajax({
            type: 'POST',
            url: url_base + buscaResponsavel,
            data: { id_aluno: id_aluno },  // Passa o id do aluno
            dataType: "json",
            success: function(result) {
                console.log("Resultado da requisição AJAX:", result);

                // Verifica se o resultado é válido
                if (result) {
                    document.getElementById("nome_responsavel").value = result.nome;
                    document.getElementById("id_responsavel").value = result.id;
                }
            },
            error: function(xhr, status, error) {
                console.error("Erro na requisição AJAX:", status, error);
            }
        });
    } else {
        console.log("ID do aluno inválido ou igual a 0.");
    }
}


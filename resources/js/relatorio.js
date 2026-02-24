
// LISTAGEM POR BUSCA
$(document).on("submit", "#formBusca", function(event) {
    event.preventDefault(); // Evita o envio do formulário de forma tradicional
    $.ajax({
     url: url_base+listagem,
     type: "POST",
        data: $(this).serialize(), // Serializa os dados do formulário
        dataType: "json", 
        success: function(result) {


            $('#listar').html(result.itens);
            $('#filtragem').html(result.filtragem);

        }
    });
});




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
        $('#filtragem').html(result.filtragem);
 

},

})
}

function gerarPdf() {
    const conteudoCompleto = document.querySelector("#listar");
    const conteudoClone = conteudoCompleto.cloneNode(true); // Clonar o conteúdo para manipulação

    // Criar dois containers: um para a primeira página e outro para as demais
    const conteudoPrimeiraPagina = document.createElement("div");
    const conteudoDemaisPaginas = document.createElement("div");

    // Adicionar o título no cabeçalho da primeira página
    const tituloCabecalho = document.createElement("div");
    tituloCabecalho.style.textAlign = "center";
    tituloCabecalho.style.fontSize = "20px";
    tituloCabecalho.style.marginBottom = "5mm"; // Ajustando a margem inferior do título
    tituloCabecalho.innerText = "Relatório Financeiro";

    // Adicionar o título e o conteúdo na primeira página
    conteudoPrimeiraPagina.appendChild(tituloCabecalho);
    conteudoPrimeiraPagina.innerHTML += conteudoClone.innerHTML;

    // Adicionar apenas o conteúdo nas demais páginas
    conteudoDemaisPaginas.innerHTML = conteudoClone.innerHTML;

    // Configurar margens específicas
    conteudoPrimeiraPagina.style.marginTop = "0mm"; // Margem menor para a primeira página
    conteudoDemaisPaginas.style.marginTop = "0mm"; // Margem menor para as demais páginas

    // Configurar opções do PDF
    const opt = {
        margin: [5, 5, 5, 5], // Margem padrão (será ajustada no conteúdo)
        filename: 'relatorio.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'landscape' }
    };

    // Instanciar o html2pdf e gerar o PDF com os dois containers
    const worker = html2pdf();

    worker.from(conteudoPrimeiraPagina)
        .set(opt)
        .toContainer()
        .then(function () {
            worker.from(conteudoDemaisPaginas)
                .set(opt)
                .save();
        });
}

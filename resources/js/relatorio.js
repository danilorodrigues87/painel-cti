
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
 prepararImpressaoRelatorio();
})

/** Oculta botões/filtros na impressão do navegador (fallback além do CSS). */
function prepararImpressaoRelatorio() {
    var ocultos = [];

    function esconder(el) {
        if (!el || el.getAttribute('data-print-hidden') === '1') return;
        ocultos.push({ el: el, display: el.style.display, visibility: el.style.visibility });
        el.setAttribute('data-print-hidden', '1');
        el.style.display = 'none';
        el.style.visibility = 'hidden';
    }

    function restaurar() {
        ocultos.forEach(function (item) {
            item.el.style.display = item.display;
            item.el.style.visibility = item.visibility;
            item.el.removeAttribute('data-print-hidden');
        });
        ocultos = [];
    }

    window.addEventListener('beforeprint', function () {
        document.querySelectorAll(
            '#filtragem, #relatorio-filtragem, .relatorio-acoes, #listar .btn-group, #listar button'
        ).forEach(esconder);
    });

    window.addEventListener('afterprint', restaurar);
}




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

/** Clone pronto para PDF: fundo branco, sem botões, cabeçalho/rodapé visíveis. */
function prepararRelatorioParaImpressao(node) {
    var clone = node.cloneNode(true);

    clone.querySelectorAll('.relatorio-acoes, .no-print, .d-print-none, .btn-group, button, .btn').forEach(function (el) {
        el.remove();
    });

    clone.querySelectorAll('.relatorio-cabecalho .d-flex').forEach(function (el) {
        el.style.display = 'flex';
    });

    clone.style.background = '#fff';
    clone.style.color = '#000';

    clone.querySelectorAll('.relatorio-financeiro-tabela, table, thead, tbody, tr, th, td').forEach(function (el) {
        el.style.background = '#fff';
        el.style.backgroundColor = '#fff';
        el.style.color = '#000';
        el.style.borderColor = '#ccc';
    });

    clone.querySelectorAll('.table-striped tbody tr:nth-child(odd) td').forEach(function (el) {
        el.style.background = '#f5f5f5';
        el.style.backgroundColor = '#f5f5f5';
        el.style.color = '#000';
    });

    return clone;
}

function gerarPdf() {
    var conteudoCompleto = document.querySelector("#listar .relatorio-financeiro-impressao")
        || document.querySelector("#listar");
    if (!conteudoCompleto) return;

    var conteudoClone = prepararRelatorioParaImpressao(conteudoCompleto);

    var wrapper = document.createElement("div");
    wrapper.style.background = '#fff';
    wrapper.style.color = '#000';
    wrapper.style.padding = '8px';
    wrapper.appendChild(conteudoClone);

    var opt = {
        margin: [8, 8, 8, 8],
        filename: 'relatorio-financeiro.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: {
            scale: 2,
            useCORS: true,
            backgroundColor: '#ffffff'
        },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'landscape' }
    };

    html2pdf().from(wrapper).set(opt).save();
}

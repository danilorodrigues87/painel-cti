// Atualiza o resumo do carrinho (card flutuante)
function atualizarWidgetCarrinho() {
	$.ajax({
		url: url_base + rotaCarrinhoResumo,
		method: "post",
		dataType: "json",
		success: function(result){

			$('#carrinho-qtd').text(result.qtd);
			$('#carrinho-total').text('R$ ' + result.total);
			$('#carrinho-itens').html(result.html_itens);

			if(result.qtd > 0){
				$('#caixa-carrinho-widget').removeClass('d-none');
			} else {
				$('#caixa-carrinho-widget').addClass('d-none');
			}
		}
	});
} 

// Adiciona um título (lançamento do caixa) ao carrinho
function addCarrinhoTitulo(id){

	$.ajax({
		url: url_base + rotaCarrinhoAddTitulo,
		method: "post",
		data: {id},
		dataType: "json",
		success: function(result){

			if(result.erro){
				Swal.fire({
					title: "Ops...",
					text: result.erro,
					icon: "error"
				});
			} else {
				Swal.fire({
					title: "Adicionado!",
					text: "Título adicionado ao carrinho.",
					icon: "success",
					timer: 1200,
					showConfirmButton: false
				});
				atualizarWidgetCarrinho();
			}

		}
	});
}

// Remove item do carrinho
function removerItemCarrinho(id){
	$.ajax({
		url: url_base + rotaCarrinhoRemove,
		method: "post",
		data: {id},
		dataType: "json",
		success: function(result){
			if(result.erro){
				Swal.fire({
					title: "Ops...",
					text: result.erro,
					icon: "error"
				});
			}
			atualizarWidgetCarrinho();
		}
	});
}

// Abre/fecha os detalhes do carrinho
function toggleCarrinhoDetalhes(){
	$('#carrinho-detalhes').toggleClass('d-none');
}

// Form para adicionar item avulso (serviço/produto)
$(document).on("submit", "#form-carrinho-add-avulso", function(event){
	event.preventDefault();

	const form = $(this);

	$.ajax({
		url: url_base + rotaCarrinhoAddAvulso,
		method: "post",
		data: form.serialize(),
		dataType: "json",
		success: function(result){
			if(result.erro){
				$("#response-carrinho-avulso").html('<div class="alert alert-danger mb-1">'+result.erro+'</div>');
			} else {
				form.trigger("reset");
				$("#response-carrinho-avulso").html('<div class="alert alert-success mb-1">Item adicionado ao carrinho.</div>');
				atualizarWidgetCarrinho();
			}
		}
	});
});

// Abre o modal de pagamento do carrinho
function abrirPagamentoCarrinho(){
	$.ajax({
		url: url_base + rotaCarrinhoForm,
		method: "post",
		dataType: "json",
		success: function(result){
			$('#body_carrinho_pagamento').html(result);
			$('#modalCarrinhoPagamento').modal('show');
		}
	});
}

// Submit do formulário de pagamento do carrinho
$(document).on("submit", "#form-carrinho", function(event){
	event.preventDefault();

	var formData = $(this).serialize();

	$.ajax({
		url: url_base + rotaCarrinhoFinalizar,
		method: "post",
		data: formData,
		dataType: "json",
		success: function(response){
			if(response.erro){
				$("#response-carrinho").html('<div class="alert alert-danger">'+response.erro+'</div>');
			} else {
				$('#modalCarrinhoPagamento').modal('hide');
				Swal.fire({
					title: "Pagamento registrado!",
					text: "Total recebido: R$ "+response.total,
					icon: "success"
				});
				atualizarWidgetCarrinho();
				// Recarrega a listagem atual de carnês
				if(typeof listar === 'function'){
					listar(null,1);
				}
			}
		}
	});
});

// Calcula o troco no formulário do carrinho
function calcularTrocoCarrinho(){
	let valorPagar = parseFloat($('#valor_pagar_total').val() || 0);
	let recebidoStr = $('#valor_recebido_carrinho').val() || '0';

	// Remove tudo que não for número ou vírgula/ponto
	recebidoStr = recebidoStr.replace(/[^\d.,]/g,'').replace(',','.');

	let valorRecebido = parseFloat(recebidoStr || 0);

	let troco = 0;
	if(!isNaN(valorRecebido) && valorRecebido > valorPagar){
		troco = valorRecebido - valorPagar;
	}

	$('#troco_carrinho').val(troco.toFixed(2).replace('.',','));
}

// Ao carregar a página de carnês, atualiza o widget
$(document).ready(function(){
	if(typeof rotaCarrinhoResumo !== 'undefined'){
		atualizarWidgetCarrinho();
	}
});


<?php 

namespace App\Controller\Admin;

use \App\Model\Entity\CaixaCarrinho as EntityCaixaCarrinho;
use \App\Model\Entity\Caixa as EntityCaixa;
use \App\Common\Helpers\DateTimeHelper;
use \App\Common\Helpers\NumeroHelper;

class CaixaCarrinho extends Page{

	//RESUMO DO CARRINHO (USADO PELO CARD FLUTUANTE)
	public static function getResumo($request){

		$dadosUser = parent::getIdAdmin();
		$id_admin  = $dadosUser['usuario']['id_admin'];
		$id_usuario = $dadosUser['usuario']['id'];

		$results = EntityCaixaCarrinho::getCaixaCarrinho(
			'id_admin = '.(int)$id_admin.' AND id_usuario = '.(int)$id_usuario,
			'id DESC'
		);

		$itensHtml = '';
		$total = 0;
		$qtd   = 0;

		while ($obItem = $results->fetchObject(EntityCaixaCarrinho::class)) {
			$qtd++;
			$total += (float)$obItem->valor;

			$tipo = ucfirst($obItem->tipo);

			$itensHtml .= '
			<li class="list-group-item d-flex justify-content-between align-items-center">
				<div>
					<strong>'.$obItem->descricao.'</strong><br>
					<small class="text-muted">'.$tipo.'</small>
				</div>
				<div class="text-end">
					<span>R$ '.NumeroHelper::moedaBr($obItem->valor).'</span><br>
					<button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="removerItemCarrinho('.$obItem->id.')">
						&times; remover
					</button>
				</div>
			</li>';
		}

		if($qtd == 0){
			$itensHtml = '
			<li class="list-group-item">
				<small class="text-muted">Nenhum item no carrinho.</small>
			</li>';
		}

		$conteudo = [
			'qtd'        => $qtd,
			'total'      => NumeroHelper::moedaBr($total),
			'html_itens' => $itensHtml
		];

		return json_encode($conteudo);
	}

	//ADICIONA UM TÍTULO (LANÇAMENTO DO CAIXA) AO CARRINHO
	public static function addTitulo($request){

		$postVars = $request->getPostVars();
		$idCaixa  = isset($postVars['id']) ? (int)$postVars['id'] : 0;

		$dadosUser = parent::getIdAdmin();
		$id_admin  = $dadosUser['usuario']['id_admin'];
		$id_usuario = $dadosUser['usuario']['id'];

		$resposta = [];

		if($idCaixa <= 0){
			$resposta['erro'] = 'Título inválido.';
			return json_encode($resposta);
		}

		$obCaixa = EntityCaixa::getCaixaById($idCaixa);

		if(!$obCaixa instanceof EntityCaixa){
			$resposta['erro'] = 'Título não encontrado.';
			return json_encode($resposta);
		}

		if($obCaixa->status == 1){
			$resposta['erro'] = 'Este título já está pago.';
			return json_encode($resposta);
		}

		//VERIFICA SE JÁ ESTÁ NO CARRINHO
		$existe = EntityCaixaCarrinho::getCaixaCarrinho(
			'id_admin = '.(int)$id_admin.
			' AND id_usuario = '.(int)$id_usuario.
			' AND referencia_id = '.$idCaixa.
			' AND tipo = "titulo"'
		)->fetchObject(EntityCaixaCarrinho::class);

		
		if($existe instanceof EntityCaixaCarrinho){
			$resposta['erro'] = 'Este título já foi adicionado ao carrinho.';
			return json_encode($resposta);
		}

		$descricao = $obCaixa->descricao.' - Venc. '.DateTimeHelper::databr($obCaixa->vencimento);

		$obCarrinho = new EntityCaixaCarrinho;
		$obCarrinho->id_admin      = $id_admin;
		$obCarrinho->id_usuario    = $id_usuario;
		$obCarrinho->referencia_id = $idCaixa;
		$obCarrinho->tipo          = 'titulo';
		$obCarrinho->descricao     = $descricao;
		$obCarrinho->valor         = (float)$obCaixa->valor;
		$obCarrinho->cadastrar();

		$resposta['sucesso'] = true;

		return json_encode($resposta);
	}

	//ADICIONA UM ITEM AVULSO (SERVIÇO/PRODUTO) AO CARRINHO
	public static function addAvulso($request){

		$postVars   = $request->getPostVars();
		$descricao  = trim($postVars['descricao'] ?? '');
		$valorBruto = $postVars['valor'] ?? '';

		$dadosUser = parent::getIdAdmin();
		$id_admin  = $dadosUser['usuario']['id_admin'];
		$id_usuario = $dadosUser['usuario']['id'];

		$resposta = [];

		if($descricao == ''){
			$resposta['erro'] = 'Informe a descrição do serviço/produto.';
			return json_encode($resposta);
		}

		$valor = (float) NumeroHelper::removerFormatacaoNumero($valorBruto);

		if($valor <= 0){
			$resposta['erro'] = 'Informe um valor válido.';
			return json_encode($resposta);
		}

		$obCarrinho = new EntityCaixaCarrinho;
		$obCarrinho->id_admin      = $id_admin;
		$obCarrinho->id_usuario    = $id_usuario;
		$obCarrinho->referencia_id = 0;
		$obCarrinho->tipo          = 'servico';
		$obCarrinho->descricao     = $descricao;
		$obCarrinho->valor         = $valor;
		$obCarrinho->cadastrar();

		$resposta['sucesso'] = true;

		return json_encode($resposta);
	}

	//REMOVE ITEM DO CARRINHO
	public static function removerItem($request){

		$postVars = $request->getPostVars();
		$id       = isset($postVars['id']) ? (int)$postVars['id'] : 0;

		$dadosUser = parent::getIdAdmin();
		$id_admin  = $dadosUser['usuario']['id_admin'];
		$id_usuario = $dadosUser['usuario']['id'];

		$resposta = [];

		if($id <= 0){
			$resposta['erro'] = 'Item inválido.';
			return json_encode($resposta);
		}

		EntityCaixaCarrinho::deleteById($id,$id_admin,$id_usuario);

		$resposta['sucesso'] = true;

		return json_encode($resposta);
	}

	//FORMULÁRIO DE PAGAMENTO DO CARRINHO
	public static function formPagamento($request){

		$dadosUser = parent::getIdAdmin();
		$id_admin  = $dadosUser['usuario']['id_admin'];
		$id_usuario = $dadosUser['usuario']['id'];

		$results = EntityCaixaCarrinho::getCaixaCarrinho(
			'id_admin = '.(int)$id_admin.' AND id_usuario = '.(int)$id_usuario,
			'id DESC'
		);

		$itensHtml = '';
		$total = 0;

		while ($obItem = $results->fetchObject(EntityCaixaCarrinho::class)) {
			$total += (float)$obItem->valor;

			$itensHtml .= '
			<li class="list-group-item d-flex justify-content-between align-items-center">
				<div>
					<strong>'.$obItem->descricao.'</strong><br>
					<small class="text-muted">'.ucfirst($obItem->tipo).'</small>
				</div>
				<span>R$ '.NumeroHelper::moedaBr($obItem->valor).'</span>
			</li>';
		}

		if($itensHtml == ''){
			$itensHtml = '
			<li class="list-group-item">
				<small class="text-muted">Nenhum item no carrinho.</small>
			</li>';
		}

		$valorTotalBr = NumeroHelper::moedaBr($total);

		$form = '
		<form id="form-carrinho" method="post">
			<div class="modal-header">
				<h1 class="modal-title fs-5" id="exampleModalLabel">Pagamento do carrinho</h1>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<div id="response-carrinho"></div>
				<ul class="list-group mb-3 col-md-12">
					'.$itensHtml.'
					<li class="list-group-item d-flex justify-content-between">
						<span>Total a pagar</span>
						<strong>R$ '.$valorTotalBr.'</strong>
					</li>
				</ul>

				<input value="'.$total.'" type="hidden" id="valor_pagar_total" name="valor_pagar_total">

				<div class="row">
					<div class="form-group col-md-6">
						<label>Forma de pagamento</label>
						<select name="tipo_pagamento" class="form-control">
							<option value="">Selecione o tipo</option>
							<option value="Dinheiro">Dinheiro</option>  
							<option value="Pix">Pix</option>    
							<option value="Cartão">Cartão</option>    
							<option value="Boleto">Boleto</option>                   
						</select>
					</div>

					<div class="form-group col-md-6">
						<label>Data de pagamento</label>
						<input type="datetime-local" name="data_pagamento" value="'.DateTimeHelper::agora().'" class="form-control">
					</div>

					<div class="form-group col-md-6">
						<label>Valor recebido</label>
						<input type="text" id="valor_recebido_carrinho" name="valor_recebido" class="form-control" oninput="calcularTrocoCarrinho()" required>
					</div>

					<div class="form-group col-md-6">
						<label>Troco</label>
						<input type="text" id="troco_carrinho" readonly class="form-control">
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" id="btn-fechar-carrinho" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
				<button type="submit" class="btn btn-primary">Finalizar pagamento</button>
			</div>
		</form>';

		return json_encode($form);
	}

	//FINALIZA O PAGAMENTO DO CARRINHO
	public static function finalizar($request){

		$postVars = $request->getPostVars();

		$resposta = [];

		$tipo_pagamento = $postVars['tipo_pagamento'] ?? '';
		$data_pagamento = $postVars['data_pagamento'] ?? '';

		$valor_pagar_total = (float)($postVars['valor_pagar_total'] ?? 0);

		$valor_recebido = (float) NumeroHelper::removerFormatacaoNumero($postVars['valor_recebido'] ?? '0');

		if($valor_pagar_total <= 0){
			$resposta['erro'] = 'Carrinho vazio ou total inválido.';
			return json_encode($resposta);
		}

		if($valor_recebido < $valor_pagar_total){
			$resposta['erro'] = 'Valor recebido é menor que o total a receber.';
			return json_encode($resposta);
		}

		if($tipo_pagamento == ''){
			$resposta['erro'] = 'Selecione uma forma de pagamento.';
			return json_encode($resposta);
		}

		$dadosUser = parent::getIdAdmin();
		$id_admin  = $dadosUser['usuario']['id_admin'];
		$id_usuario = $dadosUser['usuario']['id'];

		//BUSCA ITENS DO CARRINHO
		$results = EntityCaixaCarrinho::getCaixaCarrinho(
			'id_admin = '.(int)$id_admin.' AND id_usuario = '.(int)$id_usuario,
			'id ASC'
		);

		$totalCalculado = 0;

		while ($obItem = $results->fetchObject(EntityCaixaCarrinho::class)) {

			if($obItem->tipo == 'titulo'){

				$obCaixa = EntityCaixa::getCaixaById($obItem->referencia_id);

				if(!$obCaixa instanceof EntityCaixa){
					continue;
				}

				//CÁLCULO BÁSICO (MANTÉM VALOR ORIGINAL)
				$valorItem = (float)$obCaixa->valor;
				$totalCalculado += $valorItem;

				$obUpdate = new EntityCaixa;
				$obUpdate->id             = $obCaixa->id;
				$obUpdate->valor_pago     = $valorItem;
				$obUpdate->data_pagamento = $data_pagamento;
				$obUpdate->tipo_pagamento = $tipo_pagamento;
				$obUpdate->status         = 1;
				$obUpdate->atualizar();

			} else {

				//ITEM AVULSO - LANÇA COMO ENTRADA AVULSA NO CAIXA
				$valorItem = (float)$obItem->valor;
				$totalCalculado += $valorItem;

				$obCaixa = new EntityCaixa;
				$obCaixa->id_admin       = $id_admin;
				$obCaixa->descricao      = $obItem->descricao;
				$obCaixa->valor          = $valorItem;
				$obCaixa->valor_pago     = $valorItem;
				$obCaixa->vencimento     = $data_pagamento;
				$obCaixa->data_pagamento = $data_pagamento;
				$obCaixa->tipo_pagamento = $tipo_pagamento;
				$obCaixa->tipo_transacao = 'Entrada';
				$obCaixa->referencia     = 'Venda/serviço avulso';
				$obCaixa->id_ref         = 0;
				$obCaixa->txt_id         = '';
				$obCaixa->pix_copia_cola = '';
				$obCaixa->nosso_numero   = '';
				$obCaixa->status         = 1;
				$obCaixa->lancarMovimentacao();
			}
		}

		if($totalCalculado <= 0){
			$resposta['erro'] = 'Nenhum item válido encontrado no carrinho.';
			return json_encode($resposta);
		}

		//LIMPA O CARRINHO
		EntityCaixaCarrinho::clearByUser($id_admin,$id_usuario);

		$resposta['sucesso'] = true;
		$resposta['total']   = NumeroHelper::moedaBr($totalCalculado);
		$resposta['filtro']  = 'hoje';

		return json_encode($resposta);
	}

}


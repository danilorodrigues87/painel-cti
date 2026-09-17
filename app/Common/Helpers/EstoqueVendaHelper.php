<?php

namespace App\Common\Helpers;

use App\Model\Entity\Caixa as EntityCaixa;
use App\Common\Helpers\CaixaRecebimentoService;
use App\Model\Entity\Estoque\Stq_Produtos;
use App\Model\Entity\Estoque\Stq_VendaItens;
use App\Model\Entity\Estoque\Stq_Vendas;
use App\Session\User\Login as SessionUser;

class EstoqueVendaHelper {

	private static $formas = ['Dinheiro', 'Pix', 'Cartão', 'Transferência', 'Boleto'];

	/**
	 * Finaliza venda PDV: valida estoque, grava venda/itens, baixa estoque, lança Entrada paga.
	 *
	 * @param array<int,array{id_produto:int,qtd:int,valor_unitario?:float}> $itens
	 * @param string|array<int,array{tipo_pagamento:string,valor:float}> $tipoPagamentoOuLinhas
	 * @return array{ok:bool,message:string,id_venda?:int,id_caixa?:int,total?:float}
	 */
	public static function finalizar(int $idAdmin, array $itens, $tipoPagamentoOuLinhas, string $observacao = ''): array {
		$pagamentos = is_array($tipoPagamentoOuLinhas)
			? CaixaRecebimentoService::normalizarLinhas($tipoPagamentoOuLinhas)
			: [];
		$tipoLegado = is_string($tipoPagamentoOuLinhas) ? trim($tipoPagamentoOuLinhas) : '';
		if (empty($pagamentos)) {
			if ($tipoLegado === '' || !in_array($tipoLegado, self::$formas, true)) {
				return ['ok' => false, 'message' => 'Forma de pagamento inválida.'];
			}
		}
		if (empty($itens)) {
			return ['ok' => false, 'message' => 'Inclua ao menos um produto.'];
		}

		$produtos = [];
		$total = 0.0;

		foreach ($itens as $raw) {
			$idProduto = (int)($raw['id_produto'] ?? 0);
			$qtd = (int)($raw['qtd'] ?? 0);
			if ($idProduto <= 0 || $qtd <= 0) {
				return ['ok' => false, 'message' => 'Item inválido no carrinho.'];
			}
			$prod = Stq_Produtos::getByIdAdmin($idProduto, $idAdmin);
			if (!$prod || (int)($prod->status ?? 1) !== 1) {
				return ['ok' => false, 'message' => 'Produto #' . $idProduto . ' não encontrado.'];
			}
			if ((int)$prod->quantidade < $qtd) {
				return [
					'ok' => false,
					'message' => 'Estoque insuficiente para "' . $prod->nome . '" (disp.: ' . (int)$prod->quantidade . ').',
				];
			}
			$unit = array_key_exists('valor_unitario', $raw) && $raw['valor_unitario'] !== '' && $raw['valor_unitario'] !== null
				? (float)str_replace(',', '.', (string)$raw['valor_unitario'])
				: (float)$prod->valor_venda;
			if ($unit < 0) {
				return ['ok' => false, 'message' => 'Valor unitário inválido.'];
			}
			$sub = round($unit * $qtd, 2);
			$total += $sub;
			$produtos[] = [
				'produto' => $prod,
				'qtd' => $qtd,
				'valor_unitario' => $unit,
				'subtotal' => $sub,
			];
		}

		$total = round($total, 2);
		if ($total <= 0) {
			return ['ok' => false, 'message' => 'Total da venda deve ser maior que zero.'];
		}

		if (!empty($pagamentos)) {
			$somaPag = 0.0;
			foreach ($pagamentos as $p) {
				$somaPag += $p['valor'];
			}
			if (abs($somaPag - $total) > FinanceiroAlunoHelper::TOLERANCIA_VALOR) {
				return ['ok' => false, 'message' => 'Soma das formas de pagamento deve igualar o total da venda.'];
			}
		}

		$user = SessionUser::getUserLogedData();
		$idUsuario = (int)($user['usuario']['id'] ?? 0);

		$venda = new Stq_Vendas();
		$venda->id_admin = $idAdmin;
		$venda->id_usuario = $idUsuario > 0 ? $idUsuario : null;
		$venda->total = $total;
		$venda->tipo_pagamento = !empty($pagamentos)
			? CaixaRecebimentoService::resumoTipoPagamento($pagamentos)
			: $tipoLegado;
		$venda->id_caixa = null;
		$venda->observacao = $observacao !== '' ? mb_substr($observacao, 0, 500) : null;
		if (!$venda->cadastrar()) {
			return ['ok' => false, 'message' => 'Falha ao registrar a venda.'];
		}

		foreach ($produtos as $linha) {
			$item = new Stq_VendaItens();
			$item->id_venda = (int)$venda->id;
			$item->id_produto = (int)$linha['produto']->id;
			$item->nome_snapshot = (string)$linha['produto']->nome;
			$item->qtd = $linha['qtd'];
			$item->valor_unitario = $linha['valor_unitario'];
			$item->subtotal = $linha['subtotal'];
			$item->cadastrar();

			try {
				$linha['produto']->saidaEstoque(
					$linha['qtd'],
					$idAdmin,
					'Venda PDV #' . (int)$venda->id
				);
			} catch (\Throwable $e) {
				return [
					'ok' => false,
					'message' => 'Falha ao baixar estoque: ' . $e->getMessage() . ' (venda #' . (int)$venda->id . ' parcialmente registrada — revise o estoque).',
					'id_venda' => (int)$venda->id,
				];
			}
		}

		$hoje = date('Y-m-d');
		$desc = 'Venda PDV #' . (int)$venda->id;
		$nomes = array_map(function ($l) {
			return $l['produto']->nome . ' x' . $l['qtd'];
		}, $produtos);
		if (count($nomes) <= 3) {
			$desc .= ' — ' . implode(', ', $nomes);
		} else {
			$desc .= ' — ' . count($nomes) . ' itens';
		}

		$caixa = new EntityCaixa();
		$caixa->id_admin = $idAdmin;
		$caixa->descricao = mb_substr($desc, 0, 250);
		$caixa->tipo_transacao = 'Entrada';
		$caixa->tipo_pagamento = '';
		$caixa->valor = $total;
		$caixa->valor_pago = 0;
		$caixa->vencimento = $hoje;
		$caixa->data_pagamento = '';
		$caixa->referencia = 'venda_stq';
		$caixa->id_ref = (int)$venda->id;
		$caixa->status = \App\Common\Helpers\FinanceiroAlunoHelper::STATUS_ABERTO;
		$caixa->txt_id = '';
		$caixa->pix_copia_cola = '';
		$caixa->nosso_numero = '';
		$caixa->lancarMovimentacao();

		$idCaixa = (int)($caixa->id ?? 0);
		if ($idCaixa > 0) {
			$venda->atualizarCaixa($idCaixa);
			if (!empty($pagamentos)) {
				$resRec = CaixaRecebimentoService::registrar(
					$idAdmin,
					$idCaixa,
					$pagamentos,
					$hoje,
					false,
					$total
				);
				if (empty($resRec['ok'])) {
					return ['ok' => false, 'message' => $resRec['message'] ?? 'Falha ao registrar pagamento.'];
				}
			} else {
				$caixa->id = $idCaixa;
				$caixa->valor_pago = $total;
				$caixa->tipo_pagamento = $tipoLegado;
				$caixa->data_pagamento = $hoje;
				$caixa->status = FinanceiroAlunoHelper::STATUS_PAGO;
				$caixa->atualizar();
			}
		}

		return [
			'ok' => true,
			'message' => 'Venda #' . (int)$venda->id . ' registrada.',
			'id_venda' => (int)$venda->id,
			'id_caixa' => $idCaixa,
			'total' => $total,
		];
	}
}

<?php 

namespace App\Controller\Admin;
use \App\Utils\View;
use \App\Model\Entity\Caixa as EntityCaixa;
use \App\Model\Db\Pagination;
use \App\Common\Helpers\DateTimeHelper;
use \App\Common\Helpers\NumeroHelper;
use \App\Common\Helpers\TenantHelper;
use \App\Common\Helpers\FinanceiroAlunoHelper;
use \App\Common\Helpers\CaixaRecebimentoService;
use \App\Common\Helpers\EncargosContratoHelper;
use \App\Model\Entity\Matriculas as EntityMatri;

class CaixaEntrada extends Page{

	//RETORNA O FORMULARIO
	public static function index($request){
		//CONTEÚDO DE FORMULÁRIO
		$content = View::render('admin/modules/caixa/entrada',[]);

		//RETORNA A PÁGINA COMPLETA
		/**
         * TITULO DA PAGINA
         * CONTEUDO
         * CURRENTSESSION SESSÃO ATUAL
         * REQUEST SE NESCESSÁRIO
         */
		return parent::getPanel('Entrada',$content,'Financeiro');
	}

	private static function getDataItens($request,&$obPagination){

		//DADOS DO ADMIN
		$id_admin = parent::getIdAdmin()['usuario']['id_admin'];
		
		//PAGINA ATUAL
		$postVars = $request->getPostVars();
		$paginaAtual = $postVars['page'] ?? 1;

		$filtro = $postVars['filtro'] ?? null;

		if ($filtro == 'hoje') {
			$vencimentoSql = 'AND vencimento = CURDATE()';

		} elseif ($filtro == 'semana') {
			$vencimentoSql = "AND WEEK(CURRENT_TIMESTAMP) = WEEK(vencimento) AND YEAR(CURRENT_DATE) = YEAR(vencimento)";

		} elseif ($filtro == 'mes') {
			$vencimentoSql = "AND MONTH(CURRENT_DATE) = MONTH(vencimento) AND YEAR(CURRENT_DATE) = YEAR(vencimento)";

} elseif ($filtro == 'atraso') { // Corrigido "atrazo" para "atraso"
$vencimentoSql = "AND vencimento < CURDATE()";

} else {
	$vencimentoSql = 'AND vencimento = CURDATE()';
}

// Corrigido a construção da cláusula WHERE
$where = 'id_admin = "' . $id_admin . '" AND '.FinanceiroAlunoHelper::sqlTituloAberto('status').' AND tipo_transacao = "Entrada" ' . $vencimentoSql;

$itens = '';

// QUANTIDADE TOTAL DE REGISTROS
$quantidadeTotal = EntityCaixa::getCaixa($where, null, null, 'COUNT(*) as qtd')->fetchObject()->qtd;


		//INSTANCIA DE PAGINAÇÃO
$limite = (int)(getenv('PAGINATION_LIMIT') ?: 10);
$obPagination = new Pagination($quantidadeTotal,$paginaAtual,$limite);

		//RESULTADOS DA PAGINA
$results = EntityCaixa::getCaixa($where, 'vencimento DESC', $obPagination->getLimit());

		//REDERIZA O ITEM
while ($obDados = $results->fetchObject(EntityCaixa::class)) {

    if (FinanceiroAlunoHelper::tituloAberto($obDados->status)) {
        $status = 'Em aberto';
      } else if (FinanceiroAlunoHelper::tituloPago($obDados->status)) {
        $status = 'Pago';
      } else {
        $status = (string)($obDados->status ?? '');
      } 

	$itens .= '<tr>
	<td>'.$obDados->descricao.'</td>
	<td>'.DateTimeHelper::databr($obDados->vencimento).'</td>
	<td>R$ <span class="mascara-dinheiro">'.$obDados->valor.'</span></td>
	<td>R$ <span class="mascara-dinheiro">'.$obDados->valor_pago.'</span></td>
	<td>'.$obDados->data_pagamento.'</td>
	<td>'.$status.'</td>
	<td>
	<a class="btn btn-secondary" href="#" onclick="list_itens('.$obDados->id.', \'editar\')"><i class="far fa-edit fa-lg"></i> Editar</a>
    </td>
	</tr>';

}


$table = '<div class="card-body">
<div class="table-responsive">
<table class="table table-striped" id="dataTable" width="100%" cellspacing="0">
<thead>
<tr>
<th>Titulo</th>
<th>Vencimento</th>
<th>Valor</th>
<th>Valor Pago</th>
<th>Data Pgto</th>
<th>Status</th>
<th>Editar</th>
</tr>
</thead>
<tbody>'.$itens.'</tbody>
</table>
</div>
<script src="'.URL.'/resources/js/js_mascara.js"></script>
</div>';

		//RETORNA
return $table;
}

public static function getInfo($request){


	//CONTEÚDO 
	$conteudo = [
		'itens' => self::getDataItens($request,$obPagination),
		'pagination' => parent::getPagination($request,$obPagination)
	];

	return parent::jsonLista($conteudo);

}

private static function getForm($request) {
    $postVars = $request->getPostVars();
    $habilitado = '';
    $desconto = 0;
    $valorComDesconto = 0;
    $valorPagar = 0;
    $vencimento = '';
    $valor = 0;
    $vencido='';
    $edicao = '';
    $data_vencimento_mostrar ='';
    $total_pagar='';
    $parc = ['ja_pago' => 0.0, 'saldo' => 0.0, 'html' => '', 'total_exibir' => 0.0];

    $inputValorPagar = '<input id="valor_pagar" name="valor_pagar" class="form-control my-1" style="width: 100%" type="text" value="" oninput="valorPagar()" required>';

    $dados = [];
    if (isset($postVars['funcao']) && $postVars['funcao'] == 'editar') {
        $id = (int)($postVars['id'] ?? 0);
        $id_admin = parent::getIdAdminInt();
        if (!TenantHelper::pertenceCaixa($id, $id_admin)) {
            return json_encode(['erro' => 'Registro não encontrado.']);
        }
        $dados = (array) EntityCaixa::getCaixaById($id);

        $obMatricula = [];
        $idRef = (int)($dados['id_ref'] ?? 0);
        if ($idRef > 0 && TenantHelper::pertenceMatricula($idRef, $id_admin)) {
            $obMatricula = (array) EntityMatri::getMatriculaById($idRef);
        }

        // DADOS DO USUARIO
        $nivel = parent::getIdAdmin()['usuario']['nivel'];
        $habilitado = ($nivel != 'Diretor') ? 'readonly' : '';

        $edicao = 'readonly';

        $dias = DateTimeHelper::subtrairDatas($dados['vencimento'], DateTimeHelper::hoje())->d;

        $pont = FinanceiroAlunoHelper::calcularPontualidade(
            (float)($dados['valor'] ?? 0),
            $dados['vencimento'] ?? '',
            $obMatricula['desconto_pontualidade'] ?? 0
        );
        $desconto = $pont['desconto'];
        $valorComDesconto = $pont['valor_com_desconto'];
        $valorPagar = $pont['valor_pagar'];

        $blocoEncargos = '';
        if ($idRef > 0 && ($dados['vencimento'] ?? '') !== '' && ($dados['vencimento'] ?? '') <= DateTimeHelper::hoje()) {
            $paramsEnc = EncargosContratoHelper::parametrosPorMatricula($idRef);
            $enc = EncargosContratoHelper::calcularAtraso(
                $valorPagar,
                (string)($dados['vencimento'] ?? ''),
                $paramsEnc
            );
            if (!empty($enc['elegivel_encargos'])) {
                $blocoEncargos = EncargosContratoHelper::htmlBlocoPagamento($enc);
                $valorPagar = $enc['total_com_encargos'];
            }
        }

        $obCaixaParc = EntityCaixa::getCaixaById($id);
        $parc = ($obCaixaParc instanceof EntityCaixa)
            ? CaixaRecebimentoService::resumoParcialFormulario($valorPagar, $obCaixaParc)
            : ['ja_pago' => 0.0, 'saldo' => $valorPagar, 'html' => '', 'total_exibir' => $valorPagar];
        $rotuloTotal = $parc['ja_pago'] > FinanceiroAlunoHelper::TOLERANCIA_VALOR ? 'Saldo a pagar' : 'Total a pagar';

        if (($dados['vencimento'] ?? '') > DateTimeHelper::hoje()) {
            $vencido = 'vence em';
        } else {
            $vencido = 'vencido há';
        }

        $valor = $dados['valor'];

        $vencimento = DateTimeHelper::databr($dados['vencimento']);

        $data_vencimento_mostrar =
        '<li class="list-group-item d-flex justify-content-between lh-sm">
                <div>
                    <h6 class="my-0">Data de vencimento</h6>
                    <small class="text-muted">' . $vencimento . '</small>
                </div>
                <span class="text-muted">' . $vencido . ' ' . ($dias ?? 0) . ' dias</span>
            </li>';

         $inputValorPagar = '
         <small class="text-muted">' .'R$ '. NumeroHelper::moedaBr(@$dados['valor']) . '</small>
         <input type="hidden" id="valor_devido_titulo" name="valor_devido_titulo" value="' . htmlspecialchars((string)$valorPagar, ENT_QUOTES, 'UTF-8') . '">
         <input type="hidden" id="valor_saldo_receber" name="valor_saldo_receber" value="' . htmlspecialchars((string)$parc['total_exibir'], ENT_QUOTES, 'UTF-8') . '">
         <input value="' . $valorPagar . '" type="hidden" id="valor_pagar" name="valor_pagar">';

         $htmlParc = $parc['html'] ?? '';
         if ($blocoEncargos !== '') {
            $total_pagar = $htmlParc . $blocoEncargos . '
            <li class="list-group-item d-flex justify-content-between enc-total-row">
                <span>' . $rotuloTotal . '</span>
                <strong id="enc_total_label">R$ ' . NumeroHelper::moedaBr($parc['total_exibir']) . '</strong>
            </li>';
         } elseif ($pont['elegivel']) {
         $total_pagar = $htmlParc . '<li class="list-group-item d-flex justify-content-between lh-sm">
                <div>
                    <h6 class="my-0">Desconto pontualidade</h6>
                    <small class="text-muted">R$ ' . NumeroHelper::moedaBr($valorComDesconto) . '</small>
                </div>
                <span class="text-muted">Valor do desconto: R$ ' . NumeroHelper::moedaBr($desconto) . '</span>
            </li>
            <li class="list-group-item d-flex justify-content-between">
                <span>' . $rotuloTotal . '</span>
                <strong id="enc_total_label">R$ ' . NumeroHelper::moedaBr($parc['total_exibir']) . '</strong>
            </li>';
         } else {
            $total_pagar = $htmlParc . '<li class="list-group-item d-flex justify-content-between">
                <span>' . $rotuloTotal . '</span>
                <strong id="enc_total_label">R$ ' . NumeroHelper::moedaBr($parc['total_exibir']) . '</strong>
            </li>';
         }
    }

    $form = '<form id="form" method="post">
    <div class="modal-header">
        <h1 class="modal-title fs-5" id="exampleModalLabel">Titulo a Receber</h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <div class="modal-body">
        <div id="response"></div>
        <ul class="list-group mb-3 col-md-12">
            <li class="list-group-item justify-content-between lh-sm">
                <div>
                    <h6 class="my-0">Descrição do titulo</h6>
                    <input class="form-control my-1" '.$edicao.' name="titulo" type="text" value="' . ($dados['descricao'] ?? '') . '">
                </div>
            </li>
            <li class="list-group-item d-flex justify-content-between lh-sm">
                <div>
                    <h6 class="my-0">Valor Original</h6>
                    '.$inputValorPagar.'
                </div>
                <span class="text-muted"></span>
            </li>
            '.$data_vencimento_mostrar.'
            
            '.$total_pagar.'
        </ul>

        

        <div class="form-group col-md-12 mb-2">
            <label>Data de pagamento</label>
            <input type="datetime-local" name="data_pagamento" ' . $habilitado . ' value="' . DateTimeHelper::agora() . '" class="form-control">
        </div>
        <input type="hidden" id="ja_pago_titulo" value="' . htmlspecialchars((string)($parc['ja_pago'] ?? 0), ENT_QUOTES, 'UTF-8') . '">
        ' . CaixaRecebimentoService::renderFormPagamento([
            'prefix' => 'entrada',
            'total_input_id' => 'valor_saldo_receber',
            'permitir_parcial' => true,
            'ja_pago' => 0,
        ]) . '
    </div>
    <div class="modal-footer">
        <input value="' . ($dados['id'] ?? '') . '" type="hidden" name="id">
        <button type="button" id="btn-fechar" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary">Salvar</button>
    </div>
    </form>';

    return $form;
}




public static function getNewCaixa($request){

	$form = self::getForm($request);
	return json_encode($form);
}

public static function registrarPagamento($request){

  $postVars = $request->getPostVars();

  $resposta = [
    "filtro" => 'hoje'
  ];

  $titulo = filter_var($postVars['titulo'] ?? '', FILTER_SANITIZE_STRING);

$valor_pagar = (float) NumeroHelper::removerFormatacaoNumero($postVars['valor_pagar']);
  $linhas = CaixaRecebimentoService::parseLinhasFromPost($postVars);
  if (empty($linhas)) {
    $resposta['erro'] = 'Informe ao menos uma forma de pagamento com valor.';
    return json_encode($resposta);
  }

  if($titulo == ''){
    $resposta ["erro"] = 'Insira uma descrição para a movimentação.';
    return json_encode($resposta);
  }

  $id_admin = parent::getIdAdmin()['usuario']['id_admin'];
  $dataPag = (string)($postVars['data_pagamento'] ?? '');
  $obCaixa = null;

if($postVars['id'] == ''){

  $obCaixa = new EntityCaixa;
  $obCaixa->id_admin = $id_admin;
  $obCaixa->descricao = $titulo;
  $obCaixa->valor = $valor_pagar;
  $obCaixa->valor_pago = 0;
  $obCaixa->vencimento = $dataPag;
  $obCaixa->data_pagamento = '';
  $obCaixa->tipo_pagamento = '';
  $obCaixa->tipo_transacao = 'Entrada';
  $obCaixa->referencia = 'Entrada avulsa';
  $obCaixa->id_ref = 0;
  $obCaixa->txt_id = '';
  $obCaixa->pix_copia_cola = '';
  $obCaixa->nosso_numero = '';
  $obCaixa->status = FinanceiroAlunoHelper::STATUS_ABERTO;
  $obCaixa->lancarMovimentacao();
  $caixaId = (int)($obCaixa->id ?? 0);
  if ($caixaId <= 0) {
    $resposta['erro'] = 'Erro ao criar movimentação.';
    return json_encode($resposta);
  }
  $res = CaixaRecebimentoService::registrar(
    (int)$id_admin,
    $caixaId,
    $linhas,
    $dataPag,
    true,
    $valor_pagar
  );
  if (empty($res['ok'])) {
    $resposta['erro'] = $res['message'] ?? 'Erro ao registrar pagamento.';
    return json_encode($resposta);
  }

} else {

    $caixaId = (int)($postVars['id'] ?? 0);
    if (!TenantHelper::pertenceCaixa($caixaId, (int)$id_admin)) {
      $resposta['erro'] = 'Registro não encontrado.';
      return json_encode($resposta);
    }
  $obTitulo = EntityCaixa::getCaixaById($caixaId);
  $dataCaixa = $dataPag !== '' ? date('Y-m-d', strtotime($dataPag) ?: time()) : date('Y-m-d');
  $valorDevido = CaixaRecebimentoService::valorDevidoFromPost(
    $postVars,
    $obTitulo instanceof EntityCaixa ? $obTitulo : null,
    $dataCaixa
  );
  $res = CaixaRecebimentoService::registrar(
    (int)$id_admin,
    $caixaId,
    $linhas,
    $dataPag,
    true,
    $valorDevido > 0 ? $valorDevido : null
  );
  if (empty($res['ok'])) {
    $resposta['erro'] = $res['message'] ?? 'Erro ao registrar pagamento.';
    return json_encode($resposta);
  }
  $obCaixa = EntityCaixa::getCaixaById($caixaId);

}

  if(!$obCaixa){
    $resposta ["erro"] = 'Erro ao registrar o pagamento';
  }

   return json_encode($resposta);

}


}
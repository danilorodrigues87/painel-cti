<?php 

namespace App\Controller\Admin;
use \App\Utils\View;
use \App\Model\Entity\Vendas as EntityVendas;
use \App\Model\Db\Pagination;
use \App\Common\Helpers\DateTimeHelper;
use \App\Common\Helpers\NumeroHelper;

class Vendas extends Page{

	//RETORNA O FORMULARIO
	public static function index($request){
		//CONTEÚDO DE FORMULÁRIO
		$content = View::render('admin/modules/vendas/index',[]);

		//RETORNA A PÁGINA COMPLETA
		/**
         * TITULO DA PAGINA
         * CONTEUDO
         * CURRENTSESSION SESSÃO ATUAL
         * REQUEST SE NESCESSÁRIO
         */
		return parent::getPanel('Vendas',$content,'Financeiro');
	}

	private static function getVendaItens($request,&$obPagination){

		//DADOS DO ADMIN
		$id_admin = parent::getIdAdmin()['usuario']['id_admin'];
		$id_user = parent::getIdAdmin()['usuario']['id'];
		
		$itens = '<button type="button" class="btn btn-success" onclick="list_itens(\'\',\'novo\')" data-toggle="modal">Nova Venda</button>';

		$mesAtual = date('m');
$anoAtual = date('Y');

$where = "id_admin = " . (int)$id_admin . "
          AND id_vendedor = " . (int)$id_user . "
          AND MONTH(created_in) = " . (int)$mesAtual . "
          AND YEAR(created_in) = " . (int)$anoAtual;


		//QUANTIDADE TOTAL DE REGISTROS
		$quantidadeTotal = EntityVendas::getVendas($where,null,null,'COUNT(*) as qtd')->fetchObject()->qtd;

		//PAGINA ATUAL
		$queryParams = $request->getPostVars();
		$paginaAtual = $queryParams['page'] ?? 1;

		//INSTANCIA DE PAGINAÇÃO
		$obPagination = new Pagination($quantidadeTotal,$paginaAtual,5);

		//RESULTADOS DA PAGINA
		$results = EntityVendas::getVendas($where, 'id DESC', $obPagination->getLimit(),$fields,$innerJoin);


		//REDERIZA O ITEM
		while ($obDados = $results->fetchObject(EntityVendas::class)) {

		$itens .= '<tr>
	<td>'.$obDados->descricao.'</td>
	<td>'.DateTimeHelper::databr($data->data).'</td>
	<td>R$ '.NumeroHelper::moedaBr($obDados->valor * $obDados->qtd_parcelas).'</td>
	<td>
		<div class="dropdown">
			<button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
				<i class="far fa-edit fa-lg"></i>
			</button>
			<ul class="dropdown-menu">
				<li>
					<a class="dropdown-item" href="#" onclick="list_itens('.$obDados->id.', \'editar\')"><i class="far fa-edit fa-lg"></i> Editar</a>
				</li>

			</ul>
		</div>
	</td>
</tr>';
		    
		}


		$table = '<div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped" id="dataTable" width="100%" cellspacing="0">
	<thead>
		<tr>
			<th>Descrição</th>
			<th>Data</th>
			<th>Valor da Venda</th>
			<th>Ações</th>
		</tr>
	</thead>
	<tbody>'.$itens.'</tbody>
</table>
</div>
</div>';

		//RETORNA
		return $table;
	}

	public static function getInfo($request){


	//CONTEÚDO 
		$conteudo = [
			'itens' => self::getTrilhaItens($request,$obPagination),
			'pagination' => parent::getPagination($request,$obPagination)
		];

		return json_encode($conteudo);


	}

	private static function getForm($request) {
    $postVars = $request->getPostVars();

    // Verifica se a função é 'editar' e carrega os dados correspondentes
    $dados = [];
    if (isset($postVars['funcao']) && $postVars['funcao'] == 'editar') {
        $dados = (array) EntityVendas::getVendaById($postVars['id']);
    }


    // Criação do formulário
    $form = '<form id="form" method="post">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="exampleModalLabel">Venda</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="response"></div>
                <div class="row">
                    <div class="form-group col-md-12">
                        <label>Descrição</label>
                        <input type="text" name="descricao" value="' . (isset($dados['descricao']) ? $dados['descricao'] : '') . '" class="form-control" required>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Data</label>
                        <input type="date" name="data" value="' . (isset($dados['data']) ? $dados['data'] : '') . '" class="form-control" required>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Valor mensal</label>
                        <input type="number" name="valor" value="' . (isset($dados['valor']) ? $dados['valor'] : '') . '" class="form-control" required>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Qdt Parcelas</label>
                        <input type="number" name="qtd_parcelas" value="' . (isset($dados['qtd_parcelas']) ? $dados['qtd_parcelas'] : '') . '" class="form-control" required>
                    </div>
   
                </div>
            </div>
            <div class="modal-footer">
                <input value="' . (isset($dados['id']) ? $dados['id'] : '') . '" type="hidden" name="id">
                <button type="button" id="btn-fechar" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Salvar</button>
            </div>
        </form>';

    return $form;
}



	public static function getNewVenda($request){

		$form = self::getForm($request);
		return json_encode($form);
	}

	public static function setNewVenda($request){

		//DADOS DO ADMIN
		$id_admin = parent::getIdAdmin()['usuario']['id_admin'];

		$postVars = $request->getPostVars();

		$resposta = [
			"filtro" => null
		];

		if($postVars['id'] != ''){

			//NOVA INSTANCIA
		$obData = new EntityVendas;
		$obData->id = $postVars['id'];
		$obData->descricao = filter_var($postVars['descricao'] ?? '', FILTER_SANITIZE_STRING);
		$obData->valor = (float) NumeroHelper::removerFormatacaoNumero($postVars['pagar']) ?? '';
		$obData->qtd_parcelas = filter_var($postVars['qtd_parcelas'] ?? '', FILTER_SANITIZE_NUMBER_INT);
		$obData->atualizar();

		} else {

		//NOVA INSTANCIA
		$obData = new EntityVendas;
		$obData->descricao = filter_var($postVars['descricao'] ?? '', FILTER_SANITIZE_STRING);
		$obData->valor = (float) NumeroHelper::removerFormatacaoNumero($postVars['pagar']) ?? '';
		$obData->qtd_parcelas = filter_var($postVars['qtd_parcelas'] ?? '', FILTER_SANITIZE_NUMBER_INT);
		$obData->id_admin = $id_admin;
		$obData->cadastrar();
}

		if(!$obData){
			$resposta ["erro"] = 'Erro ao registrar venda';
		}

		return json_encode($resposta);
		

	}


}
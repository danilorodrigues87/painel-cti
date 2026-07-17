<?php 

namespace App\Controller\Admin;
use \App\Utils\View;
use \App\Model\Entity\CategoryCourses as Category_Courses;
use \App\Model\Db\Pagination;
use \App\Common\Helpers\TenantHelper;

class CategoryCourses extends Page{

	//RETORNA O FORMULARIO
	public static function index($request){
		//CONTEÚDO DE FORMULÁRIO
		$content = View::render('admin/modules/categoria_cursos/index',[]);

		//RETORNA A PÁGINA COMPLETA
		/**
         * TITULO DA PAGINA
         * CONTEUDO
         * CURRENTSESSION SESSÃO ATUAL
         * REQUEST SE NESCESSÁRIO
         */
		return parent::getPanel('Categorias',$content,'pedagogico');
	}

	private static function getCategoryItens($request,&$obPagination){

		//DADOS DO ADMIN
		$id_admin = parent::getIdAdmin()['usuario']['id_admin'];

		$itens = '<button type="button" class="btn btn-success" onclick="list_itens(\'\',\'novo\')" data-toggle="modal">Nova Categoria</button>';

		//QUANTIDADE TOTAL DE REGISTROS
		$quantidadeTotal = Category_Courses::getCategory('id_admin = ' . (int)$id_admin,null,null,'COUNT(*) as qtd')->fetchObject()->qtd;

		//PAGINA ATUAL
		$queryParams = $request->getPostVars();
		$paginaAtual = $queryParams['page'] ?? 1;

		//INSTANCIA DE PAGINAÇÃO
		$obPagination = new Pagination($quantidadeTotal,$paginaAtual,5);

		// -=-=-=-    NÃO TERMINEI    -=-=-=-==-=-=  /// -=-=-=-=-=-=

		$innerJoin = 'INNER JOIN categorias_curso ON trilhas.id_categoria = categorias_curso.id';

		$fields = 'trilhas.id, trilhas.nome as trilha, categorias_curso.nome as categoria, trilhas.carga_h';

		//RESULTADOS DA PAGINA
		$results = Category_Courses::getCategory('id_admin = ' . (int)$id_admin, 'nome ASC', $obPagination->getLimit());

		//REDERIZA O ITEM
		while ($obUsers = $results->fetchObject(Category_Courses::class)) {

			$itens .= '<tr>
			<td>'.$obUsers->nome.'</td>
			<td>'.$obUsers->descricao.'</td>
			<td>
			<div class="dropdown">
			<button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
			<i class="far fa-edit fa-lg"></i>
			</button>
			<ul class="dropdown-menu">
			<li>
			<a class="dropdown-item" href="#" onclick="list_itens('.$obUsers->id.', \'editar\')"><i class="far fa-edit fa-lg"></i> Editar</a>
			</li>
			<li>
			<a class="dropdown-item" href="#" onclick="excluir('.$obUsers->id.')" ><i class="far fa-trash-alt fa-lg"></i> Excluir</a>
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
		<th>Nome</th>
		<th>Descricão</th>
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
			'itens' => self::getCategoryItens($request,$obPagination),
			'pagination' => parent::getPagination($request,$obPagination)
		];

		return parent::jsonLista($conteudo);

	}

	private static function getForm($request) {
		$postVars = $request->getPostVars();

		if ($postVars['funcao'] == 'editar') {
			$id = (int)($postVars['id'] ?? 0);
			$id_admin = parent::getIdAdminInt();
			if (!TenantHelper::pertence('categorias_curso', $id, $id_admin)) {
				return json_encode(['erro' => 'Registro não encontrado.']);
			}
			$dados = (array) Category_Courses::getCategoryById($id);
		}

		$form = '<form id="form" method="post">
		<div class="modal-header">
		<h1 class="modal-title fs-5" id="exampleModalLabel">Categoria</h1>
		<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
		</div>
		<div class="modal-body">
		<div id="response"></div>
		<div class="form-group">
		<label>Nome</label>
		<input type="text" name="nome" value="' . @$dados['nome'] . '" class="form-control" required>
		</div>
		<div class="form-group my-3">
		<label>Descrição</label>
		<input type="descricao" name="descricao" value="' . @$dados['descricao'] . '" class="form-control" required>
		</div>
		</div>

		<div class="modal-footer">
		<input value="' . @$dados['id'] . '" type="hidden" name="id">
		<button type="button" id="btn-fechar" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
		<button type="submit" class="btn btn-primary">Salvar</button>
		</div>
		</form>';

		return $form;
	}


	public static function getNewCategory($request){

		$form = self::getForm($request);
		return json_encode($form);
	}

	public static function setNewCategory($request) {

    // DADOS DO ADMIN
    $id_admin = parent::getIdAdmin()['usuario']['id_admin'];
    $postVars = $request->getPostVars();

    // Filtrando os valores de entrada
    $nome = filter_var($postVars['nome'] ?? '', FILTER_SANITIZE_STRING);
    $descricao = filter_var($postVars['descricao'] ?? '', FILTER_SANITIZE_STRING);

    // Array de resposta padrão
    $resposta = ["filtro" => null];

    // Verificação de ID para atualizar ou cadastrar nova categoria
    if (!empty($postVars['id'])) {
        $id = (int)$postVars['id'];
        if (!TenantHelper::pertence('categorias_curso', $id, (int)$id_admin)) {
            $resposta['erro'] = 'Registro não encontrado.';
            return json_encode($resposta);
        }

        // Instância para atualização
        $obData = new Category_Courses;
        $obData->id = $id;
        $obData->nome = $nome;
        $obData->descricao = $descricao;

        // Executa a atualização
        $obData->atualizar();

    } else {
        // Instância para cadastro
        $obData = new Category_Courses;
        $obData->nome = $nome;
        $obData->descricao = $descricao;
        $obData->id_admin = $id_admin;

        // Executa o cadastro
        $obData->cadastrar();
    }

    // Verifica sucesso da operação e define resposta
    if (!$obData) {
        $resposta["erro"] = 'Erro ao cadastrar categoria';
    }

    return json_encode($resposta);
}



	public static function deleteCategory($request){

		$postVars = $request->getPostVars();
		$id = (int)($postVars['id'] ?? 0);
		$id_admin = parent::getIdAdminInt();

		if (!TenantHelper::pertence('categorias_curso', $id, $id_admin)) {
			return 'Registro não encontrado.';
		}

		//NOVA INSTANCIA
		$obData = new Category_Courses;
		$obData->id = $id;
		$obData->excluir();

		if($obData){
			return true;
		} else {
			return 'Erro ao excluir essa categoria';
		}
		
	}

}
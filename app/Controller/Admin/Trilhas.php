<?php 

namespace App\Controller\Admin;
use \App\Utils\View;
use \App\Model\Entity\Trilhas as EntityTrilhas;
use \App\Model\Entity\CategoryCourses as Category_Courses;
use \App\Model\Db\Pagination;
use \App\Common\Upload;

class Trilhas extends Page{

	//RETORNA O FORMULARIO
	public static function index($request){
		//CONTEÚDO DE FORMULÁRIO
		$content = View::render('admin/modules/trilhas/index',[]);

		//RETORNA A PÁGINA COMPLETA
		/**
         * TITULO DA PAGINA
         * CONTEUDO
         * CURRENTSESSION SESSÃO ATUAL
         * REQUEST SE NESCESSÁRIO
         */
		return parent::getPanel('Trilhas',$content,'pedagogico');
	}

	private static function getTrilhaItens($request,&$obPagination){

		//DADOS DO ADMIN
		$id_admin = parent::getIdAdmin()['usuario']['id_admin'];
		
		$itens = '<button type="button" class="btn btn-success" onclick="list_itens(\'\',\'novo\')" data-toggle="modal">Nova Trilha</button>';

		//QUANTIDADE TOTAL DE REGISTROS
		$quantidadeTotal = EntityTrilhas::getTrilha('id_admin = ' . (int)$id_admin,null,null,'COUNT(*) as qtd')->fetchObject()->qtd;

		//PAGINA ATUAL
		$queryParams = $request->getPostVars();
		$paginaAtual = $queryParams['page'] ?? 1;

		//INSTANCIA DE PAGINAÇÃO
		$obPagination = new Pagination($quantidadeTotal,$paginaAtual,5);

		$innerJoin = 'INNER JOIN categorias_curso ON trilhas.id_categoria = categorias_curso.id';

		$fields = 'trilhas.id, trilhas.nome as trilha, categorias_curso.nome as categoria, trilhas.carga_h';

		//RESULTADOS DA PAGINA
		$results = EntityTrilhas::getTrilha('trilhas.id_admin = ' . (int)$id_admin, 'id DESC', $obPagination->getLimit(),$fields,$innerJoin);


		//REDERIZA O ITEM
		while ($obDados = $results->fetchObject(EntityTrilhas::class)) {

			$itens .= '<tr>

			<td>'.$obDados->trilha.'</td>
			<td>'.$obDados->categoria.'</td>
			<td>'.$obDados->carga_h.'</td>
			<td>
			<div class="dropdown">
			<button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
			<i class="far fa-edit fa-lg"></i>
			</button>
			<ul class="dropdown-menu">
			<li>
			<a class="dropdown-item" href="#" onclick="list_itens('.$obDados->id.', \'editar\')"><i class="far fa-edit fa-lg"></i> Editar</a>
			</li>
			<li>
			<a class="dropdown-item" href="#" onclick="excluir('.$obDados->id.')" ><i class="far fa-trash-alt fa-lg"></i> Excluir</a>
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
		<th>Categoria</th>
		<th>Carga Horária</th>
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

// Usando aspas duplas no PHP para permitir aspas simples no JS
$scriptJs = "<script>
    $(document).ready(function() {
        $('#input-img').change(function() {
            const file = this.files[0];
            if (file) {
                let reader = new FileReader();
                reader.onload = function(event) {
                    $('#preview-img').attr('src', event.target.result);
                };
                reader.readAsDataURL(file);
            }
        });
    });

     ClassicEditor
    .create(document.querySelector('#editor'), {
        language: 'pt-br',
        toolbar: [
            'heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote', 'insertTable', 'undo', 'redo'
        ],
        // Adicione esta configuração para remover o erro:
        image: {
            toolbar: [
                'imageStyle:inline',
                'imageStyle:block',
                'imageStyle:side',
                '|',
                'toggleImageCaption',
                'imageTextAlternative'
            ]
        }
    })
    .then(editor => {
        meuEditor = editor;
    })
    .catch(error => {
        console.error(error);
    });

</script>

<style>
.ck-editor__editable_inline {
    min-height: 300px;
    max-height: 500px; /* Se o texto passar disso, aparece o scroll */
    overflow-y: auto;
}
</style>
";


    $postVars = $request->getPostVars();

    // Verifica se a função é 'editar' e carrega os dados correspondentes
    $dados = [];
    if (isset($postVars['funcao']) && $postVars['funcao'] == 'editar') {
        $dados = (array) EntityTrilhas::getTrilhaById($postVars['id']);
    }

    // DADOS DO ADMIN
    $id_admin = parent::getIdAdmin()['usuario']['id_admin'];

    $results = Category_Courses::getCategory('id_admin = ' . (int)$id_admin, 'id ASC');

    // Carrega o SELECT
    $optionSelect = '';
    while ($obDados = $results->fetchObject(Category_Courses::class)) {
        $selected = (isset($dados['id_categoria']) && $dados['id_categoria'] == $obDados->id) ? 'selected' : '';
        $optionSelect .= '
        <option ' . $selected . ' value="' . htmlspecialchars($obDados->id, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($obDados->nome, ENT_QUOTES, 'UTF-8') . '</option>
        ';
    }

    // Lógica da Imagem: Definimos o caminho antes de montar o formulário
    $caminhoImg = URL . '/uploads/img/site/curso/';
    $imagemExibir = (!empty($dados['img'])) ? $caminhoImg . $dados['img'] : $caminhoImg . 'sem-foto.png';

    // Criação do formulário
    $form = '<form id="form" method="post" enctype="multipart/form-data">
    <div class="modal-header">
        <h1 class="modal-title fs-5" id="exampleModalLabel">Categoria</h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <div class="modal-body">
        <div id="response"></div>
        <div class="row">
            <div class="form-group col-md-12">
                <label>Nome</label>
                <input type="text" name="nome" value="' . (isset($dados['nome']) ? htmlspecialchars($dados['nome']) : '') . '" class="form-control" required>
            </div>
            
            <div class="form-group col-md-12">
    <label>Descrição</label>
    <textarea name="descricao" rows="5" id="editor" class="form-control">' . (isset($dados['descricao']) ? htmlspecialchars($dados['descricao']) : '') . '</textarea>
</div>
            <div class="form-group col-md-12">
                <label>Imagem</label>
                <input type="file" name="img" id="input-img" class="form-control" accept="image/*">
                <div class="row">
                <div class="mt-3 col-md-6">
                    <img id="preview-img" src="' . $imagemExibir . '" 
                         style="max-width: 200px; display: block; border: 1px solid #ddd; padding: 5px; border-radius: 5px;">
                </div>
                <div class="col-md-6">
    <div class="form-group form-check my-3">
        <input type="checkbox" name="ativoSite" value="1" class="form-check-input" ' . (isset($dados['site']) && $dados['site'] == 1 ? 'checked' : '') . '>
        <label class="form-check-label">Ativo no site</label>
    </div>
</div>
		<div class="form-group ">
                <label>Carga Horária</label>
                <input type="text" name="carga_h" value="' . (isset($dados['carga_h']) ? htmlspecialchars($dados['carga_h']) : '') . '" class="form-control" required>
            </div>
            <div class="form-group ">
                <label>Categoria</label>
                <select class="form-control" name="id_categoria">
                    ' . $optionSelect . '
                </select>                   
            </div>
		</div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <input value="' . (isset($dados['id']) ? $dados['id'] : '') . '" type="hidden" name="id">
        <button type="button" id="btn-fechar" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary">Salvar</button>
    </div>
    </form> '.$scriptJs;

    return $form;
}



	public static function getNewTrilha($request){

		$form = self::getForm($request);
		return json_encode($form);
	}

	public static function setNewTrilha($request){

		//DADOS DO ADMIN
		$id_admin = parent::getIdAdmin()['usuario']['id_admin'];

		$fileVars = $request->getFileVars();
		$postVars = $request->getPostVars();

		
		$resposta = [
			"filtro" => null
		];

		// VEREFICA SE TEM UM ID
		if($postVars['id'] != ''){

			if($postVars['img'] == ''){
				$img = false;
			} else {
				$img = $postVars['img'];
			}
			if(isset($fileVars['img'])){

			$obUpload = new Upload($fileVars['img']);
			$obUpload->generateNewName();
			$obUpload->Upload('/img/site/',false,$img);
			$img = $obUpload->getBasename();

		}

			//NOVA INSTANCIA
			$obData = new EntityTrilhas;
			$obData->id = (int)$postVars['id'];
			$obData->nome = filter_var($postVars['nome'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
			$obData->descricao = isset($postVars['descricao']) ? trim($postVars['descricao']) : '';
			$obData->id_categoria = (int)($postVars['id_categoria'] ?? 0);
			$obData->id_admin     = (int)$id_admin;
			$obData->carga_h = filter_var($postVars['carga_h'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
			$obData->site = isset($postVars['ativoSite']) ? 1 : 0;
			$obData->img = $img;
			$obData->atualizar();

		} else {

		if(isset($fileVars['img'])){

			$obUpload = new Upload($fileVars['img']);
			$obUpload->generateNewName();
			$obUpload->Upload('/img/site/',false);
			$img = $obUpload->getBasename();

		}


		//NOVA INSTANCIA
		$obData = new EntityTrilhas;
		$obData->nome = filter_var($postVars['nome'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
		$obData->descricao = isset($postVars['descricao']) ? trim($postVars['descricao']) : '';
		$obData->id_categoria = (int)($postVars['id_categoria'] ?? 0);
		$obData->id_admin     = (int)$id_admin;
		$obData->carga_h = filter_var($postVars['carga_h'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
		$obData->site = isset($postVars['ativoSite']) ? 1 : 0;
		$obData->img = $img;
		$obData->cadastrar();
		}

		if(!$obData){
			$resposta ["erro"] = 'Erro ao registrar os dados';
		}

		return json_encode($resposta);
		

	}


	public static function deleteTrilha($request){

		$postVars = $request->getPostVars();

		//NOVA INSTANCIA
		$obData = new EntityTrilhas;
		$obData->id = $postVars['id'];
		$obData->excluir();

		if($obData){
			return true;
		} else {
			return 'Erro ao excluir essa trilha';
		}
		
	}

}
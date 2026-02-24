<?php 

namespace App\Controller\Admin;
use \App\Utils\View;
use \App\Model\Entity\User as EntityUser;
use \App\Model\Entity\EstadoCidades;
use \App\Model\Db\Pagination;
use \App\Common\Helpers\DateTimeHelper;

class Gerentes extends Page{

	//RETORNA O FORMULARIO
	public static function index($request){
		//CONTEÚDO DE FORMULÁRIO
		$content = View::render('admin/modules/gerentes/index',[]);

		//RETORNA A PÁGINA COMPLETA
		/**
         * TITULO DA PAGINA
         * CONTEUDO
         * CURRENTSESSION SESSÃO ATUAL
         * REQUEST SE NESCESSÁRIO
         */
		return parent::getPanel('Gerentes',$content,'Parcerias');
	}

	private static function getUserItems($request,&$obPagination){

//DADOS DO ADMIN
    $id_admin = parent::getIdAdmin()['usuario']['id_admin'];

    //PAGINA ATUAL
		$postVars = $request->getPostVars();
		$paginaAtual = $postVars['page'] ?? 1;

    $id_cliente = (isset($postVars['filtro']) && !empty($postVars['filtro'])) ? intval($postVars['filtro']) : '';


    // SELECT PARA PESQUISA POR CLIENTE
    $selecteCliente =
    '<div class="col-sm-6 col-md-4 col-lg-4 col-xg-2 mb-2">
    <select onchange="listar(this.value,1)" class="form-control" id="parceiro" name="parceiro">
    <option value="0">Filtrar por parceiro</option>';

    $results = EntityUser::getUser("nivel = 'Empresa' AND id_admin = '". $id_admin ."'", 'nome ASC');

    while ($obCliente = $results->fetchObject(EntityUser::class)) {

      $selected = ($obCliente->id == $id_cliente) ? 'selected' : '';

      $selecteCliente .=
      '<option '.$selected.' value="'.$obCliente->id.'">'.$obCliente->nome.'</option>';

    }

    $selecteCliente .=
    ' </select>
    </div>';

    if($id_cliente != '') {
    $where = 'id =' . $id_cliente;
} else {
    $where = "nivel = 'Empresa' AND id_admin = '" . $id_admin . "'";
}

$itens = '<div class="row">' . $selecteCliente . '
    <div class="col">
        <button type="button" class="btn btn-success" onclick="list_itens(\'\',\'novo\')" data-toggle="modal">Cadastrar gerente</button>
    </div>
</div>';


// QUANTIDADE TOTAL DE REGISTROS
$quantidadeTotal = EntityUser::getUser($where, null, null, 'COUNT(*) as qtd')->fetchObject()->qtd;

// INSTANCIA DE PAGINAÇÃO
$obPagination = new Pagination($quantidadeTotal, $paginaAtual, 5);

// RESULTADOS DA PAGINA
$results = EntityUser::getUser($where, 'nome ASC', $obPagination->getLimit());


		//REDERIZA O ITEM
		while ($obUsers = $results->fetchObject(EntityUser::class)) {
			$itens .= '<tr>
			<td>'.$obUsers->nome.'</td>
			<td>'.$obUsers->email.'</td>
			<td class="mascara-celular">'.$obUsers->whatsapp.'</td>
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
			<a class="dropdown-item" href="#" onclick="anotacoes('.$obUsers->id.', \'editar\')"><i class="fa-regular fa-note-sticky fa-lg"></i> Anotações</a>
			</li>
			<li>
			<a class="dropdown-item" href="#" onclick="resetSenha('.$obUsers->id.')"><i class="fa-solid fa-key fa-lg"></i> Resetar Senha</a>
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
		<th>Parceiro</th>
		<th>Email</th>
		<th>Whatsapp</th>
		<th>Ações</th>
		</tr>
		</thead>
		<tbody>'.$itens.'</tbody>
		</table>
		</div>
		</div>
		<script src="'.URL.'/resources/js/js_mascara.js"></script>';

		//RETORNA OS USUÁRIOS
		return $table;
	}

	public static function getInfo($request){


	//CONTEÚDO DE USUÁRIOS
		$conteudo = [
			'itens' => self::getUserItems($request,$obPagination),
			'pagination' => parent::getPagination($request,$obPagination)
		];

		return json_encode($conteudo);

	}


	private static function getForm($request) {

		$postVars = $request->getPostVars();

		if ($postVars['funcao'] == 'editar') {
			$dados = (array) EntityUser::getUserById($postVars['id']);
		}


		$results = EstadoCidades::getEstados();

    // Carrega o SELECT
		$optEstadoSelect = '<select class="form-control" onchange="selectEstado('.(int)@$dados['cidade'].')" id="estado" name="estado">';

		while ($obDados = $results->fetchObject(EstadoCidades::class)) {
			$selected = (isset($dados['uf']) && $dados['uf'] == $obDados->id) ? 'selected' : '';
			$optEstadoSelect .= '
			<option ' . $selected . ' value="' . $obDados->id . '">' . htmlspecialchars($obDados->nome, ENT_QUOTES, 'UTF-8') . '</option>
			';
		}
		$optEstadoSelect .= '</select>';

		$id_admin = parent::getIdAdmin()['usuario']['id_admin']; 


		// COMEÇA O FORM
		$form = '<form id="form" method="post">

		<!-- HEADER -->
		<div class="modal-header">
		<h1 class="modal-title fs-5" id="exampleModalLabel">Dados do Gerente</h1>
		<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
		</div>

		<!-- BODY -->
		<div class="modal-body">
		<div id="response"></div>

		<!-- INICIA A ROW PRINCIPAL -->
		<div class="row">

		<div class="form-group col-md-6">
		<label>Nome</label>
		<input type="text" name="nome" value="' . @$dados['nome'] . '" class="form-control" required>
		</div>

		<div class="form-group col-md-6">
		<label>Email</label>
		<input type="email" name="email" value="' . @$dados['email'] . '" class="form-control" required>
		</div>

		<div class="form-group col-md-6">
		<label>Whatsapp</label>
		<input type="text" name="whatsapp" value="' . @$dados['whatsapp'] . '" class="form-control mascara-celular"  required>
		</div>

		<div class="form-group col-md-6">
		<label>CPF</label>
		<input type="text" name="cpf" value="' . @$dados['cpf'] . '" class="form-control mascara-cpf"  required>
		</div>

		<div class="form-group col-md-5">
		<label>Nascimento</label>
		<input type="date" name="nascimento" value="' . @$dados['nascimento'] . '" class="form-control" required>
		</div>

		<div class="form-group col-md-5">
		<label>RG</label>
		<input type="text" name="rg" value="' . @$dados['rg']. '" class="form-control mascara-rg"  required>
		</div>

		<div class="col-md-2">
		<label>Ativo</label>
		<select class="form-control" name="ativo">
		<option ' . (@$dados['ativo'] == 's' ? 'selected' : '') . ' value="s" >Sim</option>
		<option ' . (@$dados['ativo'] == 'n' ? 'selected' : '') . ' value="n" value="n">Não</option>
		</select>
		</div>


		<div class="form-group col-md-9">
		<label>Endereço</label>
		<input type="text" name="endereco" value="' . @$dados['endereco'] . '" class="form-control" required>
		</div>

		<div class="form-group col-md-3">
		<label>Número</label>
		<input type="text" name="numero" value="' . @$dados['numero'] . '" class="form-control" required>
		</div>

		<div class="form-group col-md-4">
		<label>Bairro</label>
		<input type="text" name="bairro" value="' . @$dados['bairro'] . '" class="form-control" required>
		</div>

		<div class="form-group col-md-4">
		<label>Estado</label>

		' . $optEstadoSelect . '
		</div>

		<div class="form-group col-md-4">
		<label>Cidade</label>
		<div id="cidades"></div>
		</div>


		
		<!-- FIM DA ROW PRINCIPAL -->
		</div>

		<!-- FIM DO BODY -->
		</div>

		<!-- FOOTER -->

		<div class="modal-footer">
		<input value="' . @$dados['id'] . '" type="hidden" name="id">
		<input value="' . @$dados['email'] . '" type="hidden" name="email_antigo">
		<button type="button" id="btn-fechar" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
		<button type="submit" class="btn btn-primary">Salvar</button>
		</div>
		<script src="'.URL.'/resources/js/js_mascara.js"></script>
		<!-- TERMINA O FORM -->
		</form>';
 

     //JUNTA OS DADOS EM UM ARRAY SÓ
		$dadosCompletos = [
			'form' => $form,
			'cidade' => (int)@$dados['cidade']
		];
		return $dadosCompletos;
	}


	public static function getNewUser($request){

		$form = self::getForm($request);
		return json_encode($form);
	}

	public static function setNewUser($request){


		//DADOS DO ADMIN
		$id_admin = parent::getIdAdmin()['usuario']['id_admin'];

		$postVars = $request->getPostVars();

		$permission = array();

$resposta = [
			"filtro" => null
	];


// Sanitização dos campos utilizando funções nativas do PHP
    $nome = filter_var($postVars['nome'] ?? '', FILTER_SANITIZE_STRING);
    $email = filter_var($postVars['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $whatsapp = filter_var($postVars['whatsapp'] ?? '', FILTER_SANITIZE_NUMBER_INT); 
    $rg = filter_var($postVars['rg'] ?? '', FILTER_SANITIZE_NUMBER_INT); 
    $cpf = filter_var($postVars['cpf'] ?? '', FILTER_SANITIZE_NUMBER_INT); 
    $endereco = filter_var($postVars['endereco'] ?? '', FILTER_SANITIZE_STRING); 
    $numero = filter_var($postVars['numero'] ?? '', FILTER_SANITIZE_STRING);
    $bairro = filter_var($postVars['bairro'] ?? '', FILTER_SANITIZE_STRING);
    $estado = filter_var($postVars['estado'] ?? 0, FILTER_SANITIZE_NUMBER_INT);
    $cidade = filter_var($postVars['cidade'] ?? 0, FILTER_SANITIZE_NUMBER_INT);
    $ativo = filter_var($postVars['ativo'] ?? 'n', FILTER_SANITIZE_STRING);


    	$permission = ["Buscar talentos","Meus cursos"];


    $acesso = json_encode($permission);

	// VERIFICAÇÃO SE EXISTE UM EMAIL ANTIGO 
	$email_antigo = filter_var($postVars['email_antigo'] ?? '', FILTER_SANITIZE_EMAIL);

	if($email_antigo != '' AND $email_antigo != $email){

	//BUSCA O USUÁRIO PELO EMAIL
		$obUser = EntityUser::getUserByEmail($email);

		if($obUser instanceof EntityUser){
			$resposta ["erro"] = 'Esse email já está cadastrado.';
			return json_encode($resposta);
		}
	}

		if($postVars['id'] != ''){

			$resposta ["filtro"] = $postVars['id'];

			//NOVA INSTANCIA
			$obUsers = new EntityUser;
			$obUsers->id = $postVars['id'];
			$obUsers->nome = $nome;
			$obUsers->email = $email;
			$obUsers->nivel = 'Empresa';
			$obUsers->id_responsavel = $postVars['responsavel'] ?? 0;
			$obUsers->whatsapp = $whatsapp;
			$obUsers->rg = $rg;
			$obUsers->cpf = $cpf;
			$obUsers->nascimento = $postVars['nascimento'] ?? '';
			$obUsers->endereco = $endereco;
			$obUsers->numero = $numero;
			$obUsers->bairro = $bairro;
			$obUsers->uf = $estado;
			$obUsers->cidade = $cidade;
			$obUsers->ativo = $ativo;
			$obUsers->acesso = $acesso;
			$obUsers->atualizar();

		} else {

			//BUSCA O USUÁRIO PELO EMAIL
		$obUser = EntityUser::getUserByEmail($email);

		if($obUser instanceof EntityUser){
			$resposta ["erro"] = 'Esse email já está cadastrado.';
			return json_encode($resposta);
		}

		//NOVA INSTANCIA
			$obUsers = new EntityUser;
			$obUsers->nome = $nome;
			$obUsers->email = $email;
			$obUsers->nivel = 'Empresa';
			$obUsers->id_responsavel = $postVars['responsavel'] ?? 0;
			$obUsers->senha = password_hash('12345678', PASSWORD_DEFAULT);
			$obUsers->whatsapp = $whatsapp;
			$obUsers->rg = $rg;
			$obUsers->cpf = $cpf;
			$obUsers->nascimento = $postVars['nascimento'] ?? '';
			$obUsers->endereco = $endereco;
			$obUsers->numero = $numero;
			$obUsers->bairro = $bairro;
			$obUsers->uf = $estado;
			$obUsers->cidade = $cidade;
			$obUsers->ativo = $ativo;
			$obUsers->acesso = $acesso;
			$obUsers->id_admin = $id_admin;
			$obUsers->cadastrar();
		}


		if(!$obUsers){
			$resposta ["erro"] = 'Erro ao cadastrar usuário';
		}
		return json_encode($resposta);
		

	}


	public static function deleteUser($request){

		$postVars = $request->getPostVars();

		//NOVA INSTANCIA
		$obUsers = new EntityUser;
		$obUsers->id = $postVars['id'];
		$obUsers->excluir();

		if($obUsers){
			return true;
		} else {
			return 'Erro ao excluir usuário';
		}
		
	}

}
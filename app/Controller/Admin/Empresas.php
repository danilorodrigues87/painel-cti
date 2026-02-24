<?php 

namespace App\Controller\Admin;
use \App\Utils\View;
use \App\Model\Entity\Empresas as EntityEmpresas;
use \App\Model\Entity\User as EntityUser;
use \App\Model\Entity\EstadoCidades;
use \App\Model\Db\Pagination;
use \App\Common\Helpers\DateTimeHelper;
use \App\Common\Upload;

class Empresas extends Page{

	//RETORNA O FORMULARIO
	public static function index($request){
		//CONTEÚDO DE FORMULÁRIO
		$content = View::render('admin/modules/empresa/index',[]);

		//RETORNA A PÁGINA COMPLETA
		/**
         * TITULO DA PAGINA
         * CONTEUDO
         * CURRENTSESSION SESSÃO ATUAL
         * REQUEST SE NESCESSÁRIO
         */
		return parent::getPanel('Empresas',$content,'Parcerias');
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
		<select onchange="listar(this.value,1)" class="form-control" id="gerente" name="gerente">
		<option value="0">Filtrar por gerente</option>';

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
			$where = "tipo = 'Empresa' AND id_gerente =" . $id_cliente;
		} else {
			$where = "tipo = 'Empresa' AND id_admin = " . $id_admin;
		}

		$itens = '<div class="row">' . $selecteCliente . '
		<div class="col">
		<button type="button" class="btn btn-success" onclick="list_itens(\'\',\'novo\')" data-toggle="modal">Cadastrar empresa</button>
		</div>
		</div>';


// QUANTIDADE TOTAL DE REGISTROS
		$quantidadeTotal = EntityEmpresas::getEmpresas($where, null, null, 'COUNT(*) as qtd')->fetchObject()->qtd;

// INSTANCIA DE PAGINAÇÃO
		$obPagination = new Pagination($quantidadeTotal, $paginaAtual, 5);

// RESULTADOS DA PAGINA
		$results = EntityEmpresas::getEmpresas($where, 'nome ASC', $obPagination->getLimit());


		//REDERIZA O ITEM
		while ($obEmpresa = $results->fetchObject(EntityUser::class)) {
			$itens .= '<tr>
			<td>'.$obEmpresa->nome.'</td>
			<td>'.$obEmpresa->email.'</td>
			<td class="mascara-celular">'.$obEmpresa->telefone.'</td>
			<td>
			<div class="dropdown">
			<button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
			<i class="far fa-edit fa-lg"></i>
			</button>
			<ul class="dropdown-menu">
			<li>
			<a class="dropdown-item " href="#" onclick="list_itens('.$obEmpresa->id.', \'editar\')"><i class="far fa-edit fa-lg"></i> Editar</a>
			</li>
			<li>
			<li>
			<a class="dropdown-item" href="#" onclick="excluir('.$obEmpresa->id.')" ><i class="far fa-trash-alt fa-lg"></i> Excluir</a>
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
		<th>Telefone</th>
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
			$dados = (array) EntityEmpresas::getEmpresaById($postVars['id']);
		}

		$fotoPerfil = !empty(@$dados['logo']) 
    ? $dados['logo'] 
    : 'sua-logo.png';

		$results = EstadoCidades::getEstados();

    // Carrega o SELECT
		$optEstadoSelect = '<select class="form-control" onchange="selectEstado('.(int)@$dados['cidade'].')" id="estado" name="estado">';

		while ($obDados = $results->fetchObject(EstadoCidades::class)) {
			$selected = (isset($dados['estado']) && $dados['estado'] == $obDados->id) ? 'selected' : '';
			$optEstadoSelect .= '
			<option ' . $selected . ' value="' . $obDados->id . '">' . $obDados->nome . '</option>
			';
		}

		$optEstadoSelect .= '</select>';

		$id_admin = parent::getIdAdmin()['usuario']['id_admin']; 

		 // Carrega o SELECT
    $optGerente = '<option value="">Selecione o gerente</option>';

	$resultGerente = EntityUser::getUser(" nivel = 'Empresa' AND id_admin = " . (int)$id_admin, 'nome ASC');

    while ($dadosGerente = $resultGerente->fetchObject(EntityUser::class)) {
        $selected = (isset($dados['id_gerente']) && $dados['id_gerente'] == $dadosGerente->id) ? 'selected' : '';
        $optGerente .= '
            <option ' . $selected . ' value="' . htmlspecialchars($dadosGerente->id, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($dadosGerente->nome, ENT_QUOTES, 'UTF-8') . '</option>
        ';
    }
  


		// COMEÇA O FORM
		$form = '<form id="formEmpresa" method="post" enctype="multipart/form-data">

		<!-- HEADER -->
		<div class="modal-header">
		<h1 class="modal-title fs-5" id="exampleModalLabel">Dados da empresa</h1>
		<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
		</div>

		<!-- BODY -->
		<div class="modal-body">
		<div id="response"></div>

		<!-- INICIA A ROW PRINCIPAL -->
	<div class="row">
    	
		<div class="form-group col-md-6">
		<label>Empresa</label>
		<input type="text" name="nome" value="' . @$dados['nome'] . '" class="form-control" required>
		</div>

		<div class="form-group col-md-6">
		<label>CNPJ</label>
		<input type="text" name="cpf_cnpj" value="' . @$dados['cpf_cnpj'] . '" class="form-control mascara-cnpj"  required>
		</div>

		<div class="form-group col-md-4">
		<label>Gerente Responsável</label>
            <select class="form-control"  name="gerente">
                ' . $optGerente . '
            </select> 
		</div>

		<div class="form-group col-md-4">
		<label>Telefone</label>
		<input type="text" name="telefone" value="' . @$dados['telefone'] . '" class="form-control mascara-celular"  required>
		</div>

		<div class="form-group col-md-4">
		<label>Email</label>
		<input type="email" name="email" value="' . @$dados['email'] . '" class="form-control" required>
		</div>

		<div class="form-group col-md-4">
		<label>Site</label>
		<input type="text" name="site" value="' . @$dados['site'] . '" class="form-control" >
		</div>

		<div class="form-group col-md-4">
		<label>Instagram (@meuinsta)</label>
		<input type="text" name="instagram" value="' . @$dados['instagram'] . '" class="form-control" >
		</div>

		<div class="form-group col-md-4">
		<label>youtube (@meucanal)</label>
		<input type="text" name="youtube" value="' . @$dados['youtube'] . '" class="form-control" >
		</div>


		<div class="col-md-2">
		<label>Ativo</label>
		<select class="form-control" name="ativo">
		<option ' . (@$dados['ativo'] == 's' ? 'selected' : '') . ' value="s" >Sim</option>
		<option ' . (@$dados['ativo'] == 'n' ? 'selected' : '') . ' value="n" value="n">Não</option>
		</select>
		</div>

		<div class="form-group col-md-3">
		<label>CEP</label>
		<input type="text" name="cep" value="' . @$dados['cep'] . '" class="form-control mascara-cep" required>
		</div>

		<div class="form-group col-md-5">
		<label>Endereço</label>
		<input type="text" name="endereco" value="' . @$dados['endereco'] . '" class="form-control" required>
		</div>

		<div class="form-group col-md-2">
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


	public static function getNewEmpresa($request){

		$form = self::getForm($request);
		return json_encode($form);
	}

	public static function setNew($request){


		//DADOS DO ADMIN
		$id_admin = parent::getIdAdmin()['usuario']['id_admin'];

		$postVars = $request->getPostVars();

		
		$resposta = [
			"filtro" => null
		];

		$nome = filter_var($postVars['nome'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$site = filter_var($postVars['site'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$instagram = filter_var($postVars['instagram'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$youtube = filter_var($postVars['youtube'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$email = filter_var($postVars['email'] ?? '', FILTER_SANITIZE_EMAIL);
		$telefone = preg_replace('/\D/', '', $postVars['telefone'] ?? '');
		$cpf_cnpj = preg_replace('/\D/', '', $postVars['cpf_cnpj'] ?? '');
		$cep = preg_replace('/\D/', '', $postVars['cep'] ?? '');
		$endereco = filter_var($postVars['endereco'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS); 
		$numero = filter_var($postVars['numero'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$bairro = filter_var($postVars['bairro'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$estado = filter_var($postVars['estado'] ?? 0, FILTER_SANITIZE_NUMBER_INT);
		$cidade = filter_var($postVars['cidade'] ?? 0, FILTER_SANITIZE_NUMBER_INT);
		$ativo = filter_var($postVars['ativo'] ?? 'n', FILTER_SANITIZE_FULL_SPECIAL_CHARS);




		if($postVars['id'] != ''){

			$resposta ["filtro"] = 0;

			//NOVA INSTANCIA
			$obUsers = new EntityEmpresas;
			$obUsers->id = $postVars['id'];
			$obUsers->nome = $nome;
			$obUsers->email = $email;
			$obUsers->site = $site;
			$obUsers->instagram = $instagram;
			$obUsers->youtube = $youtube;
			$obUsers->tipo = 'Empresa';
			$obUsers->id_gerente = $postVars['gerente'] ?? 0;
			$obUsers->telefone = $telefone;
			$obUsers->cpf_cnpj = $cpf_cnpj;
			$obUsers->endereco = $endereco;
			$obUsers->numero = $numero;
			$obUsers->bairro = $bairro;
			$obUsers->estado = $estado;
			$obUsers->cidade = $cidade;
			$obUsers->ativo = $ativo;
			$obUsers->cep = $cep;
			$obUsers->atualizar();

		} else {

		//NOVA INSTANCIA
			$obUsers = new EntityEmpresas;
			$obUsers->id = $postVars['id'];
			$obUsers->nome = $nome;
			$obUsers->id_admin = 0;
			$obUsers->email = $email;
			$obUsers->site = $site;
			$obUsers->instagram = $instagram;
			$obUsers->youtube = $youtube;
			$obUsers->tipo = 'Empresa';
			$obUsers->id_gerente = $postVars['gerente'] ?? 0;
			$obUsers->telefone = $telefone;
			$obUsers->cpf_cnpj = $cpf_cnpj;
			$obUsers->endereco = $endereco;
			$obUsers->numero = $numero;
			$obUsers->bairro = $bairro;
			$obUsers->estado = $estado;
			$obUsers->cidade = $cidade;
			$obUsers->ativo = $ativo;
			$obUsers->id_admin = $id_admin;
			$obUsers->cep = $cep;
			$obUsers->cadastrar();
		}


		if(!$obUsers){
			$resposta ["erro"] = 'Erro ao cadastrar empresa';
		}
		return json_encode($resposta);
		

	}


	public static function deleteItem($request){

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
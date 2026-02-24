<?php 

namespace App\Controller\Admin;
use \App\Utils\View;
use \App\Model\Entity\Trilhas as EntityTrilhas;
use \App\Model\Entity\User as EntityUser;
use \App\Model\Entity\Certificados as EntityCertificados;
use \App\Model\Db\Pagination;

class Certificados extends Page{

	//RETORNA O FORMULARIO
	public static function index($request){
		//CONTEÚDO DE FORMULÁRIO
		$content = View::render('admin/modules/certificados/index',[]);

		//RETORNA A PÁGINA COMPLETA
		/**
         * TITULO DA PAGINA
         * CONTEUDO
         * CURRENTSESSION SESSÃO ATUAL
         * REQUEST SE NESCESSÁRIO
         */
		return parent::getPanel('Certificações',$content,'pedagogico');
	}


 private static function getItens($request, &$obPagination) {
    // Dados do Admin
  $id_admin = parent::getIdAdmin()['usuario']['id_admin'];

    // Página Atual
  $postVars = $request->getPostVars();
  $paginaAtual = $postVars['page'] ?? 1;

    // Filtro de Cliente
  $id_cliente = (isset($postVars['filtro']) && !empty($postVars['filtro'])) ? intval($postVars['filtro']) : '';

    // Select para Pesquisa por Cliente
  $selecteCliente = '
  <div class="col-sm-6 col-md-4 col-lg-4 col-xg-2 mb-2">
  <select onchange="listar(this.value,1)" class="form-control" id="aluno" name="aluno">
  <option value="0">Filtrar por aluno</option>';

  $results = EntityUser::getUser("nivel = 'Cliente' AND id_admin = '" . $id_admin . "'", 'nome ASC');
  while ($obCliente = $results->fetchObject(EntityUser::class)) {
    $selected = ($obCliente->id == $id_cliente) ? 'selected' : '';
    $selecteCliente .= '<option ' . $selected . ' value="' . $obCliente->id . '">' . $obCliente->nome . '</option>';
 }
 $selecteCliente .= '</select></div>';

    // Condição para Filtro de Cliente
 if ($id_cliente != '') {
    $where = 'usuarios.id =' . (int)$id_cliente;
    $wherePagination = 'id_aluno = ' . (int)$id_cliente;
 } else {
    $where = 'certificados.id_admin = ' . (int)$id_admin;
    $wherePagination = 'id_admin = ' . (int)$id_admin;
 }

 $itens = '<div class="row">' . $selecteCliente . '
 <div class="col">
 <button type="button" class="btn btn-success" onclick="list_itens(\'\',\'novo\')" data-toggle="modal">Nova Certificação</button>
 </div>
 </div>';

    // Quantidade Total de Registros
 $quantidadeTotal = EntityCertificados::getCertificados($wherePagination, null, null, 'COUNT(*) as qtd')->fetchObject()->qtd;

    // Instância de Paginação
 $obPagination = new Pagination($quantidadeTotal, $paginaAtual, 5);

    // Inner Join
 $innerJoin = '
 INNER JOIN usuarios ON certificados.id_aluno = usuarios.id 
 INNER JOIN trilhas ON certificados.id_trilha = trilhas.id
 ';

    // Campos Selecionados
 $fields = '
 certificados.id,
 certificados.id_aluno, 
 certificados.conclusao,
 certificados.carga_h, 
 certificados.codigo,
 usuarios.nome,
 trilhas.nome as trilha
 ';

    // Resultados da Página
 $results = EntityCertificados::getCertificados($where, 'certificados.id DESC', $obPagination->getLimit(), $fields, $innerJoin);

    // Renderiza o Item
 while ($obDados = $results->fetchObject(EntityCertificados::class)) {
    $itens .= '<tr>
    <td>' . htmlspecialchars($obDados->nome) . '</td>
    <td>' . htmlspecialchars($obDados->trilha) . '</td>
    <td class="text-center">' . htmlspecialchars($obDados->carga_h) . ' Horas</td>
    <td>' . htmlspecialchars($obDados->conclusao) . '</td>
    <td><a class="btn btn-secondary" href="#" title="Copiar código" onclick="copiar(\'' . addslashes($obDados->codigo) . '\')"><i class="far fa-copy"></i></a></td>
    <td>
    <div class="dropdown">
    <button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
    <i class="far fa-edit fa-lg"></i>
    </button>
    <ul class="dropdown-menu">

    <li><a class="dropdown-item" target="_blank" href="'.URL.'/painel/certificado/download/'. $obDados->id .'"><i class="fa-solid fa-award fa-lg"></i> Baixar Certificado</a></li>

    <li><a class="dropdown-item" href="#" onclick="list_itens(' . $obDados->id . ', \'editar\')"><i class="far fa-edit fa-lg"></i> Editar</a></li>

    <li><a class="dropdown-item" href="#" onclick="excluir(' . $obDados->id . ')"><i class="far fa-trash-alt fa-lg"></i> Excluir</a></li>
    </ul>
    </div>
    </td>
    </tr>';
 }

    // Tabela HTML
 $table = '<div class="card-body">
 <div class="table-responsive">
 <table class="table table-striped" id="dataTable" width="100%" cellspacing="0">
 <thead>
 <tr>
 <th>Aluno</th>
 <th>Trilha</th>
 <th>Carga Horária</th>
 <th>Conclusão</th>
 <th>Código</th>
 <th>Ações</th>
 </tr>
 </thead>
 <tbody>' . $itens . '</tbody>
 </table>
 </div>
 </div>

 <script>
 function copiar(codigo) {
    let link = "https://ctieducacional.com.br/certificado?crt=" + codigo;
    navigator.clipboard.writeText(link).then(function() {
      Swal.fire({
       title: "É isso ai!",
       text: "O códico foi copiado com sucesso.",
       icon: "success"
       });
       }, function(err) {
         alert("Erro ao copiar código: " + err);
         });
      }
      </script>
      ';

    // Retorna a tabela
      return $table;
   }


   public static function getInfo($request){


	//CONTEÚDO 
    $conteudo = [
      'itens' => self::getItens($request,$obPagination),
      'pagination' => parent::getPagination($request,$obPagination)
   ];

   return json_encode($conteudo);


}

private static function getForm($request) {
  $postVars = $request->getPostVars();

    // Verifica se a função é 'editar' e carrega os dados correspondentes
  $dados = [];
  if (isset($postVars['funcao']) && $postVars['funcao'] == 'editar') {
    $dados = (array) EntityCertificados::getCertificadoById($postVars['id']);
 }

    // DADOS DO ADMIN
 $id_admin = parent::getIdAdmin()['usuario']['id_admin'];

 $results = EntityUser::getUser('id_admin = ' . (int)$id_admin . ' AND nivel = "Cliente"', 'id ASC');

    // Carrega o SELECT de alunos
 $optionSelect = '';
 while ($obDados = $results->fetchObject(EntityUser::class)) {
    $selected = (isset($dados['id_aluno']) && $dados['id_aluno'] == $obDados->id) ? 'selected' : '';
    $optionSelect .= '<option ' . $selected . ' value="' . $obDados->id . '">' . $obDados->nome . '</option>';
 }

 $resultsTrilhas = EntityTrilhas::getTrilha('id_admin = ' . (int)$id_admin, 'id ASC');

    // Carrega o SELECT de trilhas
 $optSlqTrilhas = '';
 while ($obTrilhas = $resultsTrilhas->fetchObject(EntityTrilhas::class)) {
    $selected = (isset($dados['id_trilha']) && $dados['id_trilha'] == $obTrilhas->id) ? 'selected' : '';
    $optSlqTrilhas .= '<option ' . $selected . ' value="' . $obTrilhas->id . '">' . $obTrilhas->nome . '</option>';
 }

    // Criação do formulário
 $form = '<form id="form" method="post">
 <div class="modal-header">
 <h1 class="modal-title fs-5" id="exampleModalLabel">Dados da certificação</h1>
 <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
 </div>

 <div class="modal-body">
 <div id="response"></div>
 <div class="row">
 <div class="form-group col-md-6">
 <label>Aluno</label>
 <select class="form-control" name="id_aluno">' . $optionSelect . '</select>                   
 </div>
 <div class="form-group col-md-6">
 <label>Trilha</label>
 <select class="form-control" name="id_trilha">' . $optSlqTrilhas . '</select>                   
 </div>
 <div class="form-group col-md-12 mt-2">
 <label>Módulos da Trilha</label>
 <textarea rows="4" name="modulos" class="form-control" required>' . (isset($dados['modulos']) ? $dados['modulos'] : '') . '</textarea>
 </div>
 <div class="col-md-6 mt-2">
 <div class="form-group">
 <label>Carga Horária</label>
 <input type="number" name="carga_h" value="' . (isset($dados['carga_h']) ? $dados['carga_h'] : '') . '" class="form-control" required>
 </div>
 </div>
 <div class="col-md-6 mt-2">
 <div class="form-group">
 <label>Conclusão</label>
 <input type="text" name="conclusao" value="' . (isset($dados['conclusao']) ? $dados['conclusao'] : '') . '" class="form-control" required>
 </div>
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




public static function getNewCertificado($request){

 $form = self::getForm($request);
 return json_encode($form);
}

public static function setNewCertificado($request){

		//DADOS DO ADMIN
 $id_admin = parent::getIdAdmin()['usuario']['id_admin'];

 $postVars = $request->getPostVars();

 $resposta = [
    "filtro" => null
 ];

 $modulos = filter_var($postVars['modulos'] ?? '', FILTER_SANITIZE_STRING);
 $conclusao = filter_var($postVars['conclusao'] ?? '', FILTER_SANITIZE_STRING);
 $carga_h = filter_var($postVars['carga_h'] ?? '', FILTER_SANITIZE_NUMBER_INT); 

 if($postVars['id'] != ''){

			//NOVA INSTANCIA
   $obData = new EntityCertificados;
   $obData->id = $postVars['id'];
   $obData->id_aluno = $postVars['id_aluno'];
   $obData->id_trilha = $postVars['id_trilha'];
   $obData->carga_h = $carga_h;
   $obData->modulos = $modulos;
   $obData->conclusao = $conclusao;
   $obData->atualizar();

} else {

		//NOVA INSTANCIA
   $obData = new EntityCertificados;
   $obData->id_aluno = $postVars['id_aluno'];
   $obData->id_trilha = $postVars['id_trilha'];
   $obData->carga_h = $carga_h;
   $obData->modulos = $modulos;
   $obData->conclusao = $conclusao;
   $obData->id_admin = $id_admin;
   $obData->cadastrar();
}

if(!$obData){
 $resposta ["erro"] = 'Erro ao cadastrar certificado';
}
return json_encode($resposta);


}


public static function deleteCertificado($request){

 $postVars = $request->getPostVars();

		//NOVA INSTANCIA
 $obData = new EntityCertificados;
 $obData->id = $postVars['id'];
 $obData->excluir();

 if($obData){
   return true;
} else {
   return 'Erro ao excluir esse certificado';
}

}

}
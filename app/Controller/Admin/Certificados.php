<?php 

namespace App\Controller\Admin;
use \App\Utils\View;
use \App\Model\Entity\Trilhas as EntityTrilhas;
use \App\Model\Entity\User as EntityUser;
use App\Common\Helpers\ConectCandidatoFormacaoHelper;
use App\Model\Entity\Certificados as EntityCertificados;
use \App\Model\Db\Pagination;
use \App\Common\Helpers\TenantHelper;

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

  $siteBase = '';
  try {
    $siteBase = \App\Common\Helpers\LmsCertificadoHelper::urlSiteEscolaParaCertificado((int)$id_admin);
  } catch (\Throwable $e) {
    $siteBase = '';
  }
  $siteBaseJs = json_encode($siteBase, JSON_UNESCAPED_SLASHES);

    // Página Atual
  $postVars = $request->getPostVars();
  $paginaAtual = $postVars['page'] ?? 1;
  $busca = trim((string)($postVars['busca'] ?? ''));

  $where = 'certificados.id_admin = ' . (int)$id_admin;
  $wherePagination = 'id_admin = ' . (int)$id_admin;

  $idsBusca = TenantHelper::idsAlunosPorBusca((int)$id_admin, $busca);
  if (is_array($idsBusca)) {
    if (!$idsBusca) {
      $where .= ' AND 1=0';
      $wherePagination .= ' AND 1=0';
    } else {
      $where .= ' AND certificados.id_aluno IN (' . implode(',', $idsBusca) . ')';
      $wherePagination .= ' AND id_aluno IN (' . implode(',', $idsBusca) . ')';
    }
  }

  $filtroImpresso = (string)($postVars['filtro_impresso'] ?? '');
  if ($filtroImpresso === '1' || $filtroImpresso === '0') {
    $where .= ' AND certificados.impresso = '.(int)$filtroImpresso;
    $wherePagination .= ' AND impresso = '.(int)$filtroImpresso;
  }

  $filtroRetirada = (string)($postVars['filtro_retirada'] ?? '');
  if ($filtroRetirada === 'retirado') {
    $where .= ' AND certificados.data_retirada IS NOT NULL';
    $wherePagination .= ' AND data_retirada IS NOT NULL';
  } elseif ($filtroRetirada === 'pendente') {
    $where .= ' AND certificados.data_retirada IS NULL';
    $wherePagination .= ' AND data_retirada IS NULL';
  }

  $itens = '';

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
 certificados.impresso,
 certificados.data_retirada,
 usuarios.nome,
 trilhas.nome as trilha
 ';

    // Mais recentes primeiro (data de emissão ≈ cadastro no sistema)
 $results = EntityCertificados::getCertificados($where, 'certificados.id DESC', $obPagination->getLimit(), $fields, $innerJoin);

    // Renderiza o Item
 while ($obDados = $results->fetchObject(EntityCertificados::class)) {
    $idCert = (int)$obDados->id;
    $impresso = !empty($obDados->impresso);
    $dataRetirada = trim((string)($obDados->data_retirada ?? ''));
    if ($dataRetirada !== '' && preg_match('#^\d{4}-\d{2}-\d{2}$#', $dataRetirada)) {
      $dataRetiradaInput = $dataRetirada;
    } else {
      $dataRetiradaInput = '';
    }
    $chkImpresso = $impresso ? ' checked' : '';
    $badgeRetirada = $dataRetiradaInput !== ''
      ? '<span class="badge bg-success cert-badge-retirada d-none d-xl-inline ms-1">Retirado</span>'
      : '<span class="badge bg-secondary cert-badge-retirada d-none d-xl-inline ms-1">Pendente</span>';

    $itens .= '<tr data-cert-id="'.$idCert.'">
    <td>' . htmlspecialchars($obDados->nome) . '</td>
    <td>' . htmlspecialchars($obDados->trilha) . '</td>
    <td class="text-center">' . htmlspecialchars($obDados->carga_h) . ' Horas</td>
    <td>' . htmlspecialchars($obDados->conclusao) . '</td>
    <td class="text-center">
      <div class="form-check form-check-inline m-0 justify-content-center">
        <input type="checkbox" class="form-check-input cert-ctrl-impresso" data-id="'.$idCert.'" title="Já impresso / enviado à gráfica"'.$chkImpresso.'>
      </div>
    </td>
    <td class="text-nowrap">
      <input type="date" class="form-control form-control-sm cert-ctrl-retirada" data-id="'.$idCert.'" value="'.htmlspecialchars($dataRetiradaInput, ENT_QUOTES, 'UTF-8').'" style="max-width:150px">
      '.$badgeRetirada.'
    </td>
    <td><a class="btn btn-secondary btn-sm" href="#" title="Copiar código" onclick="copiar(\'' . addslashes($obDados->codigo) . '\');return false;"><i class="far fa-copy"></i></a></td>
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

 if ($itens === '') {
   $itens = '<tr><td colspan="8" class="text-center text-muted py-4">Nenhum certificado encontrado com esses filtros.</td></tr>';
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
 <th class="text-center">Impresso</th>
 <th>Retirada</th>
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
    const siteBase = ' . $siteBaseJs . ';
    if (!siteBase) {
      Swal.fire({
        title: "Site não cadastrado",
        text: "Cadastre o site da escola em Configurações → Dados da escola para copiar o link de verificação.",
        icon: "warning"
      });
      return;
    }
    let link = siteBase + "/certificado?crt=" + codigo;
    navigator.clipboard.writeText(link).then(function() {
      Swal.fire({
       title: "É isso ai!",
       text: "O código foi copiado com sucesso.",
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

   return parent::jsonLista($conteudo);


}

private static function getForm($request) {
  $postVars = $request->getPostVars();

    // Verifica se a função é 'editar' e carrega os dados correspondentes
  $dados = [];
  if (isset($postVars['funcao']) && $postVars['funcao'] == 'editar') {
    $id = (int)($postVars['id'] ?? 0);
    $id_admin = parent::getIdAdminInt();
    if (!TenantHelper::pertence('certificados', $id, $id_admin)) {
      return json_encode(['erro' => 'Registro não encontrado.']);
    }
    $dados = (array) EntityCertificados::getCertificadoById($id);
 }

    // DADOS DO ADMIN
 $id_admin = parent::getIdAdmin()['usuario']['id_admin'];

 $results = EntityUser::getUser('id_admin = ' . (int)$id_admin . ' AND nivel = "Cliente"', 'nome ASC');

    // Carrega o SELECT de alunos
 $optionSelect = '';
 while ($obDados = $results->fetchObject(EntityUser::class)) {
    $selected = (isset($dados['id_aluno']) && $dados['id_aluno'] == $obDados->id) ? 'selected' : '';
    $optionSelect .= '<option ' . $selected . ' value="' . (int)$obDados->id . '">' . htmlspecialchars((string)$obDados->nome, ENT_QUOTES, 'UTF-8') . '</option>';
 }

 $resultsTrilhas = EntityTrilhas::getTrilha('id_admin = ' . (int)$id_admin, 'nome ASC');

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
 <div class="col-md-6 mt-2">
 <div class="form-group form-check mt-4">
 <input type="checkbox" class="form-check-input" name="impresso" id="cert-form-impresso" value="1"' . (!empty($dados['impresso']) ? ' checked' : '') . '>
 <label class="form-check-label" for="cert-form-impresso">Certificado já impresso</label>
 </div>
 </div>
 <div class="col-md-6 mt-2">
 <div class="form-group">
 <label>Data de retirada pelo aluno</label>
 <input type="date" name="data_retirada" value="' . htmlspecialchars(self::dataRetiradaParaInput($dados['data_retirada'] ?? ''), ENT_QUOTES, 'UTF-8') . '" class="form-control">
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

   $id = (int)$postVars['id'];
   $id_admin = parent::getIdAdminInt();

   if (!TenantHelper::pertence('certificados', $id, $id_admin)) {
     $resposta['erro'] = 'Registro não encontrado.';
     return json_encode($resposta);
   }

			//NOVA INSTANCIA
   $obData = new EntityCertificados;
   $obData->id = $id;
   $obData->id_aluno = $postVars['id_aluno'];
   $obData->id_trilha = $postVars['id_trilha'];
   $obData->carga_h = $carga_h;
   $obData->modulos = $modulos;
   $obData->conclusao = $conclusao;
   self::aplicarControlePost($obData, $postVars);
   $obData->atualizar();

} else {

		//NOVA INSTANCIA
   $obData = new EntityCertificados;
   $obData->id_aluno = $postVars['id_aluno'];
   $obData->id_trilha = $postVars['id_trilha'];
   $obData->carga_h = $carga_h;
   $obData->modulos = $modulos;
   $obData->conclusao = $conclusao;
   self::aplicarControlePost($obData, $postVars);
   $obData->id_admin = $id_admin;
   $obData->cadastrar();
}

if($obData instanceof EntityCertificados && (int)($obData->id ?? 0) > 0){
   $fresh = EntityCertificados::getCertificadoById((int)$obData->id);
   if ($fresh instanceof EntityCertificados) {
      ConectCandidatoFormacaoHelper::syncFromCertificadoEscola($fresh);
   }
}

if(!$obData){
 $resposta ["erro"] = 'Erro ao cadastrar certificado';
}
return json_encode($resposta);


}


public static function deleteCertificado($request){

 $postVars = $request->getPostVars();
 $id = (int)($postVars['id'] ?? 0);
 $id_admin = parent::getIdAdminInt();

 if (!TenantHelper::pertence('certificados', $id, $id_admin)) {
   return 'Registro não encontrado.';
 }

 $row = EntityCertificados::getCertificadoById($id);

 $obData = new EntityCertificados;
 $obData->id = $id;
 $ok = $obData->excluir();

 if ($ok && $row instanceof EntityCertificados) {
   ConectCandidatoFormacaoHelper::removerCertificadoEscola((int)$row->id_aluno, (int)$row->id_trilha);
   return true;
 }

 return 'Erro ao excluir esse certificado';
}

public static function atualizarControle($request){
  $postVars = $request->getPostVars();
  $id = (int)($postVars['id'] ?? 0);
  $id_admin = parent::getIdAdminInt();

  if ($id <= 0 || !TenantHelper::pertence('certificados', $id, $id_admin)) {
    return json_encode(['success' => false, 'message' => 'Registro não encontrado.']);
  }

  $ob = EntityCertificados::getCertificadoById($id);
  if (!$ob instanceof EntityCertificados) {
    return json_encode(['success' => false, 'message' => 'Registro não encontrado.']);
  }

  $ob->impresso = !empty($postVars['impresso']) ? 1 : 0;
  $ob->data_retirada = self::normalizarDataRetirada($postVars['data_retirada'] ?? '');

  if (!$ob->atualizarControle()) {
    return json_encode(['success' => false, 'message' => 'Não foi possível salvar. Verifique se as colunas impresso/data_retirada existem no banco.']);
  }

  return json_encode([
    'success' => true,
    'impresso' => (int)$ob->impresso,
    'data_retirada' => $ob->data_retirada,
  ]);
}

private static function aplicarControlePost(EntityCertificados $ob, array $postVars): void {
  $ob->impresso = !empty($postVars['impresso']) ? 1 : 0;
  $ob->data_retirada = self::normalizarDataRetirada($postVars['data_retirada'] ?? '');
}

private static function normalizarDataRetirada($valor): ?string {
  $valor = trim((string)$valor);
  if ($valor === '') {
    return null;
  }
  if (preg_match('#^\d{4}-\d{2}-\d{2}$#', $valor)) {
    return $valor;
  }
  if (preg_match('#^(\d{2})/(\d{2})/(\d{4})$#', $valor, $m)) {
    return $m[3].'-'.$m[2].'-'.$m[1];
  }
  return null;
}

private static function dataRetiradaParaInput($valor): string {
  $valor = trim((string)$valor);
  if ($valor === '') {
    return '';
  }
  if (preg_match('#^\d{4}-\d{2}-\d{2}$#', $valor)) {
    return $valor;
  }
  return '';
}

}
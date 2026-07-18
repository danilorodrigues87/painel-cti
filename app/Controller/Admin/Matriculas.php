<?php 

namespace App\Controller\Admin;
use \App\Utils\View;
use \App\Model\Entity\Matriculas as EntityMatri;
use \App\Model\Entity\User as EntityUser;
use \App\Model\Entity\Responsaveis as EntityRes;
use \App\Model\Entity\Trilhas as EntityTrilhas;
use \App\Model\Entity\Caixa as EntityCaixa;
use \App\Model\Db\Pagination;
use \App\Model\Entity\EstadoCidades;
use \App\Session\User\Login as SessionUser;
use \App\Common\Helpers\DateTimeHelper;
use \App\Common\Helpers\NumeroHelper;
use \App\Common\Helpers\TenantHelper;
use \App\Common\Helpers\ModuleGateHelper;
use \App\Common\Helpers\BrandingHelper;
use \App\Common\Helpers\ContratoTemplateHelper;
use \App\Model\Entity\EscolasAssinantes;


class Matriculas extends Page{

	//RETORNA O FORMULARIO
	public static function index($request){
		//CONTEÚDO DE FORMULÁRIO
		$content = View::render('admin/modules/matriculas/index',[]);

		//RETORNA A PÁGINA COMPLETA
    /**
     * TITULO DA PAGINA
     * CONTEUDO
     * CURRENTSESSION SESSÃO ATUAL
     * REQUEST SE NESCESSÁRIO
     */
		return parent::getPanel('Matriculas',$content,'pedagogico');
	}

	private static function getMatriculasItens($request,&$obPagination){

		//DADOS DO ADMIN
		$id_admin = parent::getIdAdmin()['usuario']['id_admin'];

    //PAGINA ATUAL
    $postVars = $request->getPostVars();
    $paginaAtual = $postVars['page'] ?? 1;



 $id_cliente = (isset($postVars['filtro']) && !empty($postVars['filtro'])) ? intval($postVars['filtro']) : '';


    // SELECT PARA PESQUISA POR CLIENTE
    $selecteCliente =
    '<div class="col-sm-6 col-md-4 col-lg-4 col-xg-2 mb-2">
    <select onchange="listar(this.value,1)" class="form-control" id="aluno" name="aluno">
    <option value="0">Filtrar por aluno</option>';

    $results = EntityUser::getUser("nivel = 'Cliente' AND id_admin = '". $id_admin ."'", 'nome ASC'); 

    while ($obCliente = $results->fetchObject(EntityUser::class)) {

      $selected = ($obCliente->id == $id_cliente) ? 'selected' : '';

      $selecteCliente .=
      '<option '.$selected.' value="'.$obCliente->id.'">'.$obCliente->nome.'</option>';

    }

    $selecteCliente .=
    ' </select>
    </div>';


    $wherePadrao = "id_admin = '" . (int)$id_admin . "'";
    $where = TenantHelper::whereMatriculaFiltro((int)$id_cliente, (int)$id_admin, $wherePadrao);

    $itens = '<div class="row">' . $selecteCliente . '
    <div class="col">
        <button type="button" class="btn btn-success" onclick="list_itens(\'\',\'novo\')" data-toggle="modal">Nova Matricula</button>
    </div>
</div>';

		//QUANTIDADE TOTAL DE REGISTROS
		$quantidadeTotal = EntityMatri::getMatriculas($where,null,null,'COUNT(*) as qtd')->fetchObject()->qtd;

		//INSTANCIA DE PAGINAÇÃO
		$obPagination = new Pagination($quantidadeTotal,$paginaAtual,5);

		//RESULTADOS DA PAGINA
		$results = EntityMatri::getMatriculas($where, 'id DESC', $obPagination->getLimit()); 

		$temContrato = in_array('contratos', ModuleGateHelper::getSlugsEscola((int)$id_admin), true);

		//REDERIZA O ITEM
		while ($dados = $results->fetchObject(EntityMatri::class)) {
			$dadosUser = (array) EntityUser::getUserById($dados->id_aluno);
			$nomeAluno = htmlspecialchars((string)($dadosUser['nome'] ?? 'Aluno #'.$dados->id_aluno), ENT_QUOTES, 'UTF-8');
			$nomeTrilha = '—';
			try {
				$dadosTrilha = (array) EntityTrilhas::getTrilhaById($dados->id_trilha);
				$nomeTrilha = htmlspecialchars((string)($dadosTrilha['nome'] ?? '—'), ENT_QUOTES, 'UTF-8');
			} catch (\Throwable $e) {
				$nomeTrilha = '<span class="text-danger">Trilha indisponível</span>';
			}

            $disabled='';

			$total = $dados->qtd_parcelas * $dados->valor;
			if($dados->status == 0){
				$status = 'Em andamento';
			} else if($dados->status == 1){
				$status = 'Encerrado';
			} else {
				$status = 'Cancelado';
                $disabled='disabled';
			}

        $itens .= 
        '<tr>
        <td>'.$dados->id.'</td>
        <td>'.$nomeAluno.'</td>
        <td>'.$nomeTrilha.'</td>
        <td><span>R$ '.NumeroHelper::moedaBr($total).'</span></td>
        <td>'.$status.'</td>
        <td>
        <div class="dropdown">
        <button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="far fa-edit fa-lg"></i>
        </button>
        <ul class="dropdown-menu">
        '.($temContrato ? '<li>
        <a class="dropdown-item" target="_blank" href="'.URL.'/painel/matricula/'.$dados->id.'" >
        <i class="fa-regular fa-paste fa-lg"></i> Ver Contrato</a>
        </li>' : '').'
        <li>
        <a class="dropdown-item disabled" href="#" onclick="list_itens('.$dados->id.', \'editar\')">
        <i class="far fa-edit fa-lg"></i> Editar</a>
        </li>
        <li>
        <a class="dropdown-item '.$disabled.'" href="#" onclick="cancelar_contrato('.$dados->id.')" >
        <i class="fa-regular fa-rectangle-xmark fa-lg"></i> Cancelar</a>
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
     <th>Cód</th>
     <th>Aluno</th>
     <th>Trilha</th>
     <th>Valor Total</th>
     <th>Status</th>
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
      'itens' => self::getMatriculasItens($request,$obPagination),
      'pagination' => parent::getPagination($request,$obPagination)
   ];

   return parent::jsonLista($conteudo);

}

public static function getResponseble($request) {
 $postVars = $request->getPostVars();


 $id_aluno = isset($postVars['id_aluno']) ? $postVars['id_aluno'] : 0;
 $dadosAluno = (array)EntityUser::getUserById($id_aluno);
 $dadosResponsavel = (array)EntityRes::getResById($dadosAluno['id_responsavel']);

 $dadosRes = [
  'id' => $dadosResponsavel['id'] ?? '', 
  'nome' => $dadosResponsavel['nome'] ?? ''
];

return json_encode($dadosRes); 

}


private static function getForm($request) {

	$postVars = $request->getPostVars();
	$dados = [];

	if (($postVars['funcao'] ?? '') === 'editar') {
		$id = (int)($postVars['id'] ?? 0);
		$id_admin = (int)parent::getIdAdmin()['usuario']['id_admin'];
		if ($id > 0 && TenantHelper::pertenceMatricula($id, $id_admin)) {
			$dados = (array) EntityMatri::getMatriculaById($id);
		}
	}

    //DADOS DO ADMIN
$id_admin = parent::getIdAdmin()['usuario']['id_admin'];


$resultsUser = EntityUser::getUser("nivel = 'Cliente' AND id_admin = '". $id_admin ."'", 'nome ASC');

    // Carrega o SELECT
$optSlqUsers = '<select class="form-control" onchange="selectAluno(this.value)" name="aluno"> 
                    <option value="0">Selecione um aluno</option> ';

while ($obAlunos = $resultsUser->fetchObject(EntityUser::class)) {
  $userSelected = (isset($dados['id_aluno']) && $dados['id_aluno'] == $obAlunos->id) ? 'selected' : '';
  $optSlqUsers .= '
  <option ' . $userSelected . ' value="'.(int)$obAlunos->id.'">' . htmlspecialchars((string)$obAlunos->nome, ENT_QUOTES, 'UTF-8') . '</option>
  ';
}
$optSlqUsers .= '</select>';


$resultsTrilhas = EntityTrilhas::getTrilha("id_admin = '". $id_admin ."'",'nome ASC');

    // Carrega o SELECT
$optSlqTrilhas = '';

while ($obTrilhas = $resultsTrilhas->fetchObject(EntityTrilhas::class)) {
  $trilhaSelected = (isset($dados['id_trilha']) && $dados['id_trilha'] == $obTrilhas->id) ? 'selected' : '';
  $optSlqTrilhas .= '
  <option ' . $trilhaSelected . ' value="' . (int)$obTrilhas->id . '">' . htmlspecialchars((string)$obTrilhas->nome, ENT_QUOTES, 'UTF-8') . '</option>
  ';
}

$pp = date('m');
$ano = date('Y');

$form = '<form id="form" method="post">
<div class="modal-header">
<h1 class="modal-title fs-5" id="exampleModalLabel">Matricula Nº ' . (isset($dados['id']) ? $dados['id'] : "***") . '</h1>
<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body">
<div id="response"></div>

<div class="row">

<div class="form-group col-md-6">
<label>Nome do Aluno</label>
' . $optSlqUsers . '
</div>
 
<div class="form-group col-md-6">
<label>Responsável</label>
<input type="hidden" name="id_responsavel" id="id_responsavel">
<input class="form-control" type="text" readonly id="nome_responsavel" >
</div>

<div class="form-group col-md-8">
<label>Curso</label>
<select class="form-control" name="trilha">
' . $optSlqTrilhas . '
</select> 
</div>

<div class="form-group col-md-4">
<label class="text-center">Carga Horaria</label>
<input name="carga_horaria" type="number" min="10" max="500" class="form-control" value="' . (isset($dados['carga_horaria']) ? $dados['carga_horaria'] : '') . '" required>
</div>

<div class="form-group col-md-12">
<label>Modulos da Trilha</label>
<textarea rows="3" name="modulos" class="form-control" required>' . (isset($dados['modulos']) ? $dados['modulos'] : '') . '</textarea>
</div>

<div class="form-group col-md-6">
<label>Horarios</label>
<textarea name="horarios" placeholder="das 00:00 as 00:00" class="form-control" required>' . (isset($dados['horarios']) ? $dados['horarios'] : '') . '</textarea>
</div>

<div class="form-group col-md-6">
<label>Dia da Semana</label>
<textarea name="dia_semana" placeholder="Segunda e Quarta-feira" class="form-control" required>' . (isset($dados['dia_semana']) ? $dados['dia_semana'] : '') . '</textarea>
</div>

<div class="col-md-12 text-center">
<span id="obs" style="font-size: 12px;"></span>
</div>

<div class="form-group col-md-3">
<label>Aulas Sem</label>
<input name="aulas_semanais" type="number" min="1" max="20" class="form-control" value="' . (isset($dados['aulas_semanais']) ? $dados['aulas_semanais'] : '') . '" required>
</div>

<div class="form-group col-md-3">
<label>Valor Mensal</label>
<input id="valor" name="valor" type="text" class="form-control" value="' . (isset($dados['valor']) ? $dados['valor'] : '') . '" required>
</div>

<div class="form-group col-md-3">
<label>Parcelas</label>
<input name="qtd_parcelas" type="number" class="form-control" value="' . (isset($dados['qtd_parcelas']) ? $dados['qtd_parcelas'] : '') . '" min="1" max="212" required>
</div>

<div class="form-group col-md-3">
<label>Vencimento</label>
<select class="form-control" name="dia_vencimento">
<option ' . (isset($dados['dia_vencimento']) && $dados['dia_vencimento'] == '01' ? 'selected' : '') . ' value="01">01</option>
<option ' . (isset($dados['dia_vencimento']) && $dados['dia_vencimento'] == '05' ? 'selected' : '') . ' value="05">05</option>
<option ' . (isset($dados['dia_vencimento']) && $dados['dia_vencimento'] == '10' ? 'selected' : '') . ' value="10">10</option>
<option ' . (isset($dados['dia_vencimento']) && $dados['dia_vencimento'] == '15' ? 'selected' : '') . ' value="15">15</option>
<option ' . (isset($dados['dia_vencimento']) && $dados['dia_vencimento'] == '20' ? 'selected' : '') . ' value="20">20</option>
<option ' . (isset($dados['dia_vencimento']) && $dados['dia_vencimento'] == '25' ? 'selected' : '') . ' value="25">25</option>
</select>
</div>


<div class="form-group col-md-12 text-center">
<label>Primeira Parcela</label>
<div class="row">
<div class="col-4">
<div class="form-group form-check">
<input type="checkbox" value="1" name="pontualidade" id="pontualidade" onclick="checkPontualidade()" class="form-check-input" ' . (isset($dados['desconto_pontualidade']) && $dados['desconto_pontualidade'] ? 'checked' : '') . '>
<label class="form-check-label" for="pontualidade">Desconto</label>
</div>
</div>

<div class="col-4">
<select name="primeiromes" class="form-control">
<option ' . ($pp == 12 ? 'selected' : '') . ' value="1">Janeiro</option>
<option ' . ($pp == 1 ? 'selected' : '') . ' value="2">Fevereiro</option>
<option ' . ($pp == 2 ? 'selected' : '') . ' value="3">Março</option>
<option ' . ($pp == 3 ? 'selected' : '') . ' value="4">Abril</option>
<option ' . ($pp == 4 ? 'selected' : '') . ' value="5">Maio</option>
<option ' . ($pp == 5 ? 'selected' : '') . ' value="6">Junho</option>
<option ' . ($pp == 6 ? 'selected' : '') . ' value="7">Julho</option>
<option ' . ($pp == 7 ? 'selected' : '') . ' value="8">Agosto</option>
<option ' . ($pp == 8 ? 'selected' : '') . ' value="9">Setembro</option>
<option ' . ($pp == 9 ? 'selected' : '') . ' value="10">Outubro</option>
<option ' . ($pp == 10 ? 'selected' : '') . ' value="11">Novembro</option>
<option ' . ($pp == 11 ? 'selected' : '') . ' value="12">Dezembro</option>
</select>
</div>
<div class="col-3">
<div class="form-group">
<input name="primeiroano" type="number" class="form-control" value="' . $ano . '" required>
</div>
</div>
</div>
</div>

<div class="form-group col-md-4">
<label>Inicio das Aulas</label>
<input name="inicio" value="' . (isset($dados['inicio']) ? $dados['inicio'] : '') . '" type="date" class="form-control" required>
</div>

<div class="form-group col-md-4">
<label>Termino Aprox</label>
<input name="final" value="' . (isset($dados['fim']) ? $dados['fim'] : '') . '" type="date" class="form-control" required>
</div>
<div class="form-group col-md-4">
<label>Tipo Parcelamento</label>
<select class="form-control" name="tipo_parcelamento">
<option ' . (isset($dados['tipo_parcelamento']) && $dados['tipo_parcelamento'] == 'Carnê Simples' ? 'selected' : '') . ' value="Carnê Simples">Carnê Simples</option>
'.(\App\Common\Helpers\MercadoPagoEscolaHelper::escolaTemPixAtivo((int)$id_admin) ? '
<option ' . (isset($dados['tipo_parcelamento']) && $dados['tipo_parcelamento'] == 'Carnê com Pix' ? 'selected' : '') . ' value="Carnê com Pix">Carnê com Pix (Mercado Pago)</option>
' : '').'
</select>
'.(!\App\Common\Helpers\MercadoPagoEscolaHelper::escolaTemPixAtivo((int)$id_admin) ? '<div class="form-text">PIX indisponível: configure em Configurações → Pagamentos.</div>' : '').'
</div>
</div>

</div>
<div class="modal-footer">
<input value="' . (isset($dados['id']) ? $dados['id'] : '') . '" type="hidden" name="id">
<button type="button" id="btn-fechar" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
<button type="submit" class="btn btn-primary">Salvar</button>
</div>
</form> 

';

return [
	'form' => $form,
];
}



public static function getNovaMatricula($request){

  $payload = self::getForm($request);
  $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
  if ($json === false) {
    return json_encode([
      'form' => '<div class="alert alert-danger m-3">Erro ao montar o formulário de matrícula.</div>',
    ]);
  }
  return $json;
}

public static function setNovaMatricula($request) {
 $postVars = $request->getPostVars();

    //DADOS DO ADMIN
 $id_admin = parent::getIdAdmin()['usuario']['id_admin'];

 $inicio = $postVars['inicio'] ?? '';
 $fim = $postVars['final'] ?? '';
 $pontualidade = $postVars['pontualidade'] ?? false;

$resposta = [
        "filtro" => null
    ]; 


    // Sem FILTER_SANITIZE_STRING (removido no PHP 8.2+)
 $id_aluno = filter_var($postVars['aluno'] ?? 0, FILTER_SANITIZE_NUMBER_INT);
 $id_responsavel = filter_var($postVars['id_responsavel'] ?? 0, FILTER_SANITIZE_NUMBER_INT);
 $id_trilha = (int)filter_var($postVars['trilha'] ?? 0, FILTER_SANITIZE_NUMBER_INT);
 $carga_horaria = filter_var($postVars['carga_horaria'] ?? 0, FILTER_SANITIZE_NUMBER_INT);
 $modulos = trim(strip_tags((string)($postVars['modulos'] ?? '')));
 $horarios = trim(strip_tags((string)($postVars['horarios'] ?? '')));
 $dia_semana = trim(strip_tags((string)($postVars['dia_semana'] ?? '')));
 $aulas_semanais = filter_var($postVars['aulas_semanais'] ?? 0, FILTER_SANITIZE_NUMBER_INT);
 $qtd_parcelas = filter_var($postVars['qtd_parcelas'] ?? 0, FILTER_SANITIZE_NUMBER_INT);
 $dia_vencimento = filter_var($postVars['dia_vencimento'] ?? 0, FILTER_SANITIZE_NUMBER_INT);
 $primeiro_mes = filter_var($postVars['primeiromes'] ?? 0, FILTER_SANITIZE_NUMBER_INT);
 $primeiro_ano = filter_var($postVars['primeiroano'] ?? 0, FILTER_SANITIZE_NUMBER_INT);
 $tipo_parcelamento = trim(strip_tags((string)($postVars['tipo_parcelamento'] ?? 'Carnê Simples')));
 if ($tipo_parcelamento !== 'Carnê com Pix'
 	|| !\App\Common\Helpers\MercadoPagoEscolaHelper::escolaTemPixAtivo((int)$id_admin)) {
   $tipo_parcelamento = 'Carnê Simples';
 }

// Substitui a vírgula por ponto no valor
$valor = str_replace(',', '.', (string)($postVars['valor'] ?? '0'));
// Sanitiza o valor como um número float
$valor = filter_var($valor, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
// Converte para float
$valor = floatval($valor);


if($id_aluno == '' or $id_aluno ==0){
    $resposta ["erro"] = 'Selecione um aluno';
    return json_encode($resposta);
}

 if($valor <= 0){
  $resposta ["erro"] = 'Defina o valor da mensalidade';
    return json_encode($resposta);
}

if (!empty($postVars['id'])) {
        $matriculaId = (int)filter_var($postVars['id'], FILTER_SANITIZE_NUMBER_INT);

        if (!TenantHelper::pertenceMatricula($matriculaId, (int)$id_admin)) {
            $resposta['erro'] = 'Matrícula não encontrada.';
            return json_encode($resposta);
        }

        // Atualizar se ID estiver presente
  $obMatricula = new EntityMatri;
  $obMatricula->id = $matriculaId;
  $obMatricula->id_aluno = $id_aluno;
  $obMatricula->id_responsavel = $id_responsavel;
  $obMatricula->id_trilha = $id_trilha;
  $obMatricula->carga_horaria = $carga_horaria;
  $obMatricula->modulos = $modulos;
  $obMatricula->horarios = $horarios;
  $obMatricula->dia_semana = $dia_semana;
  $obMatricula->aulas_semanais = $aulas_semanais;
  $obMatricula->valor = $valor;
  $obMatricula->qtd_parcelas = $qtd_parcelas;
  $obMatricula->dia_vencimento = $dia_vencimento;
  $obMatricula->primeiro_mes = $primeiro_mes;
  $obMatricula->primeiro_ano = $primeiro_ano;
  $obMatricula->desconto_pontualidade = $pontualidade;
  $obMatricula->inicio = $inicio;
  $obMatricula->fim = $fim;
  $obMatricula->tipo_parcelamento = $tipo_parcelamento;

  $obMatricula->atualizar();

} else {
        // Criar nova instância se ID não estiver presente
  $obMatricula = new EntityMatri;
  $obMatricula->id_aluno = $id_aluno;
  $obMatricula->id_admin = $id_admin;
  $obMatricula->id_responsavel = $id_responsavel;
  $obMatricula->id_trilha = $id_trilha;
  $obMatricula->carga_horaria = $carga_horaria;
  $obMatricula->modulos = $modulos;
  $obMatricula->horarios = $horarios;
  $obMatricula->dia_semana = $dia_semana;
  $obMatricula->aulas_semanais = $aulas_semanais;
  $obMatricula->valor = $valor;
  $obMatricula->qtd_parcelas = $qtd_parcelas;
  $obMatricula->dia_vencimento = $dia_vencimento;
  $obMatricula->primeiro_mes = $primeiro_mes;
  $obMatricula->primeiro_ano = $primeiro_ano;
  $obMatricula->desconto_pontualidade = $pontualidade;
  $obMatricula->inicio = $inicio;
  $obMatricula->fim = $fim;
  $obMatricula->tipo_parcelamento = $tipo_parcelamento;
  $obMatricula->matricular();
}


if(!$obMatricula){
        $resposta ["erro"] = 'Erro ao matricular';
    }
    return json_encode($resposta);


}



public static function cancelarMatricula($request){

  $postVars = $request->getPostVars();
  $id = (int)($postVars['id'] ?? 0);
  $id_admin = parent::getIdAdminInt();

  if (!TenantHelper::pertenceMatricula($id, $id_admin)) {
    return 'Matrícula não encontrada.';
  }

        //NOVA INSTANCIA
  $obUsers = new EntityMatri;
  $obUsers->id = $id;
  $obUsers->cancelar();

  if($obUsers){

    $obCaixa = new EntityCaixa;
    $obCaixa->id_ref = $postVars['id'];
    $obCaixa->excluirMatricula();

   return true;
} else {
   return 'Erro ao cancelar essa matricula';
}

}

public static function verContrato($request,$id){

  $userLogedData = SessionUser::getUserLogedData();
  $id = (int)$id;
  $id_admin = (int)$userLogedData['usuario']['id_admin'];

  if (!in_array('contratos', ModuleGateHelper::getSlugsEscola($id_admin), true)) {
    return 'Contrato não disponível para esta escola.';
  }

  if (!TenantHelper::pertenceMatricula($id, $id_admin)) {
    return 'Matrícula não encontrada.';
  }

  $cidadeId = (int)($userLogedData['escola']['cidade'] ?? 0);
  $estadoId = (int)($userLogedData['escola']['estado'] ?? 0);
  $cidadeNome = '';
  $estadoSigla = '';
  if ($cidadeId > 0) {
    $cidade = EstadoCidades::getCidades('id = '.$cidadeId)->fetchObject();
    if (is_object($cidade)) {
      $cidadeNome = (string)($cidade->nome ?? '');
    }
  }
  if ($estadoId > 0) {
    $estado = EstadoCidades::getEstados('id = '.$estadoId)->fetchObject();
    if (is_object($estado)) {
      $estadoSigla = (string)($estado->sigla ?? '');
    }
  }
  $cidadeUf = trim($cidadeNome.($estadoSigla !== '' ? '/'.$estadoSigla : ''));
  $partesEndEscola = [];
  foreach ([
    trim((string)($userLogedData['escola']['endereco'] ?? '')),
    trim((string)($userLogedData['escola']['numero'] ?? '')),
    trim((string)($userLogedData['escola']['bairro'] ?? '')),
    $cidadeUf,
  ] as $parte) {
    if ($parte !== '') {
      $partesEndEscola[] = $parte;
    }
  }
  $endereco_escola = $partesEndEscola ? implode(', ', $partesEndEscola) : 'endereço não informado';

  $categoria1 = 2; // PROFISSIONALIZANTES
  $categoria2 = 7; // MUSICA
  $categoria3 = 1; // JUDÔ

        // DADOS DA EMPRESA CONTRATADA
  $empresa='';

  $logoEscola = BrandingHelper::urlLogoEscola($userLogedData['escola']['logo'] ?? null);
  $empresa .= '<img src="'.htmlspecialchars($logoEscola, ENT_QUOTES, 'UTF-8').'" width="100" alt="Logo">';

  $empresa .= '<h2 style="text-align: center;">Contrato de Prestação de Serviços Educacionais</h2><hr><br>';

  $empresa .= '<b>CONTRATADA</b><br>';

  $empresa .= '<b>Escola: </b><span>'.$userLogedData['escola']['nome'].' - <b>CNPJ: </b>'.$userLogedData['escola']['cpf_cnpj'].'</span><br>';

  $empresa .= '<b>Telefone: </b><span>'.$userLogedData['escola']['telefone']. '</span>
  <b>Endereço: </b><span>'.$endereco_escola.'</span><br>';

  $empresa .= '<b>Site: </b><span>'.$userLogedData['escola']['site']. '</span><b> Email: </b><span>'.$userLogedData['escola']['email'].'</span><br><br>';


        // PEGA OS DADOS DO CONTRATO
  $dados = (array) EntityMatri::getMatriculaById($id);

        // PEGA DADOS DO ALUNO
  $obAluno = (array) EntityUser::getUserById($dados['id_aluno']);

  $dadosTrilha = (array) EntityTrilhas::getTrilhaById($dados['id_trilha']);

  $primeira_parcela = $dados['dia_vencimento'].'/'.$dados['primeiro_mes'].'/'.$dados['primeiro_ano'];



  $dataAtual = new \DateTime(); 
  $nascimento = $obAluno['nascimento']; 

  $valor = NumeroHelper::moedaBr($dados['valor']);


  $dataInicio = new \DateTime($dados['inicio']);
  $termino = new \DateTime($dados['fim']);

// Criar um objeto DateTime com a data de nascimento do aluno
  $nascimentoAluno = new \DateTime($nascimento); 
  $dataAtual = new \DateTime(); 

// Calcular a idade
  $idade = $dataAtual->diff($nascimentoAluno)->y;

  $responsavel = '';
  if ($idade < 18) {
    if(!empty($obAluno['id_responsavel'])){
     $obResponsavel = (array) EntityRes::getResById($obAluno['id_responsavel']);
     $responsavel='';
     $nascimentoResponsavel = new \DateTime($obResponsavel['nascimento']); 
     $responsavel ='<b>Responssável: </b><span>'.$obResponsavel['nome'].'</span>';
     $responsavel .='<b> Data de Nascimento: </b><span>'.$nascimentoResponsavel->format('d/m/Y').'</span><br>';
     $responsavel .='<b>RG: </b><span>'.$obResponsavel['rg'].'</span><b> CPF: </b><span>'.$obResponsavel['cpf'].'</span><br>';
     $responsavel .='<b>Telefone: </b><span>'.$obResponsavel['whatsapp'];
     $responsavel .='</span> <b> Email: </b><span>'.$obResponsavel['email'].'</span><br>';
  }
} 

$endereco ='<b>Endereço: </b><span>'.$obAluno['endereco'].'</span><hr>';

    // DADOS DO CONTRATANTE/ALUNO

$contratante = '<b>CONTRATANTE/ALUNO</b><br>';
$contratante .= '<b>Aluno: </b><span>'.$obAluno['nome'].'</span><b> Data de Nascimento: </b><span class="">'.$nascimentoAluno->format('d/m/Y').'</span><br>';

// Verificar e exibir RG, se houver
if(!empty($obAluno['rg'])){
 $contratante .= '<b>RG: </b><span>'.$obAluno['rg']. '</span>';
}

// Verificar e exibir CPF, se houver
if(!empty($obAluno['cpf'])){ 
 $contratante .= ' <b>CPF: </b><span class="mascara-cpf"> '.$obAluno['cpf'].' </span>';
}

$contratante .= '<b>Telefone: </b><span class="mascara-celular"> '.$obAluno['whatsapp'].' </span><b> Email: </b><span>'.$obAluno['email'].'<br></span>';
$contratante .= $responsavel;
$contratante .= $endereco;

$curso ='<br><br><b>Formação em: </b><span>'.$dadosTrilha['nome']  .' </span><br><br>';

$curso .='<span>( '.$dados['modulos']  .' )</span><br><br>';

$curso .='<span>Carga Horária <b>'.$dados['carga_horaria']  .' Horas</b></span><br>'; 

$curso .='<span>Inicio das aulas dia <b>'.$dataInicio->format('d/m/Y').'</b></span><br>';

$curso .='<spam>Dia(s) de aula(s) escolhido(s): <b>'.$dados['dia_semana'].'</b> no horário <b>'.$dados['horarios'].'</b></span><br>';

$curso .='<span>Termino previsto para dia <b>'.$termino->format('d/m/Y').'</b></span><br>';

if($dados['qtd_parcelas'] > 1){

   $forma_pagamento = '<span>Forma de pagamento <b>Parcelado em '.$dados['qtd_parcelas'].' vezes</b>';
   $forma_pagamento .= '</span><span> sendo a primeira parcela no dia <b>'.$primeira_parcela.'</b><br>';
   $forma_pagamento .= '<span>Parcelas fixas de <b>R$ '.$valor.'</b></span> com VECIMENTOTO MENSAL dia <b>'.$dados['dia_vencimento'].'</b><br>';
} else {
 $forma_pagamento = '<span>Forma de pagamento <b>à vista</b>';
 $forma_pagamento .= '</span><span> pago no ato da matricula em <b>'.$primeira_parcela.'</b><br>';
 $forma_pagamento .= '<span>o valor de <b>R$ '.$valor.'</b></span><br>';
}


$curso .= $forma_pagamento;

$pontualidade='';

if($dados['desconto_pontualidade'] == 1){

   $pontualidade ='<span><b>Obs:</b> Bônus de 10% no valor das parcelas <b>PAGAS ANTECIPADAMENTE</b> ao dia do vencimento acima firmado.</span>';
}

$curso .= $pontualidade;

$clasulaExtra='';
$tipo_curso = '';

if($dadosTrilha['id_categoria'] == $categoria1){

   //--- CASO SEJA CURSO NORMAL //----

   $tipo_curso = '<p><b>1ª A CONTRATADA</b> fornecerá com <b>custo fixo mensal</b>, treinamento através do <b>Método de Ensino próprio ou terceirizado</b>, material didático (Apostilas ou outro equipamento usado no curso <b>com custos adicionais</b>) que serão entregues de acordo com o início de cada módulo, uso individual do computador, acompanhamento de um Instrutor ou suporte via Whatsapp caso seja no formato EAD, material para testes diversos e ao término do curso o <b>Certificado de Conclusão.</b></p>

   <p><b>2ª AULAS</b> – As aulas serão ministradas na sede da <b>CONTRATADA</b> com turmas de Segunda a Sexta nos períodos da manhã e tarde, o <b>CONTRATANTE/ALUNO</b> nesta data já escolheu e definiu no quadro acima o melhor dia e horário disponível para sua vaga de '.$dados['aulas_semanais'].' aula(s) semanal, assumindo frequentá-la respeitando o Regimento Interno Escolar para que obtenha o melhor aprendizado possível e o bem comum de todos. Obs "cada aula tem duração de 50 minutos".</p>

   <p><b>3ª PRAZO DE DURAÇÃO DO CURSO</b> - O período de duração do curso ora contratado será de '.$dados['qtd_parcelas'].' meses, mas por tratar-se de um <b>Método de Ensino Individual</b> podem ocorrer variações de acordo com o desempenho, comprometimento e alguma experiência ou não com o computador, mas caso o aluno necessite de mais aulas para completar o curso, poderá continuá-lo no mesmo dia e horário, até sua conclusão, desde que pague mensalmente o <b>custo fixo mensal</b> conforme descrição acima e caso conclua o curso antes do prazo poderá continuar suas aulas, reforçando o aprendizado e tirando dúvidas se houver em relação ao curso ora contratado até quitar todas as parcelas restantes, que em hipótese alguma estará isento.</p>';

} else if($dadosTrilha['id_categoria'] == $categoria2) {

   // --- CASO SEJA CURSO DE MUSICA //---

   $tipo_curso= '<p><b>1ª A CONTRATADA</b> fornecerá com <b>custo fixo mensal</b>, treinamento através do <b>Método de Ensino próprio</b>, material didático (Apostilas ou outro equipamento usado no curso <b>com custos adicionais</b>) que serão entregues de acordo com o início de cada módulo, acompanhamento de um professor, material para testes diversos e ao término do curso o <b>Certificado de Conclusão.</b></p>

   <p><b>2ª AULAS</b> – As aulas serão ministradas na sede da <b>CONTRATADA</b> com atendimento de Segunda a Sexta nos períodos da manhã, tarde e aos sabados até as 15:00. O <b>CONTRATANTE/ALUNO</b> nesta data já escolheu e definiu o melhor dia e horário disponível para sua vaga de '.$dados['aulas_semanais'].' aula(s) semanal, assumindo frequentá-la respeitando o Regimento Interno Escolar para que obtenha o melhor aprendizado possível e o bem comum de todos. Obs "cada aula tem duração de 50 minutos".</p>

   <p><b>3ª PRAZO DE DURAÇÃO DO CURSO</b> - O período de duração do curso ora contratado será de '.$dados['qtd_parcelas'].' meses, e poderá ser renovado pelo <b>CONTRATANTE/ALUNO</b>, após o 6° modulo haverá a possibilidade de continuar em um modulo extra de aperfeiçoamento e especialização.</p>';

} else if($dadosTrilha['id_categoria'] == $categoria3) {

   $clasulaExtra ='<p><b>15ª ASSUNÇÃO DE RISCOS E ISENÇÃO DE RESPONSABILIDADE</b> - O <b>CONTRATANTE/ALUNO</b> declara estar ciente de que a prática de judô envolve atividades físicas de contato, com risco inerente de acidentes e lesões, ainda que todos os cuidados e instruções de segurança sejam rigorosamente seguidos. O  <b>CONTRATANTE/ALUNO</b>, portanto, assume total responsabilidade por quaisquer lesões ou acidentes que possam ocorrer durante os treinos, competições ou outras atividades relacionadas à prática do judô, isentando a <b>CONTRATADA</b> e seus instrutores de qualquer responsabilidade por danos físicos, materiais ou morais, exceto nos casos comprovados de negligência grave por parte da <b>CONTRATADA</b></p>';

   // --- CASO SEJA AULAS DE JUDÔ //---

   $tipo_curso= '<p><b>1ª A CONTRATADA</b> fornecerá com <b>custo fixo mensal</b>, treinamento de judô com professor(a) devidamente capacitado(a), (equipamentos, acessórios e vestimentas adequadas para a pratica espotiva é de responsabilidade do <b>CONTRATANTE/ALUNO</b>), recomenda-se que pessa orientação a(o) professor(a) sobre equipamento adequado caso seja necessário.</p>

   <p><b>2ª AULAS</b> – As aulas serão ministradas na sede da <b>CONTRATADA</b> com atendimento de Segunda a Sexta nos períodos da manhã e tarde. O <b>CONTRATANTE/ALUNO</b> nesta data já escolheu e definiu o melhor dia e horário disponível para sua vaga de '.$dados['aulas_semanais'].' aula(s) semanal, assumindo frequentá-la respeitando o Regimento Interno Escolar para que obtenha o melhor aprendizado possível e o bem comum de todos. Obs "cada aula tem duração de 50 minutos".</p>

   <p><b>3ª PRAZO DE DURAÇÃO</b> - O período de duração ora contratado será de '.$dados['qtd_parcelas'].' meses, e poderá ser renovado pelo <b>CONTRATANTE/ALUNO</b>.</p>';

}

$dia_contrato = DateTimeHelper::extraiDia($dados['inicio']);
$ano_contrato = DateTimeHelper::extraiAno($dados['inicio']);
$mes_semZeroInicio = ltrim (DateTimeHelper::extraiMes($dados['inicio']), '0');
$mes_contrato = DateTimeHelper::imprimeMes($mes_semZeroInicio);

$localContrato = $cidadeUf !== '' ? $cidadeUf : 'Local';
$data_contrato = '<p style="text-align: right;"><b>'.$localContrato.'</b> dia '.$dia_contrato. ' de '.$mes_contrato.' de '.$ano_contrato.'</p><br>';

$escolaEnt = EscolasAssinantes::getEscolaById($id_admin);
if (!$escolaEnt instanceof EscolasAssinantes) {
	$escolaEnt = null;
}

// Template: NULL na escola = padrão CTI (contrato atual da escola 1)
return ContratoTemplateHelper::render([
	'URL'            => URL,
	'title'          => 'Contrato',
	'contratada'     => $empresa,
	'contratante'    => $contratante,
	'curso'          => $curso,
	'parte1'         => $tipo_curso ?? '',
	'data_contrato'  => $data_contrato,
	'clausulaExtra'  => $clasulaExtra,
], $escolaEnt);
}



}
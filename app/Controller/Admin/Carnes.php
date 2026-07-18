<?php 

  namespace App\Controller\Admin;
  use \App\Utils\View;
  use \App\Model\Entity\Matriculas as EntityMatri;
  use \App\Model\Entity\User as EntityUser;
  use \App\Model\Entity\Trilhas as EntityTrilhas;
  use \App\Model\Db\Pagination;
  use \App\Session\User\Login as SessionUser;
  use \App\Common\Helpers\DateTimeHelper;
  use \App\Common\Helpers\NumeroHelper;
  use \App\Model\Entity\Caixa;
  use \App\Common\Helpers\TenantHelper;
  use \App\Common\Helpers\BrandingHelper;
  use \App\Model\Entity\Responsaveis as EntityRes;

  class Carnes extends Page{

    //RETORNA O FORMULARIO
    public static function index($request){
      //CONTEÚDO DE FORMULÁRIO
      $content = View::render('admin/modules/carnes/index',[]);

      //RETORNA A PÁGINA COMPLETA
       /**
        * TITULO DA PAGINA
        * CONTEUDO
        * CURRENTSESSION SESSÃO ATUAL
        * REQUEST SE NESCESSÁRIO
        */
      return parent::getPanel('Carnês',$content,'Financeiro');
    }

    private static function getItens($request,&$obPagination){

      //DADOS DO ADMIN
      $id_admin = parent::getIdAdmin()['usuario']['id_admin'];

      // POSTS
      $postVars = $request->getPostVars();
      $id_cliente = (isset($postVars['filtro']) && !empty($postVars['filtro'])) ? intval($postVars['filtro']) : '';

      // SELECT PARA PESQUISA POR CLIENTE
      $selecteCliente =
      '<div class="row px-3 pt-3">
      <div class="col-sm-6 col-md-4 col-lg-4 col-xg-2 mb-2">
      <select onchange="listar(this.value,1)" class="form-control" id="aluno" name="aluno">
      <option value="0">Filtrar por aluno</option>';

      $results = EntityUser::getUser("nivel = 'Cliente' AND id_admin = '". $id_admin ."'", 'nome ASC');

      while ($obCliente = $results->fetchObject(EntityUser::class)) {

        $selected = ($obCliente->id == $id_cliente) ? 'selected' : '';

        $selecteCliente .=
        '<option '.$selected.' value="'.$obCliente->id.'">'.htmlspecialchars((string)$obCliente->nome, ENT_QUOTES, 'UTF-8').'</option>';

      }

      $selecteCliente .=
      ' </select>
      </div>
      </div>';


      //PAGINA ATUAL
      $paginaAtual = $postVars['page'] ?? 1;
      $aluno = (isset($postVars['filtro']) && $postVars['filtro'] !== '' && $postVars['filtro'] !== '0' && (int)$postVars['filtro'] > 0)
        ? ' AND id_aluno = '.(int)$postVars['filtro']
        : '';

      //QUANTIDADE TOTAL DE REGISTROS
      $quantidadeTotal = (int)(EntityMatri::getMatriculas('id_admin = ' . (int)$id_admin.' '.$aluno,null,null,'COUNT(*) as qtd')->fetchObject()->qtd ?? 0);

      //INSTANCIA DE PAGINAÇÃO
      $obPagination = new Pagination($quantidadeTotal,$paginaAtual,5);

      //RESULTADOS DA PAGINA
      $results = EntityMatri::getMatriculas('id_admin = ' . (int)$id_admin.' '.$aluno, 'id DESC', $obPagination->getLimit()); 


      //REDERIZA O ITEM
      $itens = '';

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
          $disabled='disabled';
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
        <li>
        <a class="dropdown-item" href="#" onclick="list_itens('.$dados->id.', \'listar_titulos\')">
        <i class="fa-regular fa-file-lines fa-lg"></i> Ver títulos</a>
        </li>
        <li>
        <a class="dropdown-item" target="_blank" href="'.URL.'/painel/carnes/'.$dados->id.'" >
        <i class="fa-regular fa-clone fa-lg"></i> Gerar 2ªVia</a>
        </li>
        </ul>
        </div>
        </td>
        </tr>';

      }


      $table = $selecteCliente.'
      <div class="card-body">
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
      'itens' => self::getItens($request,$obPagination),
      'pagination' => parent::getPagination($request,$obPagination)
    ];

    return parent::jsonLista($conteudo);

  }




  public static function getList($request){

    $form = self::getForm($request);
    return json_encode($form);
  }

  public static function getForm($request) {
    $postVars = $request->getPostVars();

    if (isset($postVars['funcao']) && $postVars['funcao'] == 'listar_titulos') {
      $id_admin = parent::getIdAdminInt();
      $matriculaId = (int)($postVars['id'] ?? 0);

      if (!TenantHelper::pertenceMatricula($matriculaId, $id_admin)) {
        return json_encode(['erro' => 'Matrícula não encontrada.']);
      }

      $results = Caixa::getCaixa('id_ref = '.$matriculaId.' AND id_admin = '.$id_admin);
    } 
    
      // Inicializa a tabela
    $table = '';

      // Carrega o SELECT
    while ($obDados = $results->fetchObject(Caixa::class)) {


      if($obDados->status){
        $status = 'Pago';
        $baixaIcon = 'disabled';
        $reciboIcon = '';
        $icon = '<i class="fa-solid fa-circle-check fa-lg text-success"></i>';
      } else {
        $status = 'Em aberto';
        $baixaIcon = '';
        $reciboIcon = 'disabled';
            $icon = '<i class="far fa-edit fa-lg"></i>';
      }
      
      $data_pagamento = $obDados->data_pagamento ? DateTimeHelper::databr($obDados->data_pagamento) : '__/__/____';

      $table .= '
      <tr>
      <td>'.$obDados->descricao.'</td>
      <td>'.DateTimeHelper::databr($obDados->vencimento).'</td>
      <td><span>'.NumeroHelper::moedaBr($obDados->valor).'</span></td>
      <td><span>'.NumeroHelper::moedaBr($obDados->valor_pago).'</span></td>
      <td>'.$data_pagamento.'</td>
      <td>'.$status.'</td>
      <td>
      <a class="dropdown-item '.$baixaIcon.'" href="#"  title="Adicionar ao carrinho" onclick="addCarrinhoTitulo('.$obDados->id.')">
      <i class="fa-solid fa-cart-plus fa-lg"></i>
      </a>

      <a class="dropdown-item '.$reciboIcon.'" title="Imprimir Recibo" target="_blank" href="'.URL.'/painel/carnes/recibo/'.$obDados->id.'" >
        <i class="fas fa-print fa-lg"></i></a>

      </td>

      </tr>';
    }

      // Renderiza a tabela
    $table = '
    <div class="modal-header">
    <h1 class="modal-title fs-5" id="exampleModalLabel">Títulos</h1>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <div class="card-body">
    <div class="table-responsive">
    <table class="table table-striped" id="dataTable" width="100%" cellspacing="0">
    <thead>
    <tr>
    <th>Título</th>
    <th>Vencimento</th>
    <th>Valor R$</th>
    <th>pago R$</th>
    <th>Data pgt</th>
    <th>Status pgt</th>
    <th>Ações</th>
    </tr>
    </thead>
    <tbody>'.$table.'</tbody>
    </table>
    </div>
    </div>';

    return $table;
  }

  public static function darBaixa($request){

   //DADOS DO USUARIO
   $nivel = parent::getIdAdmin()['usuario']['nivel'];

   $habilitado = ($nivel == 'Diretor') ? '' : 'readonly';

   $postVars = $request->getPostVars();
   $id_admin = parent::getIdAdminInt();
   $caixaId = (int)($postVars['id'] ?? 0);

   if (!TenantHelper::pertenceCaixa($caixaId, $id_admin)) {
     return json_encode(['erro' => 'Título não encontrado.']);
   }

   $dados = (array) Caixa::getCaixaById($caixaId);
   $obMatricula = (array) EntityMatri::getMatriculaById($dados['id_ref']);

  $dadosUser = (array) EntityUser::getUserById($obMatricula['id_aluno']);

   $dias = DateTimeHelper::subtrairDatas($dados['vencimento'],DateTimeHelper::hoje())->d;

   $desconto=0;
   $valorComDesconto=0;

   if($dados['vencimento'] > DateTimeHelper::hoje()){
    $vencido = 'vence em';

    if($obMatricula['desconto_pontualidade']){

      $valorComDesconto = $dados['valor']*90/100;
      $desconto = $dados['valor']-$valorComDesconto;
      
    }

  } else {
    $vencido = 'vencido há';

  }

  $valorPagar = $dados['valor']-$desconto;


  $vencimento = DateTimeHelper::databr($dados['vencimento']);

  $form = '<form id="form" method="post">
  <div class="modal-header">
  <h1 class="modal-title fs-5" id="exampleModalLabel">Titulo a Receber</h1>
  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
  </div>
  <div class="modal-body">

  <div id="response"></div>

  <ul class="list-group mb-3 col-md-12">

  <li class="list-group-item d-flex justify-content-between lh-sm">
  <div>
  <h6 class="my-0">Descrição do titulo</h6>
  <small class="text-muted">' . @$dados['descricao'] . '</small>
  </div>
  </li>

  <li class="list-group-item d-flex justify-content-between lh-sm">
  <div>
  <h6 class="my-0">Valor Original</h6>
  <small class="text-muted">' .'R$ '. NumeroHelper::moedaBr(@$dados['valor']) . '</small>
  </div>
  <span class="text-muted"></span>
  </li>

  <li class="list-group-item d-flex justify-content-between lh-sm">
  <div>
  <h6 class="my-0">Data de vencimento</h6>
  <small class="text-muted">'.$vencimento.'</small>
  </div>
  <span class="text-muted">'.$vencido.' '.$dias.' dias</span>
  </li>

  <li class="list-group-item d-flex justify-content-between lh-sm">
  <div>
  <h6 class="my-0">Desconto pontualidade</h6>
  <small class="text-muted">'.'R$ '.NumeroHelper::moedaBr($valorComDesconto).'</small>
  </div>
  <span class="text-muted">'.'Valor do desconto: R$ '.NumeroHelper::moedaBr($desconto).'</span>
  </li>

  <li class="list-group-item d-flex justify-content-between">
  <span>Total a pagar</span>
  <strong>R$ '.NumeroHelper::moedaBr($valorPagar).'</strong>
  </li>
  </ul>

  <input value="' . @$valorPagar. '" type="hidden" id="valor_pagar" name="valor_pagar">

  <div class="row">

  <div class="form-group col-md-6">
  <label>Forma de pagamento</label>
  <select name="tipo_pagamento" class="form-control">
  <option value="">Selecione o tipo</option>
  <option value="Dinheiro">Dinheiro</option>  
  <option value="Pix">Pix</option>    
  <option value="Cartão">Cartão</option>    
  <option value="Boleto">Boleto</option>                   
  </select>
  </div>

  <div class="form-group col-md-6">
  <label>Data de pagamento</label>
  <input type="datetime-local" name="data_pagamento" '.$habilitado.' value="'.DateTimeHelper::agora().'" class="form-control">
  </div>

  <div class="form-group col-md-6">
  <label>Valor recebido</label>
  <input type="text" id="valor_recebido" name="valor_recebido" class="form-control" oninput="calcularTroco()" required>
  </div>

  <div class="form-group col-md-6">
  <label>Troco</label>
  <input type="text" id="troco" readonly class="form-control">
  </div>

  </div>

  </div>
  <div class="modal-footer">
  <input value="' . @$dados['id'] . '" type="hidden" name="id">
  <input value="' . @$dadosUser['id'] . '" type="hidden" name="id_aluno">
  <button type="button" id="btn-fechar" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
  <button type="submit" class="btn btn-primary">Salvar</button>
  </div>
  </form>';

  return $form;

  }

 public static function recibo($request, $id, $larguraPapel = '58mm') {
    $id = (int)$id;
    $id_admin = parent::getIdAdminInt();

    if (!TenantHelper::pertenceCaixa($id, $id_admin)) {
      return 'Recibo não encontrado.';
    }

    $dados = (array) Caixa::getCaixaById($id);
    $userLogedData = SessionUser::getUserLogedData();
    $escola = $userLogedData['escola'] ?? [];
    $logoUrl = htmlspecialchars(BrandingHelper::urlLogoEscola($escola['logo'] ?? null), ENT_QUOTES, 'UTF-8');
    $nomeEscola = htmlspecialchars((string)($escola['nome'] ?? 'Escola'), ENT_QUOTES, 'UTF-8');
    $cnpjEscola = htmlspecialchars((string)($escola['cpf_cnpj'] ?? ''), ENT_QUOTES, 'UTF-8');
    $siteEscola = trim((string)($escola['site'] ?? ''));
    if ($siteEscola === '') {
      $siteEscola = 'www.ctieducacional.com.br';
    }
    $siteEscola = htmlspecialchars($siteEscola, ENT_QUOTES, 'UTF-8');
    $cidadeLinha = '';
    $cidadeRaw = trim((string)($escola['cidade'] ?? ''));
    $estadoRaw = trim((string)($escola['estado'] ?? ''));
    if ($cidadeRaw !== '' || $estadoRaw !== '') {
      if (ctype_digit($cidadeRaw) || ctype_digit($estadoRaw)) {
        try {
          $cid = ctype_digit($cidadeRaw)
            ? \App\Model\Entity\EstadoCidades::getCidades('id = '.(int)$cidadeRaw)->fetchObject()
            : null;
          $est = ctype_digit($estadoRaw)
            ? \App\Model\Entity\EstadoCidades::getEstados('id = '.(int)$estadoRaw)->fetchObject()
            : null;
          $parts = [];
          if ($cid && !empty($cid->nome)) {
            $parts[] = $cid->nome;
          } elseif ($cidadeRaw !== '' && !ctype_digit($cidadeRaw)) {
            $parts[] = $cidadeRaw;
          }
          if ($est && !empty($est->sigla)) {
            $parts[] = $est->sigla;
          } elseif ($estadoRaw !== '' && !ctype_digit($estadoRaw)) {
            $parts[] = $estadoRaw;
          }
          $cidadeLinha = htmlspecialchars(implode(' - ', $parts), ENT_QUOTES, 'UTF-8');
        } catch (\Throwable $e) {
          $cidadeLinha = '';
        }
      } else {
        $cidadeLinha = htmlspecialchars(trim($cidadeRaw.($estadoRaw !== '' ? ' - '.$estadoRaw : '')), ENT_QUOTES, 'UTF-8');
      }
    }

    // Definimos a largura útil (Safe Zone)
    // Para 58mm, o ideal é usar entre 48mm e 52mm para evitar cortes físicos
    $larguraUtil = ($larguraPapel == '58mm') ? '52mm' : '72mm';

    $reciboHtml = '
    <style>
    /* Força o tamanho da página no driver do navegador */
    @page {
        size: 58mm auto;
        margin: 0;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: Arial, sans-serif;
        font-size: 11px; /* Reduzi 1px para garantir que caiba na linha */
        color: #000;
    }

    body {
        width: 48mm; /* Largura segura para impressoras de 58mm */
        margin-left: 0; /* Cola na margem esquerda física da impressora */
        margin-right: 0;
        padding: 0;
        overflow: hidden;
    }

    .center { text-align: center; width: 100%; }
    .right { text-align: right; }
    
    .line {
        border-top: 1px dashed #000;
        margin: 4px 0;
        width: 100%;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed; /* Força a tabela a respeitar a largura de 48mm */
    }

    td {
        padding: 1px 0;
        word-wrap: break-word;
    }

    .small { font-size: 10px; }
    
    strong { font-weight: bold; }
</style>

<body>
        <div class="center">
            <img src="'.$logoUrl.'" alt="" style="max-width:42mm;max-height:18mm;object-fit:contain;"><br>
            <strong>'.$nomeEscola.'</strong><br>
            '.($cidadeLinha !== '' ? $cidadeLinha.'<br>' : '').'
            '.($cnpjEscola !== '' ? 'CNPJ: '.$cnpjEscola : '').'
        </div>

        <div class="line"></div>

        <div>
            Recibo nº: <strong>'.$dados['id'].'</strong><br>
            Data: '.DateTimeHelper::databr($dados['data_pagamento']).'<br>
            Hora: '.DateTimeHelper::extrairHorario($dados['data_pagamento']).'
        </div>

        <div class="line"></div>

        <div>
            Cliente / Descrição:<br>
            <strong>'.mb_strtoupper($dados['descricao']).'</strong>
        </div>

        <div class="line"></div>

        <table>
            <thead>
                <tr>
                    <th align="left">Item</th>
                    <th align="right">Valor</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Mensalidade</td>
                    <td class="right">R$ '.NumeroHelper::moedaBr($dados['valor_pago']).'</td>
                </tr>
            </tbody>
        </table>

        <div class="line"></div>

        <table>
            <tr>
                <td><strong>Total</strong></td>
                <td class="right"><strong>R$ '.NumeroHelper::moedaBr($dados['valor_pago']).'</strong></td>
            </tr>
            <tr>
                <td class="small">Forma de Pgto:</td>
                <td class="right small">'.$dados['tipo_pagamento'].'</td>
            </tr>
        </table>

        <div class="line"></div>

        <div class="small center">
            Referente a baixa de mensalidade.<br>
            Documento sem valor fiscal.
        </div>

        <div class="line"></div>

        <div class="center small">
            Obrigado pela preferência!<br>
            <strong>'.$siteEscola.'</strong>
        </div>

        <br>
        <div class="center">.</div> </body>';

    $content = View::render('admin/modules/carnes/recibo', [
        'title' => 'Recibo de pagamento',
        'show-recibo' => $reciboHtml,
    ]);

    return $content;
}


public static function registrarPagamento($request){

  $postVars = $request->getPostVars();

  $resposta = [
    "filtro" => $postVars['id_aluno']
  ];

$valor_recebido = str_replace(',', '.', $postVars['valor_recebido']);
$valor_recebido = filter_var($valor_recebido, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
$valor_recebido = floatval($valor_recebido);

$valor_pagar = str_replace(',', '.', $postVars['valor_pagar']);
$valor_pagar = filter_var($valor_pagar, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
$valor_pagar = floatval($valor_pagar);

  if($valor_recebido < $valor_pagar){
    $resposta ["erro"] = 'Valor recebido é menor que o valor a receber.';
    return json_encode($resposta);
  }
  if($postVars['tipo_pagamento'] == ''){
    $resposta ["erro"] = 'Selecione uma forma de pagamento.';
    return json_encode($resposta);
  }

  if($postVars['tipo_pagamento'] == ''){
    $resposta ["erro"] = 'Selecione uma forma de pagamento.';
    return json_encode($resposta);
  }

      //NOVA INSTANCIA
  $obCaixa = new Caixa;
  $obCaixa->id = $postVars['id'];
  $obCaixa->valor_pago = $valor_pagar;
  $obCaixa->data_pagamento = $postVars['data_pagamento'] ?? '';
  $obCaixa->tipo_pagamento = $postVars['tipo_pagamento'] ?? '';
  $obCaixa->status = 1;
  $obCaixa->atualizar();

  if(!$obCaixa){
    $resposta ["erro"] = 'Erro ao registrar o pagamento';
  }

  return json_encode($resposta);

}


  public static function imprimeCarne($request,$id){

    $id = (int)$id;
    $id_admin = parent::getIdAdminInt();

    if (!TenantHelper::pertenceMatricula($id, $id_admin)) {
      return 'Matrícula não encontrada.';
    }

    // DADOS DA EMPRESA
    $userLogedData = SessionUser::getUserLogedData();
    // DADOS DO CONTRATO
    $obMatricula = (array) EntityMatri::getMatriculaById($id);
    // DADOS DO CLIENTE
    $obAluno = EntityUser::getUserById($obMatricula['id_aluno'] ?? 0);
    $dadosUser = $obAluno ? (array) $obAluno : [];
    $nomeAluno = (string)($dadosUser['nome'] ?? 'Aluno não encontrado');
    // DADOS DA TRILHA
    $obTrilhaObj = EntityTrilhas::getTrilhaById($obMatricula['id_trilha'] ?? 0);
    $obTrilha = $obTrilhaObj ? (array) $obTrilhaObj : [];

    // DADOS DO RESPONSÁVEL FINANCEIRO (tabela responsaveis, não usuarios)
    $responsavelFinanceiro = $nomeAluno;
    $idResponsavel = (int)($obMatricula['id_responsavel'] ?? 0);
    if ($idResponsavel > 0) {
      $obResponsavel = EntityRes::getResById($idResponsavel);
      if ($obResponsavel && !empty($obResponsavel->nome)) {
        $responsavelFinanceiro = (string)$obResponsavel->nome;
      }
    }

    $total = NumeroHelper::moedaBr(($obMatricula['qtd_parcelas'] ?? 0) * ($obMatricula['valor'] ?? 0));

    $count = 1;
    $carne = '';
    $qrCode = '';

      // VERIFICA SE TEM DESCONTO PONTUALIDADE
    $obs = ($obMatricula['desconto_pontualidade'] ?? false) ? '<b class="tag">Obs: 10% de desconto ao pagar até o dia do vencimento</b>' : 'Observações';

    $results = Caixa::getCaixa("id_ref =".$obMatricula['id']);

    while ($obCaixa = $results->fetchObject(Caixa::class)) {

        // VERIFICA SE EXISTE QRCODE PIX
      if(!empty($obCaixa->pix_copia_cola)){
        $pixPayload = rawurlencode((string)$obCaixa->pix_copia_cola);
        $qrCode = '<tr>
        <td rowspan="4">
        <img src="https://api.qrserver.com/v1/create-qr-code/?size=130x130&data='.$pixPayload.'" alt="PIX">
        </td>
        </tr>';
      }

      $carne .= '

      <div class="espaco"></div>
      <!-- Div que envolve todo o carne -->

      <div class="parcela">

      <!-- Canhoto do carne -->
      <div class="destaca">
      <table>
      <tr>
      <td class="tag"><b>Nº Lançamento</b>
      <br>
      <span>'.$obMatricula['id'].'</span>
      </td>
      <td class="tag"><b>Parcela</b>
      <br>
      <span>'.$count.' / '.$obMatricula['qtd_parcelas'].'</span>
      </td>
      </tr>
      <tr>
      <td colspan="2" class="tag"><b>'.htmlspecialchars((string)($userLogedData['escola']['nome'] ?? ''), ENT_QUOTES, 'UTF-8').'</b>
      <br>
      <span>CNPJ: '.htmlspecialchars((string)($userLogedData['escola']['cpf_cnpj'] ?? ''), ENT_QUOTES, 'UTF-8').'</span>
      </td>
      </tr>
      <tr>
      <td class="tag"><b>Aluno</b>
      <br>
      <span>'.htmlspecialchars($nomeAluno, ENT_QUOTES, 'UTF-8').'</span>
      </td>
      <td class="tag"><b>Valor Total</b>
      <br>
      <span>R$ '.$total.'</span>
      </td>
      </tr>
      <tr>
      <td class="tag"><b>Valor Parcela</b>
      <br>
      <span>R$ '.NumeroHelper::moedaBr($obCaixa->valor).'</span>
      </td>
      <td class="tag"><b>Vencimento</b>
      <br>
      <span>'.DateTimeHelper::databr($obCaixa->vencimento).'</span>
      </td>
      </tr>
      <tr>
      <td style="text-align: center;" colspan="2" class="botton">
      <b class="tag">Assinatura Secretaria</b><br>
      </td>
      </tr>
      </table>
      </div>

      <!-- Parte com qr code do carne -->

      <table>

      '.$qrCode.'

      <tr>
      <td colspan="1" class="tag"><b>Produto/Serviço</b>
      <br>
      <span>'.htmlspecialchars((string)($obTrilha['nome'] ?? ''), ENT_QUOTES, 'UTF-8').'</span>
      </td>
      <td class="tag"><b>Valor Total</b>
      <br>
      <span>R$ '.$total.'</span>
      </td>
      </tr>

      <tr>
      <td colspan="1" class="tag"><b>Aluno</b>
      <br>
      <span>'.htmlspecialchars($nomeAluno, ENT_QUOTES, 'UTF-8').'</span>
      </td>
      <!--
      <td class="tag"><b>Responssável fianceito</b>
      <br>
      <span>'.$responsavelFinanceiro.'</span>
      </td>
      -->
      </tr>

      <tr>
      <td colspan="1" class="tag"><b>Valor Parcela</b>
      <br>
      <span>R$ '.NumeroHelper::moedaBr($obCaixa->valor).'</span>
      </td>
      <td class="tag"><b>Vencimento</b>
      <br>
      <span>'.DateTimeHelper::databr($obCaixa->vencimento).'</span>
      </td>
      </tr>
      <tr>
      <td colspan="2" class="botton">
      '.$obs.'
      <span></span><br>
      </td>
      </tr>
      </td>

      </table>
      </div>
      <div class="linha"></div>

      ';

      $count++;
    }

         //CONTEÚDO DE FORMULÁRIO
    $content = View::render('admin/modules/carnes/carne',[
     'title' => 'Carnê de pagamento',
     'show-carne' => $carne,

   ]);

    return $content;

  }


  }
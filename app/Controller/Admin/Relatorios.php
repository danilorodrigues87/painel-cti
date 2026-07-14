<?php 

namespace App\Controller\Admin;
use \App\Utils\View;
use \App\Model\Entity\Caixa;
use \App\Model\Entity\User as EntityUser;
use \App\Model\Db\Pagination;
use \App\Common\Helpers\DateTimeHelper;
use \App\Common\Helpers\NumeroHelper;

class Relatorios extends Page{

  //RETORNA O FORMULARIO
  public static function index($request){
    //CONTEÚDO DE FORMULÁRIO
    $content = View::render('admin/modules/relatorios/index',[]);

    //RETORNA A PÁGINA COMPLETA
    /**
         * TITULO DA PAGINA
         * CONTEUDO
         * CURRENTSESSION SESSÃO ATUAL
         * REQUEST SE NESCESSÁRIO
         */
    return parent::getPanel('Relatórios',$content,'Financeiro');
  }


   public static function getPrimeiroENome($nomeCompleto) {

    // Divide o nome completo em partes
    $partes = explode(' ', trim($nomeCompleto));

    // Verifica se há pelo menos duas partes no nome
    if (count($partes) < 2) {
        return $nomeCompleto; // Retorna o nome completo caso não haja sobrenome
    }

    // Sempre pega o primeiro nome
    $primeiroNome = $partes[0];

    // Verifica o tamanho do segundo nome
    if (strlen($partes[1]) <= 2) {
        // Se o segundo nome tem até 2 letras, pega também o terceiro nome, se existir
        $sobrenome = isset($partes[2]) ? $partes[1] . ' ' . $partes[2] : $partes[1];
    } else {
        // Caso contrário, pega apenas o segundo nome
        $sobrenome = $partes[1];
    }

    // Retorna o nome tratado
    return $primeiroNome . ' ' . $sobrenome;
}

  private static function getDadosItens($request,&$obPagination){

$debug = '';
$table = '';
$filtragem = '';

    //DADOS DO ADMIN
    $id_admin = parent::getIdAdmin()['usuario']['id_admin'];

    //PAGINA ATUAL
    $postVars = $request->getPostVars();
    //$paginaAtual = $postVars['page'] ?? 1;

// Definindo os campos
$campos = [
    'tituloChbx' => '',
    'tipo_transacaoChbx' => '',
    'usuarioChbx' => '',
    'vencimentoChbx' => '',
    'data_pgtoChbx' => '',
    'data_registroChbx' => '',
    'valorChbx' => '',
    'valor_pagoChbx' => '',
    'forma_pgtoChbx' => ''
];

// Verifica se algum checkbox foi marcado
$temCheckboxAtivo = false;
foreach ($campos as $campo => &$valor) {
    if (isset($postVars[$campo]) && $postVars[$campo] == 1) {
        $valor = 'checked';
        $temCheckboxAtivo = true;
    }
}

// Se nenhum checkbox estiver ativo, todos recebem 'checked'
if (!$temCheckboxAtivo) {
    foreach ($campos as &$valor) {
        $valor = 'checked';
    }
}

// Atribuindo as variáveis finais
$tituloChbx = $campos['tituloChbx'];
$tipo_transacaoChbx = $campos['tipo_transacaoChbx'];
$usuarioChbx = $campos['usuarioChbx'];
$vencimentoChbx = $campos['vencimentoChbx'];
$data_pgtoChbx = $campos['data_pgtoChbx'];
$data_registroChbx = $campos['data_registroChbx'];
$valorChbx = $campos['valorChbx'];
$valor_pagoChbx = $campos['valor_pagoChbx'];
$forma_pgtoChbx = $campos['forma_pgtoChbx'];


// CONDIÇÕES DEMAIS FILTROS

  $conditions = [];

  // Isolamento por escola
  $conditions[] = 'id_admin = '.(int)$id_admin;

  // Condição padrão
  $conditions[] = 'status = 1';

    // Filtro de tipo de transação
  if (!empty($postVars['tipo_transacao'])) {
    $tipo_transacao = filter_var($postVars['tipo_transacao'], FILTER_SANITIZE_STRING);
    $conditions[] = " tipo_transacao = '$tipo_transacao' ";
  }

     // Filtro de tipo de pagamento
  if (!empty($postVars['tipo_pagamento'])) {
    $tipo_pagamento = filter_var($postVars['tipo_pagamento'], FILTER_SANITIZE_STRING);
    $conditions[] = " tipo_pagamento = '$tipo_pagamento' ";
  }

// filtro periodo
if (!empty($postVars['de']) && !empty($postVars['ate'])) {

    $de  = $postVars['de'] . ' 00:00:00';
    $ate = $postVars['ate'] . ' 23:59:59';

    $conditions[] = "data_pagamento BETWEEN '{$de}' AND '{$ate}'";

} else {

    $conditions[] = "
        data_pagamento >= DATE_FORMAT(CURRENT_DATE, '%Y-%m-01')
        AND data_pagamento <  DATE_ADD(DATE_FORMAT(CURRENT_DATE, '%Y-%m-01'), INTERVAL 1 MONTH)
    ";
}

  // Combina as condições dinâmicas
 $whereClause = implode(' AND ', $conditions);

$filtragem = 
'<div class="container my-4">
  <h3 class="mb-4">Opções de Filtragem</h3>

  <form id="formBusca" method="post">
    <div class="row g-3">

      <div class="form-group col-md-3">
        <label for="tipo_transacao">Tipo de Transação</label>
        <select class="form-control" id="tipo_transacao" name="tipo_transacao">
          <option value="">Todos</option>
          <option ' . ((isset($tipo_transacao) && $tipo_transacao === 'Entrada') ? 'selected' : '') . ' value="Entrada">Entrada</option>
          <option ' . ((isset($tipo_transacao) && $tipo_transacao === 'Saida') ? 'selected' : '') . ' value="Saida">Saida</option>
        </select>
      </div>
      
      <div class="form-group col-md-3">
        <label for="de">De</label>
        <input type="date" id="de" name="de" value="' . (isset($postVars['de']) ? $postVars['de'] : '') . '" class="form-control">
      </div>

      <div class="form-group col-md-3">
        <label for="ate">Até</label>
        <input type="date" id="ate" name="ate" value="' . (isset($postVars['ate']) ? $postVars['ate'] : '') . '" class="form-control">
      </div>

      <div class="form-group col-md-3">
        <label for="tipo_pagamento">Forma de Pagamento</label>
        <select class="form-control" id="tipo_pagamento" name="tipo_pagamento">
          <option value="">Todos os tipos</option>
          <option ' . ((isset($tipo_pagamento) && $tipo_pagamento === 'Pix') ? 'selected' : '') . ' value="Pix">Pix</option>
          <option ' . ((isset($tipo_pagamento) && $tipo_pagamento === 'Dinheiro') ? 'selected' : '') . ' value="Dinheiro">Dinheiro</option>
          <option ' . ((isset($tipo_pagamento) && $tipo_pagamento === 'Cartão') ? 'selected' : '') . ' value="Cartão">Cartão</option>
          <option ' . ((isset($tipo_pagamento) && $tipo_pagamento === 'Boleto') ? 'selected' : '') . ' value="Boleto">Boleto</option>
          <option ' . ((isset($tipo_pagamento) && $tipo_pagamento === 'QrCode') ? 'selected' : '') . ' value="QrCode">QrCode</option>
        </select>
      </div>
      

    </div>

    <div class="form-control mt-2">
    <div class="form-check form-check-inline">
      <input class="form-check-input" type="checkbox" ' . $tituloChbx . ' name="tituloChbx" value="1">
      <label class="form-check-label">titulo</label>
    </div>
    <div class="form-check form-check-inline">
      <input class="form-check-input" type="checkbox" ' . $tipo_transacaoChbx. ' name="tipo_transacaoChbx" value="1">
      <label class="form-check-label">tipo transação</label>
    </div>
    <div class="form-check form-check-inline">
      <input class="form-check-input" type="checkbox" ' . $usuarioChbx . ' name="usuarioChbx" value="1">
      <label class="form-check-label">usuário</label>
    </div>
    <div class="form-check form-check-inline">
      <input class="form-check-input" type="checkbox" ' . $vencimentoChbx . ' name="vencimentoChbx" value="1">
      <label class="form-check-label">vencimento</label>
    </div>
    <div class="form-check form-check-inline">
      <input class="form-check-input" type="checkbox" ' . $data_pgtoChbx . ' name="data_pgtoChbx" value="1">
      <label class="form-check-label">data pgto</label>
    </div>
    <div class="form-check form-check-inline">
      <input class="form-check-input" type="checkbox" ' . $data_registroChbx . ' name="data_registroChbx" value="1">
      <label class="form-check-label">data registro</label>
    </div>
    <div class="form-check form-check-inline">
      <input class="form-check-input" type="checkbox" ' . $valorChbx . ' name="valorChbx" value="1">
      <label class="form-check-label">valor</label>
    </div>
    <div class="form-check form-check-inline">
      <input class="form-check-input" type="checkbox" ' . $valor_pagoChbx . ' name="valor_pagoChbx" value="1">
      <label class="form-check-label">valor pago</label>
    </div>
    <div class="form-check form-check-inline">
      <input class="form-check-input" type="checkbox" ' . $forma_pgtoChbx . ' name="forma_pgtoChbx" value="1">
      <label class="form-check-label">forma pgto</label>
    </div>
    </div>

    <div class="d-flex justify-content-end mt-3">
    <button class="btn btn-secondary" onclick="gerarPdf()">Gerar PDF</button>
    <button type="submit" class="btn btn-primary ms-3">Filtrar</button> 
    </div>

  </form>
</div>';



// QUANTIDADE TOTAL DE REGISTROS
  $quantidadeTotal = Caixa::getCaixa($whereClause, null, null, 'COUNT(*) as qtd')->fetchObject()->qtd;

// INSTANCIA DE PAGINAÇÃO
 // $obPagination = new Pagination($quantidadeTotal,$paginaAtual,5);

// RESULTADOS DA PAGINA
  $results = Caixa::getCaixa($whereClause, 'ultima_alteracao DESC');

$itens = '';

    //REDERIZA O ITEM
while ($obDados = $results->fetchObject(Caixa::class)) {

  $ultima_alteracao = $obDados->ultima_alteracao ? DateTimeHelper::databr($obDados->ultima_alteracao).' as '.DateTimeHelper::horario($obDados->ultima_alteracao) : '__/__/__';

  if($obDados->id_usuario > 0){
  $obUser = (array) EntityUser::getUserById($obDados->id_usuario);
  $nome_usuario = self::getPrimeiroENome($obUser['nome']);
} else {
  $nome_usuario = "Registro via API";
}

  $itens .= '<tr>
  '. (($tituloChbx) ? '<td>'.$obDados->descricao.'</td>' : '') .'
  '. (($tipo_transacaoChbx) ? '<td>'.$obDados->tipo_transacao.'</td>' : '') .'
  '. (($usuarioChbx) ? '<td>'.$nome_usuario.'</td>' : '') .'
  '. (($vencimentoChbx) ? '<td>'.DateTimeHelper::databr($obDados->vencimento).'</td>' : '') .'
  '. (($data_pgtoChbx) ? '<td>'.DateTimeHelper::databr($obDados->data_pagamento).'</td>' : '') .'
  '. (($data_registroChbx) ? '<td>'.$ultima_alteracao.'</td>' : '') .'
  '. (($valorChbx) ? '<td>R$ <span class="mascara-dinheiro">'.$obDados->valor.'</span></td>' : '') .'
  '. (($valor_pagoChbx) ? '<td>R$ <span class="mascara-dinheiro">'.$obDados->valor_pago.'</span></td>' : '') .'
  '. (($forma_pgtoChbx) ? '<td>'.$obDados->tipo_pagamento.'</td>' : '') .'
  </tr>
  <script src="'.URL.'/resources/js/js_mascara.js"></script>';

}


$table = '<div class="card-body">
<div class="table-responsive">
<table class="table table-striped" id="dataTable" width="100%" cellspacing="0">
<thead>
<tr> 
'. (($tituloChbx) ? '<th>Titulo</th>' : '') .'
'. (($tipo_transacaoChbx) ? '<th>Tipo transação</th>' : '') .'
'. (($usuarioChbx) ? '<th>Usuário</th>' : '') .'
'. (($vencimentoChbx) ? '<th>Vencimento</th>' : '') .'
'. (($data_pgtoChbx) ? '<th>Data Pgto</th>' : '') .'
'. (($data_registroChbx) ? '<th>Data Registro</th>' : '') .'
'. (($valorChbx) ? '<th>Valor</th>' : '') .'
'. (($valor_pagoChbx) ? '<th>Valor Pago</th>' : '') .'
'. (($forma_pgtoChbx) ? '<th>Forma Pgto</th>' : '') .'
</tr>
</thead>
<tbody>' . $itens . '</tbody>
</table>
</div>
</div>';



  if($quantidadeTotal <= 0){
    $table = 
    '<div class="alert alert-warning mt-2" role="alert">
  Nenhum registro encontrado.
    </div>';
  }

$dados['filtragem'] = $filtragem;
$dados['itens'] = $table;
$dados['debug'] = $debug;

    //RETORNA
  return $dados;

  
}

public static function getInfo($request) {

    $dadosInfo = self::getDadosItens($request, $obPagination);

    return json_encode([
        'itens' => $dadosInfo['itens'],
        'filtragem' => $dadosInfo['filtragem'],
        'debug' => $dadosInfo['debug']
    ]);
}



}
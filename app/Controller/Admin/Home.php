<?php

namespace App\Controller\Admin;

use \App\Utils\View;
use \App\Model\Entity\User as EntityUser;
use \App\Model\Entity\Matriculas;
use \App\Model\Entity\Caixa;
use \App\Session\User\Login as SessionUser;
use \App\Common\Helpers\DateTimeHelper;
use \App\Common\Helpers\NumeroHelper;
use PDO;

// REDERIZA A VIEW DA HOME DO PAINEL

class Home extends Page{

	public static function index($request){


         $dados = self::getInfo();

		// CONTEÚDO DA HOME
            $content = View::render('admin/modules/home/index',[

             'qtt-clientes' => $dados['qtt_clientes'],
             'clientes-cadastrados' => $dados['qtt_clientes_cadastrados'],
             'recebido-hoje' => $dados['recebido_hoje'],
             'receber-semana' => $dados['receber_semana'],
             'balanco' => self::dinheiroEmCaixa(),
             'inadimplentesDoMes' => self::inadimplentesDoMes(), 
             'visible' => $dados['visible']

         ]);


		// RETORNA A PÁGINA COMPLETA

        return parent::getPanel('Dashboard', $content, 'Dashboard',$request);

    }


    public static function getInfo(){

		// DADOS DO ADMIN

        $obUserLoged = SessionUser::getUserLogedData(); 

        if($obUserLoged['usuario']['nivel'] == 'Diretor' or $obUserLoged['usuario']['nivel'] == 'Financeiro'){

            $visible = '';

        } else {

            $visible = 'd-none';

        }


        $id_admin = $obUserLoged['usuario']['id_admin'];

		// QUANTIDADE TOTAL DE CLIENTES ATIVOS

        $qtt_clientes = Matriculas::getMatriculas('id_admin = ' . (int)$id_admin .' AND status = 0 AND fim < CURDATE()',null,null,'COUNT(*) as qtd')->fetchObject()->qtd;


        // QUANTIDADE TOTAL DE CLIENTES CADASTRADOS

        $qtt_clientes_cadastrados = EntityUser::getUser('id_admin = ' . (int)$id_admin .' AND NIVEL = "Cliente"',null,null,'COUNT(*) as qtd')->fetchObject()->qtd;


        // VALORES RECEBIDOS HOJE

        $whereHoje = 'id_admin = "' . $id_admin . '" AND tipo_transacao = "Entrada" AND status = 1 AND date(data_pagamento) = CURDATE()';

        $recebido_hoje = Caixa::getCaixa($whereHoje, null, null, 'SUM(valor_pago) as recebe')->fetchObject()->recebe;

        // VALORES A RECEBER NA SEMANA

        $vencimentoSql = "AND WEEK(CURRENT_TIMESTAMP) = WEEK(vencimento) AND YEAR(CURRENT_DATE) = YEAR(vencimento)";

        // Corrigido a construção da cláusula WHERE
        $where = 'id_admin = "' . $id_admin . '" AND tipo_transacao = "Entrada" ' . $vencimentoSql;

        $receber_semana = Caixa::getCaixa($where, null, null, 'SUM(valor) as recebe')->fetchObject()->recebe;


		// JUNTA TODAS AS INFORMAÇÕES PARA RETORNAR

        $data = [

         "qtt_clientes" => $qtt_clientes,
         "qtt_clientes_cadastrados" => $qtt_clientes_cadastrados,
         "recebido_hoje" => NumeroHelper::moedaBr($recebido_hoje ? $recebido_hoje : 0),
         "receber_semana" => NumeroHelper::moedaBr($receber_semana ? $receber_semana : 0),
         "visible" => $visible

     ];


     //return self::getData();
     return $data;

 }

 public static function dinheiroEmCaixa(){

    $obUserLoged = SessionUser::getUserLogedData(); 

    $id_admin = $obUserLoged['usuario']['id_admin'];


       // VALOR TOTAL RECEBIDO

        $where = 'id_admin = "' . $id_admin . '" AND tipo_transacao = "Entrada" AND status = 1 AND tipo_pagamento = "Dinheiro" ';

        $recebido = Caixa::getCaixa($where, null, null, 'SUM(valor_pago) as recebe')->fetchObject()->recebe;

        // VALOR TOTAL RETIRADO

        $where = 'id_admin = "' . $id_admin . '" AND tipo_transacao = "Saida" AND status = 1 AND tipo_pagamento = "Dinheiro" ';

        $retirado = Caixa::getCaixa($where, null, null, 'SUM(valor_pago) as recebe')->fetchObject()->recebe;

        $balanco = $recebido - $retirado;

        return NumeroHelper::moedaBr($balanco ? $balanco : 0);

 }

public static function inadimplentesDoMes(){

    $obUserLoged = SessionUser::getUserLogedData(); 
    $id_admin = $obUserLoged['usuario']['id_admin'];

    // INADIMPLENTES DO MÊS
    $where = 
    'id_admin = "' . $id_admin . '" 
    AND tipo_transacao = "Entrada" 
    AND MONTH(CURRENT_TIMESTAMP) = MONTH(vencimento) 
    AND YEAR(CURRENT_DATE) = YEAR(vencimento) 
    AND DATE(vencimento) < CURRENT_DATE
    AND status = 0';

    $valores = Caixa::getCaixa($where, null, null, 'SUM(valor) as recebe')->fetchObject()->recebe;

    return NumeroHelper::moedaBr($valores ? $valores : 0);
}




 public static function getData(){

$obUserLoged = SessionUser::getUserLogedData();

$id_admin = $obUserLoged['usuario']['id_admin'];

// WHERE clause
$where = 'tipo_transacao = "Entrada" AND id_admin = "' . $id_admin . '" AND status = 1 AND data_pagamento >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)';

// GROUP BY clause
$group = 'DATE_FORMAT(data_pagamento, "%Y-%m")'; 

// ORDER BY clause
$order = 'mes_ano ASC';

// Fields to select
$fields = "DATE_FORMAT(data_pagamento, '%Y-%m') AS mes_ano, SUM(valor_pago) AS total_recebido";

// Execute query
$results = Caixa::getCaixa($where,$order, null, $fields, null, $group)->fetchAll(PDO::FETCH_OBJ); 


// WHERE clause
$where = 'id_admin = "' . $id_admin . '" AND matriculado_em >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)';

// GROUP BY clause
$group = 'mes_ano'; 

// ORDER BY clause
$order = 'mes_ano ASC';

// Fields to select
$fields = "DATE_FORMAT(matriculado_em, '%Y-%m') AS mes_ano, COUNT(*) AS total_matriculas";

// Execute query
$resultVendas = Matriculas::getMatriculas($where,$order, null, $fields, null, $group)->fetchAll(PDO::FETCH_OBJ); 


// Mapeamento de mês numérico para o nome do mês
$mesesNomes = [
    "01" => "Janeiro",
    "02" => "Fevereiro",
    "03" => "Março",
    "04" => "Abril",
    "05" => "Maio",
    "06" => "Junho",
    "07" => "Julho",
    "08" => "Agosto",
    "09" => "Setembro",
    "10" => "Outubro",
    "11" => "Novembro",
    "12" => "Dezembro"
];


// Arrays para armazenar os meses e valores recebidos
$financa_meses = [];
$financa_valores = [];

// Preencher os arrays com os dados retornados da consulta
foreach ($results as $result) {
    // Extrai o mês e o ano
    $mes_ano = $result->mes_ano; // Exemplo: "2024-10"
    $mes = substr($mes_ano, 5, 2); // Extrai o número do mês (Exemplo: "10")
    $ano = substr($mes_ano, 0, 4); // Extrai o ano (Exemplo: "2024")
    
    // Adiciona o nome do mês no array
    $financa_meses[] = $mesesNomes[$mes] . ' ' . $ano; // Exemplo: "Outubro 2024"
    
    // Adiciona o valor total recebido no array
    $financa_valores[] = (float)$result->total_recebido; // Converte para float para garantir o formato numérico

}

// Arrays para armazenar os meses e valores recebidos
$vendas_meses = [];
$vendas_valores = [];

// Preencher os arrays com os dados retornados da consulta
foreach ($resultVendas as $result) {
    // Extrai o mês e o ano
    $mes_ano = $result->mes_ano; 
    $mes = substr($mes_ano, 5, 2); 
    $ano = substr($mes_ano, 0, 4); 
    
    // Adiciona o nome do mês no array
    $vendas_meses[] = $mesesNomes[$mes] . ' ' . $ano; 
    
    // Adiciona o valor total recebido no array
    $vendas_valores[] = (float)$result->total_matriculas; 

}

$dadosFinanca = [
    "financas_meses" => $financa_meses,
    "financas_valores" => $financa_valores
];

$dadosVendas = [
    "vendas_meses" => $vendas_meses,
    "vendas_valores" => $vendas_valores
];

//TOP 5 CURSOS MAIS VENDIDOS

$resultado = Matriculas::getCursosMaisMatriculadosMes(
    $id_admin,
    null,      
    null,   
    '5'      
)->fetchAll(\PDO::FETCH_ASSOC);

$top_produtos = [];
$top_porcentagem = [];
$top_cores = [];

// Paleta fixa (ou você pode gerar dinamicamente)
$cores = ['#007bff', '#dc3545', '#ffc107', '#28a745', '#6610f2'];

// Soma total de matrículas (para calcular %)
$totalGeral = array_sum(array_column($resultado, 'total_matriculas'));

foreach ($resultado as $index => $curso) {
    $top_produtos[] = $curso['curso'];

    // Percentual
    $percentual = $totalGeral > 0
        ? round(($curso['total_matriculas'] / $totalGeral) * 100, 2)
        : 0;

    $top_porcentagem[] = $percentual;

    // Cor
    $top_cores[] = $cores[$index] ?? '#6c757d';
}

if (empty($top_produtos)) {
    $top_produtos    = ["Sem matrículas"];
    $top_porcentagem = [100];
    $top_cores       = ["#e0e0e0"];
}

$top5 = [
    "top_produtos"    => $top_produtos,
    "top_porcentagem" => $top_porcentagem,
    "top_cores"       => $top_cores
];




$totalDados = array_merge($dadosFinanca,$dadosVendas,$top5);

return json_encode($totalDados);


}


}
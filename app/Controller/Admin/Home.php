<?php

namespace App\Controller\Admin;

use \App\Utils\View;
use \App\Model\Entity\User as EntityUser;
use \App\Model\Entity\Matriculas;
use \App\Model\Entity\Caixa;
use \App\Model\Entity\CrmLeads as EntityCrmLeads;
use \App\Model\Entity\CrmHistorico as EntityCrmHistorico;
use \App\Session\User\Login as SessionUser;
use \App\Common\Helpers\NumeroHelper;
use PDO;

class Home extends Page{

	private static $mesesNomes = [
		'01' => 'Jan', '02' => 'Fev', '03' => 'Mar', '04' => 'Abr',
		'05' => 'Mai', '06' => 'Jun', '07' => 'Jul', '08' => 'Ago',
		'09' => 'Set', '10' => 'Out', '11' => 'Nov', '12' => 'Dez'
	];

	private static $crmStatusOrdem = ['novo', 'em_atendimento', 'matriculado', 'perdido'];

	private static $crmStatusLabels = [
		'novo'           => 'Novo',
		'em_atendimento' => 'Em atendimento',
		'matriculado'    => 'Matriculado',
		'perdido'        => 'Perdido'
	];

	public static function index($request){

		$dados = self::getInfo();

		$content = View::render('admin/modules/home/index',[
			'qtt-clientes'           => $dados['qtt_clientes'],
			'clientes-cadastrados'   => $dados['qtt_clientes_cadastrados'],
			'recebido-hoje'          => $dados['recebido_hoje'],
			'receber-semana'         => $dados['receber_semana'],
			'balanco'                => $dados['balanco'],
			'inadimplentesDoMes'     => $dados['inadimplentes_mes'],
			'visible'                => $dados['visible'],
			'visible-crm'            => $dados['visible_crm'],
			'crm-novo'               => $dados['crm_novo'],
			'crm-atendimento'        => $dados['crm_atendimento'],
			'crm-matriculado'        => $dados['crm_matriculado'],
			'crm-perdido'            => $dados['crm_perdido'],
			'crm-esquecidos'         => $dados['crm_esquecidos'],
			'crm-conversao'          => $dados['crm_conversao'],
			'comp-matriculas'        => $dados['comp_matriculas'],
			'comp-receita'           => $dados['comp_receita']
		]);

		return parent::getPanel('Dashboard', $content, 'Dashboard', $request);
	}

	public static function getInfo(){

		$obUserLoged = SessionUser::getUserLogedData();
		$nivel = $obUserLoged['usuario']['nivel'] ?? '';
		$acesso = $obUserLoged['usuario']['acesso'] ?? [];
		$id_admin = (int)$obUserLoged['usuario']['id_admin'];

		$visibleFinanceiro = in_array($nivel, ['Diretor', 'Financeiro']) ? '' : 'd-none';
		$visibleCrm = in_array('Leads', $acesso, true) ? '' : 'd-none';

		$qtt_clientes = (int)Matriculas::getMatriculas(
			'id_admin = '.$id_admin.' AND status = 0 AND fim >= CURDATE()',
			null, null, 'COUNT(*) as qtd'
		)->fetchObject()->qtd;

		$qtt_clientes_cadastrados = (int)EntityUser::getUser(
			'id_admin = '.$id_admin.' AND NIVEL = "Cliente"',
			null, null, 'COUNT(*) as qtd'
		)->fetchObject()->qtd;

		$recebido_hoje = Caixa::getCaixa(
			'id_admin = "'.$id_admin.'" AND tipo_transacao = "Entrada" AND status = 1 AND DATE(data_pagamento) = CURDATE()',
			null, null, 'SUM(valor_pago) as recebe'
		)->fetchObject()->recebe;

		$receber_semana = Caixa::getCaixa(
			'id_admin = "'.$id_admin.'" AND tipo_transacao = "Entrada" AND WEEK(CURRENT_TIMESTAMP) = WEEK(vencimento) AND YEAR(CURRENT_DATE) = YEAR(vencimento)',
			null, null, 'SUM(valor) as recebe'
		)->fetchObject()->recebe;

		$crmStats = self::getCrmStatsCards($id_admin);
		$comparativo = self::getComparativoMes($id_admin);

		return [
			'qtt_clientes'             => $qtt_clientes,
			'qtt_clientes_cadastrados' => $qtt_clientes_cadastrados,
			'recebido_hoje'            => NumeroHelper::moedaBr($recebido_hoje ?: 0),
			'receber_semana'           => NumeroHelper::moedaBr($receber_semana ?: 0),
			'balanco'                  => self::dinheiroEmCaixa(),
			'inadimplentes_mes'        => self::inadimplentesDoMes(),
			'visible'                  => $visibleFinanceiro,
			'visible_crm'              => $visibleCrm,
			'crm_novo'                 => $crmStats['novo'],
			'crm_atendimento'          => $crmStats['em_atendimento'],
			'crm_matriculado'          => $crmStats['matriculado'],
			'crm_perdido'              => $crmStats['perdido'],
			'crm_esquecidos'           => $crmStats['esquecidos'],
			'crm_conversao'            => $crmStats['conversao'],
			'comp_matriculas'          => $comparativo['matriculas'],
			'comp_receita'             => $comparativo['receita']
		];
	}

	public static function getData($request){

		$obUserLoged = SessionUser::getUserLogedData();
		$id_admin = (int)$obUserLoged['usuario']['id_admin'];
		$postVars = $request->getPostVars();
		$periodo = $postVars['periodo'] ?? 'ano';

		$meses = self::resolverQuantidadeMeses($periodo);
		$intervaloSql = self::resolverIntervaloSql($periodo);
		$dataMatricula = Matriculas::campoDataMatricula('matriculas');

		$financasRaw = self::agruparPorMes(
			Caixa::getCaixa(
				'tipo_transacao = "Entrada" AND id_admin = "'.$id_admin.'" AND status = 1 AND data_pagamento >= '.$intervaloSql,
				'mes_ano ASC', null,
				'DATE_FORMAT(data_pagamento, "%Y-%m") AS mes_ano, SUM(valor_pago) AS total',
				null, 'DATE_FORMAT(data_pagamento, "%Y-%m")'
			)->fetchAll(PDO::FETCH_ASSOC),
			'total'
		);

		$vendasRaw = self::agruparPorMes(
			Matriculas::getMatriculas(
				'id_admin = "'.$id_admin.'" AND '.$dataMatricula.' >= '.$intervaloSql,
				'mes_ano ASC', null,
				'DATE_FORMAT('.$dataMatricula.', "%Y-%m") AS mes_ano, COUNT(*) AS total',
				null, 'DATE_FORMAT('.$dataMatricula.', "%Y-%m")'
			)->fetchAll(PDO::FETCH_ASSOC),
			'total'
		);

		$financasSerie = self::preencherMesesContinuos($financasRaw, $meses);
		$vendasSerie   = self::preencherMesesContinuos($vendasRaw, $meses);

		return json_encode(array_merge(
			[
				'financas_meses'   => $financasSerie['meses'],
				'financas_valores' => $financasSerie['valores'],
				'vendas_meses'     => $vendasSerie['meses'],
				'vendas_valores'   => $vendasSerie['valores']
			],
			self::getTopCursos($id_admin),
			self::getCrmStatusChart($id_admin),
			self::getCrmOrigensChart($id_admin),
			['top_vendedores' => self::getTopVendedores($id_admin)]
		));
	}

	public static function dinheiroEmCaixa(){

		$id_admin = SessionUser::getUserLogedData()['usuario']['id_admin'];

		$recebido = Caixa::getCaixa(
			'id_admin = "'.$id_admin.'" AND tipo_transacao = "Entrada" AND status = 1 AND tipo_pagamento = "Dinheiro"',
			null, null, 'SUM(valor_pago) as recebe'
		)->fetchObject()->recebe;

		$retirado = Caixa::getCaixa(
			'id_admin = "'.$id_admin.'" AND tipo_transacao = "Saida" AND status = 1 AND tipo_pagamento = "Dinheiro"',
			null, null, 'SUM(valor_pago) as recebe'
		)->fetchObject()->recebe;

		return NumeroHelper::moedaBr(($recebido ?: 0) - ($retirado ?: 0));
	}

	public static function inadimplentesDoMes(){

		$id_admin = SessionUser::getUserLogedData()['usuario']['id_admin'];

		$valores = Caixa::getCaixa(
			'id_admin = "'.$id_admin.'" AND tipo_transacao = "Entrada" AND MONTH(CURRENT_TIMESTAMP) = MONTH(vencimento) AND YEAR(CURRENT_DATE) = YEAR(vencimento) AND DATE(vencimento) < CURRENT_DATE AND status = 0',
			null, null, 'SUM(valor) as recebe'
		)->fetchObject()->recebe;

		return NumeroHelper::moedaBr($valores ?: 0);
	}

	private static function getCrmStatsCards($id_admin){

		$stats = [
			'novo' => 0, 'em_atendimento' => 0, 'matriculado' => 0, 'perdido' => 0,
			'esquecidos' => 0, 'conversao' => '0%'
		];

		foreach(self::$crmStatusOrdem as $status){
			$stats[$status] = (int)EntityCrmLeads::getLeads(
				'id_admin = '.$id_admin.' AND status = "'.$status.'"',
				null, null, 'COUNT(*) as qtd'
			)->fetch(PDO::FETCH_ASSOC)['qtd'];
		}

		$total = array_sum([
			$stats['novo'], $stats['em_atendimento'], $stats['matriculado'], $stats['perdido']
		]);

		$stats['esquecidos'] = self::contarLeadsEsquecidos($id_admin);

		if($total > 0){
			$stats['conversao'] = round(($stats['matriculado'] / $total) * 100, 1).'%';
		}

		return $stats;
	}

	private static function contarLeadsEsquecidos($id_admin){

		$leads = EntityCrmLeads::getLeads(
			'id_admin = '.$id_admin.' AND status IN ("novo","em_atendimento")',
			null, null, 'id, data_cadastro'
		);

		$esquecidos = 0;
		$limite = time() - (48 * 3600);

		while ($row = $leads->fetch(PDO::FETCH_ASSOC)) {
			$ultima = EntityCrmHistorico::getHistorico(
				'lead_id = '.(int)$row['id'],
				'data_registro DESC',
				1,
				'data_registro'
			)->fetch(PDO::FETCH_ASSOC);

			$dataRef = $ultima['data_registro'] ?? $row['data_cadastro'];
			if(strtotime($dataRef) <= $limite){
				$esquecidos++;
			}
		}

		return $esquecidos;
	}

	private static function getComparativoMes($id_admin){

		$dataMatricula = Matriculas::campoDataMatricula('matriculas');

		$matAtual = (int)Matriculas::getMatriculas(
			'id_admin = '.$id_admin.' AND MONTH('.$dataMatricula.') = MONTH(CURDATE()) AND YEAR('.$dataMatricula.') = YEAR(CURDATE())',
			null, null, 'COUNT(*) as qtd'
		)->fetchObject()->qtd;

		$matAnterior = (int)Matriculas::getMatriculas(
			'id_admin = '.$id_admin.' AND MONTH('.$dataMatricula.') = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND YEAR('.$dataMatricula.') = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))',
			null, null, 'COUNT(*) as qtd'
		)->fetchObject()->qtd;

		$recAtual = (float)Caixa::getCaixa(
			'id_admin = "'.$id_admin.'" AND tipo_transacao = "Entrada" AND status = 1 AND MONTH(data_pagamento) = MONTH(CURDATE()) AND YEAR(data_pagamento) = YEAR(CURDATE())',
			null, null, 'SUM(valor_pago) as total'
		)->fetchObject()->total;

		$recAnterior = (float)Caixa::getCaixa(
			'id_admin = "'.$id_admin.'" AND tipo_transacao = "Entrada" AND status = 1 AND MONTH(data_pagamento) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND YEAR(data_pagamento) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))',
			null, null, 'SUM(valor_pago) as total'
		)->fetchObject()->total;

		return [
			'matriculas' => self::formatarComparativo($matAtual, $matAnterior, false),
			'receita'    => self::formatarComparativo($recAtual, $recAnterior, true)
		];
	}

	private static function formatarComparativo($atual, $anterior, $moeda){

		if($anterior > 0){
			$diff = round((($atual - $anterior) / $anterior) * 100, 1);
			$sinal = $diff >= 0 ? '+' : '';
			$texto = $sinal.$diff.'% vs mês anterior';
		} else {
			$texto = $atual > 0 ? 'Novo período' : 'Sem variação';
		}

		$valor = $moeda ? 'R$ '.NumeroHelper::moedaBr($atual) : (int)$atual;

		return $valor.' <small class="text-muted d-block">'.$texto.'</small>';
	}

	private static function resolverQuantidadeMeses($periodo){
		if($periodo === 'mes') return 1;
		if($periodo === 'trimestre') return 3;
		return 12;
	}

	private static function resolverIntervaloSql($periodo){
		if($periodo === 'mes'){
			return 'DATE_FORMAT(CURDATE(), "%Y-%m-01")';
		}
		if($periodo === 'trimestre'){
			return 'DATE_SUB(CURDATE(), INTERVAL 3 MONTH)';
		}
		return 'DATE_SUB(CURDATE(), INTERVAL 12 MONTH)';
	}

	private static function agruparPorMes($rows, $campoValor){

		$dados = [];
		foreach($rows as $row){
			$dados[$row['mes_ano']] = (float)$row[$campoValor];
		}
		return $dados;
	}

	private static function preencherMesesContinuos($dadosPorMes, $quantidadeMeses){

		$meses = [];
		$valores = [];

		for($i = $quantidadeMeses - 1; $i >= 0; $i--){
			$chave = date('Y-m', strtotime('-'.$i.' months'));
			$partes = explode('-', $chave);
			$label = self::$mesesNomes[$partes[1]] . ' ' . $partes[0];
			$meses[] = $label;
			$valores[] = $dadosPorMes[$chave] ?? 0;
		}

		return ['meses' => $meses, 'valores' => $valores];
	}

	private static function getTopCursos($id_admin){

		$resultado = Matriculas::getCursosMaisMatriculadosMes($id_admin, null, null, '5')->fetchAll(PDO::FETCH_ASSOC);

		$top_produtos = [];
		$top_porcentagem = [];
		$top_cores = [];
		$cores = ['#007bff', '#dc3545', '#ffc107', '#28a745', '#6610f2'];
		$totalGeral = array_sum(array_column($resultado, 'total_matriculas'));

		foreach($resultado as $index => $curso){
			$top_produtos[] = $curso['curso'];
			$top_porcentagem[] = $totalGeral > 0
				? round(($curso['total_matriculas'] / $totalGeral) * 100, 2)
				: 0;
			$top_cores[] = $cores[$index] ?? '#6c757d';
		}

		if(empty($top_produtos)){
			$top_produtos    = ['Sem matrículas'];
			$top_porcentagem = [100];
			$top_cores       = ['#e0e0e0'];
		}

		return [
			'top_produtos'    => $top_produtos,
			'top_porcentagem' => $top_porcentagem,
			'top_cores'       => $top_cores
		];
	}

	private static function getCrmStatusChart($id_admin){

		$labels = [];
		$valores = [];
		$cores = ['#0d6efd', '#ffc107', '#198754', '#6c757d'];

		foreach(self::$crmStatusOrdem as $index => $status){
			$labels[] = self::$crmStatusLabels[$status];
			$valores[] = (int)EntityCrmLeads::getLeads(
				'id_admin = '.$id_admin.' AND status = "'.$status.'"',
				null, null, 'COUNT(*) as qtd'
			)->fetch(PDO::FETCH_ASSOC)['qtd'];
		}

		return [
			'crm_status_labels'  => $labels,
			'crm_status_valores' => $valores,
			'crm_status_cores'   => $cores
		];
	}

	private static function getCrmOrigensChart($id_admin){

		$labels = [];
		$valores = [];

		$results = EntityCrmLeads::getLeads(
			'id_admin = '.$id_admin.' AND origem IS NOT NULL AND origem != ""',
			'total DESC', '8',
			'origem, COUNT(*) as total',
			null, 'origem'
		);

		while ($row = $results->fetch(PDO::FETCH_ASSOC)) {
			$labels[] = $row['origem'];
			$valores[] = (int)$row['total'];
		}

		if(empty($labels)){
			$labels = ['Sem dados'];
			$valores = [0];
		}

		return [
			'origem_labels'  => $labels,
			'origem_valores' => $valores
		];
	}

	private static function getTopVendedores($id_admin){

		$dataInicio = date('Y-m-01');
		$dataFim = date('Y-m-d', strtotime($dataInicio.' +1 month'));

		$innerJoin = 'INNER JOIN usuarios ON usuarios.id = crm_leads.usuario_id';

		$results = EntityCrmLeads::getLeads(
			'crm_leads.id_admin = '.$id_admin.'
			AND crm_leads.status = "matriculado"
			AND crm_leads.usuario_id IS NOT NULL
			AND crm_leads.data_cadastro >= "'.$dataInicio.'"
			AND crm_leads.data_cadastro < "'.$dataFim.'"',
			'total DESC', '3',
			'usuarios.id, usuarios.nome, usuarios.email, COUNT(crm_leads.id) AS total',
			$innerJoin,
			'usuarios.id, usuarios.nome, usuarios.email'
		);

		$vendedores = [];
		$posicao = 1;

		while ($row = $results->fetch(PDO::FETCH_ASSOC)) {
			$vendedores[] = [
				'posicao'  => $posicao++,
				'nome'     => $row['nome'],
				'email'    => $row['email'],
				'total'    => (int)$row['total']
			];
		}

		return $vendedores;
	}

}

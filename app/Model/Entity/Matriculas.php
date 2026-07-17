<?php

namespace App\Model\Entity;
use App\Model\Db\Database;
use App\Model\Entity\Caixa;
use App\Model\Entity\User as EntityUser;
use \App\Model\Entity\Responsaveis as EntityRes;
use App\Common\Gateways\BancoInter\Authentication;
use App\Common\Gateways\BancoInter\Pix;

class Matriculas{

	public 
	$id,
	$id_aluno,
	$id_admin,
	$id_responsavel,
	$id_trilha,
	$carga_horaria,
	$modulos,
	$horarios,
	$dia_semana,
	$aulas_semanais,
	$valor,
	$qtd_parcelas,
	$dia_vencimento,
	$primeiro_mes,
	$primeiro_ano,
	$inicio,
	$fim,
	$matriculado_em,
	$tipo_parcelamento,
	$desconto_pontualidade = 0,
	$status = 0;

	

	//ENVIA A MENSAGEM PARA O BANCO
	public function matricular(){

		//INSERIR OSDADOS PARA O BANCO DE DADOS
		$obDatabase = new Database('matriculas');
		$this->id = $obDatabase->insert([

			'id_aluno' => $this->id_aluno,
			'id_admin' => $this->id_admin,
			'id_responsavel' => $this->id_responsavel,
			'id_trilha' => $this->id_trilha,
			'carga_horaria' => $this->carga_horaria,
			'modulos' => $this->modulos,
			'horarios' => $this->horarios,
			'dia_semana' => $this->dia_semana,
			'aulas_semanais' => $this->aulas_semanais,
			'valor' => $this->valor,
			'qtd_parcelas' => $this->qtd_parcelas,
			'dia_vencimento' => $this->dia_vencimento,
			'primeiro_mes' => $this->primeiro_mes,
			'primeiro_ano' => $this->primeiro_ano,
			'inicio' => $this->inicio,
			'fim' => $this->fim,
			'matriculado_em' => $this->inicio ?: date('Y-m-d'),
			'tipo_parcelamento' => $this->tipo_parcelamento,
			'desconto_pontualidade' => $this->desconto_pontualidade,
			'status' => $this->status,

		]);

		
		if(!$this->id_responsavel){
			$idPagador = $this->id_aluno;
			$dadosPagador = (array)EntityUser::getUserById($idPagador);
		} else {
			$idPagador = $this->id_responsavel;
			$dadosPagador = (array)EntityRes::getResById($idPagador);
		}

		$dadosAluno = (array)EntityUser::getUserById($this->id_aluno);
		

		if($this->desconto_pontualidade){
			$desconto = $this->valor*10/100;
		} else {
			$desconto = 0;
		}

		$pixCopiaECola='';
		$txtId='';


		$accessToken=null;
		if($this->tipo_parcelamento == 'Carnê com Pix'){

			$accessToken = Authentication::getAccessToken();

		}

		//$accessToken = '09c35a91-74a7-48a6-98c2-4c3bae5904ab';


		$count = 1;
		$ano_vence = $this->primeiro_ano;
		$mes_vence = $this->primeiro_mes - 1;

		while ($count <= $this->qtd_parcelas) {

			if ($mes_vence == 12) { 
				$ano_vence = $ano_vence + 1;
				$mes_vence = 1;
			} else {
				$mes_vence++;
			}

			$vencimento = date("Y-m-d", strtotime($ano_vence.'/'.$mes_vence.'/'.$this->dia_vencimento));
			$descricao = 'Cód '.$this->id.' '.$dadosAluno['nome'].' parc '.$count.'/'.$this->qtd_parcelas;

			$cpfPagador = preg_replace('/\D/', '', (string)($dadosPagador['cpf'] ?? ''));


			if($this->tipo_parcelamento == 'Carnê com Pix'){

				if ($cpfPagador === '' || empty($dadosPagador['nome'])) {
					$pixCopiaECola = '';
					$txtId = '';
				} else {
				$txtId = bin2hex(random_bytes(16));
				$chavePix = 'ea01c8b0-dc2f-40b9-961a-98f08a330a42';


			        // Dados do Pix
				$data = [
					"calendario" => [
						"dataDeVencimento" => $vencimento,
						"validadeAposVencimento" => 30
					],
					"devedor" => [
						"cpf" => $cpfPagador,
						"nome" => $dadosPagador['nome']
					],
					"valor" => [
						"original" => number_format($this->valor, 2, '.', ''),
						"multa" => [
							"modalidade" => "2",
							"valorPerc" => "10.00"
						],
						"juros" => [
							"modalidade" => "2",
							"valorPerc" => "2.00"
						],
						"desconto" => [
							"modalidade" => "2",
							"descontoDataFixa" => [
								[
									"data" => $vencimento,
									"valorPerc" => "0.00"
								]
							]
						]
					],
					"chave" => $chavePix,
					"solicitacaoPagador" => "Mensalidade Curso"
				];


				$pixCopiaECola = Pix::pixComVencimento($accessToken,$data, $txtId);
				}
			}

			

			//NOVA INSTANCIA
			$obCaixa = new Caixa;
			$obCaixa->id_admin = $this->id_admin;
			$obCaixa->descricao = $descricao;
			$obCaixa->tipo_transacao = 'Entrada';
			$obCaixa->valor = $this->valor;
			$obCaixa->vencimento = $vencimento;
			$obCaixa->referencia = 'Mtrcicula Curso';
			$obCaixa->id_ref = $this->id;
			$obCaixa->status = 'Em aberto';
			$obCaixa->tipo_pagamento = '';
			$obCaixa->valor_pago = 0;
			$obCaixa->txt_id = $txtId;
			$obCaixa->pix_copia_cola = $pixCopiaECola;
			$obCaixa->lancarMovimentacao();

			$count++;
		}

		return true;
	}

	//ATUALIZA A MENSAGEM NO BANCO
	public function atualizar(){

		//ATUALIZA OS DADOS PARA O BANCO DE DADOS
		return (new Database('matriculas'))->update('id = '.$this->id,[

			'id_aluno' => $this->id_aluno,
			'id_responsavel' => $this->id_responsavel,
			'id_trilha' => $this->id_trilha,
			'carga_horaria' => $this->carga_horaria,
			'modulos' => $this->modulos,
			'horarios' => $this->horarios,
			'dia_semana' => $this->dia_semana,
			'aulas_semanais' => $this->aulas_semanais,
			'valor' => $this->valor,
			'qtd_parcelas' => $this->qtd_parcelas,
			'dia_vencimento' => $this->dia_vencimento,
			'primeiro_mes' => $this->primeiro_mes,
			'primeiro_ano' => $this->primeiro_ano,
			'inicio' => $this->inicio,
			'fim' => $this->fim,
			'tipo_parcelamento' => $this->tipo_parcelamento,
			'desconto_pontualidade' => $this->desconto_pontualidade,
			'status' => $this->status,

		]);

	}

	//CANCELA A MATRICULA NO BANCO DE DADOS
	public function cancelar(){

		return (new Database('matriculas'))->update('id = '.$this->id,[

			'status' => 3

		]);

	}

	//RETORNA UM DEPOIMENTO COM BASE NO ID
	public static function getMatriculaById($id){

		return self::getMatriculas('id = '.$id)->fetchObject(self::class);

	}

	//RETORNA DEPOIMENTOS
	public static function getMatriculas($where=null,$order=null,$limit=null,$fields='*',$innerJoin=null,$group=null){

		return (new Database('matriculas'))->select($where,$order,$limit,$fields,$innerJoin,$group);
	}


	// Campo de data usado nos relatórios (fallback para inicio quando matriculado_em inválido)
	public static function campoDataMatricula($alias = 'matriculas'){
		return 'COALESCE(NULLIF('.$alias.'.matriculado_em, "0000-00-00"), '.$alias.'.inicio)';
	}

	public static function getCursosMaisMatriculadosMes(
		int $id_admin,
		?int $mes = null,
		?int $ano = null,
		$limit = null
	){
    // Mês e ano atual por padrão
		$mes = $mes ?? date('m');
		$ano = $ano ?? date('Y');

    // Intervalo de datas (performático)
		$dataInicio = $ano . '-' . str_pad($mes, 2, '0', STR_PAD_LEFT) . '-01';
		$dataFim = date('Y-m-d', strtotime($dataInicio . ' +1 month'));

		$innerJoin = '
		INNER JOIN trilhas 
		ON trilhas.id = matriculas.id_trilha
		';

		$fields = '
		trilhas.id,
		trilhas.nome AS curso,
		COUNT(matriculas.id) AS total_matriculas
		';

		$dataCampo = self::campoDataMatricula('matriculas');

		$where = '
		matriculas.id_admin = ' . (int)$id_admin . '
		AND ' . $dataCampo . ' >= "' . $dataInicio . '"
		AND ' . $dataCampo . ' < "' . $dataFim . '"
		';

		$group = 'trilhas.id';
		$order = 'total_matriculas DESC';
		$limit = $limit ?: null;


		return (new Database('matriculas'))->select(
			$where,
			$order,
			$limit,
			$fields,
			$innerJoin,
			$group
		);
	}




}
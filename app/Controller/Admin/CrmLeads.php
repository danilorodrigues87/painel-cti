<?php

namespace App\Controller\Admin;

use \App\Utils\View;
use \App\Model\Entity\CrmLeads as EntityCrmLeads;
use \App\Model\Entity\CrmHistorico as EntityCrmHistorico;
use \App\Model\Entity\User as EntityUser;
use \App\Common\Helpers\DateTimeHelper;
use \App\Common\Helpers\NumeroHelper;
use \App\Common\Helpers\PlanilhaHelper;
use \App\Model\Db\Pagination;

class CrmLeads extends Page{

	private static $statusPermitidos = [
		'novo',
		'em_atendimento',
		'matriculado',
		'perdido'
	];

	private static $labelsStatus = [
		'novo'           => 'Novo',
		'em_atendimento' => 'Em atendimento',
		'matriculado'    => 'Matriculado',
		'perdido'        => 'Perdido'
	];

	private static $motivosPerda = [
		'Preço Alto',
		'Horário Incompatível',
		'Não Respondeu',
		'Outros'
	];

	private static $origensPermitidas = [
		'Balcão',
		'Macrocaptação',
		'Instagram',
		'Facebook',
		'Site',
		'Google',
		'Indicação',
		'Panfleto',
		'Outros'
	];

	private static $visibilidadesPermitidas = [
		'publico',
		'privado'
	];

	private static $niveisAdministrador = [
		'Diretor'
	];

	public static function index($request){
		$content = View::render('admin/modules/crm/index',[]);
		return parent::getPanel('Leads',$content,'CRM');
	}

	public static function getInfo($request){

		$dadosUser = parent::getIdAdmin();
		$postVars  = $request->getPostVars();
		$paginaAtual = $postVars['page'] ?? 1;
		$limite = (int)(getenv('PAGINATION_LIMIT') ?: 5);

		$whereBase = self::montarWhereListagem($dadosUser);
		$whereFiltro = $whereBase.self::montarFiltrosBusca($postVars);

		$colunas = [];
		$totais  = [];

		foreach(self::$statusPermitidos as $status){
			$colunas[$status] = [];
			$totais[$status]  = 0;
		}

		$quantidadeTotal = (int)EntityCrmLeads::getLeads($whereFiltro, null, null, 'COUNT(*) as qtd')->fetchObject()->qtd;
		$obPagination = new Pagination($quantidadeTotal, $paginaAtual, $limite);

		$results = EntityCrmLeads::getLeads(
			$whereFiltro,
			'data_cadastro DESC',
			$obPagination->getLimit()
		);

		while ($obLead = $results->fetchObject(EntityCrmLeads::class)) {
			$status = in_array($obLead->status, self::$statusPermitidos) ? $obLead->status : 'novo';

			$valorEstimado = (float)($obLead->valor_estimado ?? 0);
			$totais[$status] += $valorEstimado;

			$ultimaAtualizacao = self::getUltimaAtualizacao($obLead->id, $obLead->data_cadastro);
			$horasSemContato   = self::horasDesde($ultimaAtualizacao);

			$colunas[$status][] = [
				'id'                 => $obLead->id,
				'nome'               => $obLead->nome,
				'whatsapp'           => $obLead->whatsapp,
				'curso_interesse'    => $obLead->curso_interesse,
				'valor_estimado'     => $valorEstimado,
				'valor_estimado_br'  => $valorEstimado > 0 ? NumeroHelper::moedaBr($valorEstimado) : '',
				'data_cadastro'      => DateTimeHelper::databr($obLead->data_cadastro),
				'status_wa'          => $obLead->status_wa,
				'motivo_perda'       => $obLead->motivo_perda,
				'esquecido'          => $horasSemContato >= 48,
				'ultima_atualizacao' => $ultimaAtualizacao
			];
		}

		$cursos = self::getCursosDisponiveis($whereBase);

		$totaisFormatados = [];
		foreach($totais as $status => $valor){
			$totaisFormatados[$status] = NumeroHelper::moedaBr($valor);
		}

		return json_encode([
			'colunas'    => $colunas,
			'totais'     => $totaisFormatados,
			'cursos'     => $cursos,
			'pagination' => parent::getPagination($request, $obPagination)
		]);
	}

	public static function cadastrar($request){

		$postVars = $request->getPostVars();
		$resposta = [];

		$nome = trim($postVars['nome'] ?? '');
		$whatsapp = preg_replace('/\D/','',$postVars['whatsapp'] ?? '');
		$curso = trim($postVars['curso_interesse'] ?? '');
		$valorEstimado = self::parseValor($postVars['valor_estimado'] ?? '');
		$dadosCadastrais = self::extrairDadosCadastrais($postVars);

		if($nome == ''){
			$resposta['erro'] = 'Informe o nome do lead.';
			return json_encode($resposta);
		}

		if(strlen($whatsapp) < 10){
			$resposta['erro'] = 'Informe um WhatsApp válido (apenas números).';
			return json_encode($resposta);
		}

		if(!empty($dadosCadastrais['erro'])){
			$resposta['erro'] = $dadosCadastrais['erro'];
			return json_encode($resposta);
		}

		$visibilidade = $postVars['visibilidade'] ?? 'publico';
		if(!in_array($visibilidade, self::$visibilidadesPermitidas)){
			$resposta['erro'] = 'Visibilidade do lead inválida.';
			return json_encode($resposta);
		}

		$dadosUser = parent::getIdAdmin();
		$id_admin  = $dadosUser['usuario']['id_admin'];
		$id_usuario = $dadosUser['usuario']['id'];

		$obLead = new EntityCrmLeads;
		$obLead->id_admin        = $id_admin;
		$obLead->usuario_id      = $id_usuario;
		$obLead->visibilidade    = $visibilidade;
		$obLead->nome            = $nome;
		$obLead->whatsapp        = $whatsapp;
		$obLead->curso_interesse = $curso;
		$obLead->valor_estimado  = $valorEstimado > 0 ? $valorEstimado : null;
		$obLead->origem          = $dadosCadastrais['origem'];
		$obLead->email           = $dadosCadastrais['email'];
		$obLead->bairro          = $dadosCadastrais['bairro'];
		$obLead->cidade          = $dadosCadastrais['cidade'];
		$obLead->idade           = $dadosCadastrais['idade'];
		$obLead->responsavel_nome = $dadosCadastrais['responsavel_nome'];
		$obLead->status          = 'novo';
		$obLead->status_wa       = 'pendente';
		$obLead->cadastrar();

		$msgHistorico = 'Lead cadastrado no CRM com status "'.self::$labelsStatus['novo'].'".';
		if($valorEstimado > 0){
			$msgHistorico .= ' Valor estimado: R$ '.NumeroHelper::moedaBr($valorEstimado).'.';
		}
		if(!empty($dadosCadastrais['origem'])){
			$msgHistorico .= ' Origem: '.$dadosCadastrais['origem'].'.';
		}
		$msgHistorico .= ' Visibilidade: '.($visibilidade === 'privado' ? 'Privado' : 'Público').'.';

		self::registrarHistorico($obLead->id, $id_usuario, 'lead_cadastrado', $msgHistorico);

		self::dispararMensagemWhatsApp($obLead, null, 'novo');

		$resposta['sucesso'] = true;
		return json_encode($resposta);
	}

	public static function importar($request){

		$postVars = $request->getPostVars();
		$fileVars = $request->getFileVars();
		$resposta = [];

		$visibilidade = $postVars['visibilidade'] ?? 'publico';

		if(!in_array($visibilidade, self::$visibilidadesPermitidas)){
			$resposta['erro'] = 'Visibilidade inválida.';
			return json_encode($resposta);
		}

		$arquivo = $fileVars['planilha'] ?? null;

		if(!$arquivo || ($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK){
			$resposta['erro'] = 'Selecione um arquivo CSV ou XLSX válido.';
			return json_encode($resposta);
		}

		$nomeArquivo = $arquivo['name'] ?? '';
		$extensao = strtolower(pathinfo($nomeArquivo, PATHINFO_EXTENSION));

		if(!in_array($extensao, ['csv', 'xlsx'])){
			$resposta['erro'] = 'Formato não suportado. Envie arquivos .csv ou .xlsx.';
			return json_encode($resposta);
		}

		$tamanhoMaximo = 5 * 1024 * 1024;

		if(($arquivo['size'] ?? 0) > $tamanhoMaximo){
			$resposta['erro'] = 'Arquivo muito grande. O limite é 5MB.';
			return json_encode($resposta);
		}

		$dadosUser  = parent::getIdAdmin();
		$id_admin   = $dadosUser['usuario']['id_admin'];
		$id_usuario = $dadosUser['usuario']['id'];

		try{
			$linhas = PlanilhaHelper::lerArquivo($arquivo['tmp_name'], $extensao);
		}catch(\Throwable $e){
			$resposta['erro'] = 'Erro ao ler a planilha: '.$e->getMessage();
			return json_encode($resposta);
		}

		if(count($linhas) === 0){
			$resposta['erro'] = 'A planilha não contém linhas para importar.';
			return json_encode($resposta);
		}

		if(PlanilhaHelper::pareceCabecalho($linhas[0])){
			array_shift($linhas);
		}

		if(count($linhas) === 0){
			$resposta['erro'] = 'A planilha possui apenas o cabeçalho, sem dados.';
			return json_encode($resposta);
		}

		$importados = 0;
		$ignorados   = 0;
		$erros       = [];

		foreach($linhas as $indice => $linha){

			$numeroLinha = $indice + 2;
			$dadosLinha  = self::extrairDadosPlanilha($linha);

			if(!empty($dadosLinha['erro'])){
				$ignorados++;
				$erros[] = 'Linha '.$numeroLinha.': '.$dadosLinha['erro'];
				continue;
			}

			$obLead = new EntityCrmLeads;
			$obLead->id_admin         = $id_admin;
			$obLead->usuario_id       = $id_usuario;
			$obLead->visibilidade     = $visibilidade;
			$obLead->nome             = $dadosLinha['nome'];
			$obLead->whatsapp         = $dadosLinha['whatsapp'];
			$obLead->email            = $dadosLinha['email'];
			$obLead->curso_interesse  = $dadosLinha['curso_interesse'];
			$obLead->bairro           = $dadosLinha['bairro'];
			$obLead->cidade           = $dadosLinha['cidade'];
			$obLead->status           = 'novo';
			$obLead->status_wa        = 'pendente';
			$obLead->cadastrar();

			self::registrarHistorico(
				$obLead->id,
				$id_usuario,
				'lead_importado',
				'Lead importado via planilha com visibilidade "'.($visibilidade === 'privado' ? 'Privado' : 'Público').'".'
			);

			$importados++;
		}

		if($importados === 0){
			$resposta['erro'] = 'Nenhum lead foi importado. Verifique o formato das linhas.';
			if(count($erros) > 0){
				$resposta['detalhes'] = array_slice($erros, 0, 5);
			}
			return json_encode($resposta);
		}

		$resposta['sucesso']    = true;
		$resposta['importados'] = $importados;
		$resposta['ignorados']  = $ignorados;

		if(count($erros) > 0){
			$resposta['avisos'] = array_slice($erros, 0, 10);
		}

		return json_encode($resposta);
	}

	public static function atualizarStatus($request){

		$postVars = $request->getPostVars();
		$resposta = [];

		$id          = isset($postVars['id']) ? (int)$postVars['id'] : 0;
		$status      = $postVars['status'] ?? '';
		$motivoPerda = trim($postVars['motivo_perda'] ?? '');

		if($id <= 0){
			$resposta['erro'] = 'Lead inválido.';
			return json_encode($resposta);
		}

		if(!in_array($status, self::$statusPermitidos)){
			$resposta['erro'] = 'Status inválido.';
			return json_encode($resposta);
		}

		if($status === 'perdido' && !in_array($motivoPerda, self::$motivosPerda)){
			$resposta['erro'] = 'Informe o motivo da perda.';
			$resposta['requer_motivo'] = true;
			return json_encode($resposta);
		}

		$dadosUser = parent::getIdAdmin();
		$id_admin  = $dadosUser['usuario']['id_admin'];
		$id_usuario = $dadosUser['usuario']['id'];

		$obLead = EntityCrmLeads::getLeads(
			'id = '.$id.' AND id_admin = '.(int)$id_admin
		)->fetchObject(EntityCrmLeads::class);

		if(!$obLead instanceof EntityCrmLeads){
			$resposta['erro'] = 'Lead não encontrado.';
			return json_encode($resposta);
		}

		if(!self::podeAcessarLead($obLead, $dadosUser)){
			$resposta['erro'] = 'Você não tem permissão para alterar este lead.';
			return json_encode($resposta);
		}

		$statusAnterior = $obLead->status;

		if($statusAnterior === $status){
			$resposta['sucesso'] = true;
			return json_encode($resposta);
		}

		if($status === 'em_atendimento'){
			$obLead->usuario_id = $id_usuario;
		}

		$obLead->status = $status;
		$obLead->motivo_perda = ($status === 'perdido') ? $motivoPerda : null;
		$obLead->atualizarStatus();

		$labelAnterior = self::$labelsStatus[$statusAnterior] ?? $statusAnterior;
		$labelNovo     = self::$labelsStatus[$status] ?? $status;

		$msgHistorico = 'Status alterado de "'.$labelAnterior.'" para "'.$labelNovo.'".';
		if($status === 'perdido'){
			$msgHistorico .= ' Motivo da perda: '.$motivoPerda.'.';
		}
		if($status === 'em_atendimento'){
			$msgHistorico .= ' Lead assumido para atendimento exclusivo.';
		}

		self::registrarHistorico($obLead->id, $id_usuario, 'status_alterado', $msgHistorico);

		self::dispararMensagemWhatsApp($obLead, $statusAnterior, $status);

		$resposta['sucesso'] = true;
		return json_encode($resposta);
	}

	public static function getDetalhes($request){

		$postVars = $request->getPostVars();
		$resposta = [];

		$id = isset($postVars['id']) ? (int)$postVars['id'] : 0;

		if($id <= 0){
			$resposta['erro'] = 'Lead inválido.';
			return json_encode($resposta);
		}

		$dadosUser = parent::getIdAdmin();
		$id_admin  = $dadosUser['usuario']['id_admin'];

		$obLead = EntityCrmLeads::getLeads(
			'id = '.$id.' AND id_admin = '.(int)$id_admin
		)->fetchObject(EntityCrmLeads::class);

		if(!$obLead instanceof EntityCrmLeads){
			$resposta['erro'] = 'Lead não encontrado.';
			return json_encode($resposta);
		}

		if(!self::podeAcessarLead($obLead, $dadosUser)){
			$resposta['erro'] = 'Você não tem permissão para visualizar este lead.';
			return json_encode($resposta);
		}

		$resposta['lead'] = self::formatarLeadDetalhes($obLead);

		$resposta['whatsapp_link'] = self::montarLinkWhatsApp($obLead->whatsapp);
		$resposta['timeline']      = self::montarTimeline($obLead->id);

		return json_encode($resposta);
	}

	public static function salvarComentario($request){

		$postVars = $request->getPostVars();
		$resposta = [];

		$id         = isset($postVars['id']) ? (int)$postVars['id'] : 0;
		$observacao = trim($postVars['observacao'] ?? '');

		if($id <= 0){
			$resposta['erro'] = 'Lead inválido.';
			return json_encode($resposta);
		}

		if($observacao == ''){
			$resposta['erro'] = 'Digite uma observação antes de salvar.';
			return json_encode($resposta);
		}

		$dadosUser = parent::getIdAdmin();
		$id_admin  = $dadosUser['usuario']['id_admin'];
		$id_usuario = $dadosUser['usuario']['id'];

		$obLead = EntityCrmLeads::getLeads(
			'id = '.$id.' AND id_admin = '.(int)$id_admin
		)->fetchObject(EntityCrmLeads::class);

		if(!$obLead instanceof EntityCrmLeads){
			$resposta['erro'] = 'Lead não encontrado.';
			return json_encode($resposta);
		}

		if(!self::podeAcessarLead($obLead, $dadosUser)){
			$resposta['erro'] = 'Você não tem permissão para comentar neste lead.';
			return json_encode($resposta);
		}

		self::registrarHistorico($obLead->id, $id_usuario, 'comentario', $observacao);

		$resposta['sucesso']  = true;
		$resposta['timeline'] = self::montarTimeline($obLead->id);

		return json_encode($resposta);
	}

	public static function atualizarDados($request){

		$postVars = $request->getPostVars();
		$resposta = [];

		$id = isset($postVars['id']) ? (int)$postVars['id'] : 0;

		if($id <= 0){
			$resposta['erro'] = 'Lead inválido.';
			return json_encode($resposta);
		}

		$nome = trim($postVars['nome'] ?? '');
		$whatsapp = preg_replace('/\D/','',$postVars['whatsapp'] ?? '');
		$curso = trim($postVars['curso_interesse'] ?? '');
		$valorEstimado = self::parseValor($postVars['valor_estimado'] ?? '');
		$dadosCadastrais = self::extrairDadosCadastrais($postVars);

		if($nome == ''){
			$resposta['erro'] = 'Informe o nome do lead.';
			return json_encode($resposta);
		}

		if(strlen($whatsapp) < 10){
			$resposta['erro'] = 'Informe um WhatsApp válido (apenas números).';
			return json_encode($resposta);
		}

		if(!empty($dadosCadastrais['erro'])){
			$resposta['erro'] = $dadosCadastrais['erro'];
			return json_encode($resposta);
		}

		$dadosUser = parent::getIdAdmin();
		$id_admin  = $dadosUser['usuario']['id_admin'];
		$id_usuario = $dadosUser['usuario']['id'];

		$obLead = EntityCrmLeads::getLeads(
			'id = '.$id.' AND id_admin = '.(int)$id_admin
		)->fetchObject(EntityCrmLeads::class);

		if(!$obLead instanceof EntityCrmLeads){
			$resposta['erro'] = 'Lead não encontrado.';
			return json_encode($resposta);
		}

		if(!self::podeAcessarLead($obLead, $dadosUser)){
			$resposta['erro'] = 'Você não tem permissão para editar este lead.';
			return json_encode($resposta);
		}

		$obLead->nome             = $nome;
		$obLead->whatsapp         = $whatsapp;
		$obLead->curso_interesse  = $curso;
		$obLead->valor_estimado   = $valorEstimado > 0 ? $valorEstimado : null;
		$obLead->origem           = $dadosCadastrais['origem'];
		$obLead->email            = $dadosCadastrais['email'];
		$obLead->bairro           = $dadosCadastrais['bairro'];
		$obLead->cidade           = $dadosCadastrais['cidade'];
		$obLead->idade            = $dadosCadastrais['idade'];
		$obLead->responsavel_nome = $dadosCadastrais['responsavel_nome'];
		$obLead->atualizarDados();

		self::registrarHistorico(
			$obLead->id,
			$id_usuario,
			'dados_atualizados',
			'Dados cadastrais do lead foram atualizados.'
		);

		$resposta['sucesso'] = true;
		$resposta['lead']    = self::formatarLeadDetalhes($obLead);
		$resposta['whatsapp_link'] = self::montarLinkWhatsApp($obLead->whatsapp);
		$resposta['timeline'] = self::montarTimeline($obLead->id);

		return json_encode($resposta);
	}

	private static function extrairDadosCadastrais($postVars){

		$origem = trim($postVars['origem'] ?? '');
		$email  = trim($postVars['email'] ?? '');
		$bairro = trim($postVars['bairro'] ?? '');
		$cidade = trim($postVars['cidade'] ?? '');
		$idade  = trim($postVars['idade'] ?? '');
		$responsavel = trim($postVars['responsavel_nome'] ?? '');

		if($origem !== '' && !in_array($origem, self::$origensPermitidas)){
			return ['erro' => 'Origem do lead inválida.'];
		}

		if($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)){
			return ['erro' => 'Informe um e-mail válido.'];
		}

		$idadeInt = null;
		if($idade !== ''){
			$idadeInt = (int)$idade;
			if($idadeInt < 1 || $idadeInt > 120){
				return ['erro' => 'Informe uma idade válida.'];
			}
		}

		return [
			'origem'           => $origem !== '' ? $origem : null,
			'email'            => $email !== '' ? $email : null,
			'bairro'           => $bairro !== '' ? $bairro : null,
			'cidade'           => $cidade !== '' ? $cidade : null,
			'idade'            => $idadeInt,
			'responsavel_nome' => $responsavel !== '' ? $responsavel : null
		];
	}

	private static function formatarLeadDetalhes($obLead){

		$valorEstimado = (float)($obLead->valor_estimado ?? 0);

		return [
			'id'               => $obLead->id,
			'nome'             => $obLead->nome,
			'whatsapp'         => $obLead->whatsapp,
			'curso_interesse'  => $obLead->curso_interesse ?? '',
			'origem'           => $obLead->origem ?? '',
			'email'            => $obLead->email ?? '',
			'bairro'           => $obLead->bairro ?? '',
			'cidade'           => $obLead->cidade ?? '',
			'idade'            => $obLead->idade ?? '',
			'responsavel_nome' => $obLead->responsavel_nome ?? '',
			'valor_estimado'   => $valorEstimado > 0 ? NumeroHelper::moedaBr($valorEstimado) : '',
			'valor_estimado_raw' => $valorEstimado > 0 ? $valorEstimado : '',
			'motivo_perda'     => $obLead->motivo_perda ?: '-',
			'status'           => $obLead->status,
			'status_label'     => self::$labelsStatus[$obLead->status] ?? $obLead->status,
			'visibilidade'     => $obLead->visibilidade ?? 'publico',
			'visibilidade_label' => ($obLead->visibilidade ?? 'publico') === 'privado' ? 'Privado' : 'Público',
			'data_cadastro'    => DateTimeHelper::databr($obLead->data_cadastro),
			'status_wa'        => $obLead->status_wa
		];
	}

	private static function parseValor($valorBruto){
		if($valorBruto === '' || $valorBruto === null){
			return 0;
		}
		return (float) NumeroHelper::removerFormatacaoNumero($valorBruto);
	}

	private static function isAdministrador($dadosUser){
		$nivel = $dadosUser['usuario']['nivel'] ?? '';
		return in_array($nivel, self::$niveisAdministrador);
	}

	private static function montarFiltrosBusca($postVars){

		$filtros = '';

		$nome = trim($postVars['filtro_nome'] ?? '');
		if($nome !== ''){
			$filtros .= ' AND nome LIKE "%'.addslashes($nome).'%"';
		}

		$curso = trim($postVars['filtro_curso'] ?? '');
		if($curso !== ''){
			$filtros .= ' AND LOWER(curso_interesse) = LOWER("'.addslashes($curso).'")';
		}

		return $filtros;
	}

	private static function getCursosDisponiveis($where){

		$cursos = [];

		$results = EntityCrmLeads::getLeads(
			$where.' AND curso_interesse IS NOT NULL AND curso_interesse != ""',
			'curso_interesse ASC',
			null,
			'DISTINCT curso_interesse'
		);

		while ($obLead = $results->fetchObject(EntityCrmLeads::class)) {
			if(!empty($obLead->curso_interesse)){
				$cursos[] = $obLead->curso_interesse;
			}
		}

		return $cursos;
	}

	private static function montarWhereListagem($dadosUser){

		$id_admin   = (int)$dadosUser['usuario']['id_admin'];
		$id_usuario = (int)$dadosUser['usuario']['id'];

		$where = 'id_admin = '.$id_admin;

		if(!self::isAdministrador($dadosUser)){
			$where .= ' AND (
				usuario_id = '.$id_usuario.'
				OR (
					visibilidade = "publico"
					AND NOT (
						status = "em_atendimento"
						AND usuario_id IS NOT NULL
						AND usuario_id != '.$id_usuario.'
					)
				)
			)';
		}

		return $where;
	}

	private static function podeAcessarLead($obLead, $dadosUser){

		if(self::isAdministrador($dadosUser)){
			return true;
		}

		$id_usuario   = (int)$dadosUser['usuario']['id'];
		$usuarioLead  = (int)($obLead->usuario_id ?? 0);
		$visibilidade = $obLead->visibilidade ?? 'publico';

		if($usuarioLead === $id_usuario){
			return true;
		}

		if($visibilidade !== 'publico'){
			return false;
		}

		if(
			$obLead->status === 'em_atendimento'
			&& $usuarioLead > 0
			&& $usuarioLead !== $id_usuario
		){
			return false;
		}

		return true;
	}

	private static function extrairDadosPlanilha($linha){

		$nome           = trim($linha[0] ?? '');
		$whatsappBruto  = trim($linha[1] ?? '');
		$email          = trim($linha[2] ?? '');
		$cursoInteresse = trim($linha[3] ?? '');
		$bairro         = trim($linha[4] ?? '');
		$cidade         = trim($linha[5] ?? '');

		$whatsapp = preg_replace('/\D/', '', $whatsappBruto);

		if($nome === ''){
			return ['erro' => 'Nome não informado.'];
		}

		if(strlen($whatsapp) < 10){
			return ['erro' => 'WhatsApp inválido (mínimo 10 dígitos com DDD).'];
		}

		$email = self::normalizarEmailPlanilha($email);

		// Planilha sem coluna de e-mail: a 3ª coluna pode ser o curso
		if($email !== '' && strpos($email, '@') === false){
			if($cursoInteresse === ''){
				$cursoInteresse = $email;
			}
			$email = '';
		}

		if($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)){
			return ['erro' => 'E-mail inválido.'];
		}

		return [
			'nome'            => $nome,
			'whatsapp'        => $whatsapp,
			'email'           => $email !== '' ? $email : null,
			'curso_interesse' => $cursoInteresse !== '' ? $cursoInteresse : null,
			'bairro'          => $bairro !== '' ? $bairro : null,
			'cidade'          => $cidade !== '' ? $cidade : null
		];
	}

	private static function normalizarEmailPlanilha($email){

		$email = trim($email);

		if($email === ''){
			return '';
		}

		$vazio = ['-', '--', 'n/a', 'na', 'sem email', 'sem e-mail', 's/email', 'null', 'nenhum'];

		if(in_array(strtolower($email), $vazio)){
			return '';
		}

		return $email;
	}

	private static function getUltimaAtualizacao($leadId, $dataCadastro){

		$obHist = EntityCrmHistorico::getHistorico(
			'lead_id = '.(int)$leadId,
			'data_registro DESC',
			1
		)->fetchObject(EntityCrmHistorico::class);

		if($obHist instanceof EntityCrmHistorico){
			return $obHist->data_registro;
		}

		return $dataCadastro;
	}

	private static function horasDesde($dataHora){
		$timestamp = strtotime($dataHora);
		if(!$timestamp){
			return 0;
		}
		return (time() - $timestamp) / 3600;
	}

	private static function registrarHistorico($leadId, $usuarioId, $acao, $observacao){

		$obHistorico = new EntityCrmHistorico;
		$obHistorico->lead_id    = $leadId;
		$obHistorico->usuario_id = $usuarioId;
		$obHistorico->acao       = $acao;
		$obHistorico->observacao = $observacao;
		$obHistorico->cadastrar();
	}

	private static function montarLinkWhatsApp($whatsapp){

		$numero = preg_replace('/\D/','',$whatsapp);

		if(strlen($numero) <= 11){
			$numero = '55'.$numero;
		}

		return 'https://wa.me/'.$numero;
	}

	private static function montarTimeline($leadId){

		$results = EntityCrmHistorico::getHistorico(
			'lead_id = '.(int)$leadId,
			'data_registro DESC'
		);

		$itens = '';

		while ($obHist = $results->fetchObject(EntityCrmHistorico::class)) {

			$obUser = EntityUser::getUserById($obHist->usuario_id);
			$nomeUsuario = ($obUser instanceof EntityUser) ? $obUser->nome : 'Sistema';

			$icone  = 'fa-circle-info text-primary';
			$titulo = 'Registro';

			if($obHist->acao == 'status_alterado'){
				$icone  = 'fa-arrows-left-right text-warning';
				$titulo = 'Mudança de status';
			} elseif($obHist->acao == 'comentario'){
				$icone  = 'fa-comment-dots text-success';
				$titulo = 'Comentário';
			} elseif($obHist->acao == 'lead_cadastrado'){
				$icone  = 'fa-user-plus text-primary';
				$titulo = 'Lead cadastrado';
			} elseif($obHist->acao == 'lead_importado'){
				$icone  = 'fa-file-import text-info';
				$titulo = 'Importação de planilha';
			} elseif($obHist->acao == 'dados_atualizados'){
				$icone  = 'fa-pen text-info';
				$titulo = 'Dados atualizados';
			}

			$dataFormatada = DateTimeHelper::databr($obHist->data_registro);
			$horaFormatada = DateTimeHelper::extrairHorario($obHist->data_registro);

			$itens .= '
			<li class="timeline-item">
				<div class="timeline-icon">
					<i class="fas '.$icone.'"></i>
				</div>
				<div class="timeline-content">
					<div class="d-flex justify-content-between align-items-start">
						<strong>'.$titulo.'</strong>
						<small class="text-muted">'.$dataFormatada.' '.$horaFormatada.'</small>
					</div>
					<p class="mb-1">'.nl2br(htmlspecialchars($obHist->observacao)).'</p>
					<small class="text-muted">por '.htmlspecialchars($nomeUsuario).'</small>
				</div>
			</li>';
		}

		if($itens == ''){
			return '<p class="text-muted small mb-0">Nenhum histórico registrado ainda.</p>';
		}

		return '<ul class="timeline-list">'.$itens.'</ul>';
	}

	private static function dispararMensagemWhatsApp($lead, $statusAnterior, $statusNovo){

		if(!in_array($statusNovo, ['novo','em_atendimento'])){
			return;
		}

		if($statusAnterior === $statusNovo){
			return;
		}

		// TODO: Implementar envio via Evolution API
		// curl_setopt($ch, CURLOPT_URL, EVOLUTION_API_URL.'/message/sendText/'.$lead->id_instancia_wa);
		// curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['number' => $lead->whatsapp, 'text' => $mensagem]));
	}

}

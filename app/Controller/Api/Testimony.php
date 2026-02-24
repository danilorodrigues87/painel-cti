<?php 

namespace App\Controller\Api;
use \App\Model\Entity\Testimony as EntityTestimony;
use \App\Model\Db\Pagination;

class Testimony extends Api{

	private static function getTestimonyItems($request,&$obPagination){
		$itens = [];

		//QUANTIDADE TOTAL DE REGISTROS
		$quantidadeTotal = EntityTestimony::getTestimonies(null,null,null,'COUNT(*) as qtd')->fetchObject()->qtd;

		//PAGINA ATUAL
		$queryParams = $request->getQueryParams();
		$paginaAtual = $queryParams['page'] ?? 1;

		//INSTANCIA DE PAGINAÇÃO
		$obPagination = new Pagination($quantidadeTotal,$paginaAtual,5);

		//RESULTADOS DA PAGINA
		$results = EntityTestimony::getTestimonies(null,'id DESC', $obPagination->getLimit());

		//REDERIZA O ITEM
		while ($obTestimony = $results->fetchObject(EntityTestimony::class)) {
			$itens[] = [

				'id' => (int)$obTestimony->id,
				'nome' => $obTestimony->nome,
				'mensagem' => $obTestimony->mensagem,
				'data' => $obTestimony->data

			];
		}

		//RETORNA OS DEPOIMENTOS
		return $itens;
	}

	public static function getTestimonies($request){

		return [
			'depoimentos' => self::getTestimonyItems($request,$obPagination),
			'paginacao' => parent::getPagination($request,$obPagination)
		];
	}

	public static function getTestimony($request,$id){
		
		if(!is_numeric($id)){
			throw new \Exception("O id '".$id."' não é válido", 400);
		}
		$obTestimony = EntityTestimony::getTestimonyById($id);
		
		if(!$obTestimony instanceof EntityTestimony){
			throw new \Exception("O depoimento ".$id." não foi encontrado", 404);
		}

		return [
			'id' => (int)$obTestimony->id,
			'nome' => $obTestimony->nome,
			'mensagem' => $obTestimony->mensagem,
			'data' => $obTestimony->data
		];

	}

	public static function setNewTestimony($request){
		//POST VARS
		$postVars = $request->getPostVars();
		
		//VALIDA OS CAMPOSS OBRIGATORIOS
		if(!isset($postVars['nome']) or !isset($postVars['mensagem'])){
			throw new \Exception("Os campos 'nome' e 'menssagem' são obrigatórios",400);
		}

		//NOVO DEPOIMENTO
		$obTestimony = new EntityTestimony;
		$obTestimony->nome = $postVars['nome'];
		$obTestimony->mensagem = $postVars['mensagem'];
		$obTestimony->cadastrar();

		//RETORNA OS DETALHES DO DEPOIMENTO CADASTRADO

		return [
			'id' => (int)$obTestimony->id,
			'nome' => $obTestimony->nome,
			'mensagem' => $obTestimony->mensagem,
			'data' => $obTestimony->data
		];
	}

	public static function setEditTestimony($request,$id){
		//POST VARS
		$postVars = $request->getPostVars();
		
		//VALIDA OS CAMPOSS OBRIGATORIOS
		if(!isset($postVars['nome']) or !isset($postVars['mensagem'])){
			throw new \Exception("Os campos 'nome' e 'menssagem' são obrigatórios",400);
		}

		//BUSCA O REGISTRO
		$obTestimony = EntityTestimony::getTestimonyById($id);

		//VALIDA A INSTANCIA
		if(!$obTestimony instanceof EntityTestimony){
			throw new \Exception("O depoimento ".$id." não foi encontrado", 404);
		}

		//ATUALIZA O REGISTRO
		$obTestimony->nome = $postVars['nome'];
		$obTestimony->mensagem = $postVars['mensagem'];
		$obTestimony->atualizar();

		//RETORNA OS DETALHES DO DEPOIMENTO ATUALIZADO

		return [
			'id' => (int)$obTestimony->id,
			'nome' => $obTestimony->nome,
			'mensagem' => $obTestimony->mensagem,
			'data' => $obTestimony->data
		];
	}

	public static function setDeleteTestimony($request,$id){

		//BUSCA O REGISTRO
		$obTestimony = EntityTestimony::getTestimonyById($id);

		//VALIDA A INSTANCIA
		if(!$obTestimony instanceof EntityTestimony){
			throw new \Exception("O depoimento ".$id." não foi encontrado", 404);
		}

		//EXCLUI O REGISTRO
		$obTestimony->excluir();

		//RETORNA OS DETALHES DO DEPOIMENTO ATUALIZADO

		return [
			'sucesso' => true
		];
	}


}
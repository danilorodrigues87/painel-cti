<?php

namespace App\Model\Entity;
use App\Model\Db\Database;

class Trilhas{

	public $id,
	$nome,
	$id_categoria,
	$carga_h,
	$descricao,
	$valor_mensal,
	$site,
	$img;

	//RETORNA COM BASE NO ID
	public static function getTrilhaById($id){

		return self::getTrilha('id = '.$id)->fetchObject(self::class);

	}

	//ENVIA PARA O BANCO
	public function cadastrar(){
		
		//INSERIR OS DADOS PARA O BANCO DE DADOS
		$obDatabase = new Database('trilhas');
		$this->id = $obDatabase->insert([
			'nome' => $this->nome,
			'id_categoria' => $this->id_categoria,
			'carga_h' => $this->carga_h,
			'id_admin' => $this->id_admin,
			'descricao' => $this->descricao,
			'valor_mensal' => $this->valor_mensal,
			'site' => $this->site,
			'img' => $this->img
		]);
		
		return true;
	} 

	//RETORNA A INFORMAÇÃO
	public static function getTrilha(
    $where = null,
    $order = null,
    $limit = null,
    $fields = '*',
    $innerJoin = null,
    $group = null
){
    return (new Database('trilhas'))->select(
        $where,
        $order,
        $limit,
        $fields,
        $innerJoin,
        $group
    );
}


	//RETORNA A INFORMAÇÃO
	public static function getCustomTrilha($where = null){

		return (new Database())->customSelect($where);
	}

	//ATUALIZA NO BANCO
	public function atualizar(){

		//ATUALIZA OS DADOS PARA O BANCO DE DADOS
		return (new Database('trilhas'))->update('id = '.$this->id,[
			'nome' => $this->nome,
			'id_categoria' => $this->id_categoria,
			'carga_h' => $this->carga_h,
			'descricao' => $this->descricao,
			'valor_mensal' => $this->valor_mensal,
			'site' => $this->site,
			'img' => $this->img
		]);

	}

	//EXCLUI DO BANCO DE DADOS
	public function excluir(){

		return (new Database('trilhas'))->delete('id = '.$this->id);

	}

}
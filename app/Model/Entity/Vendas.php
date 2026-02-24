<?php

namespace App\Model\Entity;
use App\Model\Db\Database;

class Vendas{

	public $id_trilha,$id_vendedor,$descricao,$valor,$qtd_parcelas;

	//RETORNA COM BASE NO ID
	public static function getVendaById($id){

		return self::getVendas('id = '.$id)->fetchObject(self::class);

	}

	//ENVIA PARA O BANCO
	public function cadastrar(){
		
		//INSERIR OS DADOS PARA O BANCO DE DADOS
		$obDatabase = new Database('vendas');
		$this->id = $obDatabase->insert([
			'id_trilha' => $this->id_trilha,
			'id_vendedor' => $this->id_vendedor,
			'descricao' => $this->descricao,
			'qtd_parcelas' => $this->qtd_parcelas,
			'valor' => $this->valor,
			'id_admin' => $this->id_admin
		]);
		
		return true;
	} 

	//RETORNA A INFORMAÇÃO
	public static function getVendas(
    $where = null,
    $order = null,
    $limit = null,
    $fields = '*',
    $innerJoin = null,
    $group = null
){
    return (new Database('vendas'))->select(
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
		return (new Database('vendas'))->update('id = '.$this->id,[
			'id_trilha' => $this->id_trilha,
			'id_vendedor' => $this->id_vendedor,
			'descricao' => $this->descricao,
			'qtd_parcelas' => $this->qtd_parcelas,
			'valor' => $this->valor
		]);

	}

	//EXCLUI DO BANCO DE DADOS
	public function excluir(){

		return (new Database('vendas'))->delete('id = '.$this->id);

	}

}
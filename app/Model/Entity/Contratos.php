<?php

namespace App\Model\Entity;
use App\Model\Db\Database;

class Contratos{

	public $id,$nome,$conteudo;

	//RETORNA COM BASE NO ID
	public static function getContratoById($id){

		return self::getContratos('id = '.$id)->fetchObject(self::class);

	}

	//ENVIA PARA O BANCO
	public function cadastrar(){
		
		//INSERIR OS DADOS PARA O BANCO DE DADOS
		$obDatabase = new Database('contratos');
		$this->id = $obDatabase->insert([
			'nome' => $this->nome,
			'conteudo' => $this->conteudo,
			'id_admin' => $this->id_admin
		]);
		
		return true;
	} 

	//RETORNA A INFORMAÇÃO
	public static function getContratos($where = null,$order = null,$limit = null,$fields = '*'){

		return (new Database('contratos'))->select($where,$order,$limit,$fields);
	}

	//ATUALIZA NO BANCO
	public function atualizar(){

		//ATUALIZA OS DADOS PARA O BANCO DE DADOS
		return (new Database('contratos'))->update('id = '.$this->id,[
			'nome' => $this->nome,
			'conteudo' => $this->conteudo
		]);

	}

	//EXCLUI DO BANCO DE DADOS
	public function excluir(){

		return (new Database('contratos'))->delete('id = '.$this->id);

	}

}
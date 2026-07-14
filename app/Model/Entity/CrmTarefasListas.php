<?php

namespace App\Model\Entity;
use App\Model\Db\Database;

class CrmTarefasListas{

	public
		$id,
		$titulo,
		$posicao,
		$data_cadastro;

	public static function getListaById($id){
		return self::getListas('id = '.(int)$id)->fetchObject(self::class);
	}

	public static function getListas($where = null,$order = null,$limit = null,$fields = '*',$innerJoin = null){
		return (new Database('crm_tarefas_listas'))->select($where,$order,$limit,$fields,$innerJoin);
	}

	public static function getProximaPosicao(){
		$row = self::getListas(null,'posicao DESC',1,'posicao')->fetchObject(self::class);
		return $row ? ((int)$row->posicao + 1) : 0;
	}

	public function cadastrar(){
		$obDatabase = new Database('crm_tarefas_listas');
		$this->id = $obDatabase->insert([
			'titulo'        => $this->titulo,
			'posicao'       => $this->posicao ?? self::getProximaPosicao(),
			'data_cadastro' => $this->data_cadastro ?? date('Y-m-d H:i:s')
		]);
		return true;
	}

	public function atualizar(){
		return (new Database('crm_tarefas_listas'))->update('id = '.(int)$this->id,[
			'titulo'  => $this->titulo,
			'posicao' => $this->posicao
		]);
	}

	public static function excluir($id){
		return (new Database('crm_tarefas_listas'))->delete('id = '.(int)$id);
	}

}

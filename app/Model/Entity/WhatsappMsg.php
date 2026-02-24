<?php

namespace App\Model\Entity;
use App\Model\Db\Database;

class WhatsappMsg{

	public $id,$id_usuario,$nome,$telefone;

	//RETORNA COM BASE NO ID
	public static function getMessageById($id){

		return self::getMessages('id = '.$id)->fetchObject(self::class);

	}

	//ENVIA PARA O BANCO
	public function enviar(){
		
		//INSERIR OS DADOS PARA O BANCO DE DADOS
		$obDatabase = new Database('whatsapp_messages');
		$this->id = $obDatabase->insert([
			'id_usuario' => $this->id_usuario,
			'nome' => $this->nome,
			'telefone' => $this->telefone,
			'mensagem' => $this->mensagem
		]);
		
		return true;
	} 

	//RETORNA A INFORMAÇÃO
	public static function getMessages($where = null,$order = null,$limit = null,$fields = '*'){

		return (new Database('whatsapp_messages'))->select($where,$order,$limit,$fields);
	}

	//RETORNA A INFORMAÇÃO
	public static function getListChat($where = null,$order = null,$limit = null,$fields = '*'){

		return (new Database('chat'))->select($where,$order,$limit,$fields);
	}

	//ATUALIZA NO BANCO
	public function atualizar(){

		//ATUALIZA OS DADOS PARA O BANCO DE DADOS
		return (new Database('whatsapp_messages'))->update('id = '.$this->id,[
			'id_usuario' => $this->id_usuario,
			'nome' => $this->nome,
			'telefone' => $this->telefone,
			'mensagem' => $this->mensagem
		]);

	}

	//EXCLUI DO BANCO DE DADOS
	public function excluir(){

		return (new Database('whatsapp_messages'))->delete('id = '.$this->id);

	}

}
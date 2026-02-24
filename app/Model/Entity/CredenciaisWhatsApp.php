<?php

namespace App\Model\Entity;
use App\Model\Db\Database;
use App\Common\Logger;

class CredenciaisWhatsApp{

	public $id,
	$id_admin,
	$webhook_url,
	$my_token,
	$access_token,
	$whatsapp_id;



	//RETORNA COM BASE NO ID
	public static function getCredencialByWebhookUrl($webhook_url){
		try {
			Logger::log([
				'step' => 'buscando_credencial',
				'webhook_url' => $webhook_url
			]);

			$query = 'SELECT * FROM whatsapp_credenciais WHERE webhook_url = ?';
			
			Logger::log([
				'step' => 'debug_query',
				'query' => $query,
				'param' => $webhook_url
			]);

			$database = new Database('whatsapp_credenciais');
			$result = $database->execute($query, [$webhook_url]);
			
			Logger::log([
				'step' => 'debug_result',
				'raw_result' => $result->rowCount()
			]);

			$credencial = $result->fetchObject(self::class);

			Logger::log([
				'step' => 'credencial_encontrada',
				'found' => !empty($credencial),
				'has_tokens' => [
					'my_token' => isset($credencial->my_token),
					'access_token' => isset($credencial->access_token),
					'whatsapp_id' => isset($credencial->whatsapp_id)
				]
			]);

			return $credencial;

		} catch(\Exception $e) {
			Logger::log([
				'step' => 'erro_buscar_credencial',
				'error' => $e->getMessage(),
				'webhook_url' => $webhook_url
			]);
			return null;
		}
	}

	//ENVIA PARA O BANCO
	public function cadastrar(){
		
		//INSERIR OS DADOS PARA O BANCO DE DADOS
		$obDatabase = new Database('whatsapp_credenciais');
		$this->id = $obDatabase->insert([
			'id_admin' => $this->id_admin,
			'webhook_url' => $this->webhook_url,
			'my_token' => $this->my_token,
			'access_token' => $this->access_token,
			'whatsapp_id' => $this->whatsapp_id
		]);
		
		return true;
	} 

	//RETORNA A INFORMAÇÃO
	public static function getWhatsAppCredenciais($where = null,$order = null,$limit = null,$fields = '*'){

		return (new Database('whatsapp_credenciais'))->select($where,$order,$limit,$fields);
	}

	//ATUALIZA NO BANCO
	public function atualizar(){

		//ATUALIZA OS DADOS PARA O BANCO DE DADOS
		return (new Database('whatsapp_credenciais'))->update('id = '.$this->id,[
			'webhook_url' => $this->webhook_url,
			'my_token' => $this->my_token,
			'access_token' => $this->access_token,
			'whatsapp_id' => $this->whatsapp_id
		]);

	}

	//EXCLUI DO BANCO DE DADOS
	public function excluir(){

		return (new Database('whatsapp_credenciais'))->delete('id = '.$this->id);

	}

}
<?php

namespace App\Model\Entity;
use App\Model\Db\Database;

class Empresas {

	public $id;
	public $id_admin;
	public $id_gerente;
	public $tipo;
	public $ativo;
	public $nome;
	public $cpf_cnpj;
	public $email;
	public $site;
	public $logo;
	public $instagram;
	public $telefone;
	public $youtube;
	public $endereco;
	public $numero;
	public $bairro;
	public $estado;
	public $cidade;
	public $cep;


	//RETORNA UM DEPOIMENTO COM BASE NO ID
	public static function getEmpresaById($id){

		return self::getEmpresas('id = '.$id)->fetchObject(self::class);

	}

	//ENVIA A MENSAGEM PARA O BANCO
	public function cadastrar(){
		
		//INSERIR OS DADOS PARA O BANCO DE DADOS
		$obDatabase = new Database('empresas');
		$this->id = $obDatabase->insert([
			'nome' => $this->nome,
			'id_admin' => $this->id_admin,
			'id_gerente' => $this->id_gerente,
			'tipo' => $this->tipo,
			'ativo' => $this->ativo,
			'cpf_cnpj' => $this->cpf_cnpj,
			'email' => $this->email,
			'site' => $this->site,
			'logo' => $this->logo,
			'youtube' => $this->youtube,
			'instagram' => $this->instagram,
			'telefone' => $this->telefone,
			'endereco' => $this->endereco,
			'numero' => $this->numero,
			'bairro' => $this->bairro,
			'estado' => $this->estado,
			'cidade' => $this->cidade,
			'cep' => $this->cep
		]);
		
		return true;
	} 
 
	//RETORNA 
	public static function getEmpresas($where = null,$order = null,$limit = null,$fields = '*'){

		return (new Database('empresas'))->select($where,$order,$limit,$fields);
	}

	//ATUALIZA A MENSAGEM NO BANCO
	public function atualizar(){

		//ATUALIZA OS DADOS PARA O BANCO DE DADOS
		return (new Database('empresas'))->update('id = '.$this->id,[
			'nome' => $this->nome,
			'tipo' => $this->tipo,
			'ativo' => $this->ativo,
			'id_gerente' => $this->id_gerente,
			'cpf_cnpj' => $this->cpf_cnpj,
			'email' => $this->email,
			'site' => $this->site,
			'logo' => $this->logo,
			'youtube' => $this->youtube,
			'instagram' => $this->instagram,
			'telefone' => $this->telefone,
			'endereco' => $this->endereco,
			'numero' => $this->numero,
			'bairro' => $this->bairro,
			'estado' => $this->estado,
			'cidade' => $this->cidade,
			'cep' => $this->cep
		]);

	}


	//EXCLUI DO BANCO DE DADOS
	public function excluir(){

		return (new Database('empresas'))->delete('id = '.$this->id);

	}


}
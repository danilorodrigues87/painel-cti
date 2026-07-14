<?php

namespace App\Model\Entity;

use App\Model\Db\Database;

class EscolasAssinantes {

	public $id;
	public $id_admin;
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
	public $modulos_liberados;

	public static function getEscolaById($id) {
		return self::getEscolas('id = '.(int)$id)->fetchObject(self::class);
	}

	public static function getEscolas($where = null, $order = null, $limit = null, $fields = '*') {
		return (new Database('escolas_assinantes'))->select($where, $order, $limit, $fields);
	}

	public function cadastrar() {
		$obDatabase = new Database('escolas_assinantes');
		$this->id = $obDatabase->insert([
			'nome'               => $this->nome,
			'id_admin'           => $this->id_admin,
			'ativo'              => $this->ativo,
			'cpf_cnpj'           => $this->cpf_cnpj,
			'email'              => $this->email,
			'site'               => $this->site,
			'logo'               => $this->logo,
			'youtube'            => $this->youtube,
			'instagram'          => $this->instagram,
			'telefone'           => $this->telefone,
			'endereco'           => $this->endereco,
			'numero'             => $this->numero,
			'bairro'             => $this->bairro,
			'estado'             => $this->estado,
			'cidade'             => $this->cidade,
			'cep'                => $this->cep,
			'modulos_liberados'  => $this->modulos_liberados
		]);
		return true;
	}

	public function atualizar() {
		return (new Database('escolas_assinantes'))->update('id = '.(int)$this->id, [
			'nome'               => $this->nome,
			'ativo'              => $this->ativo,
			'cpf_cnpj'           => $this->cpf_cnpj,
			'email'              => $this->email,
			'site'               => $this->site,
			'logo'               => $this->logo,
			'youtube'            => $this->youtube,
			'instagram'          => $this->instagram,
			'telefone'           => $this->telefone,
			'endereco'           => $this->endereco,
			'numero'             => $this->numero,
			'bairro'             => $this->bairro,
			'estado'             => $this->estado,
			'cidade'             => $this->cidade,
			'cep'                => $this->cep,
			'modulos_liberados'  => $this->modulos_liberados
		]);
	}

	public function excluir() {
		return (new Database('escolas_assinantes'))->delete('id = '.(int)$this->id);
	}

}

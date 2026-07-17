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
	public $plan_id;
	public $modelo_certificado;

	public static function temColunaPlanId(): bool {
		return self::temColuna('plan_id');
	}

	public static function temColunaModeloCertificado(): bool {
		return self::temColuna('modelo_certificado');
	}

	private static function temColuna(string $coluna): bool {
		static $cache = [];
		$coluna = preg_replace('/[^a-z0-9_]/i', '', $coluna) ?: '';
		if ($coluna === '') {
			return false;
		}
		if (array_key_exists($coluna, $cache)) {
			return $cache[$coluna];
		}
		try {
			$row = (new Database('escolas_assinantes'))->execute(
				"SHOW COLUMNS FROM escolas_assinantes LIKE '{$coluna}'"
			)->fetch(\PDO::FETCH_ASSOC);
			$cache[$coluna] = !empty($row);
		} catch (\Throwable $e) {
			$cache[$coluna] = false;
		}
		return $cache[$coluna];
	}

	public static function getEscolaById($id) {
		$id = (int)$id;
		if ($id <= 0) {
			return false;
		}
		// Aceita busca por PK ou id_admin (legado empresas)
		$ob = self::getEscolas('id = '.$id)->fetchObject(self::class);
		if ($ob instanceof self) {
			return $ob;
		}
		return self::getEscolas('id_admin = '.$id)->fetchObject(self::class);
	}

	public static function getEscolas($where = null, $order = null, $limit = null, $fields = '*') {
		return (new Database('escolas_assinantes'))->select($where, $order, $limit, $fields);
	}

	/** Aceita 1, "1", "s", "S", true como ativo (legado misturado). */
	public static function isAtivaValor($ativo): bool {
		if ($ativo === true || $ativo === 1 || $ativo === '1') {
			return true;
		}
		if (is_string($ativo)) {
			$v = strtolower(trim($ativo));
			return $v === 's' || $v === 'sim' || $v === 'ativo' || $v === 'true';
		}
		return false;
	}

	public function isAtiva(): bool {
		return self::isAtivaValor($this->ativo ?? null);
	}

	public function cadastrar() {
		$obDatabase = new Database('escolas_assinantes');
		$dados = [
			'nome'               => $this->nome,
			'id_admin'           => (int)($this->id_admin ?: 0),
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
			'modulos_liberados'  => $this->modulos_liberados,
		];
		if (self::temColunaPlanId()) {
			$dados['plan_id'] = $this->plan_id !== null && $this->plan_id !== ''
				? (int)$this->plan_id
				: null;
		}
		if (self::temColunaModeloCertificado()) {
			$dados['modelo_certificado'] = $this->modelo_certificado ?: null;
		}
		$this->id = (int)$obDatabase->insert($dados);

		// Tenant = PK da escola
		if ($this->id > 0 && (int)$this->id_admin !== $this->id) {
			$this->id_admin = $this->id;
			(new Database('escolas_assinantes'))->update('id = '.$this->id, [
				'id_admin' => $this->id,
			]);
		}

		return true;
	}

	public function atualizar() {
		$dados = [
			'nome'               => $this->nome,
			'id_admin'           => (int)($this->id_admin ?: $this->id),
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
			'modulos_liberados'  => $this->modulos_liberados,
		];
		if (self::temColunaPlanId()) {
			$dados['plan_id'] = $this->plan_id !== null && $this->plan_id !== ''
				? (int)$this->plan_id
				: null;
		}
		if (self::temColunaModeloCertificado()) {
			$dados['modelo_certificado'] = $this->modelo_certificado ?: null;
		}
		return (new Database('escolas_assinantes'))->update('id = '.(int)$this->id, $dados);
	}

	public function excluir() {
		return (new Database('escolas_assinantes'))->delete('id = '.(int)$this->id);
	}

}

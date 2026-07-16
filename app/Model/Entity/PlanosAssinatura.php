<?php

namespace App\Model\Entity;

use App\Model\Db\Database;
use App\Common\SystemModules;

class PlanosAssinatura {

	public $id;
	public $nome;
	public $descricao;
	public $modulos;
	public $ativo = 1;
	public $ordem = 0;
	public $criado_em;

	public static function tabelaExiste(): bool {
		static $cache = null;
		if ($cache !== null) {
			return $cache;
		}
		try {
			$row = (new Database('planos_assinatura'))->execute(
				"SHOW TABLES LIKE 'planos_assinatura'"
			)->fetch(\PDO::FETCH_NUM);
			$cache = !empty($row);
		} catch (\Throwable $e) {
			$cache = false;
		}
		return $cache;
	}

	public static function getById(int $id) {
		if ($id <= 0 || !self::tabelaExiste()) {
			return false;
		}
		return self::get('id = '.$id)->fetchObject(self::class);
	}

	public static function get($where = null, $order = null, $limit = null, $fields = '*') {
		return (new Database('planos_assinatura'))->select($where, $order, $limit, $fields);
	}

	public function cadastrar(): bool {
		$this->id = (int)(new Database('planos_assinatura'))->insert([
			'nome'      => $this->nome,
			'descricao' => $this->descricao,
			'modulos'   => $this->modulos,
			'ativo'     => (int)$this->ativo ? 1 : 0,
			'ordem'     => (int)$this->ordem,
		]);
		return $this->id > 0;
	}

	public function atualizar(): bool {
		return (bool)(new Database('planos_assinatura'))->update('id = '.(int)$this->id, [
			'nome'      => $this->nome,
			'descricao' => $this->descricao,
			'modulos'   => $this->modulos,
			'ativo'     => (int)$this->ativo ? 1 : 0,
			'ordem'     => (int)$this->ordem,
		]);
	}

	public function excluir(): bool {
		return (bool)(new Database('planos_assinatura'))->delete('id = '.(int)$this->id);
	}

	/** true = todos os módulos (modulos NULL/vazio) */
	public function temTodosModulos(): bool {
		$raw = $this->modulos ?? null;
		return $raw === null || $raw === '';
	}

	/** @return string[] slugs */
	public function getSlugs(): array {
		if ($this->temTodosModulos()) {
			return SystemModules::getSlugs();
		}
		$decoded = json_decode((string)$this->modulos, true);
		if (!is_array($decoded)) {
			return [];
		}
		$validos = array_flip(SystemModules::getSlugs());
		$out = [];
		foreach ($decoded as $s) {
			$s = (string)$s;
			if (isset($validos[$s])) {
				$out[] = $s;
			}
		}
		return $out;
	}

	/** Valor para gravar em escolas_assinantes.modulos_liberados */
	public function modulosParaEscola(): ?string {
		if ($this->temTodosModulos()) {
			return null;
		}
		$slugs = $this->getSlugs();
		return empty($slugs) ? null : json_encode($slugs, JSON_UNESCAPED_UNICODE);
	}
}

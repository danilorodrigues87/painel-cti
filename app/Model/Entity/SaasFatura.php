<?php

namespace App\Model\Entity;

use App\Model\Db\Database;

class SaasFatura {

	public $id;
	public $id_admin;
	public $plan_id;
	public $competencia;
	public $valor;
	public $vencimento;
	public $status = 'aberta';
	public $mp_payment_id;
	public $pix_copia_cola;
	public $pago_em;
	public $criado_em;
	public $atualizado_em;

	public static function tabelaExiste(): bool {
		static $cache = null;
		if ($cache !== null) {
			return $cache;
		}
		try {
			$row = (new Database('saas_faturas'))->execute(
				"SHOW TABLES LIKE 'saas_faturas'"
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
		return (new Database('saas_faturas'))->select($where, $order, $limit, $fields);
	}

	public function cadastrar(): bool {
		$this->id = (int)(new Database('saas_faturas'))->insert([
			'id_admin'       => (int)$this->id_admin,
			'plan_id'        => $this->plan_id !== null && $this->plan_id !== '' ? (int)$this->plan_id : null,
			'competencia'    => $this->competencia,
			'valor'          => round((float)$this->valor, 2),
			'vencimento'     => $this->vencimento,
			'status'         => $this->status ?: 'aberta',
			'mp_payment_id'  => $this->mp_payment_id ?: null,
			'pix_copia_cola' => $this->pix_copia_cola ?: null,
			'pago_em'        => $this->pago_em ?: null,
		]);
		return $this->id > 0;
	}

	public function atualizar(): bool {
		return (bool)(new Database('saas_faturas'))->update('id = '.(int)$this->id, [
			'plan_id'        => $this->plan_id !== null && $this->plan_id !== '' ? (int)$this->plan_id : null,
			'valor'          => round((float)$this->valor, 2),
			'vencimento'     => $this->vencimento,
			'status'         => $this->status,
			'mp_payment_id'  => $this->mp_payment_id ?: null,
			'pix_copia_cola' => $this->pix_copia_cola ?: null,
			'pago_em'        => $this->pago_em ?: null,
		]);
	}

	public static function getPorEscolaCompetencia(int $idAdmin, string $competencia) {
		return self::get(
			'id_admin = '.(int)$idAdmin.' AND competencia = "'.addslashes($competencia).'"',
			null,
			'1'
		)->fetchObject(self::class);
	}

	public static function getPorMpPaymentId(string $paymentId) {
		$paymentId = preg_replace('/\D/', '', $paymentId);
		if ($paymentId === '') {
			return false;
		}
		return self::get(
			"mp_payment_id = '".addslashes($paymentId)."'",
			null,
			'1'
		)->fetchObject(self::class);
	}
}

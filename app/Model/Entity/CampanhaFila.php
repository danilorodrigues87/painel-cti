<?php

namespace App\Model\Entity;

use App\Model\Db\Database;

class CampanhaFila {

	public $id;
	public $campanha_id;
	public $id_admin;
	public $destinatario_tipo;
	public $destinatario_id;
	public $nome;
	public $contato;
	public $curso;
	public $status = 'pendente';
	public $tentativas = 0;
	public $erro_msg;
	public $enviado_em;
	public $criado_em;

	public static function tabelaExiste(): bool {
		return Campanhas::tabelaExiste();
	}

	public static function get($where = null, $order = null, $limit = null, $fields = '*') {
		return (new Database('campanha_fila'))->select($where, $order, $limit, $fields);
	}

	public static function contarPorCampanha(int $campanhaId, int $idAdmin, ?string $status = null): int {
		$where = 'campanha_id = '.(int)$campanhaId.' AND id_admin = '.(int)$idAdmin;
		if ($status !== null) {
			$where .= ' AND status = "'.addslashes($status).'"';
		}
		$row = self::get($where, null, null, 'COUNT(*) AS qtd')->fetch(\PDO::FETCH_ASSOC);
		return (int)($row['qtd'] ?? 0);
	}

	public static function limparCampanha(int $campanhaId, int $idAdmin): void {
		(new Database('campanha_fila'))->delete(
			'campanha_id = '.(int)$campanhaId.' AND id_admin = '.(int)$idAdmin
		);
	}

	public static function temColunaCurso(): bool {
		static $cache = null;
		if ($cache !== null) {
			return $cache;
		}
		try {
			$row = (new Database('campanha_fila'))->execute(
				"SHOW COLUMNS FROM campanha_fila LIKE 'curso'"
			)->fetch(\PDO::FETCH_ASSOC);
			$cache = !empty($row);
		} catch (\Throwable $e) {
			$cache = false;
		}
		return $cache;
	}

	public static function inserirLote(array $itens): int {
		$inseridos = 0;
		$db = new Database('campanha_fila');
		$comCurso = self::temColunaCurso();

		foreach ($itens as $item) {
			$row = [
				'campanha_id'        => (int)$item['campanha_id'],
				'id_admin'           => (int)$item['id_admin'],
				'destinatario_tipo'  => $item['destinatario_tipo'],
				'destinatario_id'    => $item['destinatario_id'] ?? null,
				'nome'               => $item['nome'] ?? null,
				'contato'            => $item['contato'],
				'status'             => 'pendente',
			];
			if ($comCurso) {
				$row['curso'] = isset($item['curso']) && $item['curso'] !== ''
					? mb_substr((string)$item['curso'], 0, 255)
					: null;
			}
			$db->insert($row);
			$inseridos++;
		}

		return $inseridos;
	}

	public static function getPendentes(int $idAdmin, int $limite = 10, ?int $campanhaId = null) {
		$where = 'id_admin = '.(int)$idAdmin.' AND status = "pendente"';
		if ($campanhaId !== null) {
			$where .= ' AND campanha_id = '.(int)$campanhaId;
		}
		return self::get($where, 'id ASC', (int)$limite);
	}

	/** Pendentes de campanhas em envio no canal informado (evita misturar e-mail e WhatsApp). */
	public static function getPendentesPorCanal(int $idAdmin, string $canal, int $limite = 10) {
		$canal = $canal === 'whatsapp' ? 'whatsapp' : 'email';
		$limite = max(1, (int)$limite);
		$sql = '
			SELECT f.*
			FROM campanha_fila f
			INNER JOIN campanhas c ON c.id = f.campanha_id AND c.id_admin = f.id_admin
			WHERE f.id_admin = '.(int)$idAdmin.'
			  AND f.status = "pendente"
			  AND c.status = "enviando"
			  AND c.canal = "'.addslashes($canal).'"
			ORDER BY f.id ASC
			LIMIT '.$limite.'
		';
		return (new Database('campanha_fila'))->execute($sql);
	}

	public function marcarEnviado(): void {
		(new Database('campanha_fila'))->update('id = '.(int)$this->id, [
			'status'     => 'enviado',
			'tentativas' => (int)$this->tentativas + 1,
			'enviado_em' => date('Y-m-d H:i:s'),
			'erro_msg'   => null,
		]);
	}

	public function marcarErro(string $mensagem): void {
		(new Database('campanha_fila'))->update('id = '.(int)$this->id, [
			'status'     => 'erro',
			'tentativas' => (int)$this->tentativas + 1,
			'erro_msg'   => mb_substr($mensagem, 0, 500),
		]);
	}

	public static function cancelarPendentes(int $campanhaId, int $idAdmin): void {
		$db = new Database('campanha_fila');
		$db->update(
			'campanha_id = '.(int)$campanhaId.' AND id_admin = '.(int)$idAdmin.' AND status = "pendente"',
			['status' => 'cancelado']
		);
	}
}

<?php

namespace App\Model\Entity;

use App\Model\Db\Database;

class WhatsappMensagem {

	public $id;
	public $id_admin;
	public $conversa_id;
	public $direction;
	public $tipo = 'text';
	public $corpo;
	public $media_url;
	public $wa_message_id;
	public $status;
	public $id_usuario;
	public $created_at;

	public static function tabelaExiste(): bool {
		static $cache = null;
		if ($cache !== null) {
			return $cache;
		}
		try {
			$pdo = new \PDO(
				'mysql:host='.getenv('DB_HOST').';dbname='.getenv('DB_NAME').';charset=utf8mb4',
				getenv('DB_USER'),
				getenv('DB_PASS'),
				[\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
			);
			$stmt = $pdo->query("SHOW TABLES LIKE 'whatsapp_mensagens'");
			$cache = $stmt && $stmt->rowCount() > 0;
		} catch (\Throwable $e) {
			$cache = false;
		}
		return $cache;
	}

	public static function registrar(array $dados): ?int {
		$r = self::registrarComStatus($dados);
		return $r['id'];
	}

	/**
	 * @return array{id:?int,novo:bool}
	 */
	public static function registrarComStatus(array $dados): array {
		if (!self::tabelaExiste()) {
			return ['id' => null, 'novo' => false];
		}

		if (!empty($dados['wa_message_id'])) {
			$wa = addslashes((string)$dados['wa_message_id']);
			$existe = (new Database('whatsapp_mensagens'))
				->select('wa_message_id = "'.$wa.'"', null, 1, 'id')
				->fetch(\PDO::FETCH_ASSOC);
			if (!empty($existe['id'])) {
				return ['id' => (int)$existe['id'], 'novo' => false];
			}
		}

		$id = (int)(new Database('whatsapp_mensagens'))->insert([
			'id_admin'      => (int)$dados['id_admin'],
			'conversa_id'   => (int)$dados['conversa_id'],
			'direction'     => $dados['direction'] ?? 'in',
			'tipo'          => $dados['tipo'] ?? 'text',
			'corpo'         => $dados['corpo'] ?? null,
			'media_url'     => $dados['media_url'] ?? null,
			'wa_message_id' => $dados['wa_message_id'] ?? null,
			'status'        => $dados['status'] ?? null,
			'id_usuario'    => $dados['id_usuario'] ?? null,
		]);

		return ['id' => $id > 0 ? $id : null, 'novo' => $id > 0];
	}

	public static function existeWaMessageId(?string $waMessageId): bool {
		if ($waMessageId === null || trim($waMessageId) === '') {
			return false;
		}
		if (!self::tabelaExiste()) {
			return false;
		}
		$wa = addslashes(trim($waMessageId));
		$existe = (new Database('whatsapp_mensagens'))
			->select('wa_message_id = "'.$wa.'"', null, 1, 'id')
			->fetch(\PDO::FETCH_ASSOC);
		return !empty($existe['id']);
	}

	/** Última saída de texto recente (eco do bot/inbox sem wa_id casado). */
	public static function saidaTextoRecente(int $conversaId, string $corpo, int $janelaSeg = 90): bool {
		if (!self::tabelaExiste() || $conversaId <= 0) {
			return false;
		}
		$corpo = trim($corpo);
		if ($corpo === '') {
			return false;
		}
		$cid = (int)$conversaId;
		$janelaSeg = max(5, min(300, $janelaSeg));
		$row = (new Database('whatsapp_mensagens'))
			->select(
				'conversa_id = '.$cid.' AND direction = "out" AND tipo = "text" AND created_at >= (NOW() - INTERVAL '.$janelaSeg.' SECOND)',
				'id DESC',
				8,
				'id, corpo'
			)
			->fetchAll(\PDO::FETCH_ASSOC);
		if (!$row) {
			return false;
		}
		$norm = static function (string $s): string {
			$s = preg_replace('/\s+/u', ' ', trim($s)) ?? trim($s);
			return mb_strtolower($s, 'UTF-8');
		};
		$alvo = $norm($corpo);
		foreach ($row as $r) {
			if ($norm((string)($r['corpo'] ?? '')) === $alvo) {
				return true;
			}
		}
		return false;
	}
}

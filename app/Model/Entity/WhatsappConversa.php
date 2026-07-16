<?php

namespace App\Model\Entity;

use App\Model\Db\Database;

class WhatsappConversa {

	public $id;
	public $id_admin;
	public $telefone;
	public $nome_contato;
	public $status = 'aberta';
	public $id_atendente;
	public $ultima_mensagem_em;
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
			$stmt = $pdo->query("SHOW TABLES LIKE 'whatsapp_conversas'");
			$cache = $stmt && $stmt->rowCount() > 0;
		} catch (\Throwable $e) {
			$cache = false;
		}
		return $cache;
	}

	public static function getByIdAdminTelefone(int $idAdmin, string $telefone) {
		if (!self::tabelaExiste()) {
			return null;
		}
		$tel = addslashes($telefone);
		return (new Database('whatsapp_conversas'))
			->select('id_admin = '.(int)$idAdmin.' AND telefone = "'.$tel.'"', null, 1)
			->fetchObject(self::class) ?: null;
	}

	public static function findOrCreate(int $idAdmin, string $telefone, ?string $nome = null): ?self {
		if (!self::tabelaExiste()) {
			return null;
		}

		$existente = self::getByIdAdminTelefone($idAdmin, $telefone);
		if ($existente instanceof self) {
			if ($nome && empty($existente->nome_contato)) {
				(new Database('whatsapp_conversas'))->update(
					'id = '.(int)$existente->id,
					['nome_contato' => $nome]
				);
				$existente->nome_contato = $nome;
			}
			return $existente;
		}

		$db = new Database('whatsapp_conversas');
		$id = $db->insert([
			'id_admin'     => $idAdmin,
			'telefone'     => $telefone,
			'nome_contato' => $nome,
			'status'       => 'aberta',
		]);

		$ob = new self;
		$ob->id = (int)$id;
		$ob->id_admin = $idAdmin;
		$ob->telefone = $telefone;
		$ob->nome_contato = $nome;
		$ob->status = 'aberta';
		return $ob;
	}

	public function tocarUltimaMensagem(): void {
		(new Database('whatsapp_conversas'))->update(
			'id = '.(int)$this->id,
			['ultima_mensagem_em' => date('Y-m-d H:i:s')]
		);
	}
}

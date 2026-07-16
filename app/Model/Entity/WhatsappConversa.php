<?php

namespace App\Model\Entity;

use App\Model\Db\Database;

class WhatsappConversa {

	public $id;
	public $id_admin;
	public $numero_id;
	public $telefone;
	public $nome_contato;
	public $status = 'aberta';
	public $setor_id;
	public $id_atendente;
	public $chatbot_estado = 'novo';
	public $assigned_at;
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

	public static function temColunasChatbot(): bool {
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
			$stmt = $pdo->query("SHOW COLUMNS FROM whatsapp_conversas LIKE 'chatbot_estado'");
			$cache = $stmt && $stmt->rowCount() > 0;
		} catch (\Throwable $e) {
			$cache = false;
		}
		return $cache;
	}

	public static function getById(int $id, int $idAdmin) {
		if (!self::tabelaExiste()) {
			return null;
		}
		return (new Database('whatsapp_conversas'))
			->select('id = '.(int)$id.' AND id_admin = '.(int)$idAdmin, null, 1)
			->fetchObject(self::class) ?: null;
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

	public static function findOrCreate(int $idAdmin, string $telefone, ?string $nome = null, ?int $numeroId = null): ?self {
		if (!self::tabelaExiste()) {
			return null;
		}

		$existente = self::getByIdAdminTelefone($idAdmin, $telefone);
		if ($existente instanceof self) {
			$upd = [];
			if ($nome && empty($existente->nome_contato)) {
				$upd['nome_contato'] = $nome;
				$existente->nome_contato = $nome;
			}
			if ($numeroId && empty($existente->numero_id) && self::temColunasChatbot()) {
				$upd['numero_id'] = $numeroId;
				$existente->numero_id = $numeroId;
			}
			if ($upd) {
				(new Database('whatsapp_conversas'))->update('id = '.(int)$existente->id, $upd);
			}
			return $existente;
		}

		$dados = [
			'id_admin'     => $idAdmin,
			'telefone'     => $telefone,
			'nome_contato' => $nome,
			'status'       => 'aberta',
		];
		if (self::temColunasChatbot()) {
			$dados['chatbot_estado'] = 'novo';
			$dados['numero_id'] = $numeroId;
		}

		$db = new Database('whatsapp_conversas');
		$id = $db->insert($dados);

		$ob = new self;
		$ob->id = (int)$id;
		$ob->id_admin = $idAdmin;
		$ob->telefone = $telefone;
		$ob->nome_contato = $nome;
		$ob->status = 'aberta';
		$ob->chatbot_estado = 'novo';
		$ob->numero_id = $numeroId;
		return $ob;
	}

	public function tocarUltimaMensagem(): void {
		(new Database('whatsapp_conversas'))->update(
			'id = '.(int)$this->id,
			['ultima_mensagem_em' => date('Y-m-d H:i:s')]
		);
	}

	public function atualizar(array $dados): void {
		if (!$dados) {
			return;
		}
		(new Database('whatsapp_conversas'))->update('id = '.(int)$this->id, $dados);
		foreach ($dados as $k => $v) {
			$this->$k = $v;
		}
	}

	/**
	 * Lista conversas visíveis ao usuário.
	 * @param string $filtro todas|minhas|fila
	 */
	public static function listarInbox(
		int $idAdmin,
		int $usuarioId,
		string $nivel,
		array $setorIds,
		int $limite = 80,
		string $filtro = 'todas',
		string $busca = ''
	): array {
		if (!self::tabelaExiste()) {
			return [];
		}

		$limite = max(1, min(200, $limite));
		$filtro = in_array($filtro, ['minhas', 'fila', 'todas'], true) ? $filtro : 'todas';
		$where = 'c.id_admin = '.(int)$idAdmin;

		// Visibilidade base
		if ($nivel !== 'Diretor') {
			$parts = ['c.id_atendente = '.(int)$usuarioId];
			if ($setorIds) {
				$ids = implode(',', array_map('intval', $setorIds));
				$parts[] = '(c.setor_id IN ('.$ids.') AND (c.id_atendente IS NULL OR c.id_atendente = 0))';
			}
			$parts[] = "(c.chatbot_estado IN ('novo','aguardando_setor') OR (c.setor_id IS NULL AND c.id_atendente IS NULL))";
			$where .= ' AND ('.implode(' OR ', $parts).')';
		}

		if ($filtro === 'minhas') {
			$where .= ' AND c.id_atendente = '.(int)$usuarioId;
		} elseif ($filtro === 'fila') {
			$where .= ' AND (c.id_atendente IS NULL OR c.id_atendente = 0)';
			$where .= " AND c.status != 'fechada' AND IFNULL(c.chatbot_estado,'') != 'encerrado'";
		} else {
			// todas: oculta encerradas antigas da lista principal (ainda aparecem na busca)
			if ($busca === '') {
				$where .= " AND (c.status IS NULL OR c.status != 'fechada' OR c.ultima_mensagem_em >= DATE_SUB(NOW(), INTERVAL 2 DAY))";
			}
		}

		$busca = trim($busca);
		if ($busca !== '') {
			$like = addslashes(str_replace(['%', '_'], ['\\%', '\\_'], $busca));
			$digitos = preg_replace('/\D+/', '', $busca) ?? '';
			$or = [
				'c.nome_contato LIKE "%'.$like.'%"',
				'c.telefone LIKE "%'.$like.'%"',
			];
			if ($digitos !== '') {
				$or[] = 'c.telefone LIKE "%'.addslashes($digitos).'%"';
			}
			$where .= ' AND ('.implode(' OR ', $or).')';
		}

		$joinSetor = WhatsappSetor::tabelaExiste()
			? 'LEFT JOIN whatsapp_setores s ON s.id = c.setor_id'
			: '';
		$camposSetor = WhatsappSetor::tabelaExiste() ? ', s.nome AS setor_nome' : ', NULL AS setor_nome';

		$sql = 'SELECT c.*, u.nome AS atendente_nome'.$camposSetor.'
			FROM whatsapp_conversas c
			LEFT JOIN usuarios u ON u.id = c.id_atendente
			'.$joinSetor.'
			WHERE '.$where.'
			ORDER BY COALESCE(c.ultima_mensagem_em, c.created_at) DESC
			LIMIT '.$limite;

		return (new Database('whatsapp_conversas'))->execute($sql)->fetchAll(\PDO::FETCH_ASSOC) ?: [];
	}
}

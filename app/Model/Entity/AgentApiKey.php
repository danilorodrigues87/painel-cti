<?php

namespace App\Model\Entity;

use App\Model\Db\Database;

class AgentApiKey {

	public $id;
	public $nome;
	public $escopo;
	public $id_admin;
	public $key_prefix;
	public $key_hash;
	public $scopes;
	public $ativo = 1;
	public $ultimo_uso_em;
	public $rate_window_start;
	public $rate_count = 0;
	public $criado_em;
	public $revogado_em;

	public const RATE_LIMIT_PER_MIN = 60;
	public const PREFIX = 'cti_ak_';

	private static $tabelaOk = null;

	public static function tabelaExiste(): bool {
		if (self::$tabelaOk !== null) {
			return self::$tabelaOk;
		}
		try {
			$row = (new Database())->execute("SHOW TABLES LIKE 'agent_api_keys'")->fetch(\PDO::FETCH_NUM);
			self::$tabelaOk = !empty($row);
		} catch (\Throwable $e) {
			self::$tabelaOk = false;
		}
		return self::$tabelaOk;
	}

	public static function getById(int $id): ?self {
		if (!self::tabelaExiste() || $id <= 0) {
			return null;
		}
		$row = self::select('id = '.(int)$id)->fetchObject(self::class);
		return $row instanceof self ? $row : null;
	}

	public static function select($where = null, $order = null, $limit = null, $fields = '*') {
		return (new Database('agent_api_keys'))->select($where, $order, $limit, $fields);
	}

	/**
	 * Gera chave em claro (só mostrar 1×) e persiste o hash.
	 * @return array{key:self,plain:string}|null
	 */
	public static function criar(string $nome, string $escopo, ?int $idAdmin, array $scopes = ['read:all']): ?array {
		if (!self::tabelaExiste()) {
			return null;
		}
		$escopo = $escopo === 'master' ? 'master' : 'escola';
		if ($escopo === 'master') {
			$idAdmin = null;
		} elseif ($idAdmin === null || $idAdmin <= 0) {
			return null;
		}

		$plain = self::PREFIX.bin2hex(random_bytes(24));
		$prefix = substr($plain, 0, 12);

		$ob = new self();
		$ob->nome = mb_substr(trim($nome) !== '' ? trim($nome) : 'API Key', 0, 120);
		$ob->escopo = $escopo;
		$ob->id_admin = $idAdmin;
		$ob->key_prefix = $prefix;
		$ob->key_hash = password_hash($plain, PASSWORD_DEFAULT);
		$ob->scopes = json_encode(array_values($scopes), JSON_UNESCAPED_UNICODE);
		$ob->ativo = 1;

		$db = new Database('agent_api_keys');
		$ob->id = (int)$db->insert([
			'nome' => $ob->nome,
			'escopo' => $ob->escopo,
			'id_admin' => $ob->id_admin,
			'key_prefix' => $ob->key_prefix,
			'key_hash' => $ob->key_hash,
			'scopes' => $ob->scopes,
			'ativo' => 1,
		]);
		if ($ob->id <= 0) {
			return null;
		}
		return ['key' => $ob, 'plain' => $plain];
	}

	public static function autenticar(string $plain): ?self {
		if (!self::tabelaExiste()) {
			return null;
		}
		$plain = trim($plain);
		if ($plain === '' || strpos($plain, self::PREFIX) !== 0) {
			return null;
		}
		$prefix = substr($plain, 0, 12);
		$st = self::select(
			'key_prefix = "'.addslashes($prefix).'" AND ativo = 1 AND revogado_em IS NULL',
			'id DESC',
			20
		);
		while ($ob = $st->fetchObject(self::class)) {
			if (password_verify($plain, (string)$ob->key_hash)) {
				return $ob;
			}
		}
		return null;
	}

	public function getScopes(): array {
		$decoded = json_decode((string)$this->scopes, true);
		return is_array($decoded) ? $decoded : ['read:all'];
	}

	public function temScope(string $scope): bool {
		$scopes = $this->getScopes();
		if (in_array('read:all', $scopes, true)) {
			return true;
		}
		return in_array($scope, $scopes, true);
	}

	public function isMaster(): bool {
		return $this->escopo === 'master';
	}

	/** @return bool true se dentro do limite */
	public function checkAndBumpRateLimit(): bool {
		$now = date('Y-m-d H:i:s');
		$window = $this->rate_window_start ? strtotime((string)$this->rate_window_start) : 0;
		$count = (int)$this->rate_count;
		if ($window <= 0 || (time() - $window) >= 60) {
			$this->rate_window_start = $now;
			$this->rate_count = 1;
		} else {
			if ($count >= self::RATE_LIMIT_PER_MIN) {
				return false;
			}
			$this->rate_count = $count + 1;
		}
		$this->ultimo_uso_em = $now;
		(new Database('agent_api_keys'))->update(
			'id = '.(int)$this->id,
			[
				'ultimo_uso_em' => $this->ultimo_uso_em,
				'rate_window_start' => $this->rate_window_start,
				'rate_count' => (int)$this->rate_count,
			]
		);
		return true;
	}

	public function revogar(): bool {
		$this->ativo = 0;
		$this->revogado_em = date('Y-m-d H:i:s');
		return (new Database('agent_api_keys'))->update(
			'id = '.(int)$this->id,
			[
				'ativo' => 0,
				'revogado_em' => $this->revogado_em,
			]
		) !== false;
	}

	public function toPublicArray(): array {
		return [
			'id' => (int)$this->id,
			'nome' => (string)$this->nome,
			'escopo' => (string)$this->escopo,
			'id_admin' => $this->id_admin !== null ? (int)$this->id_admin : null,
			'key_prefix' => (string)$this->key_prefix.'…',
			'scopes' => $this->getScopes(),
			'ativo' => (int)$this->ativo === 1,
			'ultimo_uso_em' => $this->ultimo_uso_em,
			'criado_em' => $this->criado_em,
		];
	}

	public static function listarMaster(): array {
		if (!self::tabelaExiste()) {
			return [];
		}
		$out = [];
		$st = self::select('escopo = "master"', 'id DESC', 50);
		while ($ob = $st->fetchObject(self::class)) {
			$out[] = $ob->toPublicArray();
		}
		return $out;
	}

	public static function listarEscola(int $idAdmin): array {
		if (!self::tabelaExiste() || $idAdmin <= 0) {
			return [];
		}
		$out = [];
		$st = self::select(
			'escopo = "escola" AND id_admin = '.(int)$idAdmin,
			'id DESC',
			50
		);
		while ($ob = $st->fetchObject(self::class)) {
			$out[] = $ob->toPublicArray();
		}
		return $out;
	}

	public static function audit(?int $idKey, ?string $escopo, ?int $idAdmin, string $method, string $path, int $status, ?string $ip): void {
		try {
			$row = (new Database())->execute("SHOW TABLES LIKE 'agent_api_audit'")->fetch(\PDO::FETCH_NUM);
			if (empty($row)) {
				return;
			}
			(new Database('agent_api_audit'))->insert([
				'id_key' => $idKey,
				'escopo' => $escopo,
				'id_admin' => $idAdmin,
				'method' => mb_substr($method, 0, 10),
				'path' => mb_substr($path, 0, 255),
				'status_code' => $status,
				'ip' => $ip ? mb_substr($ip, 0, 45) : null,
			]);
		} catch (\Throwable $e) {
			// silencioso
		}
	}
}

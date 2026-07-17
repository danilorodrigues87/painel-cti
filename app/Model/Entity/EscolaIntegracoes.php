<?php

namespace App\Model\Entity;

use App\Model\Db\Database;
use App\Common\Helpers\CryptoHelper;

class EscolaIntegracoes {

	private static $ultimoErro = null;

	public static function getUltimoErro(): ?string {
		return self::$ultimoErro;
	}

	public $id_admin;
	public $smtp_host;
	public $smtp_port = 587;
	public $smtp_user;
	public $smtp_pass;
	public $smtp_from_email;
	public $smtp_from_name;
	public $smtp_encryption = 'tls';
	public $smtp_ativo = 0;
	public $email_delay_segundos = 3;
	public $email_max_hora = 80;
	public $cobranca_ativo = 0;
	public $cobranca_dias_antes = '3,5';
	public $cobranca_aviso_vencimento = 1;
	public $cobranca_dias_depois = '1,3,7';
	public $cobranca_enviar_responsavel = 1;
	public $cobranca_whatsapp_ativo = 0;
	public $cobranca_assunto_antes;
	public $cobranca_assunto_vencimento;
	public $cobranca_assunto_atraso;
	public $cobranca_msg_antes;
	public $cobranca_msg_vencimento;
	public $cobranca_msg_atraso;
	public $aniversario_ativo = 0;
	public $aniversario_apenas_matriculados = 1;
	public $aniversario_whatsapp_ativo = 0;
	public $aniversario_assunto;
	public $aniversario_mensagem;
	public $evolution_instance;
	public $evolution_status = 'disconnected';
	public $evolution_ativo = 0;
	public $evolution_numero;
	public $whatsapp_delay_segundos = 5;
	public $whatsapp_max_hora = 40;
	public $whatsapp_grupo_delay_segundos = 3600;
	public $whatsapp_horario_inicio;
	public $whatsapp_horario_fim;
	public $whatsapp_dias = '1,2,3,4,5';
	public $whatsapp_msg_fora;
	/** Quando true, o salvar() também grava campos Evolution/WhatsApp. */
	public $touchEvolution = false;
	public $updated_at;

	public static function temColunasCobranca(): bool {
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
			$stmt = $pdo->query("SHOW COLUMNS FROM escola_integracoes LIKE 'cobranca_ativo'");
			$cache = $stmt && $stmt->rowCount() > 0;
		} catch (\Throwable $e) {
			$cache = false;
		}
		return $cache;
	}

	public static function temColunasAniversario(): bool {
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
			$stmt = $pdo->query("SHOW COLUMNS FROM escola_integracoes LIKE 'aniversario_ativo'");
			$cache = $stmt && $stmt->rowCount() > 0;
		} catch (\Throwable $e) {
			$cache = false;
		}
		return $cache;
	}

	public static function temColunasHorarioWhatsapp(): bool {
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
			$stmt = $pdo->query("SHOW COLUMNS FROM escola_integracoes LIKE 'whatsapp_horario_inicio'");
			$cache = $stmt && $stmt->rowCount() > 0;
		} catch (\Throwable $e) {
			$cache = false;
		}
		return $cache;
	}

	public static function temColunasWhatsappAutomacao(): bool {
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
			$stmt = $pdo->query("SHOW COLUMNS FROM escola_integracoes LIKE 'cobranca_whatsapp_ativo'");
			$cache = $stmt && $stmt->rowCount() > 0;
		} catch (\Throwable $e) {
			$cache = false;
		}
		return $cache;
	}

	public static function temColunasEvolution(): bool {
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
			$stmt = $pdo->query("SHOW COLUMNS FROM escola_integracoes LIKE 'evolution_instance'");
			$cache = $stmt && $stmt->rowCount() > 0;
		} catch (\Throwable $e) {
			$cache = false;
		}
		return $cache;
	}

	public static function temColunaWhatsappGrupoDelay(): bool {
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
			$stmt = $pdo->query("SHOW COLUMNS FROM escola_integracoes LIKE 'whatsapp_grupo_delay_segundos'");
			$cache = $stmt && $stmt->rowCount() > 0;
		} catch (\Throwable $e) {
			$cache = false;
		}
		return $cache;
	}

	public static function tabelaExiste(): bool {
		try {
			$host = getenv('DB_HOST');
			$name = getenv('DB_NAME');
			$user = getenv('DB_USER');
			$pass = getenv('DB_PASS');
			$pdo = new \PDO(
				'mysql:host='.$host.';dbname='.$name.';charset=utf8mb4',
				$user,
				$pass,
				[\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
			);
			$stmt = $pdo->query("SHOW TABLES LIKE 'escola_integracoes'");
			return $stmt && $stmt->rowCount() > 0;
		} catch (\Throwable $e) {
			return false;
		}
	}

	public static function getByIdAdmin(int $idAdmin) {
		if (!self::tabelaExiste()) {
			return null;
		}
		return self::get('id_admin = '.(int)$idAdmin)->fetchObject(self::class);
	}

	public static function get($where = null, $order = null, $limit = null, $fields = '*') {
		return (new Database('escola_integracoes'))->select($where, $order, $limit, $fields);
	}

	public function getSenhaDescriptografada(): ?string {
		return CryptoHelper::decrypt($this->smtp_pass ?? null);
	}

	public function salvar(): bool {
		self::$ultimoErro = null;

		$dados = [
			'smtp_host'            => $this->smtp_host,
			'smtp_port'            => (int)$this->smtp_port,
			'smtp_user'            => $this->smtp_user,
			'smtp_from_email'      => $this->smtp_from_email,
			'smtp_from_name'       => $this->smtp_from_name,
			'smtp_encryption'      => $this->smtp_encryption,
			'smtp_ativo'           => (int)$this->smtp_ativo,
			'email_delay_segundos' => (int)$this->email_delay_segundos,
			'email_max_hora'       => (int)$this->email_max_hora,
		];

		if ($this->smtp_pass !== null && $this->smtp_pass !== '') {
			$criptografada = CryptoHelper::encrypt($this->smtp_pass);
			if ($criptografada === null) {
				self::$ultimoErro = 'Não foi possível criptografar a senha SMTP.';
				return false;
			}
			$dados['smtp_pass'] = $criptografada;
		}

		if (self::temColunasCobranca()) {
			$dados['cobranca_ativo'] = (int)$this->cobranca_ativo;
			$dados['cobranca_dias_antes'] = $this->cobranca_dias_antes;
			$dados['cobranca_aviso_vencimento'] = (int)$this->cobranca_aviso_vencimento;
			$dados['cobranca_dias_depois'] = $this->cobranca_dias_depois;
			$dados['cobranca_enviar_responsavel'] = (int)$this->cobranca_enviar_responsavel;
			$dados['cobranca_assunto_antes'] = $this->cobranca_assunto_antes;
			$dados['cobranca_assunto_vencimento'] = $this->cobranca_assunto_vencimento;
			$dados['cobranca_assunto_atraso'] = $this->cobranca_assunto_atraso;
			$dados['cobranca_msg_antes'] = $this->cobranca_msg_antes;
			$dados['cobranca_msg_vencimento'] = $this->cobranca_msg_vencimento;
			$dados['cobranca_msg_atraso'] = $this->cobranca_msg_atraso;
			if (self::temColunasWhatsappAutomacao()) {
				$dados['cobranca_whatsapp_ativo'] = (int)$this->cobranca_whatsapp_ativo;
			}
		}

		if (self::temColunasAniversario()) {
			$dados['aniversario_ativo'] = (int)$this->aniversario_ativo;
			$dados['aniversario_apenas_matriculados'] = (int)$this->aniversario_apenas_matriculados;
			$dados['aniversario_assunto'] = $this->aniversario_assunto;
			$dados['aniversario_mensagem'] = $this->aniversario_mensagem;
			if (self::temColunasWhatsappAutomacao()) {
				$dados['aniversario_whatsapp_ativo'] = (int)$this->aniversario_whatsapp_ativo;
			}
		}

		if (self::temColunasEvolution() && $this->touchEvolution) {
			$dados['evolution_instance'] = $this->evolution_instance;
			$dados['evolution_status'] = $this->evolution_status ?: 'disconnected';
			$dados['evolution_ativo'] = (int)$this->evolution_ativo;
			$dados['evolution_numero'] = $this->evolution_numero;
			$dados['whatsapp_delay_segundos'] = (int)($this->whatsapp_delay_segundos ?? 5);
			$dados['whatsapp_max_hora'] = (int)($this->whatsapp_max_hora ?? 40);
			if (self::temColunaWhatsappGrupoDelay()) {
				$dados['whatsapp_grupo_delay_segundos'] = max(60, (int)($this->whatsapp_grupo_delay_segundos ?? 3600));
			}
			if (self::temColunasHorarioWhatsapp()) {
				$dados['whatsapp_horario_inicio'] = $this->whatsapp_horario_inicio ?: null;
				$dados['whatsapp_horario_fim'] = $this->whatsapp_horario_fim ?: null;
				$dados['whatsapp_dias'] = $this->whatsapp_dias ?: '1,2,3,4,5';
				$dados['whatsapp_msg_fora'] = $this->whatsapp_msg_fora;
			}
		}

		$existente = self::getByIdAdmin((int)$this->id_admin);
		$db = new Database('escola_integracoes');

		if ($existente instanceof self) {
			$db->update('id_admin = '.(int)$this->id_admin, $dados);
			return true;
		}

		$dados['id_admin'] = (int)$this->id_admin;
		if (!isset($dados['smtp_pass'])) {
			$dados['smtp_pass'] = null;
		}

		$db->insert($dados);
		return true;
	}

	public function temSmtpConfigurado(): bool {
		return (int)$this->smtp_ativo === 1
			&& !empty($this->smtp_host)
			&& !empty($this->smtp_user)
			&& !empty($this->smtp_from_email)
			&& !empty($this->getSenhaDescriptografada());
	}
}

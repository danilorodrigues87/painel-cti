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
	public $valor_mensal_custom;
	public $dia_vencimento_assinatura = 10;
	public $assinatura_status = 'ativa';
	public $trial_ate;
	public $assinatura_proximo_vencimento;
	public $modelo_certificado;
	public $modelo_contrato_html;
	public $certificado_frase_conclusao;

	public static function temColunaPlanId(): bool {
		return self::temColuna('plan_id');
	}

	public static function temColunaValorMensalCustom(): bool {
		return self::temColuna('valor_mensal_custom');
	}

	public static function temColunaTrialAte(): bool {
		return self::temColuna('trial_ate');
	}

	public static function temColunasAssinatura(): bool {
		return self::temColuna('dia_vencimento_assinatura');
	}

	public static function temColunaModeloCertificado(): bool {
		return self::temColuna('modelo_certificado');
	}

	public static function temColunaModeloContrato(): bool {
		return self::temColuna('modelo_contrato_html');
	}

	public static function temColunaCertificadoFrase(): bool {
		return self::temColuna('certificado_frase_conclusao');
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

	/** Normaliza campos NOT NULL da tabela (legado sem DEFAULT). */
	private function dadosPersistencia(): array {
		return [
			'nome'               => (string)($this->nome ?? ''),
			'id_admin'           => (int)($this->id_admin ?: 0),
			'ativo'              => self::isAtivaValor($this->ativo ?? null) ? 's' : 'n',
			'cpf_cnpj'           => (string)($this->cpf_cnpj ?? ''),
			'email'              => (string)($this->email ?? ''),
			'site'               => (string)($this->site ?? ''),
			'logo'               => (string)($this->logo ?? ''),
			'youtube'            => $this->youtube !== null && $this->youtube !== '' ? (string)$this->youtube : null,
			'instagram'          => $this->instagram !== null && $this->instagram !== '' ? (string)$this->instagram : null,
			'telefone'           => (string)($this->telefone ?? ''),
			'endereco'           => (string)($this->endereco ?? ''),
			'numero'             => (string)($this->numero ?? ''),
			'bairro'             => (string)($this->bairro ?? ''),
			'estado'             => (int)($this->estado ?: 0),
			'cidade'             => (int)($this->cidade ?: 0),
			'cep'                => (string)($this->cep ?? ''),
			'modulos_liberados'  => $this->modulos_liberados,
		];
	}

	public function cadastrar() {
		$obDatabase = new Database('escolas_assinantes');
		$dados = $this->dadosPersistencia();
		if (self::temColunaPlanId()) {
			$dados['plan_id'] = $this->plan_id !== null && $this->plan_id !== ''
				? (int)$this->plan_id
				: null;
		}
		if (self::temColunaValorMensalCustom()) {
			$custom = $this->valor_mensal_custom;
			$dados['valor_mensal_custom'] = ($custom !== null && $custom !== '' && (float)$custom > 0)
				? round((float)$custom, 2)
				: null;
		}
		if (self::temColunasAssinatura()) {
			$dados['dia_vencimento_assinatura'] = max(1, min(28, (int)($this->dia_vencimento_assinatura ?: 10)));
			$dados['assinatura_status'] = in_array((string)($this->assinatura_status ?? ''), ['ativa', 'suspensa', 'trial'], true)
				? (string)$this->assinatura_status
				: 'ativa';
			$dados['assinatura_proximo_vencimento'] = $this->assinatura_proximo_vencimento ?: null;
		}
		if (self::temColunaTrialAte()) {
			$dados['trial_ate'] = !empty($this->trial_ate) ? (string)$this->trial_ate : null;
		}
		if (self::temColunaModeloCertificado()) {
			$dados['modelo_certificado'] = $this->modelo_certificado ?: null;
		}
		if (self::temColunaModeloContrato()) {
			$dados['modelo_contrato_html'] = $this->modelo_contrato_html !== null && trim((string)$this->modelo_contrato_html) !== ''
				? (string)$this->modelo_contrato_html
				: null;
		}
		if (self::temColunaCertificadoFrase()) {
			$dados['certificado_frase_conclusao'] = $this->certificado_frase_conclusao !== null && trim((string)$this->certificado_frase_conclusao) !== ''
				? mb_substr(trim((string)$this->certificado_frase_conclusao), 0, 255)
				: null;
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
		$dados = $this->dadosPersistencia();
		$dados['id_admin'] = (int)($this->id_admin ?: $this->id);
		if (self::temColunaPlanId()) {
			$dados['plan_id'] = $this->plan_id !== null && $this->plan_id !== ''
				? (int)$this->plan_id
				: null;
		}
		if (self::temColunaValorMensalCustom()) {
			$custom = $this->valor_mensal_custom;
			$dados['valor_mensal_custom'] = ($custom !== null && $custom !== '' && (float)$custom > 0)
				? round((float)$custom, 2)
				: null;
		}
		if (self::temColunasAssinatura()) {
			$dados['dia_vencimento_assinatura'] = max(1, min(28, (int)($this->dia_vencimento_assinatura ?: 10)));
			$dados['assinatura_status'] = in_array((string)($this->assinatura_status ?? ''), ['ativa', 'suspensa', 'trial'], true)
				? (string)$this->assinatura_status
				: 'ativa';
			$dados['assinatura_proximo_vencimento'] = $this->assinatura_proximo_vencimento ?: null;
		}
		if (self::temColunaTrialAte()) {
			$dados['trial_ate'] = !empty($this->trial_ate) ? (string)$this->trial_ate : null;
		}
		if (self::temColunaModeloCertificado()) {
			$dados['modelo_certificado'] = $this->modelo_certificado ?: null;
		}
		if (self::temColunaModeloContrato()) {
			$dados['modelo_contrato_html'] = $this->modelo_contrato_html !== null && trim((string)$this->modelo_contrato_html) !== ''
				? (string)$this->modelo_contrato_html
				: null;
		}
		if (self::temColunaCertificadoFrase()) {
			$dados['certificado_frase_conclusao'] = $this->certificado_frase_conclusao !== null && trim((string)$this->certificado_frase_conclusao) !== ''
				? mb_substr(trim((string)$this->certificado_frase_conclusao), 0, 255)
				: null;
		}
		return (new Database('escolas_assinantes'))->update('id = '.(int)$this->id, $dados);
	}

	/**
	 * Atualização operacional pelo Diretor (não altera nome, CNPJ, ativo, plano, módulos).
	 */
	public function atualizarOperacional(): bool {
		$dados = [
			'email'     => (string)($this->email ?? ''),
			'telefone'  => (string)($this->telefone ?? ''),
			'site'      => (string)($this->site ?? ''),
			'logo'      => (string)($this->logo ?? ''),
			'youtube'   => $this->youtube !== null && $this->youtube !== '' ? (string)$this->youtube : null,
			'instagram' => $this->instagram !== null && $this->instagram !== '' ? (string)$this->instagram : null,
			'endereco'  => (string)($this->endereco ?? ''),
			'numero'    => (string)($this->numero ?? ''),
			'bairro'    => (string)($this->bairro ?? ''),
			'estado'    => (int)($this->estado ?: 0),
			'cidade'    => (int)($this->cidade ?: 0),
			'cep'       => (string)($this->cep ?? ''),
		];
		if (self::temColunaModeloCertificado()) {
			$dados['modelo_certificado'] = $this->modelo_certificado ?: null;
		}
		return (bool)(new Database('escolas_assinantes'))->update('id = '.(int)$this->id, $dados);
	}

	/** Atualiza só o HTML do contrato (ou NULL = padrão CTI). */
	public static function salvarModeloContrato(int $idEscola, ?string $html): bool {
		if (!self::temColunaModeloContrato()) {
			return false;
		}
		$valor = ($html !== null && trim($html) !== '') ? $html : null;
		return (bool)(new Database('escolas_assinantes'))->update('id = '.(int)$idEscola, [
			'modelo_contrato_html' => $valor,
		]);
	}

	/** Atualiza frase do certificado (NULL = padrão). */
	public static function salvarFraseCertificado(int $idEscola, ?string $frase): bool {
		if (!self::temColunaCertificadoFrase()) {
			return false;
		}
		$valor = ($frase !== null && trim($frase) !== '') ? mb_substr(trim($frase), 0, 255) : null;
		return (bool)(new Database('escolas_assinantes'))->update('id = '.(int)$idEscola, [
			'certificado_frase_conclusao' => $valor,
		]);
	}

	public function excluir() {
		return (new Database('escolas_assinantes'))->delete('id = '.(int)$this->id);
	}

}

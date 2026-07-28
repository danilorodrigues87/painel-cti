<?php

namespace App\Common\Helpers;

use App\Model\Db\Database;
use App\Model\Entity\Caixa;
use App\Model\Entity\Matriculas;

/**
 * Ciclo de vida comercial da matrícula:
 * 0 = em andamento · 1 = encerrado · 3 = cancelado.
 * Ativa = status 0 e (fim nulo ou fim >= hoje).
 */
class MatriculaStatusHelper {

	public const STATUS_ANDAMENTO = 0;
	public const STATUS_ENCERRADO = 1;
	public const STATUS_CANCELADO = 3;

	public const TIPO_CANCELAMENTO = 'Cancelamento';
	public const TIPO_RENEGOCIACAO = 'Renegociação';

	/** Fragmento SQL: matrícula vigente (sem alias). */
	public static function sqlAtiva(string $alias = 'matriculas'): string {
		$a = $alias !== '' ? rtrim($alias, '.').'.' : '';
		return '('.$a.'status = 0 AND ('.$a.'fim IS NULL OR '.$a.'fim >= CURDATE()))';
	}

	/** @param object|array $matricula */
	public static function ehAtiva($matricula): bool {
		$status = (int)(is_array($matricula) ? ($matricula['status'] ?? 0) : ($matricula->status ?? 0));
		if ($status !== self::STATUS_ANDAMENTO) {
			return false;
		}
		$fim = is_array($matricula) ? ($matricula['fim'] ?? null) : ($matricula->fim ?? null);
		if ($fim === null || $fim === '' || $fim === '0000-00-00') {
			return true;
		}
		return (string)$fim >= date('Y-m-d');
	}

	public static function labelStatus(int $status): string {
		if ($status === self::STATUS_ANDAMENTO) {
			return 'Em andamento';
		}
		if ($status === self::STATUS_ENCERRADO) {
			return 'Encerrado';
		}
		if ($status === self::STATUS_CANCELADO) {
			return 'Cancelado';
		}
		return (string)$status;
	}

	/** Tipos de baixa que não contam como receita real. */
	public static function sqlExcluirNaoReceita(string $colunaTipo = 'tipo_pagamento'): string {
		return '('.$colunaTipo.' IS NULL OR '.$colunaTipo.' = "" OR ('
			.$colunaTipo.' != "'.self::TIPO_CANCELAMENTO.'"'
			.' AND '.$colunaTipo.' != "'.self::TIPO_RENEGOCIACAO.'"))';
	}

	public static function ehBaixaAdministrativa(?string $tipoPagamento): bool {
		$t = trim((string)$tipoPagamento);
		return $t === self::TIPO_CANCELAMENTO || $t === self::TIPO_RENEGOCIACAO;
	}

	/**
	 * Marca como Encerrado matrículas com fim já passado (ainda status 0).
	 * @return int linhas afetadas (estimado)
	 */
	public static function encerrarVencidasTenant(int $idAdmin): int {
		if ($idAdmin <= 0) {
			return 0;
		}
		$db = new Database('matriculas');
		$db->update(
			'id_admin = '.(int)$idAdmin
			.' AND status = '.self::STATUS_ANDAMENTO
			.' AND fim IS NOT NULL AND fim != "0000-00-00" AND fim < CURDATE()',
			['status' => self::STATUS_ENCERRADO]
		);
		return 0;
	}

	public static function encerrarMatricula(int $idMatricula, int $idAdmin): bool {
		if ($idMatricula <= 0 || $idAdmin <= 0) {
			return false;
		}
		if (!TenantHelper::pertenceMatricula($idMatricula, $idAdmin)) {
			return false;
		}
		$m = Matriculas::getMatriculaById($idMatricula);
		if (!$m || (int)$m->status !== self::STATUS_ANDAMENTO) {
			return false;
		}
		return (bool)(new Database('matriculas'))->update(
			'id = '.(int)$idMatricula.' AND id_admin = '.(int)$idAdmin,
			['status' => self::STATUS_ENCERRADO]
		);
	}

	/**
	 * Baixa administrativa R$ 0 nas parcelas abertas do carnê (não apaga histórico).
	 * @return int quantidade baixada
	 */
	public static function cancelarParcelasAbertas(int $idMatricula, int $idAdmin): int {
		if ($idMatricula <= 0 || $idAdmin <= 0) {
			return 0;
		}
		$n = 0;
		$where = 'id_admin = '.(int)$idAdmin
			.' AND id_ref = '.(int)$idMatricula
			.' AND tipo_transacao = "Entrada"'
			.' AND '.FinanceiroAlunoHelper::sqlTituloAberto('status');
		$rs = Caixa::getCaixa($where, 'id ASC');
		$obs = 'Cancelamento matrícula #'.$idMatricula;
		while ($c = $rs->fetchObject(Caixa::class)) {
			$c->status = FinanceiroAlunoHelper::STATUS_PAGO;
			$c->tipo_pagamento = self::TIPO_CANCELAMENTO;
			$c->data_pagamento = date('Y-m-d');
			$c->valor_pago = 0;
			$c->atualizar();
			$desc = (string)($c->descricao ?? '');
			if (strpos($desc, $obs) === false) {
				(new Database('caixa'))->update('id = '.(int)$c->id, [
					'descricao' => mb_substr(trim($desc.' | '.$obs), 0, 250),
					'ultima_alteracao' => date('Y-m-d H:i:s'),
				]);
			}
			$n++;
		}
		return $n;
	}
}

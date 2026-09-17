<?php

namespace App\Common\Helpers;

use App\Model\Entity\Caixa;
use App\Model\Entity\CaixaRecebimento;
use App\Model\Entity\FinanceiroAcordo;
use App\Model\Db\Database;
use App\Session\User\Login as SessionUser;

class CaixaRecebimentoService {

	public const TIPO_COMBINADO = 'Combinado';

	/** @var string[] */
	public static $formasValidas = [
		'Dinheiro', 'Pix', 'Cartão', 'Boleto', 'Transferência', 'QrCode',
	];

	/**
	 * @param array<string,mixed> $postVars
	 * @return array<int,array{tipo_pagamento:string,valor:float}>
	 */
	public static function parseLinhasFromPost(array $postVars): array {
		if (isset($postVars['pagamentos']) && is_array($postVars['pagamentos'])) {
			return self::normalizarLinhas($postVars['pagamentos']);
		}
		$json = trim((string)($postVars['pagamentos_json'] ?? ''));
		if ($json !== '') {
			$decoded = json_decode($json, true);
			if (is_array($decoded)) {
				return self::normalizarLinhas($decoded);
			}
		}
		$tipo = trim((string)($postVars['tipo_pagamento'] ?? ''));
		$valor = 0.0;
		if (isset($postVars['valor_recebido']) && $postVars['valor_recebido'] !== '') {
			$valor = (float)NumeroHelper::removerFormatacaoNumero($postVars['valor_recebido']);
		} elseif (isset($postVars['valor_pago'])) {
			$valor = (float)NumeroHelper::removerFormatacaoNumero($postVars['valor_pago']);
		} elseif (isset($postVars['valor_pagar'])) {
			$valor = (float)NumeroHelper::removerFormatacaoNumero($postVars['valor_pagar']);
		}
		if ($tipo === '' || $valor <= 0) {
			return [];
		}
		return [['tipo_pagamento' => $tipo, 'valor' => round($valor, 2)]];
	}

	/**
	 * @param mixed $raw
	 * @return array<int,array{tipo_pagamento:string,valor:float}>
	 */
	public static function normalizarLinhas($raw): array {
		if (!is_array($raw)) {
			return [];
		}
		$out = [];
		foreach ($raw as $row) {
			if (!is_array($row)) {
				continue;
			}
			$tipo = trim((string)($row['tipo_pagamento'] ?? $row['tipo'] ?? ''));
			$valRaw = $row['valor'] ?? $row['value'] ?? 0;
			$valor = is_numeric($valRaw)
				? (float)$valRaw
				: (float)NumeroHelper::removerFormatacaoNumero((string)$valRaw);
			if ($tipo === '' || $valor <= 0) {
				continue;
			}
			if (!in_array($tipo, self::$formasValidas, true)) {
				continue;
			}
			$out[] = ['tipo_pagamento' => $tipo, 'valor' => round($valor, 2)];
		}
		return $out;
	}

	/** @param array<int,array{tipo_pagamento:string,valor:float}> $linhas */
	public static function resumoTipoPagamento(array $linhas): string {
		if (count($linhas) === 0) {
			return '';
		}
		if (count($linhas) === 1) {
			return $linhas[0]['tipo_pagamento'];
		}
		return self::TIPO_COMBINADO;
	}

	/** Texto para recibo / listagem. */
	public static function descricaoFormas(int $caixaId, int $idAdmin = 0, ?string $fallback = null): string {
		if (!CaixaRecebimento::tabelaExiste()) {
			return (string)($fallback ?? '');
		}
		$rows = CaixaRecebimento::listByCaixa($caixaId, $idAdmin);
		if (empty($rows)) {
			return (string)($fallback ?? '');
		}
		$parts = [];
		foreach ($rows as $r) {
			$parts[] = $r->tipo_pagamento.' R$ '.NumeroHelper::moedaBr((float)$r->valor);
		}
		return implode(' + ', $parts);
	}

	public static function valorDevidoTitulo(Caixa $c, string $dataReferencia = ''): float {
		$dataReferencia = $dataReferencia !== '' ? $dataReferencia : date('Y-m-d');
		$ref = (string)($c->referencia ?? '');
		$idRef = (int)($c->id_ref ?? 0);
		$idAcordo = (int)($c->id_acordo ?? 0);
		if ($idAcordo > 0 || ($idRef > 0 && $ref !== 'Entrada avulsa' && $ref !== 'venda_stq' && $ref !== 'Venda/serviço avulso')) {
			$vals = EncargosContratoHelper::resolverValoresTituloCaixa($c, true, $dataReferencia);
			return round((float)$vals['valor'], 2);
		}
		return round((float)($c->valor ?? 0), 2);
	}

	public static function valorJaRecebido(Caixa $c): float {
		$ja = round((float)($c->valor_pago ?? 0), 2);
		if (CaixaRecebimento::tabelaExiste()) {
			$ja = max($ja, CaixaRecebimento::somaPorCaixa((int)$c->id, (int)$c->id_admin));
		}
		return $ja;
	}

	public static function saldoEmAberto(Caixa $c, string $dataReferencia = ''): float {
		$devido = self::valorDevidoTitulo($c, $dataReferencia);
		return max(0, round($devido - self::valorJaRecebido($c), 2));
	}

	/**
	 * Soma entradas por forma (linhas em caixa_recebimentos + legado só em caixa).
	 * Inclui pagamentos parciais (título ainda em aberto).
	 */
	public static function totalEntradasPorForma(int $idAdmin, string $forma): float {
		if ($idAdmin <= 0 || $forma === '') {
			return 0.0;
		}
		$total = 0.0;
		$esc = addslashes($forma);
		if (CaixaRecebimento::tabelaExiste()) {
			$sql = 'SELECT COALESCE(SUM(r.valor), 0) AS s FROM caixa_recebimentos r '
				.'INNER JOIN caixa c ON c.id = r.caixa_id AND c.id_admin = r.id_admin '
				.'WHERE r.id_admin = '.(int)$idAdmin.' AND r.tipo_pagamento = "'.$esc.'" '
				.'AND c.tipo_transacao = "Entrada" '
				.'AND '.MatriculaStatusHelper::sqlExcluirNaoReceita('c.tipo_pagamento');
			$row = (new Database())->execute($sql)->fetch(\PDO::FETCH_ASSOC);
			$total += (float)($row['s'] ?? 0);
		}
		$sqlLegado = 'SELECT COALESCE(SUM(c.valor_pago), 0) AS s FROM caixa c '
			.'WHERE c.id_admin = '.(int)$idAdmin.' AND c.tipo_transacao = "Entrada" '
			.'AND c.tipo_pagamento = "'.$esc.'" '
			.'AND '.FinanceiroAlunoHelper::sqlTituloPago('c.status').' '
			.'AND '.MatriculaStatusHelper::sqlExcluirNaoReceita('c.tipo_pagamento');
		if (CaixaRecebimento::tabelaExiste()) {
			$sqlLegado .= ' AND NOT EXISTS (SELECT 1 FROM caixa_recebimentos r WHERE r.caixa_id = c.id)';
		}
		$rowL = (new Database())->execute($sqlLegado)->fetch(\PDO::FETCH_ASSOC);
		$total += (float)($rowL['s'] ?? 0);
		return round($total, 2);
	}

	/**
	 * @return array{ja_pago: float, saldo: float, html: string, total_exibir: float}
	 */
	public static function resumoParcialFormulario(float $valorDevido, Caixa $c): array {
		$ja = self::valorJaRecebido($c);
		$saldo = max(0, round($valorDevido - $ja, 2));
		$html = '';
		$tol = FinanceiroAlunoHelper::TOLERANCIA_VALOR;
		if ($ja > $tol) {
			$html = '
  <li class="list-group-item d-flex justify-content-between">
    <span>Já pago (parcial)</span>
    <strong class="text-success">R$ '.NumeroHelper::moedaBr($ja).'</strong>
  </li>';
		}
		$totalExibir = $ja > $tol ? $saldo : $valorDevido;
		return [
			'ja_pago' => $ja,
			'saldo' => $saldo,
			'html' => $html,
			'total_exibir' => $totalExibir,
		];
	}

	/** Valor total devido enviado pelo formulário (face + encargos conforme tela). */
	public static function valorDevidoFromPost(array $postVars, ?Caixa $c = null, string $dataCaixa = ''): float {
		foreach (['valor_devido_titulo', 'valor_pagar', 'valor_pagar_total'] as $key) {
			if (!isset($postVars[$key]) || $postVars[$key] === '') {
				continue;
			}
			$v = (float)NumeroHelper::removerFormatacaoNumero((string)$postVars[$key]);
			if ($v > 0) {
				return round($v, 2);
			}
		}
		if ($c instanceof Caixa) {
			$dataCaixa = $dataCaixa !== '' ? $dataCaixa : date('Y-m-d');
			return self::valorDevidoTitulo($c, $dataCaixa);
		}
		return 0.0;
	}

	public static function tituloParcial(Caixa $c): bool {
		if (!FinanceiroAlunoHelper::tituloAberto($c->status)) {
			return false;
		}
		$ja = (float)($c->valor_pago ?? 0);
		return $ja > FinanceiroAlunoHelper::TOLERANCIA_VALOR;
	}

	/**
	 * Registra linhas de pagamento e atualiza caixa (combinado / parcial).
	 *
	 * @param array<int,array{tipo_pagamento:string,valor:float}> $linhas
	 * @return array{ok:bool,message:string,parcial?:bool,saldo?:float,quitado?:bool}
	 */
	public static function registrar(
		int $idAdmin,
		int $caixaId,
		array $linhas,
		string $dataPagamento = '',
		bool $permitirParcial = true,
		?float $valorDevidoOverride = null,
		?int $idUsuario = null
	): array {
		if (!CaixaRecebimento::tabelaExiste()) {
			return ['ok' => false, 'message' => 'Execute database/caixa_recebimentos.sql no phpMyAdmin.'];
		}
		if ($caixaId <= 0) {
			return ['ok' => false, 'message' => 'Título inválido.'];
		}
		$linhas = self::normalizarLinhas($linhas);
		if (empty($linhas)) {
			return ['ok' => false, 'message' => 'Informe ao menos uma forma de pagamento com valor.'];
		}

		$c = Caixa::getCaixaById($caixaId);
		if (!$c instanceof Caixa || (int)$c->id_admin !== $idAdmin) {
			return ['ok' => false, 'message' => 'Título não encontrado.'];
		}
		if (FinanceiroAlunoHelper::tituloPago($c->status)) {
			return ['ok' => false, 'message' => 'Este título já está quitado.'];
		}

		if ($dataPagamento === '') {
			$dataPagamento = date('Y-m-d H:i:s');
		}
		$ts = strtotime($dataPagamento);
		if ($ts === false) {
			return ['ok' => false, 'message' => 'Data de pagamento inválida.'];
		}
		$dataGravacao = date('Y-m-d H:i:s', $ts);
		$dataCaixa = date('Y-m-d', $ts);

		$devido = $valorDevidoOverride !== null && $valorDevidoOverride > 0
			? round((float)$valorDevidoOverride, 2)
			: self::valorDevidoTitulo($c, $dataCaixa);

		$jaPago = self::valorJaRecebido($c);
		$saldo = max(0, round($devido - $jaPago, 2));

		$somaLinhas = 0.0;
		foreach ($linhas as $ln) {
			$somaLinhas += $ln['valor'];
		}
		$somaLinhas = round($somaLinhas, 2);

		if ($somaLinhas > $saldo + FinanceiroAlunoHelper::TOLERANCIA_VALOR) {
			return [
				'ok' => false,
				'message' => 'Valor informado (R$ '.NumeroHelper::moedaBr($somaLinhas).') é maior que o saldo (R$ '.NumeroHelper::moedaBr($saldo).').',
			];
		}

		$tol = FinanceiroAlunoHelper::TOLERANCIA_VALOR;
		$quitado = ($somaLinhas + $jaPago) >= ($devido - $tol);
		if (!$quitado && !$permitirParcial) {
			return ['ok' => false, 'message' => 'Valor insuficiente para quitar. Saldo: R$ '.NumeroHelper::moedaBr($saldo).'.'];
		}
		if (!$quitado && $somaLinhas <= $tol) {
			return ['ok' => false, 'message' => 'Informe um valor maior que zero.'];
		}

		if ($idUsuario === null) {
			$user = SessionUser::getUserLogedData();
			$idUsuario = (int)($user['usuario']['id'] ?? 0);
		}

		foreach ($linhas as $ln) {
			$rec = new CaixaRecebimento();
			$rec->id_admin = $idAdmin;
			$rec->caixa_id = $caixaId;
			$rec->tipo_pagamento = $ln['tipo_pagamento'];
			$rec->valor = $ln['valor'];
			$rec->data_pagamento = $dataGravacao;
			$rec->id_usuario = $idUsuario > 0 ? $idUsuario : null;
			$rec->inserir();
		}

		$novoTotal = round($jaPago + $somaLinhas, 2);
		$todasLinhas = [];
		foreach (CaixaRecebimento::listByCaixa($caixaId, $idAdmin) as $r) {
			$todasLinhas[] = ['tipo_pagamento' => (string)$r->tipo_pagamento, 'valor' => (float)$r->valor];
		}

		$ob = new Caixa();
		$ob->id = $caixaId;
		$ob->valor_pago = $novoTotal;
		$ob->data_pagamento = $dataCaixa;
		$ob->tipo_pagamento = self::resumoTipoPagamento($todasLinhas);
		$ob->status = $quitado ? FinanceiroAlunoHelper::STATUS_PAGO : FinanceiroAlunoHelper::STATUS_ABERTO;
		$ob->atualizar();

		$novoSaldo = max(0, round($devido - $novoTotal, 2));
		$msg = $quitado ? 'Pagamento registrado. Título quitado.' : 'Pagamento parcial registrado. Saldo: R$ '.NumeroHelper::moedaBr($novoSaldo).'.';

		return [
			'ok' => true,
			'message' => $msg,
			'parcial' => !$quitado,
			'saldo' => $novoSaldo,
			'quitado' => $quitado,
		];
	}

	/**
	 * Divide linhas de pagamento proporcionalmente entre parcelas.
	 *
	 * @param array<int,array{tipo_pagamento:string,valor:float}> $linhas
	 * @param array<int,float> $pesos caixa_id => valor do item
	 * @return array<int,array<int,array{tipo_pagamento:string,valor:float}>>
	 */
	public static function ratearLinhas(array $linhas, array $pesos): array {
		$totalPeso = array_sum($pesos);
		if ($totalPeso <= 0 || empty($linhas)) {
			return [];
		}
		$ids = array_keys($pesos);
		$out = [];
		foreach ($ids as $id) {
			$out[$id] = [];
		}
		foreach ($linhas as $ln) {
			$restante = $ln['valor'];
			$count = count($ids);
			foreach ($ids as $i => $cid) {
				$isLast = ($i === $count - 1);
				$parte = $isLast
					? round($restante, 2)
					: round($ln['valor'] * ($pesos[$cid] / $totalPeso), 2);
				if (!$isLast) {
					$restante = round($restante - $parte, 2);
				}
				if ($parte > 0) {
					$out[$cid][] = ['tipo_pagamento' => $ln['tipo_pagamento'], 'valor' => $parte];
				}
			}
		}
		return $out;
	}

	public static function renderFormPagamento(array $opts = []): string {
		$prefix = preg_replace('/[^a-z0-9_-]/i', '', (string)($opts['prefix'] ?? 'pm'));
		if ($prefix === '') {
			$prefix = 'pm';
		}
		$totalInputId = (string)($opts['total_input_id'] ?? 'valor_pagar');
		$permitirParcial = !empty($opts['permitir_parcial']);
		$jaPago = round((float)($opts['ja_pago'] ?? 0), 2);
		$hint = $permitirParcial
			? 'Você pode informar menos que o total para pagamento parcial.'
			: 'A soma das formas deve fechar o total a pagar.';

		$optsHtml = '';
		foreach (self::$formasValidas as $f) {
			$optsHtml .= '<option value="'.htmlspecialchars($f, ENT_QUOTES, 'UTF-8').'">'.$f.'</option>';
		}

		return '
<div class="pagamento-multiplo border rounded p-2 mb-2" data-prefix="'.htmlspecialchars($prefix, ENT_QUOTES, 'UTF-8').'" data-total-input="'.htmlspecialchars($totalInputId, ENT_QUOTES, 'UTF-8').'" data-permitir-parcial="'.($permitirParcial ? '1' : '0').'" data-ja-pago="'.htmlspecialchars((string)$jaPago, ENT_QUOTES, 'UTF-8').'">
  <div class="d-flex justify-content-between align-items-center mb-2">
    <label class="form-label mb-0 fw-semibold">Formas de pagamento</label>
    <button type="button" class="btn btn-sm btn-outline-secondary btn-pm-add">+ Forma</button>
  </div>
  <p class="small text-muted mb-2">'.$hint.'</p>
  <div class="table-responsive">
    <table class="table table-sm mb-2">
      <thead><tr><th>Forma</th><th style="width:140px">Valor (R$)</th><th style="width:40px"></th></tr></thead>
      <tbody class="pm-linhas"></tbody>
    </table>
  </div>
  <div class="row small g-2">
    <div class="col-md-4"><span class="text-muted">Total informado:</span> <strong class="pm-soma">R$ 0,00</strong></div>
    <div class="col-md-4"><span class="text-muted">Total a pagar:</span> <strong class="pm-devido">R$ 0,00</strong></div>
    <div class="col-md-4 pm-saldo-wrap"><span class="text-muted">Saldo após:</span> <strong class="pm-saldo">R$ 0,00</strong></div>
  </div>
  <input type="hidden" name="pagamentos_json" class="pm-json" value="">
  <template class="pm-row-template">
    <tr class="pm-row">
      <td><select class="form-select form-select-sm pm-tipo" required><option value="">—</option>'.$optsHtml.'</select></td>
      <td><input type="text" class="form-control form-control-sm pm-valor" inputmode="decimal" required placeholder="0,00"></td>
      <td><button type="button" class="btn btn-sm btn-outline-danger btn-pm-rm" title="Remover">&times;</button></td>
    </tr>
  </template>
</div>';
	}
}

<?php

namespace App\Common\Helpers;

use App\Model\Entity\Caixa;
use App\Model\Entity\FinanceiroAcordo;
use App\Model\Entity\Matriculas;
use App\Model\Entity\Trilhas;
use App\Model\Entity\User;
use App\Model\Db\Database;
use App\Session\User\Login as SessionUser;

/**
 * Extrato consolidado do aluno (todas as matrículas + acordos) e renegociação.
 */
class FinanceiroAlunoHelper {

	public static function tituloAberto($status): bool {
		if ($status === 0 || $status === '0') {
			return true;
		}
		return (string)$status === 'Em aberto';
	}

	public static function tituloPago($status): bool {
		if ($status === 1 || $status === '1') {
			return true;
		}
		return (string)$status === 'Pago';
	}

	/**
	 * @return array{ok:bool,message?:string,aluno?:array,matriculas?:array,acordos?:array,titulos?:array,totais?:array}
	 */
	public static function extrato(int $idAdmin, int $idAluno): array {
		$aluno = User::getUserById($idAluno);
		if (!$aluno || (int)$aluno->id_admin !== $idAdmin || ($aluno->nivel ?? '') !== 'Cliente') {
			return ['ok' => false, 'message' => 'Aluno não encontrado.'];
		}

		$matriculasResumo = [];
		$titulos = [];
		$hoje = date('Y-m-d');

		$mats = Matriculas::getMatriculas(
			'id_admin = '.(int)$idAdmin.' AND id_aluno = '.(int)$idAluno,
			'id DESC'
		);
		while ($m = $mats->fetchObject(Matriculas::class)) {
			$trilha = Trilhas::getTrilhaById((int)$m->id_trilha);
			$nomeCurso = $trilha ? (string)$trilha->nome : ('Trilha #'.(int)$m->id_trilha);
			$statusMat = (int)($m->status ?? 0);
			$statusLabel = $statusMat === 0 ? 'Em andamento' : ($statusMat === 1 ? 'Encerrada' : ($statusMat === 3 ? 'Cancelada' : (string)$statusMat));

			$pago = 0.0;
			$aberto = 0.0;
			$vencido = 0.0;
			$qtd = 0;

			$cx = Caixa::getCaixa(
				'id_admin = '.(int)$idAdmin
				.' AND id_ref = '.(int)$m->id
				.' AND tipo_transacao = "Entrada"',
				'vencimento ASC, id ASC'
			);
			while ($c = $cx->fetchObject(Caixa::class)) {
				$qtd++;
				$row = self::mapTitulo($c, 'matricula', (int)$m->id, $nomeCurso);
				$titulos[] = $row;
				if ($row['status'] === 'pago') {
					$pago += (float)$row['valor_pago'] > 0 ? (float)$row['valor_pago'] : (float)$row['valor'];
				} elseif ($row['status'] === 'vencido') {
					$vencido += (float)$row['valor'];
					$aberto += (float)$row['valor'];
				} else {
					$aberto += (float)$row['valor'];
				}
			}

			$matriculasResumo[] = [
				'id' => (int)$m->id,
				'curso' => $nomeCurso,
				'status' => $statusLabel,
				'status_code' => $statusMat,
				'tipo_parcelamento' => (string)($m->tipo_parcelamento ?? ''),
				'qtd_titulos' => $qtd,
				'total_pago' => round($pago, 2),
				'total_aberto' => round($aberto, 2),
				'total_vencido' => round($vencido, 2),
			];
		}

		$acordosResumo = [];
		if (FinanceiroAcordo::tabelasExistem() && FinanceiroAcordo::caixaTemIdAcordo()) {
			foreach (FinanceiroAcordo::listByAluno($idAluno, $idAdmin) as $ac) {
				$pago = 0.0;
				$aberto = 0.0;
				$vencido = 0.0;
				$qtd = 0;
				$parcelasAcordo = [];
				$label = 'Acordo #'.(int)$ac->id;
				$cx = Caixa::getCaixa(
					'id_admin = '.(int)$idAdmin
					.' AND id_acordo = '.(int)$ac->id
					.' AND tipo_transacao = "Entrada"',
					'vencimento ASC, id ASC'
				);
				while ($c = $cx->fetchObject(Caixa::class)) {
					$qtd++;
					$row = self::mapTitulo($c, 'acordo', (int)$ac->id, $label);
					$parcelasAcordo[] = $row;
					$titulos[] = $row;
					if ($row['status'] === 'pago') {
						$pago += (float)$row['valor_pago'] > 0 ? (float)$row['valor_pago'] : (float)$row['valor'];
					} elseif ($row['status'] === 'vencido') {
						$vencido += (float)$row['valor'];
						$aberto += (float)$row['valor'];
					} else {
						$aberto += (float)$row['valor'];
					}
				}
				$acordosResumo[] = [
					'id' => (int)$ac->id,
					'label' => $label,
					'valor_total' => (float)$ac->valor_total,
					'qtd_parcelas' => (int)$ac->qtd_parcelas,
					'status' => (string)$ac->status,
					'observacao' => (string)($ac->observacao ?? ''),
					'created_at' => (string)($ac->created_at ?? ''),
					'qtd_titulos' => $qtd,
					'total_pago' => round($pago, 2),
					'total_aberto' => round($aberto, 2),
					'total_vencido' => round($vencido, 2),
					'parcelas' => $parcelasAcordo,
				];
			}
		}

		usort($titulos, static function ($a, $b) {
			return strcmp((string)$a['vencimento'], (string)$b['vencimento']);
		});

		$totais = [
			'pago' => 0.0,
			'aberto' => 0.0,
			'vencido' => 0.0,
			'titulos' => count($titulos),
		];
		foreach ($titulos as $t) {
			if ($t['status'] === 'pago') {
				$totais['pago'] += (float)$t['valor_pago'] > 0 ? (float)$t['valor_pago'] : (float)$t['valor'];
			} elseif ($t['status'] === 'vencido') {
				$totais['vencido'] += (float)$t['valor'];
				$totais['aberto'] += (float)$t['valor'];
			} elseif ($t['status'] === 'aberto') {
				$totais['aberto'] += (float)$t['valor'];
			}
		}
		$totais['pago'] = round($totais['pago'], 2);
		$totais['aberto'] = round($totais['aberto'], 2);
		$totais['vencido'] = round($totais['vencido'], 2);

		return [
			'ok' => true,
			'aluno' => [
				'id' => (int)$aluno->id,
				'nome' => (string)$aluno->nome,
				'email' => (string)$aluno->email,
				'cpf' => (string)($aluno->cpf ?? ''),
				'whatsapp' => (string)($aluno->whatsapp ?? ''),
			],
			'matriculas' => $matriculasResumo,
			'acordos' => $acordosResumo,
			'titulos' => $titulos,
			'totais' => $totais,
			'pode_renegociar' => FinanceiroAcordo::tabelasExistem() && FinanceiroAcordo::caixaTemIdAcordo(),
			'hoje' => $hoje,
		];
	}

	/** @return array<string,mixed> */
	private static function mapTitulo(Caixa $c, string $origem, int $origemId, string $origemLabel): array {
		$hoje = date('Y-m-d');
		$venc = (string)($c->vencimento ?? '');
		$status = 'pago';
		if (self::tituloAberto($c->status)) {
			$status = ($venc !== '' && $venc < $hoje) ? 'vencido' : 'aberto';
		} elseif (!self::tituloPago($c->status)) {
			$status = self::tituloAberto($c->status) ? 'aberto' : 'pago';
		}
		return [
			'id' => (int)$c->id,
			'origem' => $origem,
			'origem_id' => $origemId,
			'origem_label' => $origemLabel,
			'descricao' => (string)($c->descricao ?? ''),
			'referencia' => (string)($c->referencia ?? ''),
			'valor' => (float)($c->valor ?? 0),
			'valor_pago' => (float)($c->valor_pago ?? 0),
			'vencimento' => $venc,
			'data_pagamento' => (string)($c->data_pagamento ?? ''),
			'tipo_pagamento' => (string)($c->tipo_pagamento ?? ''),
			'status' => $status,
			'status_raw' => (string)($c->status ?? ''),
			'selecionavel' => $status === 'aberto' || $status === 'vencido',
		];
	}

	/**
	 * Renegocia títulos em aberto: marca como Renegociação e cria novo acordo + parcelas.
	 *
	 * @param int[] $idsTitulos
	 * @return array{ok:bool,message:string,id_acordo?:int}
	 */
	public static function renegociar(
		int $idAdmin,
		int $idAluno,
		array $idsTitulos,
		float $valorTotal,
		int $qtdParcelas,
		string $primeiroVencimento,
		string $observacao = ''
	): array {
		if (!FinanceiroAcordo::tabelasExistem() || !FinanceiroAcordo::caixaTemIdAcordo()) {
			return ['ok' => false, 'message' => 'Execute database/financeiro_acordos.sql no phpMyAdmin.'];
		}
		$aluno = User::getUserById($idAluno);
		if (!$aluno || (int)$aluno->id_admin !== $idAdmin || ($aluno->nivel ?? '') !== 'Cliente') {
			return ['ok' => false, 'message' => 'Aluno inválido.'];
		}
		$idsTitulos = array_values(array_unique(array_filter(array_map('intval', $idsTitulos))));
		if (empty($idsTitulos)) {
			return ['ok' => false, 'message' => 'Selecione ao menos um título em aberto.'];
		}
		if ($valorTotal <= 0) {
			return ['ok' => false, 'message' => 'Informe o valor total do acordo.'];
		}
		$qtdParcelas = max(1, min(120, $qtdParcelas));
		$ts = strtotime($primeiroVencimento);
		if ($ts === false) {
			return ['ok' => false, 'message' => 'Data do primeiro vencimento inválida.'];
		}
		$primeiroVencimento = date('Y-m-d', $ts);
		$diaVenc = (int)date('d', $ts);

		$titulosOk = [];
		foreach ($idsTitulos as $idCx) {
			$c = Caixa::getCaixaById($idCx);
			if (!$c instanceof Caixa || (int)$c->id_admin !== $idAdmin) {
				return ['ok' => false, 'message' => 'Título #'.$idCx.' inválido.'];
			}
			if (!self::tituloAberto($c->status)) {
				return ['ok' => false, 'message' => 'Título #'.$idCx.' não está em aberto.'];
			}
			// Pertence ao aluno?
			if ((int)($c->id_acordo ?? 0) > 0) {
				$ac = FinanceiroAcordo::getById((int)$c->id_acordo);
				if (!$ac || (int)$ac->id_aluno !== $idAluno || (int)$ac->id_admin !== $idAdmin) {
					return ['ok' => false, 'message' => 'Título #'.$idCx.' não pertence a este aluno.'];
				}
			} else {
				$idRef = (int)($c->id_ref ?? 0);
				if ($idRef <= 0) {
					return ['ok' => false, 'message' => 'Título #'.$idCx.' sem vínculo.'];
				}
				$m = Matriculas::getMatriculaById($idRef);
				if (!$m || (int)$m->id_admin !== $idAdmin || (int)$m->id_aluno !== $idAluno) {
					return ['ok' => false, 'message' => 'Título #'.$idCx.' não pertence a este aluno.'];
				}
			}
			$titulosOk[] = $c;
		}

		$userLoged = SessionUser::getUserLogedData();
		$idUser = (int)($userLoged['usuario']['id'] ?? 0);
		$valorParcela = round($valorTotal / $qtdParcelas, 2);
		// Ajuste centavos na última parcela
		$somaParc = round($valorParcela * ($qtdParcelas - 1), 2);
		$ultimaParc = round($valorTotal - $somaParc, 2);

		$acordo = new FinanceiroAcordo();
		$acordo->id_admin = $idAdmin;
		$acordo->id_aluno = $idAluno;
		$acordo->valor_total = $valorTotal;
		$acordo->valor_parcela = $valorParcela;
		$acordo->qtd_parcelas = $qtdParcelas;
		$acordo->dia_vencimento = $diaVenc;
		$acordo->primeiro_vencimento = $primeiroVencimento;
		$acordo->observacao = trim($observacao) !== '' ? trim($observacao) : null;
		$acordo->ids_titulos_origem = json_encode($idsTitulos, JSON_UNESCAPED_UNICODE);
		$acordo->status = 'ativo';
		$acordo->id_usuario = $idUser > 0 ? $idUser : null;
		$idAcordo = $acordo->cadastrar();
		if ($idAcordo <= 0) {
			return ['ok' => false, 'message' => 'Falha ao criar o acordo.'];
		}

		$obsBaixa = 'Renegociação → Acordo #'.$idAcordo;
		foreach ($titulosOk as $c) {
			$c->status = 1;
			$c->tipo_pagamento = 'Renegociação';
			$c->data_pagamento = date('Y-m-d');
			$c->valor_pago = 0;
			$c->atualizar();
			// Anexa nota na descrição se ainda não tiver
			$desc = (string)($c->descricao ?? '');
			if (strpos($desc, 'Acordo #'.$idAcordo) === false) {
				(new Database('caixa'))->update('id = '.(int)$c->id, [
					'descricao' => mb_substr(trim($desc.' | '.$obsBaixa), 0, 250),
					'ultima_alteracao' => date('Y-m-d H:i:s'),
				]);
			}
		}

		$nomeAluno = (string)$aluno->nome;
		$venc = $primeiroVencimento;
		for ($n = 1; $n <= $qtdParcelas; $n++) {
			$valorN = ($n === $qtdParcelas) ? $ultimaParc : $valorParcela;
			$ob = new Caixa();
			$ob->id_admin = $idAdmin;
			$ob->descricao = 'Acordo #'.$idAcordo.' '.$nomeAluno.' parc '.$n.'/'.$qtdParcelas;
			$ob->tipo_transacao = 'Entrada';
			$ob->valor = $valorN;
			$ob->vencimento = $venc;
			$ob->referencia = 'Acordo financeiro';
			$ob->id_ref = 0;
			$ob->id_acordo = $idAcordo;
			$ob->status = 'Em aberto';
			$ob->tipo_pagamento = '';
			$ob->valor_pago = 0;
			$ob->data_pagamento = null;
			$ob->txt_id = '';
			$ob->pix_copia_cola = '';
			$ob->nosso_numero = '';
			$ob->lancarMovimentacao();

			// Próximo mês, mesmo dia
			$tsNext = strtotime($venc.' +1 month');
			if ($tsNext === false) {
				break;
			}
			// Mantém dia de vencimento desejado
			$y = (int)date('Y', $tsNext);
			$m = (int)date('m', $tsNext);
			$d = min($diaVenc, (int)date('t', strtotime(sprintf('%04d-%02d-01', $y, $m))));
			$venc = sprintf('%04d-%02d-%02d', $y, $m, $d);
		}

		return [
			'ok' => true,
			'message' => 'Acordo #'.$idAcordo.' criado com '.$qtdParcelas.' parcela(s).',
			'id_acordo' => $idAcordo,
		];
	}

	/**
	 * Payload para API aluno (só leitura).
	 * @return array{hasFinance:bool,totals:array,items:array}
	 */
	public static function forStudentApi(int $idAdmin, int $idAluno): array {
		$res = self::extrato($idAdmin, $idAluno);
		if (empty($res['ok'])) {
			return ['hasFinance' => false, 'totals' => ['paid' => 0, 'open' => 0, 'overdue' => 0], 'items' => []];
		}
		$items = [];
		foreach ($res['titulos'] as $t) {
			$items[] = [
				'id' => (string)$t['id'],
				'origin' => $t['origem_label'],
				'description' => $t['descricao'],
				'amount' => (float)$t['valor'],
				'paidAmount' => (float)$t['valor_pago'],
				'dueDate' => $t['vencimento'] ? date('c', strtotime($t['vencimento'])) : null,
				'paidAt' => !empty($t['data_pagamento']) && $t['data_pagamento'] !== '0000-00-00'
					? date('c', strtotime($t['data_pagamento']))
					: null,
				'status' => $t['status'] === 'vencido' ? 'overdue' : ($t['status'] === 'pago' ? 'paid' : 'open'),
				'paymentType' => $t['tipo_pagamento'] ?: null,
			];
		}
		$tot = $res['totais'];
		return [
			'hasFinance' => count($items) > 0,
			'totals' => [
				'paid' => (float)$tot['pago'],
				'open' => (float)$tot['aberto'],
				'overdue' => (float)$tot['vencido'],
			],
			'items' => $items,
		];
	}

	/**
	 * Baixa manual de um título do aluno (matrícula ou acordo).
	 * @return array{ok:bool,message:string}
	 */
	public static function darBaixa(
		int $idAdmin,
		int $idAluno,
		int $idTitulo,
		float $valorPago,
		string $tipoPagamento,
		string $dataPagamento = ''
	): array {
		if ($idTitulo <= 0) {
			return ['ok' => false, 'message' => 'Título inválido.'];
		}
		$c = Caixa::getCaixaById($idTitulo);
		if (!$c instanceof Caixa || (int)$c->id_admin !== $idAdmin) {
			return ['ok' => false, 'message' => 'Título não encontrado.'];
		}
		if ((string)($c->tipo_transacao ?? '') !== 'Entrada') {
			return ['ok' => false, 'message' => 'Somente títulos de entrada.'];
		}
		if (!self::tituloAberto($c->status)) {
			return ['ok' => false, 'message' => 'Este título já está pago.'];
		}

		// Pertence ao aluno?
		if ((int)($c->id_acordo ?? 0) > 0) {
			if (!FinanceiroAcordo::tabelasExistem()) {
				return ['ok' => false, 'message' => 'Tabelas de acordo ausentes.'];
			}
			$ac = FinanceiroAcordo::getById((int)$c->id_acordo);
			if (!$ac || (int)$ac->id_aluno !== $idAluno || (int)$ac->id_admin !== $idAdmin) {
				return ['ok' => false, 'message' => 'Título não pertence a este aluno.'];
			}
		} else {
			$idRef = (int)($c->id_ref ?? 0);
			if ($idRef <= 0) {
				return ['ok' => false, 'message' => 'Título sem vínculo com aluno.'];
			}
			$m = Matriculas::getMatriculaById($idRef);
			if (!$m || (int)$m->id_admin !== $idAdmin || (int)$m->id_aluno !== $idAluno) {
				return ['ok' => false, 'message' => 'Título não pertence a este aluno.'];
			}
		}

		$tipoPagamento = trim($tipoPagamento);
		if ($tipoPagamento === '') {
			return ['ok' => false, 'message' => 'Selecione a forma de pagamento.'];
		}
		if ($valorPago <= 0) {
			$valorPago = (float)$c->valor;
		}
		if ($dataPagamento === '') {
			$dataPagamento = date('Y-m-d');
		}
		$ts = strtotime($dataPagamento);
		if ($ts === false) {
			return ['ok' => false, 'message' => 'Data de pagamento inválida.'];
		}

		$c->valor_pago = $valorPago;
		$c->data_pagamento = date('Y-m-d', $ts);
		$c->tipo_pagamento = $tipoPagamento;
		$c->status = 1;
		$c->atualizar();

		return ['ok' => true, 'message' => 'Baixa registrada.'];
	}

	/**
	 * Baixa em lote: cada título pelo valor integral.
	 * @param int[] $idsTitulos
	 * @return array{ok:bool,message:string,baixados?:int}
	 */
	public static function darBaixaLote(
		int $idAdmin,
		int $idAluno,
		array $idsTitulos,
		string $tipoPagamento,
		string $dataPagamento = ''
	): array {
		$idsTitulos = array_values(array_unique(array_filter(array_map('intval', $idsTitulos))));
		if (empty($idsTitulos)) {
			return ['ok' => false, 'message' => 'Selecione ao menos uma parcela.'];
		}
		$tipoPagamento = trim($tipoPagamento);
		if ($tipoPagamento === '') {
			return ['ok' => false, 'message' => 'Selecione a forma de pagamento.'];
		}
		$ok = 0;
		$erros = [];
		foreach ($idsTitulos as $idTitulo) {
			$c = Caixa::getCaixaById($idTitulo);
			$valor = $c instanceof Caixa ? (float)$c->valor : 0;
			$res = self::darBaixa($idAdmin, $idAluno, $idTitulo, $valor, $tipoPagamento, $dataPagamento);
			if (!empty($res['ok'])) {
				$ok++;
			} else {
				$erros[] = '#'.$idTitulo.': '.($res['message'] ?? 'erro');
			}
		}
		if ($ok === 0) {
			return ['ok' => false, 'message' => $erros ? implode(' ', $erros) : 'Nenhuma parcela baixada.'];
		}
		$msg = $ok.' parcela(s) baixada(s).';
		if ($erros) {
			$msg .= ' Alguns falharam: '.implode(' ', $erros);
		}
		return ['ok' => true, 'message' => $msg, 'baixados' => $ok];
	}
}

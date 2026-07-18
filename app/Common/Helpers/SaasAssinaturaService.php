<?php

namespace App\Common\Helpers;

use App\Model\Entity\EscolasAssinantes;
use App\Model\Entity\PlanosAssinatura;
use App\Model\Entity\SaasFatura;
use App\Model\Db\Database;

class SaasAssinaturaService {

	public const GRACE_DIAS = 5;

	public static function temColunasAssinaturaEscola(): bool {
		return EscolasAssinantes::temColunasAssinatura();
	}

	public static function temColunaValorMensal(): bool {
		return PlanosAssinatura::temColunaValorMensal();
	}

	/**
	 * Gera (ou reutiliza) fatura do mês + PIX CTI.
	 * @return array{ok:bool,message:string,fatura?:array}
	 */
	public static function gerarFaturaEscola(int $idAdmin, ?string $competencia = null): array {
		if (!SaasFatura::tabelaExiste()) {
			return ['ok' => false, 'message' => 'Execute database/saas_assinatura.sql'];
		}
		$escola = EscolasAssinantes::getEscolaById($idAdmin);
		if (!$escola instanceof EscolasAssinantes) {
			return ['ok' => false, 'message' => 'Escola não encontrada.'];
		}

		$competencia = $competencia ?: date('Y-m');
		if (!preg_match('/^\d{4}-\d{2}$/', $competencia)) {
			return ['ok' => false, 'message' => 'Competência inválida.'];
		}

		$existente = SaasFatura::getPorEscolaCompetencia($idAdmin, $competencia);
		if ($existente instanceof SaasFatura) {
			if ($existente->status === 'pago') {
				return ['ok' => true, 'message' => 'Fatura já paga.', 'fatura' => self::formatar($existente, $escola)];
			}
			if (empty($existente->pix_copia_cola) || empty($existente->mp_payment_id)) {
				self::anexarPix($existente, $escola);
			}
			return ['ok' => true, 'message' => 'Fatura já existia.', 'fatura' => self::formatar($existente, $escola)];
		}

		$planId = EscolasAssinantes::temColunaPlanId() ? (int)($escola->plan_id ?? 0) : 0;
		$valor = 0.0;
		if ($planId > 0 && self::temColunaValorMensal()) {
			$plano = PlanosAssinatura::getById($planId);
			if ($plano instanceof PlanosAssinatura) {
				$valor = round((float)($plano->valor_mensal ?? 0), 2);
			}
		}
		if ($valor <= 0) {
			return ['ok' => false, 'message' => 'Plano sem valor mensal. Defina o preço no Master → Planos.'];
		}

		$dia = self::temColunasAssinaturaEscola()
			? max(1, min(28, (int)($escola->dia_vencimento_assinatura ?? 10)))
			: 10;
		[$ano, $mes] = explode('-', $competencia);
		$vencimento = sprintf('%04d-%02d-%02d', (int)$ano, (int)$mes, $dia);
		if (!checkdate((int)$mes, $dia, (int)$ano)) {
			$vencimento = date('Y-m-t', strtotime($ano.'-'.$mes.'-01'));
		}

		$fat = new SaasFatura;
		$fat->id_admin = $idAdmin;
		$fat->plan_id = $planId > 0 ? $planId : null;
		$fat->competencia = $competencia;
		$fat->valor = $valor;
		$fat->vencimento = $vencimento;
		$fat->status = 'aberta';
		if (!$fat->cadastrar()) {
			return ['ok' => false, 'message' => 'Falha ao criar fatura.'];
		}

		$pixOk = self::anexarPix($fat, $escola);
		self::atualizarProximoVencimentoEscola($escola, $vencimento);

		$msg = 'Fatura gerada.';
		if (!$pixOk) {
			$err = \App\Common\Gateways\MercadoPago\Pix::getUltimoErro();
			$msg = MercadoPagoCtiHelper::configurado()
				? ('Fatura criada, mas PIX falhou'.($err ? ': '.$err : '.'))
				: 'Fatura criada. Configure MP_CTI_ACCESS_TOKEN para gerar PIX.';
		}
		return ['ok' => true, 'message' => $msg, 'fatura' => self::formatar($fat, $escola)];
	}

	public static function anexarPix(SaasFatura $fat, ?EscolasAssinantes $escola = null): bool {
		$pix = MercadoPagoCtiHelper::pix();
		if (!$pix) {
			return false;
		}
		if (!$escola instanceof EscolasAssinantes) {
			$escola = EscolasAssinantes::getEscolaById((int)$fat->id_admin);
		}
		$nomeEscola = $escola instanceof EscolasAssinantes ? (string)$escola->nome : 'Escola';
		$email = $escola instanceof EscolasAssinantes ? trim((string)($escola->email ?? '')) : '';
		$fallback = MercadoPagoCtiHelper::payerEmailFallback();

		$cob = $pix->criarCobrancaPix([
			'valor'                => $fat->valor,
			'descricao'            => 'Assinatura Painel CTI '.$fat->competencia.' — '.$nomeEscola,
			'vencimento'           => $fat->vencimento,
			'external_reference'   => 'saas:'.(int)$fat->id,
			'notification_url'     => MercadoPagoCtiHelper::webhookUrl(),
			'statement_descriptor' => 'CTI ASSINATURA',
			'pagador_nome'         => $nomeEscola,
			'pagador_email'        => $email,
			'email_fallback'       => $fallback,
		]);
		if (!is_array($cob) || empty($cob['id']) || empty($cob['copia_cola'])) {
			return false;
		}
		$fat->mp_payment_id = $cob['id'];
		$fat->pix_copia_cola = $cob['copia_cola'];
		$fat->atualizar();
		return true;
	}

	public static function marcarPaga(SaasFatura $fat, ?string $pagoEm = null): bool {
		$fat->status = 'pago';
		$fat->pago_em = $pagoEm ?: date('Y-m-d H:i:s');
		$fat->atualizar();

		$escola = EscolasAssinantes::getEscolaById((int)$fat->id_admin);
		if ($escola instanceof EscolasAssinantes) {
			$escola->ativo = 's';
			if (self::temColunasAssinaturaEscola()) {
				$escola->assinatura_status = 'ativa';
				$escola->assinatura_proximo_vencimento = $fat->vencimento;
				(new Database('escolas_assinantes'))->update('id = '.(int)$escola->id, [
					'ativo' => 's',
					'assinatura_status' => 'ativa',
					'assinatura_proximo_vencimento' => $fat->vencimento,
				]);
			} else {
				$escola->atualizar();
			}
			ModuleGateHelper::limparCache((int)$escola->id);
		}
		return true;
	}

	/** Processa escolas: gera fatura do mês e suspende após grace. */
	public static function processar(?int $idAdminFiltro = null): array {
		$resumo = [
			'geradas'     => 0,
			'suspensas'   => 0,
			'erros'       => [],
			'mp_ok'       => MercadoPagoCtiHelper::configurado(),
			'tabela_ok'   => SaasFatura::tabelaExiste(),
		];
		if (!$resumo['tabela_ok']) {
			$resumo['erros'][] = 'Tabela saas_faturas ausente.';
			return $resumo;
		}

		$where = '1=1';
		if ($idAdminFiltro !== null && $idAdminFiltro > 0) {
			$where = 'id = '.(int)$idAdminFiltro;
		}
		$results = EscolasAssinantes::getEscolas($where, 'id ASC');
		$competencia = date('Y-m');

		while ($e = $results->fetchObject(EscolasAssinantes::class)) {
			$id = (int)$e->id;
			if ($id <= 0) {
				continue;
			}
			// Só cobra quem tem plano com preço
			$planId = (int)($e->plan_id ?? 0);
			if ($planId <= 0 || !self::temColunaValorMensal()) {
				continue;
			}
			$plano = PlanosAssinatura::getById($planId);
			if (!$plano instanceof PlanosAssinatura || (float)($plano->valor_mensal ?? 0) <= 0) {
				continue;
			}

			$r = self::gerarFaturaEscola($id, $competencia);
			if ($r['ok']) {
				$resumo['geradas']++;
			} else {
				$resumo['erros'][] = '#'.$id.' '.$e->nome.': '.$r['message'];
			}

			if (self::suspenderSeInadimplente($e)) {
				$resumo['suspensas']++;
			}
		}

		return $resumo;
	}

	public static function suspenderSeInadimplente(EscolasAssinantes $escola): bool {
		if (!SaasFatura::tabelaExiste()) {
			return false;
		}
		$hoje = date('Y-m-d');
		$limite = date('Y-m-d', strtotime('-'.self::GRACE_DIAS.' days'));

		$aberta = SaasFatura::get(
			'id_admin = '.(int)$escola->id.' AND status = "aberta" AND vencimento < "'.addslashes($limite).'"',
			'vencimento ASC',
			'1'
		)->fetchObject(SaasFatura::class);

		if (!$aberta instanceof SaasFatura) {
			return false;
		}

		if ($escola->isAtiva()) {
			(new Database('escolas_assinantes'))->update('id = '.(int)$escola->id, [
				'ativo' => 'n',
			] + (self::temColunasAssinaturaEscola() ? ['assinatura_status' => 'suspensa'] : []));
			ModuleGateHelper::limparCache((int)$escola->id);
		}

		if ($aberta->status === 'aberta' && $aberta->vencimento < $hoje) {
			$aberta->status = 'vencida';
			$aberta->atualizar();
		}

		return true;
	}

	private static function atualizarProximoVencimentoEscola(EscolasAssinantes $escola, string $vencimento): void {
		if (!self::temColunasAssinaturaEscola()) {
			return;
		}
		(new Database('escolas_assinantes'))->update('id = '.(int)$escola->id, [
			'assinatura_proximo_vencimento' => $vencimento,
		]);
	}

	public static function formatar(SaasFatura $f, ?EscolasAssinantes $escola = null): array {
		if (!$escola instanceof EscolasAssinantes) {
			$escola = EscolasAssinantes::getEscolaById((int)$f->id_admin);
		}
		return [
			'id'             => (int)$f->id,
			'id_admin'       => (int)$f->id_admin,
			'escola_nome'    => $escola instanceof EscolasAssinantes ? (string)$escola->nome : '',
			'plan_id'        => $f->plan_id !== null ? (int)$f->plan_id : null,
			'competencia'    => (string)$f->competencia,
			'valor'          => round((float)$f->valor, 2),
			'valor_br'       => number_format((float)$f->valor, 2, ',', '.'),
			'vencimento'     => (string)$f->vencimento,
			'status'         => (string)$f->status,
			'mp_payment_id'  => (string)($f->mp_payment_id ?? ''),
			'pix_copia_cola' => (string)($f->pix_copia_cola ?? ''),
			'pago_em'        => $f->pago_em,
			'tem_pix'        => trim((string)($f->pix_copia_cola ?? '')) !== '',
		];
	}
}

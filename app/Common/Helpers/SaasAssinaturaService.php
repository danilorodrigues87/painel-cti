<?php

namespace App\Common\Helpers;

use App\Common\Communication\Email;
use App\Model\Entity\EscolasAssinantes;
use App\Model\Entity\PlanosAssinatura;
use App\Model\Entity\SaasFatura;
use App\Model\Db\Database;

class SaasAssinaturaService {

	public const GRACE_DIAS = 5;
	public const TRIAL_DIAS_DEFAULT = 14;

	public static function temColunasAssinaturaEscola(): bool {
		return EscolasAssinantes::temColunasAssinatura();
	}

	public static function temColunaValorMensal(): bool {
		return PlanosAssinatura::temColunaValorMensal();
	}

	/** Valor efetivo: custom da escola >0, senão valor do plano. */
	public static function resolverValorMensal(EscolasAssinantes $escola): float {
		if (EscolasAssinantes::temColunaValorMensalCustom()) {
			$custom = (float)($escola->valor_mensal_custom ?? 0);
			if ($custom > 0) {
				return round($custom, 2);
			}
		}
		$planId = EscolasAssinantes::temColunaPlanId() ? (int)($escola->plan_id ?? 0) : 0;
		if ($planId > 0 && self::temColunaValorMensal()) {
			$plano = PlanosAssinatura::getById($planId);
			if ($plano instanceof PlanosAssinatura) {
				return round((float)($plano->valor_mensal ?? 0), 2);
			}
		}
		return 0.0;
	}

	/** Escola elegível a cobrança automática (tem preço > 0). */
	public static function escolaCobravel(EscolasAssinantes $escola): bool {
		return self::resolverValorMensal($escola) > 0;
	}

	/** Em trial válido (não gera fatura / não suspende por inadimplência do trial). */
	public static function emTrialAtivo(EscolasAssinantes $escola): bool {
		if (!self::temColunasAssinaturaEscola()) {
			return false;
		}
		$status = (string)($escola->assinatura_status ?? '');
		if ($status !== 'trial') {
			return false;
		}
		if (!EscolasAssinantes::temColunaTrialAte()) {
			return true;
		}
		$ate = trim((string)($escola->trial_ate ?? ''));
		if ($ate === '') {
			return true;
		}
		return $ate >= date('Y-m-d');
	}

	/** Trial acabou: status ainda trial mas trial_ate passou. */
	public static function trialExpirado(EscolasAssinantes $escola): bool {
		if (!self::temColunasAssinaturaEscola()) {
			return false;
		}
		if ((string)($escola->assinatura_status ?? '') !== 'trial') {
			return false;
		}
		if (!EscolasAssinantes::temColunaTrialAte()) {
			return false;
		}
		$ate = trim((string)($escola->trial_ate ?? ''));
		return $ate !== '' && $ate < date('Y-m-d');
	}

	public static function encerrarTrialSeExpirado(EscolasAssinantes $escola): void {
		if (!self::trialExpirado($escola)) {
			return;
		}
		(new Database('escolas_assinantes'))->update('id = '.(int)$escola->id, [
			'assinatura_status' => 'ativa',
		]);
		$escola->assinatura_status = 'ativa';
		ModuleGateHelper::limparCache((int)$escola->id);
	}

	/**
	 * Aplica trial padrão (14 dias) na criação — Master pode sobrescrever trial_ate.
	 */
	public static function aplicarTrialPadrao(EscolasAssinantes $escola, ?string $trialAte = null): void {
		if (!self::temColunasAssinaturaEscola()) {
			return;
		}
		$escola->assinatura_status = 'trial';
		if (EscolasAssinantes::temColunaTrialAte()) {
			if ($trialAte && preg_match('/^\d{4}-\d{2}-\d{2}$/', $trialAte)) {
				$escola->trial_ate = $trialAte;
			} else {
				$escola->trial_ate = date('Y-m-d', strtotime('+'.self::TRIAL_DIAS_DEFAULT.' days'));
			}
		}
	}

	/**
	 * Gera (ou reutiliza) fatura do mês + PIX CTI + e-mail (1×).
	 * @return array{ok:bool,message:string,fatura?:array}
	 */
	public static function gerarFaturaEscola(int $idAdmin, ?string $competencia = null, bool $forcarEmail = false): array {
		if (!SaasFatura::tabelaExiste()) {
			return ['ok' => false, 'message' => 'Execute database/saas_assinatura.sql'];
		}
		$escola = EscolasAssinantes::getEscolaById($idAdmin);
		if (!$escola instanceof EscolasAssinantes) {
			return ['ok' => false, 'message' => 'Escola não encontrada.'];
		}

		self::encerrarTrialSeExpirado($escola);

		if (self::emTrialAtivo($escola)) {
			return [
				'ok' => false,
				'message' => 'Escola em trial até '.($escola->trial_ate ?: '—').'. Cobrança começa após o trial.',
			];
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
			self::enviarEmailCobranca($existente, $escola, $forcarEmail);
			return ['ok' => true, 'message' => 'Fatura já existia.', 'fatura' => self::formatar($existente, $escola)];
		}

		$valor = self::resolverValorMensal($escola);
		if ($valor <= 0) {
			return ['ok' => false, 'message' => 'Sem valor mensal. Defina preço no plano ou valor custom da escola.'];
		}

		$planId = EscolasAssinantes::temColunaPlanId() ? (int)($escola->plan_id ?? 0) : 0;

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
		$emailOk = self::enviarEmailCobranca($fat, $escola, true);

		$msg = 'Fatura gerada.';
		if (!$pixOk) {
			$err = \App\Common\Gateways\MercadoPago\Pix::getUltimoErro();
			$msg = MercadoPagoCtiHelper::configurado()
				? ('Fatura criada, mas PIX falhou'.($err ? ': '.$err : '.'))
				: 'Fatura criada. Configure MP_CTI_ACCESS_TOKEN para gerar PIX.';
		}
		if (!$emailOk) {
			$msg .= ' (e-mail de cobrança não enviado)';
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
		if (SaasFatura::temColunaPixQrBase64() && !empty($cob['qr_base64'])) {
			$fat->pix_qr_base64 = (string)$cob['qr_base64'];
		}
		$fat->atualizar();
		return true;
	}

	/** Envia e-mail 1× por fatura (SMTP sistema). */
	public static function enviarEmailCobranca(SaasFatura $fat, ?EscolasAssinantes $escola = null, bool $forcar = false): bool {
		if ($fat->status === 'pago') {
			return true;
		}
		if (!$forcar && SaasFatura::temColunaEmailEnviado() && !empty($fat->email_enviado_em)) {
			return true;
		}
		if (!$escola instanceof EscolasAssinantes) {
			$escola = EscolasAssinantes::getEscolaById((int)$fat->id_admin);
		}
		if (!$escola instanceof EscolasAssinantes) {
			return false;
		}
		$to = trim((string)($escola->email ?? ''));
		if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
			return false;
		}

		$urlPainel = rtrim((string)(defined('URL') ? URL : ''), '/').'/painel/assinatura';
		$valorBr = number_format((float)$fat->valor, 2, ',', '.');
		$copia = trim((string)($fat->pix_copia_cola ?? ''));
		$body = '<p>Olá, <strong>'.htmlspecialchars((string)$escola->nome, ENT_QUOTES, 'UTF-8').'</strong>.</p>'
			.'<p>Sua fatura da assinatura do <strong>Painel CTI</strong> está disponível.</p>'
			.'<ul>'
			.'<li>Competência: <strong>'.htmlspecialchars((string)$fat->competencia, ENT_QUOTES, 'UTF-8').'</strong></li>'
			.'<li>Valor: <strong>R$ '.$valorBr.'</strong></li>'
			.'<li>Vencimento: <strong>'.htmlspecialchars((string)$fat->vencimento, ENT_QUOTES, 'UTF-8').'</strong></li>'
			.'</ul>'
			.'<p>Acesse o painel para pagar com PIX: <a href="'.htmlspecialchars($urlPainel, ENT_QUOTES, 'UTF-8').'">'.$urlPainel.'</a></p>';
		if ($copia !== '') {
			$body .= '<p><small>PIX copia e cola:</small><br><code style="word-break:break-all">'.htmlspecialchars($copia, ENT_QUOTES, 'UTF-8').'</code></p>';
		}
		$body .= '<p>Após '.self::GRACE_DIAS.' dias do vencimento sem pagamento, o acesso fica restrito à tela de Assinatura.</p>';

		$mail = Email::sistema();
		$ok = $mail->sendEmail(
			[$to],
			'Assinatura Painel CTI — fatura '.$fat->competencia,
			$body
		);
		if ($ok && SaasFatura::temColunaEmailEnviado()) {
			$fat->email_enviado_em = date('Y-m-d H:i:s');
			$fat->atualizar();
		}
		return (bool)$ok;
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
				$upd = [
					'ativo' => 's',
					'assinatura_status' => 'ativa',
					'assinatura_proximo_vencimento' => $fat->vencimento,
				];
				(new Database('escolas_assinantes'))->update('id = '.(int)$escola->id, $upd);
			} else {
				$escola->atualizar();
			}
			ModuleGateHelper::limparCache((int)$escola->id);
		}
		return true;
	}

	/** Processa escolas: trial → fatura do mês → e-mail → suspende após grace. */
	public static function processar(?int $idAdminFiltro = null): array {
		$resumo = [
			'geradas'     => 0,
			'suspensas'   => 0,
			'emails'      => 0,
			'trials'      => 0,
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

			self::encerrarTrialSeExpirado($e);

			if (self::emTrialAtivo($e)) {
				$resumo['trials']++;
				continue;
			}

			if (!self::escolaCobravel($e)) {
				continue;
			}

			$r = self::gerarFaturaEscola($id, $competencia);
			if ($r['ok']) {
				$resumo['geradas']++;
				$fat = SaasFatura::getPorEscolaCompetencia($id, $competencia);
				if ($fat instanceof SaasFatura && SaasFatura::temColunaEmailEnviado() && !empty($fat->email_enviado_em)) {
					$resumo['emails']++;
				}
			} else {
				$resumo['erros'][] = '#'.$id.' '.$e->nome.': '.$r['message'];
			}

			$e2 = EscolasAssinantes::getEscolaById($id);
			if ($e2 instanceof EscolasAssinantes && self::suspenderSeInadimplente($e2)) {
				$resumo['suspensas']++;
			}
		}

		return $resumo;
	}

	public static function suspenderSeInadimplente(EscolasAssinantes $escola): bool {
		if (!SaasFatura::tabelaExiste()) {
			return false;
		}
		if (self::emTrialAtivo($escola)) {
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

	/** Cards do dashboard Master. */
	public static function dashboardStats(): array {
		$hoje = date('Y-m-d');
		$comp = date('Y-m');
		$stats = [
			'escolas_ativas'    => 0,
			'escolas_trial'     => 0,
			'escolas_suspensas' => 0,
			'faturas_abertas'   => 0,
			'faturas_vencidas'  => 0,
			'receita_mes'       => 0.0,
			'competencia'       => $comp,
		];

		$results = EscolasAssinantes::getEscolas(null, 'id ASC');
		while ($e = $results->fetchObject(EscolasAssinantes::class)) {
			if (self::emTrialAtivo($e)) {
				$stats['escolas_trial']++;
			} elseif (!$e->isAtiva() || (string)($e->assinatura_status ?? '') === 'suspensa') {
				$stats['escolas_suspensas']++;
			} else {
				$stats['escolas_ativas']++;
			}
		}

		if (SaasFatura::tabelaExiste()) {
			$row = SaasFatura::get('status = "aberta"', null, null, 'COUNT(*) AS q')->fetch(\PDO::FETCH_ASSOC);
			$stats['faturas_abertas'] = (int)($row['q'] ?? 0);
			$row = SaasFatura::get('status = "vencida"', null, null, 'COUNT(*) AS q')->fetch(\PDO::FETCH_ASSOC);
			$stats['faturas_vencidas'] = (int)($row['q'] ?? 0);
			$row = SaasFatura::get(
				'status = "pago" AND competencia = "'.addslashes($comp).'"',
				null,
				null,
				'SUM(valor) AS t'
			)->fetch(\PDO::FETCH_ASSOC);
			$stats['receita_mes'] = round((float)($row['t'] ?? 0), 2);
		}

		$stats['receita_mes_br'] = number_format($stats['receita_mes'], 2, ',', '.');
		$stats['hoje'] = $hoje;
		return $stats;
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
		$copia = trim((string)($f->pix_copia_cola ?? ''));
		$qrImg = self::pixQrImageSrc($f);
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
			'pix_copia_cola' => $copia,
			'pix_qr_src'     => $qrImg,
			'pago_em'        => $f->pago_em,
			'email_enviado_em' => $f->email_enviado_em ?? null,
			'tem_pix'        => $copia !== '',
		];
	}

	/** data URI do MP ou URL gerada a partir do copia-e-cola */
	public static function pixQrImageSrc(SaasFatura $f): string {
		if (SaasFatura::temColunaPixQrBase64()) {
			$b64 = trim((string)($f->pix_qr_base64 ?? ''));
			if ($b64 !== '') {
				if (strpos($b64, 'data:image') === 0) {
					return $b64;
				}
				return 'data:image/png;base64,'.$b64;
			}
		}
		$copia = trim((string)($f->pix_copia_cola ?? ''));
		if ($copia === '') {
			return '';
		}
		return 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&ecc=M&margin=8&data='.rawurlencode($copia);
	}
}

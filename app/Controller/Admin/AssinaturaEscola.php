<?php

namespace App\Controller\Admin;

use App\Utils\View;
use App\Session\User\Login as SessionUser;
use App\Common\Helpers\TenantHelper;
use App\Common\Helpers\SaasAssinaturaService;
use App\Common\Helpers\MercadoPagoCtiHelper;
use App\Common\Gateways\MercadoPago\Pix;
use App\Model\Entity\EscolasAssinantes;
use App\Model\Entity\PlanosAssinatura;
use App\Model\Entity\SaasFatura;

class AssinaturaEscola extends Page {

	private static function assertDiretor($request, bool $api = false): bool {
		$user = SessionUser::getUserLogedData();
		if (($user['usuario']['nivel'] ?? '') !== 'Diretor') {
			if (!$api) {
				$request->getRouter()->redirect('/painel');
			}
			return false;
		}
		return true;
	}

	public static function index($request) {
		if (!self::assertDiretor($request)) {
			return '';
		}
		$content = View::render('admin/modules/assinatura/index', [
			'grace_dias' => (string)SaasAssinaturaService::GRACE_DIAS,
		]);
		return parent::getPanel('Assinatura', $content, 'Financeiro', $request);
	}

	public static function getInfo($request) {
		if (!self::assertDiretor($request, true)) {
			return json_encode(['success' => false, 'message' => 'Acesso negado.']);
		}

		$post = $request->getPostVars();
		$acao = $post['acao'] ?? '';

		switch ($acao) {
			case 'carregar':
				return self::carregar();
			case 'atualizar_pix':
				return self::atualizarPix($post);
			case 'verificar':
				return self::verificar($post);
			default:
				return json_encode(['success' => false, 'message' => 'Ação inválida.']);
		}
	}

	private static function carregar(): string {
		$idAdmin = TenantHelper::getIdAdmin();
		if ($idAdmin <= 0) {
			return json_encode(['success' => false, 'message' => 'Escola não identificada.']);
		}

		if (!SaasFatura::tabelaExiste()) {
			return json_encode([
				'success' => true,
				'tabela_ok' => false,
				'message' => 'Cobrança de assinatura ainda não está disponível.',
				'resumo' => null,
				'faturas' => [],
			]);
		}

		$escola = EscolasAssinantes::getEscolaById($idAdmin);
		$planoNome = null;
		$valorMensal = null;
		if ($escola instanceof EscolasAssinantes) {
			$planId = (int)($escola->plan_id ?? 0);
			if ($planId > 0) {
				$plano = PlanosAssinatura::getById($planId);
				if ($plano instanceof PlanosAssinatura) {
					$planoNome = (string)$plano->nome;
					if (PlanosAssinatura::temColunaValorMensal()) {
						$valorMensal = round((float)($plano->valor_mensal ?? 0), 2);
					}
				}
			}
		}

		$results = SaasFatura::get(
			'id_admin = '.(int)$idAdmin,
			'competencia DESC, id DESC',
			'12'
		);
		$faturas = [];
		$aberta = null;
		while ($f = $results->fetchObject(SaasFatura::class)) {
			$row = SaasAssinaturaService::formatar($f, $escola instanceof EscolasAssinantes ? $escola : null);
			$faturas[] = $row;
			if ($aberta === null && in_array($row['status'], ['aberta', 'vencida'], true)) {
				$aberta = $row;
			}
		}

		return json_encode([
			'success'   => true,
			'tabela_ok' => true,
			'resumo'    => [
				'plano_nome'                   => $planoNome,
				'valor_mensal'                 => $valorMensal,
				'valor_mensal_br'              => $valorMensal !== null
					? number_format($valorMensal, 2, ',', '.')
					: null,
				'dia_vencimento'               => $escola instanceof EscolasAssinantes && EscolasAssinantes::temColunasAssinatura()
					? max(1, min(28, (int)($escola->dia_vencimento_assinatura ?? 10)))
					: 10,
				'assinatura_status'            => $escola instanceof EscolasAssinantes && EscolasAssinantes::temColunasAssinatura()
					? (string)($escola->assinatura_status ?? 'ativa')
					: 'ativa',
				'assinatura_proximo_vencimento'=> $escola instanceof EscolasAssinantes
					? ($escola->assinatura_proximo_vencimento ?? null)
					: null,
				'escola_ativa'                 => $escola instanceof EscolasAssinantes ? $escola->isAtiva() : false,
				'grace_dias'                   => SaasAssinaturaService::GRACE_DIAS,
			],
			'aberta'  => $aberta,
			'faturas' => $faturas,
		], JSON_UNESCAPED_UNICODE);
	}

	private static function atualizarPix(array $post): string {
		$idAdmin = TenantHelper::getIdAdmin();
		$id = (int)($post['id'] ?? 0);
		$fat = SaasFatura::getById($id);
		if (!$fat instanceof SaasFatura || (int)$fat->id_admin !== $idAdmin) {
			return json_encode(['success' => false, 'message' => 'Fatura não encontrada.']);
		}
		if ($fat->status === 'pago') {
			return json_encode(['success' => false, 'message' => 'Fatura já está paga.']);
		}
		if (!MercadoPagoCtiHelper::configurado()) {
			return json_encode(['success' => false, 'message' => 'Pagamento PIX temporariamente indisponível. Contate o suporte.']);
		}

		$fat->mp_payment_id = null;
		$fat->pix_copia_cola = null;
		$fat->pix_qr_base64 = null;
		$ok = SaasAssinaturaService::anexarPix($fat);
		if (!$ok) {
			$err = Pix::getUltimoErro();
			return json_encode([
				'success' => false,
				'message' => $err ?: 'Não foi possível gerar o PIX. Tente novamente em instantes.',
			]);
		}

		return json_encode([
			'success' => true,
			'message' => 'PIX atualizado.',
			'fatura'  => SaasAssinaturaService::formatar($fat),
		], JSON_UNESCAPED_UNICODE);
	}

	private static function verificar(array $post): string {
		$idAdmin = TenantHelper::getIdAdmin();
		$id = (int)($post['id'] ?? 0);
		$fat = SaasFatura::getById($id);
		if (!$fat instanceof SaasFatura || (int)$fat->id_admin !== $idAdmin) {
			return json_encode(['success' => false, 'message' => 'Fatura não encontrada.']);
		}
		if ($fat->status === 'pago') {
			return json_encode([
				'success' => true,
				'message' => 'Fatura já está paga.',
				'fatura'  => SaasAssinaturaService::formatar($fat),
			]);
		}

		$paymentId = preg_replace('/\D/', '', (string)($fat->mp_payment_id ?? ''));
		if ($paymentId === '') {
			return json_encode(['success' => false, 'message' => 'Esta fatura ainda não tem PIX gerado.']);
		}

		$pix = MercadoPagoCtiHelper::pix();
		if (!$pix instanceof Pix) {
			return json_encode(['success' => false, 'message' => 'Consulta indisponível no momento.']);
		}

		$pagamento = $pix->consultarPagamento($paymentId);
		if (!$pagamento) {
			return json_encode(['success' => false, 'message' => 'Não foi possível consultar o pagamento.']);
		}

		if (($pagamento['status'] ?? '') === 'approved') {
			$pagoEm = date('Y-m-d H:i:s');
			if (!empty($pagamento['date_approved'])) {
				try {
					$pagoEm = (new \DateTimeImmutable((string)$pagamento['date_approved']))->format('Y-m-d H:i:s');
				} catch (\Throwable $e) {
					// keep now
				}
			}
			SaasAssinaturaService::marcarPaga($fat, $pagoEm);
			return json_encode([
				'success' => true,
				'message' => 'Pagamento confirmado! Obrigado.',
				'fatura'  => SaasAssinaturaService::formatar($fat),
			], JSON_UNESCAPED_UNICODE);
		}

		return json_encode([
			'success' => true,
			'message' => 'Ainda não identificamos o pagamento (status: '.($pagamento['status'] ?? 'pendente').').',
			'fatura'  => SaasAssinaturaService::formatar($fat),
		], JSON_UNESCAPED_UNICODE);
	}
}

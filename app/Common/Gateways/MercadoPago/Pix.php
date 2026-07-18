<?php

namespace App\Common\Gateways\MercadoPago;

use App\Common\Gateways\PixGatewayInterface;

class Pix implements PixGatewayInterface {

	private Client $client;

	public function __construct(Client $client) {
		$this->client = $client;
	}

	public function criarCobrancaPix(array $dados): ?array {
		$valor = round((float)($dados['valor'] ?? 0), 2);
		if ($valor <= 0) {
			return null;
		}

		$descricao = mb_substr(trim((string)($dados['descricao'] ?? 'Mensalidade')), 0, 200);
		$external = trim((string)($dados['external_reference'] ?? ''));
		$vencimento = (string)($dados['vencimento'] ?? '');
		$email = trim((string)($dados['pagador_email'] ?? ''));
		if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$email = 'pagador+'.preg_replace('/\W+/', '', $external).'@pix.local';
		}

		$nome = trim((string)($dados['pagador_nome'] ?? 'Pagador'));
		$cpf = preg_replace('/\D/', '', (string)($dados['pagador_cpf'] ?? ''));

		$payer = [
			'email'      => $email,
			'first_name' => mb_substr($nome !== '' ? $nome : 'Pagador', 0, 60),
		];
		if (strlen($cpf) === 11) {
			$payer['identification'] = [
				'type'   => 'CPF',
				'number' => $cpf,
			];
		}

		$body = [
			'transaction_amount' => $valor,
			'description'        => $descricao !== '' ? $descricao : 'Mensalidade',
			'payment_method_id'  => 'pix',
			'payer'              => $payer,
		];
		if ($external !== '') {
			$body['external_reference'] = mb_substr($external, 0, 256);
		}

		$exp = self::formatarExpiracao($vencimento);
		if ($exp !== null) {
			$body['date_of_expiration'] = $exp;
		}

		$idempotency = $external !== ''
			? 'pix-'.$external.'-'.substr(hash('sha256', (string)$valor.$vencimento), 0, 16)
			: bin2hex(random_bytes(16));

		$res = $this->client->request('POST', '/v1/payments', $body, $idempotency);
		if (!$res['ok'] || !is_array($res['body'])) {
			return null;
		}

		$id = (string)($res['body']['id'] ?? '');
		$qr = (string)($res['body']['point_of_interaction']['transaction_data']['qr_code'] ?? '');
		$qrB64 = $res['body']['point_of_interaction']['transaction_data']['qr_code_base64'] ?? null;

		if ($id === '' || $qr === '') {
			return null;
		}

		return [
			'id'         => $id,
			'copia_cola' => $qr,
			'qr_base64'  => is_string($qrB64) && $qrB64 !== '' ? $qrB64 : null,
		];
	}

	/** @return array{id:string,status:string,transaction_amount:float,external_reference:string,date_approved:?string}|null */
	public function consultarPagamento(string $paymentId): ?array {
		$paymentId = preg_replace('/\D/', '', $paymentId);
		if ($paymentId === '') {
			return null;
		}
		$res = $this->client->request('GET', '/v1/payments/'.$paymentId);
		if (!$res['ok'] || !is_array($res['body'])) {
			return null;
		}
		return [
			'id'                  => (string)($res['body']['id'] ?? $paymentId),
			'status'              => (string)($res['body']['status'] ?? ''),
			'transaction_amount'  => (float)($res['body']['transaction_amount'] ?? 0),
			'external_reference'  => (string)($res['body']['external_reference'] ?? ''),
			'date_approved'       => $res['body']['date_approved'] ?? null,
		];
	}

	private static function formatarExpiracao(string $vencimento): ?string {
		$vencimento = trim($vencimento);
		if ($vencimento === '') {
			return null;
		}
		try {
			$dt = new \DateTimeImmutable($vencimento.' 23:59:59', new \DateTimeZone('America/Sao_Paulo'));
			$agora = new \DateTimeImmutable('now', new \DateTimeZone('America/Sao_Paulo'));
			if ($dt < $agora) {
				$dt = $agora->modify('+1 day');
			}
			return $dt->format('Y-m-d\TH:i:s.000P');
		} catch (\Throwable $e) {
			return null;
		}
	}
}

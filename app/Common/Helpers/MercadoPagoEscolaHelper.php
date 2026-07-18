<?php

namespace App\Common\Helpers;

use App\Common\Gateways\MercadoPago\Client;
use App\Common\Gateways\MercadoPago\Pix;
use App\Model\Entity\EscolaIntegracoes;

class MercadoPagoEscolaHelper {

	public static function escolaTemPixAtivo(int $idAdmin): bool {
		if ($idAdmin <= 0 || !EscolaIntegracoes::temColunasMercadoPago()) {
			return false;
		}
		$cfg = EscolaIntegracoes::getByIdAdmin($idAdmin);
		return $cfg instanceof EscolaIntegracoes && $cfg->temMercadoPagoAtivo();
	}

	public static function clientDaEscola(int $idAdmin): ?Client {
		$cfg = EscolaIntegracoes::getByIdAdmin($idAdmin);
		if (!$cfg instanceof EscolaIntegracoes || !(int)$cfg->mp_ativo) {
			return null;
		}
		$token = $cfg->getMpAccessTokenDescriptografado();
		if ($token === null || $token === '') {
			return null;
		}
		return new Client($token);
	}

	public static function pixDaEscola(int $idAdmin): ?Pix {
		$client = self::clientDaEscola($idAdmin);
		return $client ? new Pix($client) : null;
	}

	public static function webhookUrl(int $idAdmin): string {
		$token = '';
		$cfg = EscolaIntegracoes::getByIdAdmin($idAdmin);
		if ($cfg instanceof EscolaIntegracoes) {
			$token = (string)($cfg->mp_webhook_token ?? '');
		}
		if ($token === '') {
			$token = '{token}';
		}
		$base = defined('URL') ? rtrim((string)URL, '/') : '';
		return $base.'/webhook/mercadopago/'.$idAdmin.'/'.$token;
	}

	public static function validarWebhookToken(int $idAdmin, string $token): bool {
		$cfg = EscolaIntegracoes::getByIdAdmin($idAdmin);
		if (!$cfg instanceof EscolaIntegracoes) {
			return false;
		}
		$esperado = (string)($cfg->mp_webhook_token ?? '');
		return $esperado !== '' && hash_equals($esperado, $token);
	}
}

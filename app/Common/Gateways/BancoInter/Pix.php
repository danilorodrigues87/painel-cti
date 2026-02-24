<?php

namespace App\Common\Gateways\BancoInter;
use \App\Common\Gateways\BancoInter\Authentication;

class Pix {

	public static function pixComVencimento($accessToken,$dados,$txtId){

		$conta_corrente = '309388287';
		$certPath = __DIR__ . '/../../../../includes/bancointer/certificado.crt';
        $keyPath = __DIR__ . '/../../../../includes/bancointer/chave.key';

		$curl = curl_init();

		curl_setopt_array($curl, array(
			CURLOPT_URL => 'https://cdpj.partners.bancointer.com.br/pix/v2/cobv/'.$txtId,
			CURLOPT_CUSTOMREQUEST => 'PUT',
			CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2,
			CURLOPT_HTTPHEADER => array(
				'Content-Type: application/json',
				'x-inter-conta-corrente: '.$conta_corrente,
				'Authorization: Bearer '.$accessToken
			),
			CURLOPT_SSLCERT => $certPath, 
			CURLOPT_SSLKEY => $keyPath,
			CURLOPT_POSTFIELDS => json_encode($dados),
			CURLOPT_RETURNTRANSFER => true,


		));

		$response = curl_exec($curl);
		$resultado = json_decode($response);

		if (isset($resultado->pixCopiaECola)) {
    return $resultado->pixCopiaECola;
} else {
    // Tratar erro quando não houver pixCopiaECola na resposta
    throw new \Exception('Erro ao gerar Pix com vencimento: ' . json_encode($resultado));
}


	}


}
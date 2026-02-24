<?php

namespace App\Common\Gateways\BancoInter;
use \App\Common\Gateways\BancoInter\Authentication;

class Boleto {

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

	public static function getBoletoPdf($accessToken,$codigo,$nomeBoleto){

		$conta_corrente = '309388287';
		$certPath = __DIR__ . '/../../../../includes/bancointer/certificado.crt';
		$keyPath = __DIR__ . '/../../../../includes/bancointer/chave.key';

		$curl = curl_init();

		curl_setopt_array($curl, array(
			CURLOPT_URL => 'https://cdpj.partners.bancointer.com.br/cobranca/v3/cobrancas/'.$codigo.'/pdf',
			CURLOPT_CUSTOMREQUEST => 'GET',
			CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2,
			CURLOPT_HTTPHEADER => array(
				'Content-Type: application/json',
				'x-inter-conta-corrente: '.$conta_corrente,
				'Authorization: Bearer '.$accessToken
			),
			CURLOPT_SSLCERT => $certPath, 
			CURLOPT_SSLKEY => $keyPath,
			CURLOPT_RETURNTRANSFER => true,


		));

		$response = curl_exec($curl);


// Verificar se a decodificação foi bem-sucedida
		if (isset($response['pdf'])) {
    // O conteúdo do PDF está na chave 'pdf'
			$pdfContent = $response['pdf'];

    // Decodificar o conteúdo Base64
			$pdfData = base64_decode($pdfContent);

    // Verificar se a decodificação foi bem-sucedida
			if ($pdfData === false) {
				throw new \Exception('Falha ao decodificar o conteúdo Base64 do PDF.');
			}

    // Caminho para salvar o arquivo PDF
			$filePath = __DIR__ . '/../../../../includes/bancointer/boletos/'.$nomeBoleto.'.pdf';

    // Salvar o conteúdo decodificado em um arquivo PDF
			if (file_put_contents($filePath, $pdfData) === false) {
				throw new \Exception('Falha ao salvar o arquivo PDF.');
			}

			echo "Arquivo PDF gerado com sucesso em: " . $filePath;
		} else {
			throw new \Exception('Resposta não contém o campo PDF.');
		}




	}


}
<?php

namespace App\Common\Gateways\BancoInter;
use \App\Common\Environment;

    //CARREGA VARIAVEIS DE AMBIENTE
Environment::load(__DIR__.'/../');

define('URL_AUTH', getenv('URL_AUTH'));
define('CLIENT_ID', getenv('CLIENT_ID'));
define('CLIENT_SECRET', getenv('CLIENT_SECRET'));

class Authentication {


    public static function getAccessToken() {

        $certPath = __DIR__ . '/../../../../includes/bancointer/certificado.crt';
        $keyPath = __DIR__ . '/../../../../includes/bancointer/chave.key';
        
        ini_set('display_errors', 'On'); // Mantenha em 'Off' em produção
        ini_set('error_reporting', E_ALL);

        $data = array(
            'client_id' => CLIENT_ID,
            'client_secret' => CLIENT_SECRET,
            'scope' => 'cobv.write cobv.read cob.write boleto-cobranca.write boleto-cobranca.read',
            'grant_type' => 'client_credentials'
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, URL_AUTH);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/x-www-form-urlencoded'
        ));

        curl_setopt($ch, CURLOPT_SSLCERT, $certPath);
        curl_setopt($ch, CURLOPT_SSLKEY, $keyPath);

        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            // Tratar erro de requisição
            curl_close($ch);
            throw new \Exception('Curl error: ' . curl_error($ch));
        }
        curl_close($ch);

        $result = json_decode($response, true);
        if (isset($result['access_token'])) {
            return $result['access_token'];
        }

        throw new \Exception('Access token not found in response.');
    }


}

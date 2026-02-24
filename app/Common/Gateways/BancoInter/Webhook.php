<?php

namespace App\Common\Gateways\BancoInter;
use App\Model\Entity\Caixa;

class Webhook {

    public static function Notification($request) {
        $ultima_alteracao = date('Y-m-d H:i:s');
        $data_pagamento = date('Y-m-d');

        // Obtém os dados enviados pela API
        $postVars = $request->getPostVars();

        // Verifica se os dados estão no formato esperado e faz o log
        self::analyzeDataType($postVars);

        // Verifica se os dados estão no formato esperado
        if (isset($postVars['pix'][0])) {
            $dadosPix = $postVars['pix'][0];

            // Verifica se os parâmetros esperados estão presentes
            if (!isset($dadosPix['txid']) || !isset($dadosPix['valor'])) {
                self::logError('Parâmetros faltando no webhook');
                return;
            }

            // Extrai os valores
            $txtId = $dadosPix['txid'];
            $valor_pago = $dadosPix['valor'];
            $nome_pagador = $dadosPix['pagador']['nome'];
        } else {
            self::logError('Formato inesperado dos dados no webhook');
            return;
        }

        // Cria a instância de Caixa e define os valores
        $obCaixa = new Caixa;
        $obCaixa->valor_pago = $valor_pago;
        $obCaixa->data_pagamento = $data_pagamento;
        $obCaixa->ultima_alteracao = $ultima_alteracao;
        $obCaixa->txt_id = $txtId;

        // Tenta realizar a baixa no sistema
        $result = $obCaixa->baixaViaApi();

        // Verifica se o processo foi bem-sucedido
        if ($result) {
            // Salvar a mensagem de log
            self::logMessage("Pix recebido de: " . $nome_pagador);
        } else {
            self::logError("Falha na tentativa de baixa no txtId: " . $txtId);
        }
    }

    // Função para log de sucesso ou mensagem
    private static function logMessage($message) {
        $logFile = 'webhook_log.txt';
        $logEntry = "Data Recebida em: " . date('Y-m-d H:i:s') . "\n";
        $logEntry .= $message . "\n";
        $logEntry .= "---------------------------------------------\n";
        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }

    // Função para log de erro
    private static function logError($errorMessage) {
        $logFile = 'webhook_error_log.txt';
        $logEntry = "Erro em: " . date('Y-m-d H:i:s') . "\n";
        $logEntry .= $errorMessage . "\n";
        $logEntry .= "---------------------------------------------\n";
        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }

    // Função para log de teste e análise dos dados recebidos
    private static function analyzeDataType($data) {
        $logFile = 'webhook_test_log.txt';
        $logEntry = "Teste de dados recebidos em: " . date('Y-m-d H:i:s') . "\n";

        if (is_array($data)) {
            $logEntry .= "Tipo de dado: Array\n";
            $logEntry .= "Conteúdo: " . print_r($data, true) . "\n";
        } elseif (is_object($data)) {
            $logEntry .= "Tipo de dado: Objeto\n";
            $logEntry .= "Conteúdo: " . print_r($data, true) . "\n";
        } else {
            $logEntry .= "Tipo de dado: Outro\n";
            $logEntry .= "Conteúdo: " . var_export($data, true) . "\n";
        }

        $logEntry .= "---------------------------------------------\n";
        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }
}

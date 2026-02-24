<?php

namespace App\Controller\Api;

use \App\Http\Response;
use \App\Services\AI\GeminiAI;
use \App\Services\WhatsAppService;

class ReceiveMessage {
	/**
	 * Diretório para logs
	 * @var string
	 */
	private static $logDir = '/home1/dncurs82/admin.ctieducacional.com.br/logs/';

	/**
	 * Instância do WhatsAppService
	 * @var WhatsAppService
	 */
	private static $whatsapp;

	/**
	 * Inicializa o serviço WhatsApp
	 */
	private static function getWhatsAppService() {
		if (!self::$whatsapp) {
			self::$whatsapp = new WhatsAppService();
		}
		return self::$whatsapp;
	}

	/**
	 * Verifica o webhook do WhatsApp
	 */
	public static function verifyWebhook($request) {
		try {
			$params = $request->getQueryParams();
			
			self::log('webhook_verify.txt', [
				'time' => date('Y-m-d H:i:s'),
				'params' => $params
			]);

			$whatsapp = self::getWhatsAppService();

			if(isset($params['hub_verify_token'])) {
				if($whatsapp->validateWebhookToken($params['hub_verify_token'])) {
					return new Response(200, $params['hub_challenge'] ?? '', 'text/plain');
				}
			}

			return new Response(403, 'Forbidden', 'text/plain');

		} catch(\Exception $e) {
			self::log('error.txt', [
				'time' => date('Y-m-d H:i:s'),
				'error' => $e->getMessage(),
				'type' => 'VERIFY_ERROR'
			]);

			return new Response(500, 'Internal Error', 'text/plain');
		}
	}

	/**
	 * Processa mensagens recebidas
	 */
	public static function handleMessage($request) {
		try {
			// Log inicial
			self::log('debug.txt', [
				'time' => date('Y-m-d H:i:s'),
				'step' => 'Início do processamento'
			]);

			$input = file_get_contents('php://input');
			$data = json_decode($input, true);

			// Log do payload recebido
			self::log('whatsapp_messages.txt', [
				'time' => date('Y-m-d H:i:s'),
				'payload' => $data
			]);

			$whatsapp = self::getWhatsAppService();
			
			// Log antes do processamento
			self::log('debug.txt', [
				'time' => date('Y-m-d H:i:s'),
				'step' => 'Antes de processar mensagem'
			]);

			// Processa a mensagem usando WhatsAppService
			$messageData = $whatsapp->processMessage($data);

			// Log após processamento
			self::log('debug.txt', [
				'time' => date('Y-m-d H:i:s'),
				'step' => 'Mensagem processada',
				'messageData' => $messageData
			]);

			// Gera resposta usando Gemini
			$aiResponse = GeminiAI::generateResponse($messageData['message']);
			
			// Log após Gemini
			self::log('debug.txt', [
				'time' => date('Y-m-d H:i:s'),
				'step' => 'Resposta do Gemini gerada',
				'aiResponse' => $aiResponse
			]);

			// Envia resposta via WhatsApp
			$sendResult = $whatsapp->sendResponse($messageData['phone'], $aiResponse);

			// Log após envio
			self::log('debug.txt', [
				'time' => date('Y-m-d H:i:s'),
				'step' => 'Resposta enviada',
				'sendResult' => $sendResult
			]);

			// Log completo
			self::log('processed_messages.txt', [
				'time' => date('Y-m-d H:i:s'),
				'received' => $messageData,
				'ai_response' => $aiResponse,
				'whatsapp_result' => $sendResult
			]);

			return new Response(200, [
				'status' => 'success', 
				'data' => $messageData,
				'response' => $aiResponse,
				'send_result' => $sendResult
			], 'application/json');

		} catch(\Exception $e) {
			// Log de erro detalhado
			self::log('error.txt', [
				'time' => date('Y-m-d H:i:s'),
				'error' => $e->getMessage(),
				'trace' => $e->getTraceAsString(),
				'type' => 'HANDLE_ERROR'
			]);

			return new Response(500, [
				'status' => 'error', 
				'message' => 'Internal error: ' . $e->getMessage()
			], 'application/json');
		}
	}

	/**
	 * Função auxiliar para logs
	 */
	private static function log($filename, $data) {
		file_put_contents(
			self::$logDir . $filename,
			json_encode($data, JSON_PRETTY_PRINT) . "\n------------------------\n",
			FILE_APPEND
		);
	}
}
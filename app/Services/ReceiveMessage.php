<?php

namespace App\Services;

use \App\Http\Response;
use \App\Services\AI\GeminiAI;
use \App\Services\WhatsAppService;
use App\Common\Logger;
use App\Services\Chat\ConversationManager;
use App\Model\Entity\Chat;

class ReceiveMessage {

	/**
	 * Instância do WhatsAppService
	 * @var WhatsAppService
	 */
	private static $whatsapp;

	/**
	 * Inicializa o serviço WhatsApp
	 */
	private static function getWhatsAppService($webhook_url) {
		if (!self::$whatsapp) {
			self::$whatsapp = new WhatsAppService($webhook_url);
		}
		return self::$whatsapp;
	}

	/**
	 * Log centralizado
	 */
	private static function log($data) {
		$logData = array_merge([
			'time' => date('Y-m-d H:i:s')
		], $data);

		file_put_contents(
			'webhook.log', 
			json_encode($logData, JSON_PRETTY_PRINT) . 
			"\n------------------------\n",
			FILE_APPEND
		);
	}

	/**
	 * Verifica o webhook (GET request do Facebook)
	 */
	public static function verifyWebhook($request) {
		try {
			$uri = $request->getRouter()->getUri();
			$webhook_url = basename($uri);

			Logger::log([
				'step' => 'verificando_webhook',
				'uri' => $uri,
				'webhook_url' => $webhook_url
			]);

			$whatsapp = new WhatsAppService($webhook_url);
			$result = $whatsapp->verifyWebhook($request);

			return new Response(200, [
				'success' => true,
				'result' => $result
			], 'application/json');

		} catch(\Exception $e) {
			Logger::log([
				'step' => 'erro_verify_webhook',
				'error' => $e->getMessage(),
				'webhook_url' => basename($request->getRouter()->getUri())
			]);

			return new Response(500, [
				'error' => true,
				'message' => $e->getMessage()
			], 'application/json');
		}
	}

	/**
	 * Processa mensagens recebidas
	 */
	public static function handleMessage($request) {
		try {
			// Pega o token da URI
			$uri = $request->getRouter()->getUri();
			$webhook_url = basename($uri);
			$payload = $request->getPostVars();

			Logger::log([
				'step' => 'processando_webhook',
				'uri' => $uri,
				'webhook_url' => $webhook_url
			]);

			// Verifica se é um webhook de status ANTES de inicializar o WhatsApp
			if (self::isStatusWebhook($payload)) {
				Logger::log([
					'step' => 'ignorando_webhook_status',
					'status' => $payload['entry'][0]['changes'][0]['value']['statuses'][0]['status'] ?? 'desconhecido'
				]);
				return new Response(200, ['success' => true], 'application/json');
			}

			// Inicializa WhatsApp com o token
			$whatsapp = new WhatsAppService($webhook_url);
			
			// Extrai dados da mensagem
			$messageData = $whatsapp->processMessage($payload);

			// Inicializa o gerenciador de conversas
			$conversation = new ConversationManager($messageData['phone']);

			// Salva mensagem do usuário
			$conversation->saveMessage($messageData['message'], 'user');

			// Se for atendimento humano, não processa com IA
			if ($conversation->getCurrentAgentType() === 'human') {
				Logger::log([
					'step' => 'atendimento_humano',
					'phone' => $messageData['phone']
				]);
				return new Response(200, ['success' => true], 'application/json');
			}

			// Gera resposta usando IA
			$response = GeminiAI::generateResponse(
				$messageData['message'],
				$messageData['phone'],
				$webhook_url
			);

			// Salva resposta do assistente
			$conversation->saveMessage($response, 'assistant');

			// Envia resposta
			$result = $whatsapp->sendMessage($messageData['phone'], $response);

			return new Response(200, [
				'success' => true,
				'message' => 'Mensagem processada com sucesso'
			], 'application/json');

		} catch(\Exception $e) {
			Logger::log([
				'step' => 'erro_handle_message',
				'error' => $e->getMessage(),
				'webhook_url' => $webhook_url,
				'payload' => $payload ?? null
			]);

			return new Response(500, [
				'error' => true,
				'message' => $e->getMessage()
			], 'application/json');
		}
	}

	/**
	 * Verifica se é um webhook de status
	 */
	private static function isStatusWebhook($payload) {
		return isset($payload['entry'][0]['changes'][0]['value']['statuses']);
	}
}
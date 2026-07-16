<?php

namespace App\Controller\Webhook;

use App\Common\Communication\EvolutionApiService;
use App\Common\Communication\WhatsappEscolaService;
use App\Common\Communication\WhatsappChatbotService;
use App\Model\Entity\WhatsappConversa;
use App\Model\Entity\WhatsappMensagem;
use App\Model\Entity\WhatsappNumero;

class Evolution {

	public static function receber($request, $idAdmin, $token) {
		$idAdmin = (int)$idAdmin;
		$esperado = EvolutionApiService::webhookToken($idAdmin);

		if (!hash_equals($esperado, (string)$token)) {
			return json_encode(['success' => false, 'message' => 'Token inválido.']);
		}

		$raw = file_get_contents('php://input');
		$payload = json_decode((string)$raw, true);
		if (!is_array($payload)) {
			$post = $request->getPostVars();
			$payload = is_array($post) ? $post : [];
		}

		$event = strtolower((string)($payload['event'] ?? $payload['type'] ?? ''));
		$data = $payload['data'] ?? $payload;

		if (strpos($event, 'connection') !== false || isset($data['state']) || isset($data['connection'])) {
			$estado = EvolutionApiService::extrairEstado(is_array($data) ? $data : $payload);
			$numero = null;
			if (isset($data['instance']['owner'])) {
				$numero = EvolutionApiService::normalizarTelefone((string)$data['instance']['owner']);
			} elseif (isset($data['wuid'])) {
				$numero = EvolutionApiService::normalizarTelefone((string)$data['wuid']);
			}
			WhatsappEscolaService::atualizarStatusConexao($idAdmin, $estado ?: 'unknown', $numero);
		}

		if (strpos($event, 'messages.upsert') !== false || strpos($event, 'messages_upsert') !== false) {
			self::processarMensagens($idAdmin, $data);
		} elseif (isset($data['key']) || isset($data['message'])) {
			self::processarMensagens($idAdmin, $data);
		}

		return json_encode(['success' => true]);
	}

	private static function processarMensagens(int $idAdmin, $data): void {
		if (!is_array($data)) {
			return;
		}

		$numeroId = null;
		$default = WhatsappNumero::getDefault($idAdmin);
		if ($default) {
			$numeroId = (int)$default->id;
		}

		$itens = isset($data[0]) ? $data : [$data];
		foreach ($itens as $msg) {
			if (!is_array($msg)) {
				continue;
			}

			$key = $msg['key'] ?? [];
			$fromMe = !empty($key['fromMe']);
			$remoteJid = (string)($key['remoteJid'] ?? $msg['remoteJid'] ?? '');
			if ($remoteJid === '' || strpos($remoteJid, '@g.us') !== false) {
				continue;
			}

			$telefone = EvolutionApiService::normalizarTelefone(explode('@', $remoteJid)[0]);
			if ($telefone === '') {
				continue;
			}

			$nome = $msg['pushName'] ?? null;
			$corpo = self::extrairTexto($msg);
			$tipo = self::extrairTipo($msg);
			$waId = $key['id'] ?? ($msg['id'] ?? null);

			$conversa = WhatsappConversa::findOrCreate($idAdmin, $telefone, $nome, $numeroId);
			if (!$conversa) {
				continue;
			}

			WhatsappMensagem::registrar([
				'id_admin'      => $idAdmin,
				'conversa_id'   => (int)$conversa->id,
				'direction'     => $fromMe ? 'out' : 'in',
				'tipo'          => $tipo,
				'corpo'         => $corpo,
				'wa_message_id' => $waId,
				'status'        => $fromMe ? 'sent' : 'received',
			]);

			$conversa->tocarUltimaMensagem();

			if (!$fromMe) {
				WhatsappChatbotService::aoReceberMensagem($conversa, $corpo, false);
			}
		}
	}

	private static function extrairTexto(array $msg): ?string {
		$message = $msg['message'] ?? [];
		if (!is_array($message)) {
			return null;
		}
		if (!empty($message['conversation'])) {
			return (string)$message['conversation'];
		}
		if (!empty($message['extendedTextMessage']['text'])) {
			return (string)$message['extendedTextMessage']['text'];
		}
		if (!empty($message['imageMessage']['caption'])) {
			return (string)$message['imageMessage']['caption'];
		}
		if (!empty($msg['text']['message'])) {
			return (string)$msg['text']['message'];
		}
		if (!empty($msg['body'])) {
			return (string)$msg['body'];
		}
		return null;
	}

	private static function extrairTipo(array $msg): string {
		$message = $msg['message'] ?? [];
		if (!is_array($message)) {
			return 'text';
		}
		if (isset($message['imageMessage'])) {
			return 'image';
		}
		if (isset($message['audioMessage'])) {
			return 'audio';
		}
		if (isset($message['videoMessage'])) {
			return 'video';
		}
		if (isset($message['documentMessage'])) {
			return 'document';
		}
		if (isset($message['stickerMessage'])) {
			return 'sticker';
		}
		return 'text';
	}
}

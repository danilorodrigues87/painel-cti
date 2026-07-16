<?php

namespace App\Controller\Webhook;

use App\Common\Communication\EvolutionApiService;
use App\Common\Communication\WhatsappEscolaService;
use App\Common\Communication\WhatsappChatbotService;
use App\Common\Communication\WhatsappMediaStorage;
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
		$instanceName = (string)($payload['instance'] ?? $payload['instanceName'] ?? '');

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
			self::processarMensagens($idAdmin, $data, $instanceName);
		} elseif (isset($data['key']) || isset($data['message'])) {
			self::processarMensagens($idAdmin, $data, $instanceName);
		}

		return json_encode(['success' => true]);
	}

	private static function processarMensagens(int $idAdmin, $data, string $instanceName = ''): void {
		if (!is_array($data)) {
			return;
		}

		$numeroId = null;
		$default = WhatsappNumero::getDefault($idAdmin);
		if ($default) {
			$numeroId = (int)$default->id;
			if ($instanceName === '' && !empty($default->evolution_instance)) {
				$instanceName = (string)$default->evolution_instance;
			}
		}
		if ($instanceName === '') {
			$instanceName = EvolutionApiService::nomeInstancia($idAdmin);
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
			$mediaUrl = self::salvarMidiaRecebida($idAdmin, $instanceName, $msg, $tipo);

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
				'media_url'     => $mediaUrl,
				'wa_message_id' => $waId,
				'status'        => $fromMe ? 'sent' : 'received',
			]);

			$conversa->tocarUltimaMensagem();

			if (!$fromMe) {
				WhatsappChatbotService::aoReceberMensagem($conversa, $corpo, false);
			}
		}
	}

	private static function salvarMidiaRecebida(int $idAdmin, string $instance, array $msg, string $tipo): ?string {
		if (!in_array($tipo, ['image', 'audio', 'video', 'document', 'sticker'], true)) {
			return null;
		}

		$base64 = self::extrairBase64($msg);
		$mime = self::extrairMime($msg, $tipo);

		if ($base64 === null || $base64 === '') {
			$api = EvolutionApiService::fromEnv();
			$payload = [
				'key'     => $msg['key'] ?? [],
				'message' => $msg['message'] ?? [],
			];
			$res = $api->getBase64FromMediaMessage($instance, $payload);
			if (is_array($res)) {
				$base64 = $res['base64'] ?? $res['data']['base64'] ?? null;
				$mime = $res['mimetype'] ?? $res['mimeType'] ?? $mime;
			}
		}

		if (!$base64) {
			return null;
		}

		$saved = WhatsappMediaStorage::salvarBase64($idAdmin, (string)$base64, $tipo, $mime);
		return $saved['relative'] ?? null;
	}

	private static function extrairBase64(array $msg): ?string {
		$paths = [
			$msg['base64'] ?? null,
			$msg['message']['base64'] ?? null,
			$msg['data']['base64'] ?? null,
			$msg['message']['imageMessage']['base64'] ?? null,
			$msg['message']['audioMessage']['base64'] ?? null,
			$msg['message']['stickerMessage']['base64'] ?? null,
			$msg['message']['videoMessage']['base64'] ?? null,
			$msg['message']['documentMessage']['base64'] ?? null,
		];
		foreach ($paths as $v) {
			if (is_string($v) && $v !== '') {
				return $v;
			}
		}
		return null;
	}

	private static function extrairMime(array $msg, string $tipo): ?string {
		$message = $msg['message'] ?? [];
		$map = [
			'image'    => $message['imageMessage']['mimetype'] ?? null,
			'audio'    => $message['audioMessage']['mimetype'] ?? null,
			'video'    => $message['videoMessage']['mimetype'] ?? null,
			'document' => $message['documentMessage']['mimetype'] ?? null,
			'sticker'  => $message['stickerMessage']['mimetype'] ?? null,
		];
		$mime = $map[$tipo] ?? null;
		return is_string($mime) && $mime !== '' ? $mime : null;
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
		if (!empty($message['videoMessage']['caption'])) {
			return (string)$message['videoMessage']['caption'];
		}
		if (!empty($message['documentMessage']['caption'])) {
			return (string)$message['documentMessage']['caption'];
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

<?php

namespace App\Common\Helpers;

use App\Model\Entity\EscolaIntegracoes;
use App\Model\Entity\MetaWebhookLog;

/**
 * Log de diagnóstico: todo POST /webhook/meta (comentários, mensagens, etc.).
 */
class MetaWebhookDebug {

	public static function logInbound(?int $idAdmin, array $payload, string $rota = 'global'): void {
		if (!MetaWebhookLog::tabelaExiste()) {
			return;
		}

		$object = (string)($payload['object'] ?? '?');
		$hints = [];
		$commentHint = false;
		$messagingOnly = true;

		foreach (($payload['entry'] ?? []) as $entry) {
			if (!is_array($entry)) {
				continue;
			}
			$parts = ['entry='.($entry['id'] ?? '?')];
			if (!empty($entry['messaging'])) {
				$parts[] = 'messaging×'.count($entry['messaging']);
			}
			if (!empty($entry['standby'])) {
				$parts[] = 'standby×'.count($entry['standby']);
			}
			if (isset($entry['field']) && (string)$entry['field'] !== '') {
				$parts[] = 'field='.(string)$entry['field'];
				$messagingOnly = false;
				if (in_array((string)$entry['field'], ['comments', 'live_comments', 'feed'], true)) {
					$commentHint = true;
				}
			}
			if (!empty($entry['changed_fields']) && is_array($entry['changed_fields'])) {
				$cf = array_map('strval', $entry['changed_fields']);
				$parts[] = 'changed_fields='.implode(',', $cf);
				$messagingOnly = false;
				foreach ($cf as $f) {
					if (in_array($f, ['comments', 'live_comments', 'feed'], true)) {
						$commentHint = true;
					}
				}
			}
			foreach (($entry['changes'] ?? []) as $change) {
				if (!is_array($change)) {
					continue;
				}
				$f = (string)($change['field'] ?? '?');
				$parts[] = 'change='.$f;
				$messagingOnly = false;
				if (in_array($f, ['comments', 'live_comments', 'feed'], true)) {
					$commentHint = true;
				}
			}
			if (empty($entry['messaging']) && empty($entry['standby'])
				&& !isset($entry['field']) && empty($entry['changes']) && empty($entry['changed_fields'])) {
				$parts[] = 'keys='.implode(',', array_keys($entry));
				$messagingOnly = false;
			}
			$hints[] = implode(' ', $parts);
		}

		if ($idAdmin === null || $idAdmin <= 0) {
			$idAdmin = self::resolverIdAdmin($payload);
		}

		$evento = $commentHint ? 'comentario?' : ($messagingOnly ? 'mensagem' : 'outro');
		$json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		if (!is_string($json)) {
			$json = '{}';
		}

		// DM gera muito volume — guarda JSON completo só quando pode ser comentário ou formato desconhecido
		$detalhe = (!$messagingOnly || $commentHint) ? mb_substr($json, 0, 12000) : null;

		MetaWebhookLog::registrar(
			$idAdmin && $idAdmin > 0 ? $idAdmin : null,
			'webhook_inbound',
			'ok',
			$rota.'/'.$evento,
			null,
			'object='.$object.($hints ? ' · '.implode(' | ', $hints) : ''),
			$detalhe
		);
	}

	private static function resolverIdAdmin(array $payload): ?int {
		foreach (($payload['entry'] ?? []) as $entry) {
			if (!is_array($entry)) {
				continue;
			}
			$entryId = (string)($entry['id'] ?? '');
			$object = (string)($payload['object'] ?? '');
			$pageId = $object === 'page' ? $entryId : '';
			$igId = $object === 'instagram' ? $entryId : '';
			$cfg = EscolaIntegracoes::getByMetaPageOrIg($pageId !== '' ? $pageId : null, $igId !== '' ? $igId : null);
			if ($cfg instanceof EscolaIntegracoes) {
				return (int)$cfg->id_admin;
			}
		}
		return null;
	}
}

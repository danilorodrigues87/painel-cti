<?php

namespace App\Common\Helpers;

use App\Model\Entity\EscolaIntegracoes;
use App\Model\Entity\SocialAutomacao;
use App\Model\Entity\SocialAutomacaoLog;

/**
 * Processa webhooks Meta: comentário com keyword → private reply (DM).
 */
class SocialAutomacaoService {

	/**
	 * @return array{processados:int,enviados:int,ignorados:int,erros:int}
	 */
	public static function processarPayload(?int $idAdminFixo, array $payload): array {
		$resumo = ['processados' => 0, 'enviados' => 0, 'ignorados' => 0, 'erros' => 0];
		if (!SocialAutomacao::tabelaExiste()) {
			return $resumo;
		}

		$eventos = self::extrairComentarios($payload);
		foreach ($eventos as $ev) {
			$resumo['processados']++;
			$cfg = null;
			if ($idAdminFixo && $idAdminFixo > 0) {
				$cfg = EscolaIntegracoes::getByIdAdmin($idAdminFixo);
			} else {
				$cfg = EscolaIntegracoes::getByMetaPageOrIg($ev['page_id'] ?? null, $ev['ig_id'] ?? null);
			}
			if (!$cfg instanceof EscolaIntegracoes) {
				$resumo['ignorados']++;
				continue;
			}
			$r = self::processarEvento($cfg, $ev);
			if (($r['status'] ?? '') === 'ok') {
				$resumo['enviados']++;
			} elseif (($r['status'] ?? '') === 'erro') {
				$resumo['erros']++;
			} else {
				$resumo['ignorados']++;
			}
		}
		return $resumo;
	}

	/**
	 * @param array{comment_id:string,texto:string,canal:string,page_id?:string,ig_id?:string} $ev
	 * @return array{status:string,message?:string}
	 */
	public static function processarEvento(EscolaIntegracoes $cfg, array $ev): array {
		$idAdmin = (int)$cfg->id_admin;
		$commentId = trim((string)($ev['comment_id'] ?? ''));
		$texto = (string)($ev['texto'] ?? '');
		$canal = (string)($ev['canal'] ?? 'instagram');

		if ($commentId === '') {
			return ['status' => 'ignorado', 'message' => 'Sem comment_id'];
		}
		if (SocialAutomacaoLog::jaProcessou($commentId)) {
			return ['status' => 'ignorado', 'message' => 'Já processado'];
		}

		$autoOn = EscolaIntegracoes::temColunaMetaAuto()
			? ((int)($cfg->meta_auto_ativo ?? 0) === 1)
			: true;
		if (!$autoOn) {
			SocialAutomacaoLog::registrar($idAdmin, null, $commentId, $canal, 'ignorado', $texto, 'Automações desligadas');
			return ['status' => 'ignorado', 'message' => 'Automações desligadas'];
		}

		$token = $cfg->getMetaPageTokenDescriptografada();
		$pageId = trim((string)($cfg->meta_page_id ?? ''));
		if (!$token || $pageId === '') {
			SocialAutomacaoLog::registrar($idAdmin, null, $commentId, $canal, 'erro', $texto, 'Page/token ausentes');
			return ['status' => 'erro', 'message' => 'Page/token ausentes'];
		}

		$regra = null;
		foreach (SocialAutomacao::listByAdmin($idAdmin, true) as $auto) {
			$canalOk = $auto->canais === 'ambos'
				|| ($auto->canais === 'instagram' && $canal === 'instagram')
				|| ($auto->canais === 'facebook' && $canal === 'facebook');
			if (!$canalOk) {
				continue;
			}
			if ($auto->bateCom($texto)) {
				$regra = $auto;
				break;
			}
		}
		if (!$regra) {
			SocialAutomacaoLog::registrar($idAdmin, null, $commentId, $canal, 'ignorado', $texto, 'Sem keyword');
			return ['status' => 'ignorado', 'message' => 'Sem keyword'];
		}

		$msg = trim((string)$regra->mensagem_dm);
		if ($msg === '') {
			SocialAutomacaoLog::registrar($idAdmin, (int)$regra->id, $commentId, $canal, 'erro', $texto, 'Mensagem vazia');
			return ['status' => 'erro', 'message' => 'Mensagem vazia'];
		}

		if ($canal === 'facebook') {
			$api = MetaGraphHelper::privateReplyFacebook($commentId, $token, $msg);
		} else {
			$api = MetaGraphHelper::privateReplyInstagram($pageId, $token, $commentId, $msg);
		}

		if (empty($api['ok'])) {
			SocialAutomacaoLog::registrar(
				$idAdmin,
				(int)$regra->id,
				$commentId,
				$canal,
				'erro',
				$texto,
				(string)($api['message'] ?? 'Falha Meta')
			);
			return ['status' => 'erro', 'message' => $api['message'] ?? 'Falha'];
		}

		SocialAutomacaoLog::registrar($idAdmin, (int)$regra->id, $commentId, $canal, 'ok', $texto, null);
		return ['status' => 'ok', 'message' => 'DM enviada'];
	}

	/**
	 * @return array<int,array{comment_id:string,texto:string,canal:string,page_id?:string,ig_id?:string}>
	 */
	public static function extrairComentarios(array $payload): array {
		$out = [];
		$object = (string)($payload['object'] ?? '');
		$entries = $payload['entry'] ?? [];
		if (!is_array($entries)) {
			return $out;
		}

		foreach ($entries as $entry) {
			if (!is_array($entry)) {
				continue;
			}
			$entryId = (string)($entry['id'] ?? '');

			// Instagram Graph: object=instagram, field=comments
			if ($object === 'instagram' || isset($entry['changes'])) {
				foreach (($entry['changes'] ?? []) as $change) {
					if (!is_array($change)) {
						continue;
					}
					$field = (string)($change['field'] ?? '');
					$value = $change['value'] ?? [];
					if (!is_array($value)) {
						continue;
					}
					if ($field === 'comments' || $field === 'live_comments') {
						$cid = (string)($value['id'] ?? '');
						$text = (string)($value['text'] ?? $value['message'] ?? '');
						if ($cid !== '') {
							$out[] = [
								'comment_id' => $cid,
								'texto' => $text,
								'canal' => 'instagram',
								'ig_id' => $entryId,
								'page_id' => '',
							];
						}
					}
					// Facebook Page feed comment
					if ($field === 'feed') {
						$item = (string)($value['item'] ?? '');
						$verb = (string)($value['verb'] ?? '');
						if ($item === 'comment' && ($verb === 'add' || $verb === '')) {
							$cid = (string)($value['comment_id'] ?? $value['id'] ?? '');
							$text = (string)($value['message'] ?? '');
							if ($cid !== '') {
								$out[] = [
									'comment_id' => $cid,
									'texto' => $text,
									'canal' => 'facebook',
									'page_id' => $entryId,
									'ig_id' => '',
								];
							}
						}
					}
				}
			}

			// Alguns payloads page usam messaging — ignoramos (não é keyword em comentário)
		}

		if ($object === 'page') {
			// já coberto em changes feed acima via entry id = page
		}

		return $out;
	}
}

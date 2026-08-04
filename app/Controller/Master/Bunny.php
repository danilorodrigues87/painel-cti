<?php

namespace App\Controller\Master;

use App\Utils\View;
use App\Model\Entity\PlataformaBunny;
use App\Common\Helpers\BunnyStreamHelper;
use App\Common\Helpers\BunnyStorageHelper;

class Bunny extends Page {

	public static function index($request) {
		$content = View::render('master/modules/bunny/index', []);
		return parent::getPanel('Bunny — Master', $content, 'bunny');
	}

	public static function api($request) {
		$post = $request->getPostVars() ?: [];
		$acao = (string)($post['acao'] ?? '');
		if ($acao === 'carregar') {
			return self::carregar();
		}
		if ($acao === 'salvar') {
			return self::salvar($post);
		}
		if ($acao === 'testar_stream') {
			$res = BunnyStreamHelper::testar(0);
			return json_encode([
				'success' => !empty($res['ok']),
				'message' => $res['message'] ?? '',
				'name' => $res['name'] ?? null,
			], JSON_UNESCAPED_UNICODE);
		}
		if ($acao === 'testar_storage') {
			$res = BunnyStorageHelper::testar();
			return json_encode([
				'success' => !empty($res['ok']),
				'message' => $res['message'] ?? '',
			], JSON_UNESCAPED_UNICODE);
		}
		return json_encode(['success' => false, 'message' => 'Ação inválida.']);
	}

	private static function mask(?string $plain): string {
		if (!$plain) {
			return '';
		}
		$len = strlen($plain);
		return $len > 8
			? substr($plain, 0, 4).str_repeat('*', max(4, $len - 8)).substr($plain, -4)
			: '********';
	}

	private static function carregar(): string {
		$colOk = PlataformaBunny::tabelaExiste();
		$cfg = PlataformaBunny::get();
		return json_encode([
			'success' => true,
			'coluna_ok' => $colOk,
			'stream_ativo' => (int)$cfg->stream_ativo,
			'stream_library_id' => (string)($cfg->stream_library_id ?? ''),
			'stream_cdn_hostname' => (string)($cfg->stream_cdn_hostname ?? ''),
			'stream_api_salva' => $cfg->getStreamApiKey() ? true : false,
			'stream_api_mask' => self::mask($cfg->getStreamApiKey()),
			'stream_token_salva' => $cfg->getStreamTokenKey() ? true : false,
			'stream_token_mask' => self::mask($cfg->getStreamTokenKey()),
			'stream_pronto' => $cfg->streamPronto(),
			'stream_motivo' => $cfg->streamDiagnostico(),
			'storage_ativo' => (int)$cfg->storage_ativo,
			'storage_zone' => (string)($cfg->storage_zone ?? ''),
			'storage_endpoint' => (string)($cfg->storage_endpoint ?: 'storage.bunnycdn.com'),
			'storage_cdn_hostname' => (string)($cfg->storage_cdn_hostname ?? ''),
			'storage_key_salva' => $cfg->getStorageAccessKey() ? true : false,
			'storage_key_mask' => self::mask($cfg->getStorageAccessKey()),
			'storage_token_salva' => $cfg->getStorageTokenKey() ? true : false,
			'storage_token_mask' => self::mask($cfg->getStorageTokenKey()),
			'storage_pronto' => $cfg->storagePronto(),
			'storage_motivo' => $cfg->storageDiagnostico(),
		], JSON_UNESCAPED_UNICODE);
	}

	private static function salvar(array $post): string {
		$cfg = PlataformaBunny::get();
		$ok = $cfg->salvar([
			'stream_ativo' => !empty($post['stream_ativo']),
			'stream_library_id' => $post['stream_library_id'] ?? '',
			'stream_cdn_hostname' => $post['stream_cdn_hostname'] ?? '',
			'stream_api_key' => $post['stream_api_key'] ?? '',
			'stream_token_key' => $post['stream_token_key'] ?? '',
			'storage_ativo' => !empty($post['storage_ativo']),
			'storage_zone' => $post['storage_zone'] ?? '',
			'storage_endpoint' => $post['storage_endpoint'] ?? '',
			'storage_cdn_hostname' => $post['storage_cdn_hostname'] ?? '',
			'storage_access_key' => $post['storage_access_key'] ?? '',
			'storage_token_key' => $post['storage_token_key'] ?? '',
		]);
		if (!$ok) {
			return json_encode([
				'success' => false,
				'message' => PlataformaBunny::getUltimoErro() ?: 'Falha ao salvar.',
			], JSON_UNESCAPED_UNICODE);
		}
		$cfg = PlataformaBunny::get();
		return json_encode([
			'success' => true,
			'message' => 'Configuração Bunny salva (global para todas as escolas).',
			'stream_pronto' => $cfg->streamPronto(),
			'storage_pronto' => $cfg->storagePronto(),
		], JSON_UNESCAPED_UNICODE);
	}
}

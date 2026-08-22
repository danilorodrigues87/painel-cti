<?php

namespace App\Controller\Master;

use App\Common\Helpers\GooglePlacesHelper;
use App\Common\Helpers\ProspeccaoEmpresasHelper;
use App\Http\Response;
use App\Model\Entity\ProspeccaoEmpresa;
use App\Utils\View;

class ProspeccaoEmpresas extends Page {

	public static function index($request): string {
		if (!ProspeccaoEmpresa::tabelaExiste()) {
			$content = View::render('master/modules/prospeccao-empresas/sql', []);
			return parent::getPanel('Prospecção — Empresas', $content, 'prospeccao-empresas');
		}

		$googleOk = GooglePlacesHelper::configurado();
		$alertGoogle = $googleOk
			? ''
			: '<div class="alert alert-warning mb-3">'
				.'<strong>Google Places não configurado.</strong> '
				.'Adicione <code>GOOGLE_PLACES_API_KEY</code> no <code>.env</code> para importar novos leads. '
				.'A lista salva e o CSV continuam funcionando.'
				.'</div>';

		$content = View::render('master/modules/prospeccao-empresas/index', [
			'alert_google' => $alertGoogle,
			'google_ok'    => $googleOk ? '1' : '0',
		]);
		return parent::getPanel('Prospecção — Empresas (Maps)', $content, 'prospeccao-empresas');
	}

	public static function getInfo($request): string {
		header('Content-Type: application/json; charset=utf-8');
		$post = $request->getPostVars() ?: [];
		$acao = (string)($post['acao'] ?? '');

		try {
			if ($acao === 'stats') {
				return json_encode([
					'success' => true,
					'stats'   => ProspeccaoEmpresasHelper::stats(),
				], JSON_UNESCAPED_UNICODE);
			}
			if ($acao === 'listar') {
				return self::listar($post);
			}
			if ($acao === 'buscar') {
				return self::buscar($post);
			}
			if ($acao === 'excluir') {
				return self::excluir($post);
			}
		} catch (\Throwable $e) {
			return json_encode([
				'success' => false,
				'message' => $e->getMessage(),
			], JSON_UNESCAPED_UNICODE);
		}

		return json_encode(['success' => false, 'message' => 'Ação inválida.'], JSON_UNESCAPED_UNICODE);
	}

	public static function exportCsv($request): Response {
		$post = $request->getPostVars() ?: [];
		$csv = ProspeccaoEmpresasHelper::csv([
			'q'             => (string)($post['q'] ?? ''),
			'com_whatsapp'  => !empty($post['com_whatsapp']),
		]);
		$filename = 'prospeccao-empresas-'.date('Y-m-d').'.csv';
		$res = new Response(200, $csv, 'text/csv; charset=utf-8');
		$res->addHeader('Content-Disposition', 'attachment; filename="'.$filename.'"');
		return $res;
	}

	private static function listar(array $post): string {
		$filtros = self::filtros($post);
		$page = max(1, (int)($post['page'] ?? 1));
		$limit = 50;
		$offset = ($page - 1) * $limit;
		$total = ProspeccaoEmpresasHelper::contar($filtros);
		$items = ProspeccaoEmpresasHelper::listar($filtros, $limit, $offset);

		return json_encode([
			'success' => true,
			'items'   => $items,
			'total'   => $total,
			'page'    => $page,
			'pages'   => max(1, (int)ceil($total / $limit)),
		], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}

	private static function buscar(array $post): string {
		if (!GooglePlacesHelper::configurado()) {
			return json_encode([
				'success' => false,
				'message' => 'Configure GOOGLE_PLACES_API_KEY no .env para buscar no Google.',
			], JSON_UNESCAPED_UNICODE);
		}

		$q = trim((string)($post['q'] ?? ''));
		$pageToken = isset($post['pageToken']) ? (string)$post['pageToken'] : null;
		if ($pageToken === '') {
			$pageToken = null;
		}

		$result = ProspeccaoEmpresasHelper::importarDoGoogle($q, $pageToken);

		return json_encode([
			'success'         => true,
			'novos'           => $result['novos'],
			'atualizados'     => $result['atualizados'],
			'totalPagina'     => $result['totalPagina'],
			'nextPageToken'   => $result['nextPageToken'],
			'message'         => $result['novos'].' novo(s), '.$result['atualizados'].' atualizado(s).',
		], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}

	private static function excluir(array $post): string {
		$id = (int)($post['id'] ?? 0);
		if ($id <= 0) {
			return json_encode(['success' => false, 'message' => 'ID inválido.'], JSON_UNESCAPED_UNICODE);
		}
		$ok = ProspeccaoEmpresa::excluir($id);
		return json_encode([
			'success' => $ok,
			'message' => $ok ? 'Registro excluído.' : 'Não foi possível excluir.',
		], JSON_UNESCAPED_UNICODE);
	}

	/** @return array<string,mixed> */
	private static function filtros(array $post): array {
		return [
			'q'            => (string)($post['q'] ?? ''),
			'com_whatsapp' => !empty($post['com_whatsapp']),
		];
	}
}

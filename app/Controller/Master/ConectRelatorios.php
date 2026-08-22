<?php

namespace App\Controller\Master;

use App\Common\Helpers\ConectRelatoriosHelper;
use App\Http\Response;
use App\Model\Entity\CjCandidato;
use App\Utils\View;

class ConectRelatorios extends Page {

	public static function index($request): string {
		if (!CjCandidato::tabelaExiste()) {
			$content = View::render('master/modules/conect-relatorios/sql', []);
			return parent::getPanel('Conecta Jovem — Relatórios', $content, 'conect-relatorios');
		}

		$escolasOpts = '<option value="">Todas as escolas</option>';
		foreach (ConectRelatoriosHelper::listarEscolasFiltro() as $e) {
			$escolasOpts .= '<option value="'.(int)$e['id'].'">'
				.htmlspecialchars($e['nome'], ENT_QUOTES, 'UTF-8').'</option>';
		}

		$content = View::render('master/modules/conect-relatorios/index', [
			'escolas_options' => $escolasOpts,
		]);
		return parent::getPanel('Conecta Jovem — Relatórios', $content, 'conect-relatorios');
	}

	public static function getInfo($request): string {
		header('Content-Type: application/json; charset=utf-8');
		$post = $request->getPostVars() ?: [];
		$acao = (string)($post['acao'] ?? 'resumo');

		if ($acao === 'resumo') {
			return self::resumo($post);
		}
		if ($acao === 'candidatos') {
			return self::candidatos($post);
		}
		return json_encode(['success' => false, 'message' => 'Ação inválida.'], JSON_UNESCAPED_UNICODE);
	}

	public static function exportCsv($request): Response {
		$post = $request->getPostVars() ?: [];
		$csv = ConectRelatoriosHelper::csvCandidatos([
			'de'       => (string)($post['de'] ?? ''),
			'ate'      => (string)($post['ate'] ?? ''),
			'id_admin' => (int)($post['id_admin'] ?? 0),
			'uf'       => (string)($post['uf'] ?? ''),
			'tipo'     => (string)($post['tipo'] ?? ''),
			'status'   => (string)($post['status'] ?? ''),
			'q'        => (string)($post['q'] ?? ''),
		]);
		$filename = 'candidatos-conecta-jovem-'.date('Y-m-d').'.csv';
		$res = new Response(200, $csv, 'text/csv; charset=utf-8');
		$res->addHeader('Content-Disposition', 'attachment; filename="'.$filename.'"');
		return $res;
	}

	private static function resumo(array $post): string {
		$data = ConectRelatoriosHelper::kpisResumo(
			(string)($post['de'] ?? ''),
			(string)($post['ate'] ?? '')
		);
		return json_encode([
			'success' => true,
			'kpis'    => $data,
		], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}

	private static function candidatos(array $post): string {
		try {
			$filtros = [
				'de'       => (string)($post['de'] ?? ''),
				'ate'      => (string)($post['ate'] ?? ''),
				'id_admin' => (int)($post['id_admin'] ?? 0),
				'uf'       => (string)($post['uf'] ?? ''),
				'tipo'     => (string)($post['tipo'] ?? ''),
				'status'   => (string)($post['status'] ?? ''),
				'q'        => (string)($post['q'] ?? ''),
			];
			$page = max(1, (int)($post['page'] ?? 1));
			$limit = 50;
			$offset = ($page - 1) * $limit;
			$total = ConectRelatoriosHelper::contarCandidatos($filtros);
			$items = ConectRelatoriosHelper::listarCandidatos($filtros, $limit, $offset);

			$json = json_encode([
				'success' => true,
				'items'   => $items,
				'total'   => $total,
				'page'    => $page,
				'pages'   => max(1, (int)ceil($total / $limit)),
			], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
			return is_string($json) ? $json : json_encode(['success' => false, 'message' => 'Erro ao gerar resposta.'], JSON_UNESCAPED_UNICODE);
		} catch (\Throwable $e) {
			return json_encode([
				'success' => false,
				'message' => 'Erro ao carregar candidatos: '.$e->getMessage(),
			], JSON_UNESCAPED_UNICODE);
		}
	}
}

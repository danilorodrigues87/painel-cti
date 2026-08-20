<?php

namespace App\Controller\Master;

use App\Model\Entity\CjEmpresa;
use App\Model\Entity\CjVaga;
use App\Model\Db\Database;
use App\Utils\View;

class ConectJovem extends Page {

	private static function badgeStatus(string $status): string {
		return match ($status) {
			'aprovada'  => '<span class="badge bg-success">Aprovada</span>',
			'bloqueada' => '<span class="badge bg-danger">Bloqueada</span>',
			default     => '<span class="badge bg-warning text-dark">Pendente</span>',
		};
	}

	private static function formatCnpj(string $cnpj): string {
		$d = preg_replace('/\D+/', '', $cnpj);
		if (strlen($d) !== 14) {
			return htmlspecialchars($cnpj);
		}
		return substr($d, 0, 2).'.'.substr($d, 2, 3).'.'.substr($d, 5, 3).'/'
			.substr($d, 8, 4).'-'.substr($d, 12, 2);
	}

	public static function index($request): string {
		$empresas = [];
		$empresasPendentes = [];
		$vagas = [];
		if (CjEmpresa::tabelaExiste()) {
			$stmt = (new Database())->execute(
				'SELECT e.*, c.nome AS cidade_nome FROM cj_empresas e '
				.'LEFT JOIN cidades c ON c.id = e.cidade_id '
				.'ORDER BY e.created_at DESC LIMIT 200'
			);
			$empresas = $stmt ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : [];
			foreach ($empresas as $e) {
				if (($e['status'] ?? '') === 'pendente') {
					$empresasPendentes[] = $e;
				}
			}
		}
		if (CjVaga::tabelaExiste()) {
			$stmt = (new Database())->execute(
				'SELECT v.*, e.nome_fantasia FROM cj_vagas v '
				.'INNER JOIN cj_empresas e ON e.id = v.id_empresa '
				.'WHERE v.status = "pendente" ORDER BY v.created_at DESC LIMIT 50'
			);
			$vagas = $stmt ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : [];
		}

		$rowsTodas = '';
		foreach ($empresas as $e) {
			$status = (string)($e['status'] ?? 'pendente');
			$local = trim((string)($e['cidade_nome'] ?? '').' '.($e['uf'] ?? ''));
			$acao = '—';
			if ($status === 'pendente') {
				$acao = '<form method="post" action="'.URL.'/master/conect/empresa/'.(int)$e['id'].'/aprovar" class="d-inline">'
					.'<button type="submit" class="btn btn-sm btn-success">Aprovar</button></form>';
			}
			$rowsTodas .= '<tr>'
				.'<td>'.htmlspecialchars($e['nome_fantasia'] ?: $e['razao_social']).'</td>'
				.'<td>'.self::formatCnpj((string)$e['cnpj']).'</td>'
				.'<td>'.htmlspecialchars((string)$e['email']).'</td>'
				.'<td>'.htmlspecialchars($local).'</td>'
				.'<td>'.self::badgeStatus($status).'</td>'
				.'<td class="text-nowrap small text-muted">'.htmlspecialchars((string)($e['created_at'] ?? '')).'</td>'
				.'<td>'.$acao.'</td>'
				.'</tr>';
		}
		if ($rowsTodas === '') {
			$rowsTodas = '<tr><td colspan="7" class="text-muted">Nenhuma empresa cadastrada no portal.</td></tr>';
		}

		$rowsPend = '';
		foreach ($empresasPendentes as $e) {
			$rowsPend .= '<tr>'
				.'<td>'.htmlspecialchars($e['nome_fantasia'] ?: $e['razao_social']).'</td>'
				.'<td>'.self::formatCnpj((string)$e['cnpj']).'</td>'
				.'<td>'.htmlspecialchars((string)$e['email']).'</td>'
				.'<td><form method="post" action="'.URL.'/master/conect/empresa/'.(int)$e['id'].'/aprovar" class="d-inline">'
				.'<button type="submit" class="btn btn-sm btn-success">Aprovar</button></form></td>'
				.'</tr>';
		}
		if ($rowsPend === '') {
			$rowsPend = '<tr><td colspan="4" class="text-muted">Nenhuma empresa aguardando aprovação manual.</td></tr>';
		}

		$rowsVaga = '';
		foreach ($vagas as $v) {
			$rowsVaga .= '<tr>'
				.'<td>'.htmlspecialchars($v['titulo']).'</td>'
				.'<td>'.htmlspecialchars($v['nome_fantasia'] ?? '').'</td>'
				.'<td>'.htmlspecialchars($v['tipo_vaga']).'</td>'
				.'<td class="text-nowrap">'
				.'<form method="post" action="'.URL.'/master/conect/vaga/'.(int)$v['id'].'/aprovar" class="d-inline">'
				.'<button type="submit" class="btn btn-sm btn-success me-1">Publicar</button></form>'
				.'<form method="post" action="'.URL.'/master/conect/vaga/'.(int)$v['id'].'/reprovar" class="d-inline">'
				.'<button type="submit" class="btn btn-sm btn-outline-danger">Reprovar</button></form>'
				.'</td></tr>';
		}
		if ($rowsVaga === '') {
			$rowsVaga = '<tr><td colspan="4" class="text-muted">Nenhuma vaga pendente.</td></tr>';
		}

		$content = View::render('master/modules/conect/index', [
			'total_empresas'   => count($empresas),
			'total_pendentes'  => count($empresasPendentes),
			'rows_empresas'    => $rowsTodas,
			'rows_pendentes'   => $rowsPend,
			'rows_vagas'       => $rowsVaga,
		]);

		return parent::getPanel('Conecta Jovem — Moderação', $content, 'conect');
	}

	public static function aprovarEmpresa($request, int $id): void {
		if ($id > 0 && CjEmpresa::tabelaExiste()) {
			(new Database('cj_empresas'))->update('id = '.$id, [
				'status'      => 'aprovada',
				'aprovada_em' => date('Y-m-d H:i:s'),
			]);
		}
		header('Location: '.URL.'/master/conect');
		exit;
	}

	public static function aprovarVaga($request, int $id): void {
		if ($id > 0 && CjVaga::tabelaExiste()) {
			(new Database('cj_vagas'))->update('id = '.$id, [
				'status'       => 'publicada',
				'publicada_em' => date('Y-m-d H:i:s'),
			]);
		}
		header('Location: '.URL.'/master/conect');
		exit;
	}

	public static function reprovarVaga($request, int $id): void {
		if ($id > 0 && CjVaga::tabelaExiste()) {
			(new Database('cj_vagas'))->update('id = '.$id, ['status' => 'reprovada']);
		}
		header('Location: '.URL.'/master/conect');
		exit;
	}
}

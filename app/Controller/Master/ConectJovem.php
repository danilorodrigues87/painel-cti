<?php

namespace App\Controller\Master;

use App\Model\Entity\CjEmpresa;
use App\Model\Entity\CjVaga;
use App\Model\Db\Database;
use App\Utils\View;

class ConectJovem extends Page {

	public static function index($request): string {
		$empresas = [];
		$vagas = [];
		if (CjEmpresa::tabelaExiste()) {
			$stmt = (new Database())->execute(
				'SELECT * FROM cj_empresas WHERE status = "pendente" ORDER BY created_at DESC LIMIT 50'
			);
			$empresas = $stmt ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : [];
		}
		if (CjVaga::tabelaExiste()) {
			$stmt = (new Database())->execute(
				'SELECT v.*, e.nome_fantasia FROM cj_vagas v '
				.'INNER JOIN cj_empresas e ON e.id = v.id_empresa '
				.'WHERE v.status = "pendente" ORDER BY v.created_at DESC LIMIT 50'
			);
			$vagas = $stmt ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : [];
		}

		$rowsEmp = '';
		foreach ($empresas as $e) {
			$rowsEmp .= '<tr>'
				.'<td>'.htmlspecialchars($e['nome_fantasia'] ?: $e['razao_social']).'</td>'
				.'<td>'.htmlspecialchars($e['cnpj']).'</td>'
				.'<td>'.htmlspecialchars($e['email']).'</td>'
				.'<td><form method="post" action="'.URL.'/master/conect/empresa/'.(int)$e['id'].'/aprovar" class="d-inline">'
				.'<button type="submit" class="btn btn-sm btn-success">Aprovar</button></form></td>'
				.'</tr>';
		}
		if ($rowsEmp === '') {
			$rowsEmp = '<tr><td colspan="4" class="text-muted">Nenhuma empresa pendente.</td></tr>';
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
			'rows_empresas' => $rowsEmp,
			'rows_vagas'    => $rowsVaga,
		]);

		return parent::getPanel('Conecta Jovem — Moderação', $content, 'conect');
	}

	public static function aprovarEmpresa($request, int $id): void {
		if ($id > 0 && CjEmpresa::tabelaExiste()) {
			(new Database('cj_empresas'))->update('id = '.$id, [
				'status'     => 'aprovada',
				'aprovada_em'=> date('Y-m-d H:i:s'),
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

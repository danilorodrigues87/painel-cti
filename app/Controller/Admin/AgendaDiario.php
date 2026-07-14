<?php

namespace App\Controller\Admin;

use \App\Utils\View;
use \App\Model\Entity\AgendaAulas as EntityAulas;
use \App\Model\Entity\Presencas as EntityPresencas;
use \App\Model\Entity\Horarios as EntityHorarios;
use \App\Model\Entity\Laboratorios as EntityLabs;
use \App\Common\Helpers\AgendaHelper;
use \App\Common\Helpers\TenantHelper;
use PDO;

class AgendaDiario extends Page {

	public static function index($request) {
		$content = View::render('admin/modules/agenda/ag_diario', []);
		return parent::getPanel('Diário', $content, 'agenda', $request);
	}

	public static function getInfo($request) {
		$id_admin = parent::getIdAdminInt();
		$postVars = $request->getPostVars();
		$data = $postVars['data'] ?? date('Y-m-d');
		$labFiltro = (int)($postVars['laboratorio_id'] ?? 0);

		AgendaHelper::gerarAulasDia($id_admin, $data);

		$where = 'agenda_aulas.id_admin = '.(int)$id_admin.' AND agenda_aulas.data_aula = "'.$data.'"';
		if($labFiltro > 0){
			$where .= ' AND agenda_aulas.laboratorio_id = '.$labFiltro;
		}

		$innerJoin = '
			INNER JOIN usuarios ON usuarios.id = agenda_aulas.id_aluno
			INNER JOIN trilhas ON trilhas.id = agenda_aulas.id_trilha
			INNER JOIN horarios ON horarios.id = agenda_aulas.id_horario
			LEFT JOIN laboratorios ON laboratorios.id = agenda_aulas.laboratorio_id
			LEFT JOIN presencas ON presencas.agenda_aula_id = agenda_aulas.id
		';

		$fields = 'agenda_aulas.id, usuarios.nome as aluno, trilhas.nome as curso,
			horarios.inicio, horarios.final, laboratorios.nome as lab,
			presencas.status as presenca_status, presencas.observacao';

		$results = EntityAulas::getAulas($where, 'horarios.inicio ASC, usuarios.nome ASC', null, $fields, $innerJoin);

		$rows = '';
		while ($row = $results->fetch(PDO::FETCH_ASSOC)) {
			$aulaId = (int)$row['id'];
			$status = $row['presenca_status'] ?? '';
			$rows .= '<tr>
				<td>'.htmlspecialchars($row['aluno']).'</td>
				<td>'.htmlspecialchars($row['curso']).'</td>
				<td>'.htmlspecialchars($row['lab'] ?? '—').'</td>
				<td>'.$row['inicio'].' – '.$row['final'].'</td>
				<td>
					<select name="presenca['.$aulaId.']" class="form-select form-select-sm">
						<option value="presente" '.($status === 'presente' ? 'selected' : '').'>Presente</option>
						<option value="falta" '.($status === 'falta' ? 'selected' : '').'>Falta</option>
						<option value="justificada" '.($status === 'justificada' ? 'selected' : '').'>Justificada</option>
						<option value="reposicao" '.($status === 'reposicao' ? 'selected' : '').'>Reposição</option>
					</select>
				</td>
				<td><input type="text" name="obs['.$aulaId.']" class="form-control form-control-sm" value="'.htmlspecialchars($row['observacao'] ?? '').'" placeholder="Obs."></td>
			</tr>';
		}

		if($rows === ''){
			$rows = '<tr><td colspan="6" class="text-center text-muted py-4">Nenhuma aula para esta data. Verifique se há planos semanais cadastrados.</td></tr>';
		}

		$table = '<form id="formDiario">
			<input type="hidden" name="data" value="'.$data.'">
			<div class="table-responsive"><table class="table table-striped">
			<thead><tr><th>Aluno</th><th>Curso</th><th>Lab</th><th>Horário</th><th>Presença</th><th>Obs.</th></tr></thead>
			<tbody>'.$rows.'</tbody></table></div>
			<div class="text-end mt-3"><button type="submit" class="btn btn-primary">Salvar diário</button></div>
		</form>';

		$labs = '<option value="0">Todos os laboratórios</option>';
		$resLabs = EntityLabs::getLabs('id_admin = '.(int)$id_admin.' AND ativo = 1', 'nome ASC');
		while ($lab = $resLabs->fetchObject(EntityLabs::class)) {
			$sel = ($labFiltro === (int)$lab->id) ? 'selected' : '';
			$labs .= '<option '.$sel.' value="'.$lab->id.'">'.htmlspecialchars($lab->nome).'</option>';
		}

		return json_encode([
			'table'          => $table,
			'labs_options'   => $labs,
			'data'           => $data,
			'total'          => substr_count($rows, '<tr>') - ($rows === '' ? 0 : 0)
		]);
	}

	public static function salvar($request) {
		$id_admin = parent::getIdAdminInt();
		$usuarioId = (int)parent::getIdAdmin()['usuario']['id'];
		$postVars = $request->getPostVars();
		$presencas = $postVars['presenca'] ?? [];
		$observacoes = $postVars['obs'] ?? [];

		if(!is_array($presencas) || empty($presencas)){
			return json_encode(['erro' => 'Nenhuma presença para salvar.']);
		}

		foreach($presencas as $aulaId => $status){
			$aulaId = (int)$aulaId;
			if(!TenantHelper::pertence('agenda_aulas', $aulaId, $id_admin)){
				continue;
			}

			$aula = EntityAulas::getById($aulaId, $id_admin);
			if(!$aula){
				continue;
			}

			$ob = new EntityPresencas;
			$ob->id_admin = $id_admin;
			$ob->agenda_aula_id = $aulaId;
			$ob->id_aluno = (int)$aula->id_aluno;
			$ob->status = in_array($status, ['presente','falta','justificada','reposicao']) ? $status : 'presente';
			$ob->observacao = trim($observacoes[$aulaId] ?? '');
			$ob->registrado_por = $usuarioId;
			$ob->salvar();
		}

		return json_encode(['sucesso' => true]);
	}
}

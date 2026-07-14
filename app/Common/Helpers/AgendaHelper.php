<?php

namespace App\Common\Helpers;

use App\Model\Db\Database;
use App\Model\Entity\Horarios;
use App\Model\Entity\AgendaPlano;
use App\Model\Entity\AgendaAulas;
use App\Model\Entity\Matriculas;
use PDO;

class AgendaHelper {

	public static function getCapacidadeHorario(int $idHorario): int {
		$row = Horarios::getHorarios(
			'horarios.id = '.(int)$idHorario,
			null, 1,
			'laboratorios.qtd_computadores',
			'INNER JOIN laboratorios ON laboratorios.id = horarios.laboratorio_id'
		)->fetch(PDO::FETCH_ASSOC);

		return (int)($row['qtd_computadores'] ?? 10);
	}

	public static function contarPlanosHorario(int $idHorario): int {
		return (int)AgendaPlano::getPlanos(
			'id_horario = '.(int)$idHorario.' AND ativo = 1',
			null, null, 'COUNT(*) as qtd'
		)->fetch(PDO::FETCH_ASSOC)['qtd'];
	}

	public static function recalcularVagasHorario(int $idHorario): void {
		$ocupadas = self::contarPlanosHorario($idHorario);
		$ob = new Horarios;
		$ob->id = $idHorario;
		$ob->vagas_ocupadas = $ocupadas;
		$ob->atualizarVaga();
	}

	public static function getMatriculaAtivaAluno(int $idAluno, int $idAdmin): ?array {
		$row = Matriculas::getMatriculas(
			'matriculas.id_aluno = '.(int)$idAluno.'
			AND matriculas.id_admin = '.(int)$idAdmin.'
			AND matriculas.status = 0
			AND matriculas.fim >= CURDATE()',
			'id DESC', 1,
			'matriculas.id, matriculas.aulas_semanais, matriculas.id_trilha'
		)->fetch(PDO::FETCH_ASSOC);

		return $row ?: null;
	}

	public static function contarPlanosAluno(int $idAluno, int $idAdmin): int {
		return (int)AgendaPlano::getPlanos(
			'id_aluno = '.(int)$idAluno.' AND id_admin = '.(int)$idAdmin.' AND ativo = 1',
			null, null, 'COUNT(*) as qtd'
		)->fetch(PDO::FETCH_ASSOC)['qtd'];
	}

	public static function diaSemanaData(string $data): int {
		$dia = (int)date('w', strtotime($data));
		return $dia === 0 ? 1 : $dia;
	}

	public static function gerarAulasDia(int $idAdmin, string $data): int {
		$diaSemana = self::diaSemanaData($data);
		$geradas = 0;

		$planos = AgendaPlano::getPlanos(
			'agenda_plano.id_admin = '.(int)$idAdmin.'
			AND agenda_plano.ativo = 1
			AND agenda_plano.dia_semana = '.(int)$diaSemana.'
			AND agenda_plano.data_inicio <= "'.$data.'"
			AND (agenda_plano.data_fim IS NULL OR agenda_plano.data_fim >= "'.$data.'")',
			null, null,
			'agenda_plano.*, horarios.laboratorio_id',
			'INNER JOIN horarios ON horarios.id = agenda_plano.id_horario'
		);

		while ($plano = $planos->fetch(PDO::FETCH_ASSOC)) {
			$existe = AgendaAulas::getAulas(
				'id_aluno = '.(int)$plano['id_aluno'].'
				AND id_horario = '.(int)$plano['id_horario'].'
				AND data_aula = "'.$data.'"',
				null, 1, 'id'
			)->fetch(PDO::FETCH_ASSOC);

			if($existe){
				continue;
			}

			$ob = new AgendaAulas;
			$ob->id_admin = $idAdmin;
			$ob->agenda_plano_id = (int)$plano['id'];
			$ob->id_horario = (int)$plano['id_horario'];
			$ob->laboratorio_id = (int)$plano['laboratorio_id'];
			$ob->id_aluno = (int)$plano['id_aluno'];
			$ob->id_trilha = (int)$plano['id_trilha'];
			$ob->data_aula = $data;
			$ob->status = 'agendada';
			$ob->cadastrar();
			$geradas++;
		}

		return $geradas;
	}

	public static function migrarLegado(int $idAdmin): int {
		$migrados = 0;

		$results = (new Database('agenda_aula'))->select(
			'agenda_aula.id_horario IN (SELECT id FROM horarios WHERE id_admin = '.(int)$idAdmin.')',
			'id ASC'
		);

		while ($row = $results->fetch(PDO::FETCH_ASSOC)) {
			$matricula = self::getMatriculaAtivaAluno((int)$row['id_aluno'], $idAdmin);
			if(!$matricula){
				continue;
			}

			$horario = Horarios::getHorarioById((int)$row['id_horario']);
			if(!$horario){
				continue;
			}

			$existe = AgendaPlano::getPlanos(
				'id_aluno = '.(int)$row['id_aluno'].'
				AND id_horario = '.(int)$row['id_horario'].'
				AND ativo = 1',
				null, 1, 'id'
			)->fetch(PDO::FETCH_ASSOC);

			if($existe){
				continue;
			}

			$ob = new AgendaPlano;
			$ob->id_admin = $idAdmin;
			$ob->matricula_id = (int)$matricula['id'];
			$ob->id_aluno = (int)$row['id_aluno'];
			$ob->id_trilha = (int)$row['id_trilha'];
			$ob->id_horario = (int)$row['id_horario'];
			$ob->dia_semana = (int)$horario->dia_semana;
			$ob->data_inicio = date('Y-m-d');
			$ob->cadastrar();

			self::recalcularVagasHorario((int)$row['id_horario']);
			$migrados++;
		}

		return $migrados;
	}

}

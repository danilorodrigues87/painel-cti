<?php

namespace App\Common\Helpers;

use App\Model\Entity\CrmLeads;
use App\Model\Entity\User as EntityUser;
use App\Model\Entity\Responsaveis;

class EmailAuditoriaHelper {

	public static function auditarEscola(int $idAdmin, int $limite = 200): array {
		$invalidos = [];

		$invalidos = array_merge($invalidos, self::auditarAlunos($idAdmin, $limite));
		$invalidos = array_merge($invalidos, self::auditarResponsaveis($idAdmin, $limite));
		$invalidos = array_merge($invalidos, self::auditarLeads($idAdmin, $limite));

		usort($invalidos, function ($a, $b) {
			return strcmp($a['tipo'], $b['tipo']) ?: strcmp($a['nome'], $b['nome']);
		});

		$totalInvalidos = count($invalidos);
		$porMotivo = [];
		foreach ($invalidos as $item) {
			$motivo = $item['motivo'];
			$porMotivo[$motivo] = ($porMotivo[$motivo] ?? 0) + 1;
		}

		return [
			'total'      => $totalInvalidos,
			'por_motivo' => $porMotivo,
			'itens'      => array_slice($invalidos, 0, $limite),
			'truncado'   => $totalInvalidos > $limite,
		];
	}

	private static function auditarAlunos(int $idAdmin, int $limite): array {
		$lista = [];
		$results = EntityUser::getUser(
			'nivel = "Cliente" AND id_admin = '.(int)$idAdmin.' AND email IS NOT NULL AND email != ""',
			'nome ASC'
		);

		while ($row = $results->fetchObject(EntityUser::class)) {
			$motivo = EmailValidator::getRejeicao($row->email ?? '');
			if ($motivo === null) {
				continue;
			}
			$lista[] = [
				'tipo'   => 'Aluno',
				'id'     => (int)$row->id,
				'nome'   => $row->nome ?? '',
				'email'  => $row->email ?? '',
				'motivo' => $motivo,
			];
			if (count($lista) >= $limite) {
				break;
			}
		}

		return $lista;
	}

	private static function auditarResponsaveis(int $idAdmin, int $limite): array {
		$lista = [];
		$results = Responsaveis::getRes(
			'id_admin = '.(int)$idAdmin.' AND email IS NOT NULL AND email != ""',
			'nome ASC'
		);

		while ($row = $results->fetchObject(Responsaveis::class)) {
			$motivo = EmailValidator::getRejeicao($row->email ?? '');
			if ($motivo === null) {
				continue;
			}
			$lista[] = [
				'tipo'   => 'Responsável',
				'id'     => (int)$row->id,
				'nome'   => $row->nome ?? '',
				'email'  => $row->email ?? '',
				'motivo' => $motivo,
			];
			if (count($lista) >= $limite) {
				break;
			}
		}

		return $lista;
	}

	private static function auditarLeads(int $idAdmin, int $limite): array {
		$lista = [];
		$results = CrmLeads::getLeads(
			'id_admin = '.(int)$idAdmin.' AND email IS NOT NULL AND email != ""',
			'nome ASC'
		);

		while ($row = $results->fetchObject(CrmLeads::class)) {
			$motivo = EmailValidator::getRejeicao($row->email ?? '');
			if ($motivo === null) {
				continue;
			}
			$lista[] = [
				'tipo'   => 'Lead',
				'id'     => (int)$row->id,
				'nome'   => $row->nome ?? '',
				'email'  => $row->email ?? '',
				'motivo' => $motivo,
			];
			if (count($lista) >= $limite) {
				break;
			}
		}

		return $lista;
	}
}

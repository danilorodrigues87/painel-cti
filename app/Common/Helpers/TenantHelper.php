<?php

namespace App\Common\Helpers;

use App\Model\Db\Database;
use App\Session\User\Login as SessionUser;
use PDO;

class TenantHelper {

	public static function getIdAdmin(): int {
		$data = SessionUser::getUserLogedData();
		return (int)($data['usuario']['id_admin'] ?? 0);
	}

	public static function getUsuarioId(): int {
		$data = SessionUser::getUserLogedData();
		return (int)($data['usuario']['id'] ?? 0);
	}

	public static function whereComFiltroId(int $filtroId, int $idAdmin, string $wherePadrao): string {
		if ($filtroId > 0) {
			return 'id = '.$filtroId.' AND id_admin = '.$idAdmin;
		}
		return $wherePadrao;
	}

	public static function whereMatriculaFiltro(int $idAluno, int $idAdmin, string $wherePadrao): string {
		if ($idAluno > 0) {
			return 'id_aluno = '.$idAluno.' AND id_admin = '.$idAdmin;
		}
		return $wherePadrao;
	}

	public static function pertence(string $tabela, int $id, int $idAdmin, string $coluna = 'id_admin'): bool {
		if ($id <= 0 || $idAdmin <= 0) {
			return false;
		}

		$row = (new Database($tabela))->select(
			'id = '.(int)$id.' AND '.$coluna.' = '.(int)$idAdmin,
			null,
			1,
			'id'
		)->fetch(PDO::FETCH_ASSOC);

		return !empty($row);
	}

	public static function pertenceUsuario(int $id, int $idAdmin, ?string $nivel = null): bool {
		if ($id <= 0 || $idAdmin <= 0) {
			return false;
		}

		$where = 'id = '.(int)$id.' AND id_admin = '.(int)$idAdmin;

		if ($nivel !== null) {
			$where .= ' AND nivel = "'.addslashes($nivel).'"';
		}

		$row = (new Database('usuarios'))->select($where, null, 1, 'id')->fetch(PDO::FETCH_ASSOC);

		return !empty($row);
	}

	public static function pertenceMatricula(int $id, int $idAdmin): bool {
		return self::pertence('matriculas', $id, $idAdmin);
	}

	public static function pertenceCaixa(int $id, int $idAdmin): bool {
		return self::pertence('caixa', $id, $idAdmin);
	}

	public static function pertenceEscola(int $id, int $idAdmin): bool {
		return (int)$id === (int)$idAdmin;
	}

	public static function pertenceListaTarefa(int $id, int $idAdmin): bool {
		return self::pertence('crm_tarefas_listas', $id, $idAdmin);
	}

}

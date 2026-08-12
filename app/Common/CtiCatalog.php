<?php

namespace App\Common;

use App\Common\Environment;
use App\Model\Entity\EscolasAssinantes;
use App\Model\Entity\LmsCurso;
use App\Model\Entity\LmsEscolaCursoCti;
use App\Model\Entity\PlanosCurso;
use App\Model\Entity\User as EntityUser;

/**
 * Tenant e helpers do catálogo EAD CTI (cursos criados pelo Master).
 */
class CtiCatalog {

	public const ORIGEM_CTI = 'cti';
	public const ORIGEM_ESCOLA = 'escola';

	private const DIRETOR_EMAIL = 'catalogo.cti@ctieducacional.internal';

	public static function tabelasExistem(): bool {
		return PlanosCurso::tabelaExiste()
			&& LmsEscolaCursoCti::tabelaExiste()
			&& LmsCurso::temColunaOrigem();
	}

	public static function idAdmin(): int {
		static $cached = null;
		if ($cached !== null && $cached > 0) {
			return $cached;
		}

		$fromEnv = (int)Environment::get('CTI_CATALOG_ID', 0);
		if ($fromEnv > 0) {
			$cached = $fromEnv;
			return $cached;
		}

		if (EscolasAssinantes::temColunaCatalogoCti()) {
			$row = EscolasAssinantes::getEscolas('catalogo_cti = 1', null, 1, 'id')
				->fetch(\PDO::FETCH_ASSOC);
			if (!empty($row['id'])) {
				$cached = (int)$row['id'];
				return $cached;
			}
		}

		$escola = self::garantirEscolaCatalogo();
		$cached = $escola ? (int)$escola->id : 0;
		return $cached;
	}

	public static function garantirEscolaCatalogo(): ?EscolasAssinantes {
		if (!EscolasAssinantes::temColunaCatalogoCti()) {
			return null;
		}

		$row = EscolasAssinantes::getEscolas('catalogo_cti = 1', null, 1)
			->fetchObject(EscolasAssinantes::class);
		if ($row instanceof EscolasAssinantes) {
			self::garantirDiretorCatalogo((int)$row->id);
			return $row;
		}

		$ob = new EscolasAssinantes();
		$ob->nome = 'CTI Catálogo EAD';
		$ob->email = self::DIRETOR_EMAIL;
		$ob->telefone = '';
		$ob->cpf_cnpj = '';
		$ob->ativo = 's';
		$ob->modulos_liberados = null;
		$ob->catalogo_cti = 1;
		$ob->id_admin = 0;
		$ob->cadastrar();

		if ((int)$ob->id <= 0) {
			return null;
		}

		self::garantirDiretorCatalogo((int)$ob->id);
		return EscolasAssinantes::getEscolaById((int)$ob->id);
	}

	public static function garantirDiretorCatalogo(int $idAdmin): ?EntityUser {
		$diretor = EntityUser::getUser(
			'id_admin = '.(int)$idAdmin.' AND nivel = "Diretor"',
			'id ASC',
			1
		)->fetchObject(EntityUser::class);
		if ($diretor instanceof EntityUser) {
			if ($diretor->ativo !== 's') {
				$diretor->ativo = 's';
				$diretor->atualizar();
			}
			return $diretor;
		}

		$diretor = new EntityUser();
		$diretor->nome = 'CTI Catálogo';
		$diretor->email = self::DIRETOR_EMAIL;
		$diretor->nivel = 'Diretor';
		$diretor->senha = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
		$diretor->id_responsavel = 0;
		$diretor->whatsapp = '';
		$diretor->rg = '';
		$diretor->cpf = '';
		$diretor->ativo = 's';
		$diretor->acesso = json_encode(\App\Common\SystemModules::getPermissions(), JSON_UNESCAPED_UNICODE);
		$diretor->id_admin = $idAdmin;
		$diretor->cadastrar();

		return EntityUser::getUserByEmail(self::DIRETOR_EMAIL);
	}

	public static function isCursoCti(LmsCurso $curso): bool {
		if (!LmsCurso::temColunaOrigem()) {
			return false;
		}
		return (string)($curso->origem ?? self::ORIGEM_ESCOLA) === self::ORIGEM_CTI;
	}

	public static function isEscolaCatalogo(int $idAdmin): bool {
		$catId = self::idAdmin();
		return $catId > 0 && $catId === $idAdmin;
	}
}

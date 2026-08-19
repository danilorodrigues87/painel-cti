<?php

namespace App\Common\Helpers;

use App\Model\Entity\CrmFunis;
use App\Model\Entity\CrmHistorico;
use App\Model\Entity\CrmLeads as EntityCrmLeads;

/**
 * Cria lead CRM para candidatos externos do Conecta Jovem.
 */
class ConectJovemCrmHelper {

	public static function criarLeadExterno(
		int $idAdmin,
		string $nome,
		string $whatsapp,
		?string $email = null,
		?string $cursoInteresse = null,
		?int $cidadeId = null,
		?string $bairro = null
	): ?int {
		if ($idAdmin <= 0 || trim($nome) === '') {
			return null;
		}

		$funilId = self::funilConectaJovem($idAdmin);
		$lead = new EntityCrmLeads();
		$lead->id_admin = $idAdmin;
		$lead->usuario_id = 0;
		$lead->visibilidade = 'publico';
		$lead->funil_id = $funilId;
		$lead->nome = mb_substr(trim($nome), 0, 191);
		$lead->whatsapp = preg_replace('/\D+/', '', $whatsapp) ?: '';
		$lead->email = $email ? mb_substr(trim($email), 0, 191) : null;
		$lead->curso_interesse = $cursoInteresse
			? mb_substr(trim($cursoInteresse), 0, 191)
			: 'Empregabilidade';
		$lead->origem = 'Conecta Jovem';
		$lead->cidade = $cidadeId !== null && $cidadeId > 0 ? (string)$cidadeId : null;
		$lead->bairro = $bairro ? mb_substr(trim($bairro), 0, 120) : null;
		$lead->status = 'novo';
		$lead->status_wa = 'pendente';
		$lead->cadastrar();

		if ((int)$lead->id <= 0) {
			return null;
		}

		$hist = new CrmHistorico();
		$hist->lead_id = (int)$lead->id;
		$hist->usuario_id = 0;
		$hist->acao = 'lead_cadastrado';
		$hist->observacao = 'Lead criado via Conecta Jovem.';
		$hist->cadastrar();

		return (int)$lead->id;
	}

	private static function funilConectaJovem(int $idAdmin): ?int {
		try {
			foreach (['Conecta Jovem', 'Conect Jovem'] as $nomeFunil) {
				$row = CrmFunis::getFunis(
					'id_admin = '.(int)$idAdmin.' AND nome = "'.$nomeFunil.'"',
					null,
					'1'
				)->fetch(\PDO::FETCH_ASSOC);
				if (!empty($row['id'])) {
					return (int)$row['id'];
				}
			}
			$funil = new CrmFunis();
			$funil->id_admin = $idAdmin;
			$funil->nome = 'Conecta Jovem';
			$funil->ativo = 1;
			$funil->cadastrar();
			return (int)$funil->id ?: null;
		} catch (\Throwable $e) {
			return null;
		}
	}
}

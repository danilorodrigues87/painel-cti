<?php

namespace App\Common\Communication;

use App\Common\Helpers\CampanhaSegmentoHelper;
use App\Model\Entity\Campanhas;
use App\Model\Entity\CampanhaFila;
use App\Model\Entity\EscolaIntegracoes;
use App\Model\Entity\EscolasAssinantes;

class CampanhaWorker {

	public static function processar(int $idAdmin = 0, int $limitePorEscola = 10, bool $aplicarDelay = true): array {
		$resumo = [
			'processados' => 0,
			'enviados'    => 0,
			'erros'       => 0,
			'escolas'     => [],
		];

		if (!Campanhas::tabelaExiste()) {
			return $resumo;
		}

		$escolas = self::escolasComCampanhasAtivas($idAdmin);

		foreach ($escolas as $escolaId) {
			$config = EscolaIntegracoes::getByIdAdmin($escolaId);
			$delay = ($config instanceof EscolaIntegracoes) ? max(1, (int)$config->email_delay_segundos) : 3;
			$maxHora = ($config instanceof EscolaIntegracoes) ? max(1, (int)$config->email_max_hora) : 80;

			$enviadosHora = self::contarEnviadosUltimaHora($escolaId);
			$restanteHora = max(0, $maxHora - $enviadosHora);
			$limite = min($limitePorEscola, $restanteHora);

			if ($limite <= 0) {
				$resumo['escolas'][$escolaId] = ['motivo' => 'limite_hora'];
				continue;
			}

			$nomeEscola = '';
			$obEscola = EscolasAssinantes::getEscolaById($escolaId);
			if ($obEscola instanceof EscolasAssinantes) {
				$nomeEscola = $obEscola->nome ?? '';
			}

			$email = Email::escola($escolaId);
			$fila = CampanhaFila::getPendentes($escolaId, $limite);
			$statsEscola = ['enviados' => 0, 'erros' => 0];

			while ($item = $fila->fetchObject(CampanhaFila::class)) {
				$resumo['processados']++;
				$campanha = Campanhas::getById((int)$item->campanha_id, $escolaId);

				if (!$campanha instanceof Campanhas || $campanha->status !== 'enviando') {
					$item->marcarErro('Campanha não está em envio.');
					$resumo['erros']++;
					$statsEscola['erros']++;
					continue;
				}

				$vars = [
					'nome'    => $item->nome ?? '',
					'contato' => $item->contato,
					'curso'   => '',
					'escola'  => $nomeEscola,
				];

				$assunto = CampanhaSegmentoHelper::aplicarVariaveis($campanha->assunto ?? $campanha->titulo, $vars);
				$corpo = CampanhaSegmentoHelper::aplicarVariaveis($campanha->mensagem, $vars);

				$ok = $email->sendEmail($item->contato, $assunto, $corpo);

				if ($ok) {
					$item->marcarEnviado();
					$resumo['enviados']++;
					$statsEscola['enviados']++;
				} else {
					$item->marcarErro($email->getError() ?: 'Falha no envio.');
					$resumo['erros']++;
					$statsEscola['erros']++;
				}

				$campanha->recalcularTotais();

				if ($aplicarDelay && $delay > 0) {
					sleep($delay);
				}
			}

			$resumo['escolas'][$escolaId] = $statsEscola;
		}

		return $resumo;
	}

	private static function escolasComCampanhasAtivas(int $idAdminFiltro = 0): array {
		$where = 'status = "enviando" AND canal = "email"';
		if ($idAdminFiltro > 0) {
			$where .= ' AND id_admin = '.(int)$idAdminFiltro;
		}

		$results = Campanhas::get($where, null, null, 'DISTINCT id_admin');
		$ids = [];

		while ($row = $results->fetch(\PDO::FETCH_ASSOC)) {
			$ids[] = (int)$row['id_admin'];
		}

		return $ids;
	}

	private static function contarEnviadosUltimaHora(int $idAdmin): int {
		$desde = date('Y-m-d H:i:s', strtotime('-1 hour'));
		$row = CampanhaFila::get(
			'id_admin = '.(int)$idAdmin.' AND status = "enviado" AND enviado_em >= "'.$desde.'"',
			null,
			null,
			'COUNT(*) AS qtd'
		)->fetch(\PDO::FETCH_ASSOC);

		return (int)($row['qtd'] ?? 0);
	}
}

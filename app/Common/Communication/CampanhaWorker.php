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
			$statsEmail = self::processarCanal($escolaId, 'email', $limitePorEscola, $aplicarDelay, $resumo);
			$statsWa = self::processarCanal($escolaId, 'whatsapp', $limitePorEscola, $aplicarDelay, $resumo);
			$resumo['escolas'][$escolaId] = [
				'email'    => $statsEmail,
				'whatsapp' => $statsWa,
			];
		}

		return $resumo;
	}

	private static function processarCanal(
		int $escolaId,
		string $canal,
		int $limitePorEscola,
		bool $aplicarDelay,
		array &$resumo
	): array {
		$config = EscolaIntegracoes::getByIdAdmin($escolaId);
		$stats = ['enviados' => 0, 'erros' => 0, 'motivo' => null];

		if ($canal === 'whatsapp') {
			$delay = ($config instanceof EscolaIntegracoes) ? max(1, (int)($config->whatsapp_delay_segundos ?? 5)) : 5;
			$maxHora = ($config instanceof EscolaIntegracoes) ? max(1, (int)($config->whatsapp_max_hora ?? 40)) : 40;
			$statusWa = WhatsappEscolaService::status($escolaId);
			if (empty($statusWa['conectado'])) {
				$stats['motivo'] = 'whatsapp_desconectado';
				return $stats;
			}
			$instance = (string)($statusWa['instance'] ?? EvolutionApiService::nomeInstancia($escolaId));
			$api = EvolutionApiService::fromEnv();
		} else {
			$delay = ($config instanceof EscolaIntegracoes) ? max(1, (int)$config->email_delay_segundos) : 3;
			$maxHora = ($config instanceof EscolaIntegracoes) ? max(1, (int)$config->email_max_hora) : 80;
			$instance = null;
			$api = null;
		}

		$enviadosHora = self::contarEnviadosUltimaHora($escolaId, $canal);
		$restanteHora = max(0, $maxHora - $enviadosHora);
		$limite = min($limitePorEscola, $restanteHora);

		if ($limite <= 0) {
			$stats['motivo'] = 'limite_hora';
			return $stats;
		}

		$nomeEscola = '';
		$obEscola = EscolasAssinantes::getEscolaById($escolaId);
		if ($obEscola instanceof EscolasAssinantes) {
			$nomeEscola = $obEscola->nome ?? '';
		}

		$email = $canal === 'email' ? Email::escola($escolaId) : null;
		$fila = CampanhaFila::getPendentesPorCanal($escolaId, $canal, $limite);

		while ($item = $fila->fetchObject(CampanhaFila::class)) {
			$resumo['processados']++;
			$campanha = Campanhas::getById((int)$item->campanha_id, $escolaId);

			if (!$campanha instanceof Campanhas || $campanha->status !== 'enviando') {
				$item->marcarErro('Campanha não está em envio.');
				$resumo['erros']++;
				$stats['erros']++;
				continue;
			}

			$vars = [
				'nome'    => $item->nome ?? '',
				'contato' => $item->contato,
				'curso'   => '',
				'escola'  => $nomeEscola,
			];

			$ok = false;
			$erroMsg = 'Falha no envio.';

			if ($canal === 'whatsapp') {
				$texto = CampanhaSegmentoHelper::textoParaWhatsapp(
					CampanhaSegmentoHelper::aplicarVariaveis($campanha->mensagem, $vars)
				);
				if ($texto === '') {
					$item->marcarErro('Mensagem vazia após converter para texto.');
					$resumo['erros']++;
					$stats['erros']++;
					$campanha->recalcularTotais();
					continue;
				}
				$res = $api->sendText($instance, (string)$item->contato, $texto);
				$ok = $res !== null && $api->getLastHttpCode() < 400;
				$erroMsg = $api->getLastError() ?: 'Falha no envio WhatsApp.';
			} else {
				$assunto = CampanhaSegmentoHelper::aplicarVariaveis($campanha->assunto ?? $campanha->titulo, $vars);
				$corpo = CampanhaSegmentoHelper::aplicarVariaveis($campanha->mensagem, $vars);
				$ok = $email->sendEmail($item->contato, $assunto, $corpo);
				$erroMsg = $email->getError() ?: 'Falha no envio.';
			}

			if ($ok) {
				$item->marcarEnviado();
				$resumo['enviados']++;
				$stats['enviados']++;
			} else {
				$item->marcarErro($erroMsg);
				$resumo['erros']++;
				$stats['erros']++;
			}

			$campanha->recalcularTotais();

			if ($aplicarDelay && $delay > 0) {
				sleep($delay);
			}
		}

		return $stats;
	}

	private static function escolasComCampanhasAtivas(int $idAdminFiltro = 0): array {
		$where = 'status = "enviando" AND canal IN ("email","whatsapp")';
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

	private static function contarEnviadosUltimaHora(int $idAdmin, string $canal): int {
		$desde = date('Y-m-d H:i:s', strtotime('-1 hour'));
		$canal = $canal === 'whatsapp' ? 'whatsapp' : 'email';

		$sql = '
			SELECT COUNT(*) AS qtd
			FROM campanha_fila f
			INNER JOIN campanhas c ON c.id = f.campanha_id
			WHERE f.id_admin = '.(int)$idAdmin.'
			  AND f.status = "enviado"
			  AND f.enviado_em >= "'.addslashes($desde).'"
			  AND c.canal = "'.addslashes($canal).'"
		';

		$row = (new \App\Model\Db\Database('campanha_fila'))->execute($sql)->fetch(\PDO::FETCH_ASSOC);
		return (int)($row['qtd'] ?? 0);
	}
}

<?php

namespace App\Common\Communication;

use App\Common\Helpers\CampanhaSegmentoHelper;
use App\Model\Entity\Campanhas;
use App\Model\Entity\CampanhaFila;
use App\Model\Entity\CrmLeads;
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

	/** Segundos de intervalo entre envios de grupo (config da escola). */
	public static function delayGrupoSegundos(int $idAdmin): int {
		$colunaOk = EscolaIntegracoes::temColunaWhatsappGrupoDelay();
		$config = EscolaIntegracoes::getByIdAdmin($idAdmin);
		if ($colunaOk && $config instanceof EscolaIntegracoes) {
			return max(60, (int)($config->whatsapp_grupo_delay_segundos ?? 3600));
		}
		return 3600;
	}

	/**
	 * Após enviar a 1ª msg de grupo, continua as demais em background
	 * (não depende de cron nem da aba aberta). Requer PHP-FPM (fastcgi_finish_request).
	 */
	public static function agendarContinuacaoGrupos(int $idAdmin, int $campanhaId): void {
		register_shutdown_function(static function () use ($idAdmin, $campanhaId) {
			try {
				// Libera o browser imediatamente; o PHP segue no servidor
				while (ob_get_level() > 0) {
					@ob_end_flush();
				}
				@flush();
				if (function_exists('fastcgi_finish_request')) {
					@fastcgi_finish_request();
				}
				ignore_user_abort(true);
				@set_time_limit(0);
				self::continuarFilaGrupos($idAdmin, $campanhaId);
			} catch (\Throwable $e) {
				// silencioso — não quebrar a resposta já enviada
			}
		});
	}

	/** Loop: reenvia a mesma mensagem nos grupos no intervalo até pausar/encerrar. */
	public static function continuarFilaGrupos(int $idAdmin, int $campanhaId): void {
		$lockName = 'cti_camp_g_'.(int)$idAdmin.'_'.(int)$campanhaId;
		$db = new \App\Model\Db\Database('campanha_fila');
		$row = $db->execute("SELECT GET_LOCK('".addslashes($lockName)."', 0) AS l")->fetch(\PDO::FETCH_ASSOC);
		if ((int)($row['l'] ?? 0) !== 1) {
			return;
		}

		try {
			while (true) {
				$campanha = Campanhas::getById($campanhaId, $idAdmin);
				if (!$campanha instanceof Campanhas || $campanha->status !== 'enviando') {
					break;
				}
				if (!$campanha->ehCampanhaGrupos()) {
					break;
				}

				// Recorrente: quando a rodada acaba, recoloca os mesmos grupos na fila
				self::reabastecerFilaGrupos($campanha);
				if (CampanhaFila::contarPorCampanha($campanhaId, $idAdmin, 'pendente') <= 0) {
					break;
				}

				$delay = self::delayGrupoSegundos($idAdmin);
				$espera = 0;
				if (!empty($campanha->agendada_para)) {
					$espera = max(0, strtotime($campanha->agendada_para) - time());
				}
				if ($espera <= 0) {
					$espera = self::esperaPacingCampanha($campanhaId, $idAdmin, $delay);
				}

				while ($espera > 0) {
					$chunk = min(15, $espera);
					sleep($chunk);
					$espera -= $chunk;
					$campanha = Campanhas::getById($campanhaId, $idAdmin);
					if (!$campanha instanceof Campanhas || $campanha->status !== 'enviando') {
						return;
					}
				}

				self::processar($idAdmin, 1, false);
				$campanha = Campanhas::getById($campanhaId, $idAdmin);
				if ($campanha instanceof Campanhas) {
					$campanha->recalcularTotais();
				}
			}
		} finally {
			$db->execute("SELECT RELEASE_LOCK('".addslashes($lockName)."')");
		}
	}

	/**
	 * Campanha de grupos é recorrente: esvaziar a fila não encerra —
	 * recoloca os mesmos destinos do segmento como pendentes.
	 */
	public static function reabastecerFilaGrupos(Campanhas $campanha): int {
		if (!$campanha->ehCampanhaGrupos() || $campanha->status !== 'enviando') {
			return 0;
		}
		$id = (int)$campanha->id;
		$idAdmin = (int)$campanha->id_admin;
		if (CampanhaFila::contarPorCampanha($id, $idAdmin, 'pendente') > 0) {
			return 0;
		}

		$segmento = json_decode($campanha->segmento ?? '{}', true) ?: [];
		$destinatarios = CampanhaSegmentoHelper::resolverDestinatarios($idAdmin, $segmento, 'whatsapp');
		if (empty($destinatarios)) {
			return 0;
		}

		$itens = [];
		foreach ($destinatarios as $dest) {
			$itens[] = [
				'campanha_id'       => $id,
				'id_admin'          => $idAdmin,
				'destinatario_tipo' => $dest['destinatario_tipo'],
				'destinatario_id'   => $dest['destinatario_id'] ?? null,
				'nome'              => $dest['nome'] ?? '',
				'contato'           => $dest['contato'],
				'curso'             => $dest['curso'] ?? '',
			];
		}

		$n = CampanhaFila::inserirLote($itens);
		// total acumulado = histórico de linhas na fila (envios + novas rodadas)
		$campanha->recalcularTotais();
		return $n;
	}

	private static function esperaPacingCampanha(int $campanhaId, int $idAdmin, int $delay): int {
		$sql = '
			SELECT MAX(enviado_em) AS ultimo
			FROM campanha_fila
			WHERE campanha_id = '.(int)$campanhaId.'
			  AND id_admin = '.(int)$idAdmin.'
			  AND status = "enviado"
		';
		$row = (new \App\Model\Db\Database('campanha_fila'))->execute($sql)->fetch(\PDO::FETCH_ASSOC);
		if (empty($row['ultimo'])) {
			return 0;
		}
		$elapsed = time() - strtotime($row['ultimo']);
		return max(0, $delay - $elapsed);
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

		// Grupos recorrentes: se a rodada acabou e a campanha segue "enviando", recoloca destinos
		if ($canal === 'whatsapp') {
			$ativas = Campanhas::get(
				'id_admin = '.(int)$escolaId.' AND status = "enviando" AND canal = "whatsapp"'
			);
			while ($cAtiva = $ativas->fetchObject(Campanhas::class)) {
				if ($cAtiva->ehCampanhaGrupos()) {
					self::reabastecerFilaGrupos($cAtiva);
				}
			}
		}

		$fila = CampanhaFila::getPendentesPorCanal($escolaId, $canal, $limite);

		$enviadosGrupoDesde = 0;
		$delayGrupoSeg = 3600;
		if ($canal === 'whatsapp') {
			$delayGrupoSeg = self::delayGrupoSegundos($escolaId);
			$enviadosGrupoDesde = self::contarEnviadosGrupoDesde($escolaId, $delayGrupoSeg);
		}
		$podeEnviarGrupo = $enviadosGrupoDesde < 1;
		$grupoEnviadoNestaRun = false;

		while ($item = $fila->fetchObject(CampanhaFila::class)) {
			$resumo['processados']++;
			$campanha = Campanhas::getById((int)$item->campanha_id, $escolaId);

			if (!$campanha instanceof Campanhas || $campanha->status !== 'enviando') {
				$item->marcarErro('Campanha não está em envio.');
				$resumo['erros']++;
				$stats['erros']++;
				continue;
			}

			$isGrupo = $canal === 'whatsapp' && self::itemEhGrupoOuLista($item);
			if ($isGrupo) {
				// 1 grupo por intervalo: respeita agendada_para da campanha (prioritário)
				if ($grupoEnviadoNestaRun) {
					$stats['motivo'] = $stats['motivo'] ?: 'pacing_grupo';
					break;
				}
				// Trava curta evita 2 processos enviarem o mesmo intervalo
				$lockEnvio = 'cti_grp_send_'.(int)$escolaId;
				$lockRow = (new \App\Model\Db\Database('campanha_fila'))
					->execute("SELECT GET_LOCK('".addslashes($lockEnvio)."', 5) AS l")
					->fetch(\PDO::FETCH_ASSOC);
				if ((int)($lockRow['l'] ?? 0) !== 1) {
					$stats['motivo'] = $stats['motivo'] ?: 'pacing_grupo';
					break;
				}
				// Releia campanha após o lock
				$campanha = Campanhas::getById((int)$item->campanha_id, $escolaId);
				if (!$campanha instanceof Campanhas || $campanha->status !== 'enviando') {
					(new \App\Model\Db\Database('campanha_fila'))->execute("SELECT RELEASE_LOCK('".addslashes($lockEnvio)."')");
					$item->marcarErro('Campanha não está em envio.');
					$resumo['erros']++;
					$stats['erros']++;
					continue;
				}
				if (!empty($campanha->agendada_para) && strtotime($campanha->agendada_para) > time()) {
					(new \App\Model\Db\Database('campanha_fila'))->execute("SELECT RELEASE_LOCK('".addslashes($lockEnvio)."')");
					$stats['motivo'] = $stats['motivo'] ?: 'pacing_grupo';
					break;
				}
				if (!$podeEnviarGrupo) {
					(new \App\Model\Db\Database('campanha_fila'))->execute("SELECT RELEASE_LOCK('".addslashes($lockEnvio)."')");
					$stats['motivo'] = $stats['motivo'] ?: 'pacing_grupo';
					break;
				}
			} else {
				$lockEnvio = null;
			}

			$vars = [
				'nome'    => $item->nome ?? '',
				'contato' => $item->contato,
				'curso'   => self::resolverCursoItem($item),
				'escola'  => $nomeEscola,
			];

			$ok = false;
			$erroMsg = 'Falha no envio.';

			if ($canal === 'whatsapp') {
				$texto = CampanhaSegmentoHelper::textoParaWhatsapp(
					CampanhaSegmentoHelper::aplicarVariaveis((string)($campanha->mensagem ?? ''), $vars)
				);
				$segmento = json_decode($campanha->segmento ?? '{}', true) ?: [];
				$midia = is_array($segmento['midia'] ?? null) ? $segmento['midia'] : null;
				$envio = WhatsappEscolaService::enviarCampanha(
					$escolaId,
					(string)$item->contato,
					$texto,
					$midia
				);
				$ok = !empty($envio['ok']);
				$erroMsg = $envio['message'] ?? 'Falha no envio WhatsApp.';
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
				if ($isGrupo) {
					$grupoEnviadoNestaRun = true;
					$podeEnviarGrupo = false;
					// Próximo envio de grupo só após o intervalo configurado
					$campanha->agendada_para = date('Y-m-d H:i:s', time() + $delayGrupoSeg);
					$campanha->atualizar();
				}
			} else {
				$item->marcarErro($erroMsg);
				$resumo['erros']++;
				$stats['erros']++;
			}

			if ($isGrupo && !empty($lockEnvio)) {
				(new \App\Model\Db\Database('campanha_fila'))->execute("SELECT RELEASE_LOCK('".addslashes($lockEnvio)."')");
			}

			$campanha->recalcularTotais();

			// Delay só para 1:1 / e-mail — grupos usam pacing via agendada_para
			if ($aplicarDelay && $delay > 0 && !$isGrupo) {
				sleep($delay);
			}
		}

		return $stats;
	}

	private static function itemEhGrupoOuLista(CampanhaFila $item): bool {
		$tipo = strtolower(trim((string)($item->destinatario_tipo ?? '')));
		if ($tipo === 'grupo' || $tipo === 'lista' || $tipo === 'whatsapp_grupos') {
			return true;
		}
		return EvolutionApiService::isJidGrupoOuLista((string)($item->contato ?? ''));
	}

	/** Envios de grupo/lista no intervalo configurado (pacing). */
	private static function contarEnviadosGrupoDesde(int $idAdmin, int $segundos): int {
		$segundos = max(60, $segundos);
		$desde = date('Y-m-d H:i:s', time() - $segundos);
		$sql = '
			SELECT COUNT(*) AS qtd
			FROM campanha_fila f
			INNER JOIN campanhas c ON c.id = f.campanha_id
			WHERE f.id_admin = '.(int)$idAdmin.'
			  AND f.status = "enviado"
			  AND f.enviado_em >= "'.addslashes($desde).'"
			  AND c.canal = "whatsapp"
			  AND (
			    f.destinatario_tipo IN ("grupo","lista","whatsapp_grupos")
			    OR f.contato LIKE "%@g.us%"
			    OR f.contato LIKE "%@broadcast%"
			  )
		';
		$row = (new \App\Model\Db\Database('campanha_fila'))->execute($sql)->fetch(\PDO::FETCH_ASSOC);
		return (int)($row['qtd'] ?? 0);
	}

	/**
	 * Info de pacing de grupos para a UI (intervalo e tempo até o próximo envio).
	 * @return array{delay_segundos:int,delay_minutos:int,ultimo_envio:?string,proximo_em_segundos:int,pode_enviar:bool,coluna_ok:bool}
	 */
	public static function infoPacingGrupo(int $idAdmin): array {
		$colunaOk = EscolaIntegracoes::temColunaWhatsappGrupoDelay();
		$delay = self::delayGrupoSegundos($idAdmin);

		// Prioriza agendada_para da campanha de grupos em envio
		$sqlProx = '
			SELECT MIN(agendada_para) AS prox
			FROM campanhas
			WHERE id_admin = '.(int)$idAdmin.'
			  AND status = "enviando"
			  AND canal = "whatsapp"
			  AND agendada_para IS NOT NULL
			  AND segmento LIKE "%whatsapp_grupos%"
		';
		$rowProx = (new \App\Model\Db\Database('campanhas'))->execute($sqlProx)->fetch(\PDO::FETCH_ASSOC);
		$prox = !empty($rowProx['prox']) ? (string)$rowProx['prox'] : null;

		$sql = '
			SELECT MAX(f.enviado_em) AS ultimo
			FROM campanha_fila f
			INNER JOIN campanhas c ON c.id = f.campanha_id
			WHERE f.id_admin = '.(int)$idAdmin.'
			  AND f.status = "enviado"
			  AND c.canal = "whatsapp"
			  AND (
			    f.destinatario_tipo IN ("grupo","lista","whatsapp_grupos")
			    OR f.contato LIKE "%@g.us%"
			    OR f.contato LIKE "%@broadcast%"
			  )
		';
		$row = (new \App\Model\Db\Database('campanha_fila'))->execute($sql)->fetch(\PDO::FETCH_ASSOC);
		$ultimo = !empty($row['ultimo']) ? (string)$row['ultimo'] : null;

		$espera = 0;
		if ($prox) {
			$espera = max(0, strtotime($prox) - time());
		} elseif ($ultimo) {
			$elapsed = time() - strtotime($ultimo);
			$espera = max(0, $delay - $elapsed);
		}

		return [
			'delay_segundos'       => $delay,
			'delay_minutos'        => (int)max(1, (int)round($delay / 60)),
			'ultimo_envio'         => $ultimo,
			'proximo_em_segundos'  => $espera,
			'pode_enviar'          => $espera <= 0,
			'coluna_ok'            => $colunaOk,
		];
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

	/** Resolve {curso}: valor da fila (se existir) ou busca no lead/aluno. */
	private static function resolverCursoItem(CampanhaFila $item): string {
		$salvo = trim((string)($item->curso ?? ''));
		if ($salvo !== '') {
			return $salvo;
		}

		$tipo = (string)($item->destinatario_tipo ?? '');
		$id = (int)($item->destinatario_id ?? 0);
		if ($id <= 0) {
			return '';
		}

		if ($tipo === 'lead') {
			$lead = CrmLeads::getLeadById($id);
			if ($lead instanceof CrmLeads) {
				return trim((string)($lead->curso_interesse ?? ''));
			}
			return '';
		}

		if ($tipo === 'aluno') {
			return self::cursoAlunoAtivo((int)$item->id_admin, $id);
		}

		return '';
	}

	private static function cursoAlunoAtivo(int $idAdmin, int $idAluno): string {
		$sql = '
			SELECT t.nome AS curso
			FROM matriculas m
			LEFT JOIN trilhas t ON t.id = m.id_trilha
			WHERE m.id_aluno = ?
			  AND m.id_admin = ?
			  AND m.status = 0
			  AND m.fim >= ?
			ORDER BY m.fim DESC
			LIMIT 1
		';
		$row = (new \App\Model\Db\Database('matriculas'))->execute($sql, [
			$idAluno,
			$idAdmin,
			date('Y-m-d'),
		])->fetch(\PDO::FETCH_ASSOC);

		return trim((string)($row['curso'] ?? ''));
	}
}

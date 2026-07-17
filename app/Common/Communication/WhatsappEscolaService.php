<?php

namespace App\Common\Communication;

use App\Model\Entity\EscolaIntegracoes;
use App\Model\Entity\WhatsappNumero;

/**
 * Orquestra instância Evolution por escola (criar, QR, status, teste).
 */
class WhatsappEscolaService {

	public static function status(int $idAdmin): array {
		$api = EvolutionApiService::fromEnv();
		$integracao = EscolaIntegracoes::getByIdAdmin($idAdmin);
		$instance = ($integracao instanceof EscolaIntegracoes && !empty($integracao->evolution_instance))
			? (string)$integracao->evolution_instance
			: EvolutionApiService::nomeInstancia($idAdmin);

		$out = [
			'configurado_env' => $api->isConfigured(),
			'evolution_url'   => $api->isConfigured() ? $api->getBaseUrl() : '',
			'colunas_ok'      => EscolaIntegracoes::temColunasEvolution(),
			'tabelas_ok'      => \App\Model\Entity\WhatsappConversa::tabelaExiste()
				&& \App\Model\Entity\WhatsappMensagem::tabelaExiste(),
			'instance'        => $instance,
			'status'          => ($integracao instanceof EscolaIntegracoes)
				? (string)($integracao->evolution_status ?? 'disconnected')
				: 'disconnected',
			'ativo'           => ($integracao instanceof EscolaIntegracoes)
				? (int)($integracao->evolution_ativo ?? 0)
				: 0,
			'numero'          => ($integracao instanceof EscolaIntegracoes)
				? (string)($integracao->evolution_numero ?? '')
				: '',
			'delay'           => ($integracao instanceof EscolaIntegracoes)
				? (int)($integracao->whatsapp_delay_segundos ?? 5)
				: 5,
			'max_hora'        => ($integracao instanceof EscolaIntegracoes)
				? (int)($integracao->whatsapp_max_hora ?? 40)
				: 40,
			'grupo_delay'     => ($integracao instanceof EscolaIntegracoes && EscolaIntegracoes::temColunaWhatsappGrupoDelay())
				? (int)($integracao->whatsapp_grupo_delay_segundos ?? 3600)
				: 3600,
			'grupo_delay_ok'  => EscolaIntegracoes::temColunaWhatsappGrupoDelay(),
			'webhook_url'     => EvolutionApiService::webhookUrl($idAdmin),
			'conectado'       => false,
			'qrcode'          => null,
			'erro'            => null,
			'horario_inicio'  => ($integracao instanceof EscolaIntegracoes)
				? (string)($integracao->whatsapp_horario_inicio ?? '')
				: '',
			'horario_fim'     => ($integracao instanceof EscolaIntegracoes)
				? (string)($integracao->whatsapp_horario_fim ?? '')
				: '',
			'dias'            => ($integracao instanceof EscolaIntegracoes)
				? (string)($integracao->whatsapp_dias ?? '1,2,3,4,5')
				: '1,2,3,4,5',
			'msg_fora'        => ($integracao instanceof EscolaIntegracoes)
				? (string)($integracao->whatsapp_msg_fora ?? '')
				: '',
			'horario_ok'      => EscolaIntegracoes::temColunasHorarioWhatsapp(),
		];

		if (!$api->isConfigured()) {
			$out['erro'] = 'Configure EVOLUTION_URL e EVOLUTION_API_KEY no .env do servidor.';
			return $out;
		}

		if (!EscolaIntegracoes::temColunasEvolution()) {
			$out['erro'] = 'Execute o SQL das colunas Evolution no phpMyAdmin.';
			return $out;
		}

		$state = $api->connectionState($instance);
		if ($state !== null && $api->getLastHttpCode() < 400) {
			$estado = EvolutionApiService::extrairEstado($state);
			$out['status'] = $estado;
			$out['conectado'] = in_array($estado, ['open', 'connected'], true);
			self::persistirStatus($idAdmin, $instance, $estado, $integracao);
			// Webhook só depois de pareado — durante o QR ele derruba a conexão
			if ($out['conectado']) {
				self::garantirWebhook($api, $instance, $idAdmin);
			}
		} elseif ($api->getLastHttpCode() === 404) {
			// connectionState pode falhar no meio do QR; confirma na lista antes de alarmar
			if ($api->instanciaExiste($instance)) {
				$out['status'] = 'connecting';
				$out['erro'] = null;
			} else {
				$out['status'] = 'not_created';
				$out['erro'] = 'Instância não existe na Evolution. Use “Conectar / QR” ou “Trocar número”.';
				self::persistirStatus($idAdmin, $instance, 'not_created', $integracao, 0, '');
			}
		} else {
			$out['erro'] = $api->getLastError();
		}

		return $out;
	}

	public static function criarOuConectar(int $idAdmin): array {
		return self::conectarInterno($idAdmin, false);
	}

	/**
	 * Apaga a instância na Evolution e cria de novo (troca de número / após exclusão manual).
	 */
	public static function recriarInstancia(int $idAdmin): array {
		return self::conectarInterno($idAdmin, true);
	}

	private static function conectarInterno(int $idAdmin, bool $forcarRecriar): array {
		$api = EvolutionApiService::fromEnv();
		if (!$api->isConfigured()) {
			return ['ok' => false, 'message' => 'Evolution não configurada no .env.'];
		}
		if (!EscolaIntegracoes::temColunasEvolution()) {
			return ['ok' => false, 'message' => 'Execute o SQL das colunas Evolution antes.'];
		}

		$instance = EvolutionApiService::nomeInstancia($idAdmin);
		$webhook = EvolutionApiService::webhookUrl($idAdmin);
		$integracao = EscolaIntegracoes::getByIdAdmin($idAdmin);
		$logs = [];

		$state = $api->connectionState($instance);
		$httpState = $api->getLastHttpCode();
		$existe = $state !== null && $httpState < 400;
		$estadoAtual = $existe ? EvolutionApiService::extrairEstado($state) : '';
		$logs[] = 'state:HTTP '.$httpState.($estadoAtual !== '' ? ' '.$estadoAtual : '');

		// Já conectado: só configura webhook (nunca durante o QR)
		if ($existe && !$forcarRecriar && in_array($estadoAtual, ['open', 'connected'], true)) {
			self::persistirStatus($idAdmin, $instance, $estadoAtual, $integracao, 1);
			self::garantirWebhook($api, $instance, $idAdmin);
			return [
				'ok' => true,
				'message' => 'WhatsApp já está conectado. Para trocar de número, use “Trocar número”.',
				'instance' => $instance,
				'status' => $estadoAtual,
				'qrcode' => null,
				'conectado' => true,
				'webhook_url' => $webhook,
			];
		}

		// Só apaga/recria se pedido (Trocar número) ou se realmente não existe.
		// NÃO recriar só por "connecting" — isso apaga o QR no meio do scan.
		if (!$existe && !$forcarRecriar && $httpState === 404 && $api->instanciaExiste($instance)) {
			$existe = true;
			$estadoAtual = 'connecting';
			$logs[] = 'state:existe-via-lista';
		}

		$precisaRecriar = $forcarRecriar || !$existe;

		$created = null;
		if ($precisaRecriar) {
			if ($existe || $forcarRecriar) {
				$api->logout($instance);
				$logs[] = 'logout:HTTP '.$api->getLastHttpCode();
				$api->deleteInstance($instance);
				$logs[] = 'delete:HTTP '.$api->getLastHttpCode().' '.($api->getLastError() ?: 'ok');
				usleep(1500000);
			}

			$created = self::criarInstanciaComRetry($api, $instance, $logs);
			if ($created === null) {
				self::persistirStatus($idAdmin, $instance, 'error', $integracao, 0, '');
				return [
					'ok' => false,
					'message' => 'Não foi possível criar a instância na Evolution. '
						.($api->getLastError() ?: '').' ['.implode(' | ', $logs).']',
				];
			}
		}

		// Preferir QR do create (já vem com qrcode:true). Evitar connect+webhook no meio do pareamento.
		$qr = EvolutionApiService::montarQrParaExibicao($created);
		$connect = null;
		if ($qr === null) {
			usleep(500000);
			$connect = $api->obterQrComRetry($instance, 3, 600);
			$logs[] = 'connect:HTTP '.$api->getLastHttpCode();

			if (($connect === null || $api->getLastHttpCode() >= 400)
				&& ($api->getLastHttpCode() === 404 || stripos((string)$api->getLastError(), 'not found') !== false)
			) {
				$created = self::criarInstanciaComRetry($api, $instance, $logs);
				$qr = EvolutionApiService::montarQrParaExibicao($created);
				if ($qr === null) {
					$connect = $api->obterQrComRetry($instance, 3, 600);
					$logs[] = 'connect2:HTTP '.$api->getLastHttpCode();
				}
			}
			if ($qr === null) {
				$qr = EvolutionApiService::montarQrParaExibicao($connect);
			}
		} else {
			$logs[] = 'qr:from-create';
		}

		// NÃO chamar setWebhook aqui — reinicia o socket Baileys e invalida o QR
		$estado = EvolutionApiService::extrairEstado($connect)
			?: EvolutionApiService::extrairEstado($created)
			?: 'connecting';

		self::persistirStatus($idAdmin, $instance, $estado, $integracao, 1, '');

		$conectado = in_array($estado, ['open', 'connected'], true);
		if ($conectado) {
			self::garantirWebhook($api, $instance, $idAdmin);
		}

		if (!$qr && !$conectado) {
			return [
				'ok' => false,
				'message' => 'Falha ao obter QR Code. '
					.($api->getLastError() ? $api->getLastError().' ' : '')
					.'Tente “Trocar número”. ['.implode(' | ', $logs).']',
				'instance' => $instance,
				'status' => $estado,
				'qrcode' => null,
				'conectado' => false,
				'webhook_url' => $webhook,
			];
		}

		return [
			'ok'       => true,
			'message'  => $conectado
				? 'WhatsApp já está conectado.'
				: 'Escaneie o QR agora (válido por ~40s). Ele atualiza sozinho na tela. Não feche esta página.',
			'instance' => $instance,
			'status'   => $estado,
			'qrcode'   => $qr,
			'conectado'=> $conectado,
			'webhook_url' => $webhook,
		];
	}

	/** Configura webhook apenas com sessão já aberta (nunca no meio do QR). */
	private static function garantirWebhook(EvolutionApiService $api, string $instance, int $idAdmin): void {
		$api->setWebhook($instance, EvolutionApiService::webhookUrl($idAdmin));
	}

	private static function criarInstanciaComRetry(EvolutionApiService $api, string $instance, array &$logs): ?array {
		$created = $api->createInstance($instance, null);
		$logs[] = 'create:HTTP '.$api->getLastHttpCode().' '.($api->getLastError() ?: 'ok');

		if ($created !== null && $api->getLastHttpCode() < 400) {
			return $created;
		}

		$msg = (string)($api->getLastError() ?: '');
		$jaExiste = stripos($msg, 'already') !== false
			|| stripos($msg, 'exist') !== false
			|| stripos($msg, 'já') !== false
			|| $api->getLastHttpCode() === 403;

		if ($jaExiste) {
			$api->logout($instance);
			$api->deleteInstance($instance);
			$logs[] = 'delete-retry:HTTP '.$api->getLastHttpCode();
			usleep(1500000);
			$created = $api->createInstance($instance, null);
			$logs[] = 'create2:HTTP '.$api->getLastHttpCode().' '.($api->getLastError() ?: 'ok');
			if ($created !== null && $api->getLastHttpCode() < 400) {
				return $created;
			}
		}

		return null;
	}

	public static function obterQr(int $idAdmin): array {
		$api = EvolutionApiService::fromEnv();
		if (!$api->isConfigured()) {
			return ['ok' => false, 'message' => 'Evolution não configurada no .env.'];
		}

		$integracao = EscolaIntegracoes::getByIdAdmin($idAdmin);
		$instance = ($integracao instanceof EscolaIntegracoes && !empty($integracao->evolution_instance))
			? (string)$integracao->evolution_instance
			: EvolutionApiService::nomeInstancia($idAdmin);

		$state = $api->connectionState($instance);
		if ($api->getLastHttpCode() === 404 && !$api->instanciaExiste($instance)) {
			// Só cria se realmente não existir — evita apagar QR por 404 falso
			return self::criarOuConectar($idAdmin);
		}

		// Só atualiza o QR — não apaga a instância
		$connect = $api->obterQrComRetry($instance, 2, 400);
		if ($connect === null && $api->getLastHttpCode() >= 400) {
			if ($api->getLastHttpCode() === 404 && !$api->instanciaExiste($instance)) {
				return self::criarOuConectar($idAdmin);
			}
			return ['ok' => false, 'message' => $api->getLastError() ?: 'Falha ao obter QR.'];
		}

		$qr = EvolutionApiService::montarQrParaExibicao($connect);
		$estado = EvolutionApiService::extrairEstado($connect);
		self::persistirStatus($idAdmin, $instance, $estado ?: 'connecting', $integracao);

		$conectado = in_array($estado, ['open', 'connected'], true);
		if ($conectado) {
			self::garantirWebhook($api, $instance, $idAdmin);
		}

		return [
			'ok'     => true,
			'qrcode' => $qr,
			'status' => $estado ?: 'connecting',
			'conectado' => $conectado,
			'message'=> $conectado
				? 'Já conectado — QR não é necessário. Para outro número use “Trocar número”.'
				: ($qr ? 'QR atualizado.' : 'Sem QR no momento. Use “Trocar número” se persistir.'),
		];
	}

	public static function salvarLimites(int $idAdmin, array $dados): array {
		if (!EscolaIntegracoes::temColunasEvolution()) {
			return ['ok' => false, 'message' => 'Execute o SQL das colunas Evolution antes.'];
		}

		$existente = EscolaIntegracoes::getByIdAdmin($idAdmin);
		$ob = $existente instanceof EscolaIntegracoes ? $existente : new EscolaIntegracoes;
		$ob->id_admin = $idAdmin;
		$ob->touchEvolution = true;
		$ob->smtp_pass = null;
		$ob->evolution_ativo = !empty($dados['evolution_ativo']) ? 1 : 0;
		$ob->whatsapp_delay_segundos = max(1, (int)($dados['whatsapp_delay_segundos'] ?? 5));
		$ob->whatsapp_max_hora = max(1, (int)($dados['whatsapp_max_hora'] ?? 40));
		// UI envia minutos; persistimos segundos (mín. 1 min)
		if (EscolaIntegracoes::temColunaWhatsappGrupoDelay()) {
			$minutosGrupo = (int)($dados['whatsapp_grupo_delay_minutos'] ?? 0);
			if ($minutosGrupo <= 0 && isset($dados['whatsapp_grupo_delay_segundos'])) {
				$minutosGrupo = (int)ceil(((int)$dados['whatsapp_grupo_delay_segundos']) / 60);
			}
			if ($minutosGrupo <= 0) {
				$minutosGrupo = 60;
			}
			$ob->whatsapp_grupo_delay_segundos = max(60, $minutosGrupo * 60);
		}
		$ob->whatsapp_horario_inicio = trim((string)($dados['whatsapp_horario_inicio'] ?? '')) ?: null;
		$ob->whatsapp_horario_fim = trim((string)($dados['whatsapp_horario_fim'] ?? '')) ?: null;
		$ob->whatsapp_dias = trim((string)($dados['whatsapp_dias'] ?? '1,2,3,4,5')) ?: '1,2,3,4,5';
		$ob->whatsapp_msg_fora = trim((string)($dados['whatsapp_msg_fora'] ?? ''));

		if (!$ob->salvar()) {
			return ['ok' => false, 'message' => EscolaIntegracoes::getUltimoErro() ?: 'Falha ao salvar.'];
		}

		return ['ok' => true, 'message' => 'Configurações de WhatsApp salvas.'];
	}

	public static function testarEnvio(int $idAdmin, string $telefone, string $mensagem = ''): array {
		$texto = trim($mensagem) !== '' ? trim($mensagem) : 'Teste de WhatsApp — Painel CTI.';
		return self::enviarTexto($idAdmin, $telefone, $texto);
	}

	/**
	 * Grupos (@g.us) e listas de transmissão (@broadcast) da Evolution.
	 * @return array{ok:bool,message?:string,itens:array<int,array{jid:string,nome:string,kind:string}>}
	 */
	public static function listarGruposEListas(int $idAdmin): array {
		$status = self::status($idAdmin);
		if (empty($status['conectado'])) {
			return ['ok' => false, 'message' => 'WhatsApp não está conectado.', 'itens' => []];
		}

		$api = EvolutionApiService::fromEnv();
		$instance = (string)$status['instance'];
		$itens = [];
		$vistos = [];

		$grupos = $api->fetchAllGroups($instance, false);
		if (is_array($grupos)) {
			$rows = isset($grupos[0]) || $grupos === [] ? $grupos : ($grupos['groups'] ?? $grupos['data'] ?? [$grupos]);
			foreach ($rows as $g) {
				if (!is_array($g)) {
					continue;
				}
				$jid = (string)($g['id'] ?? $g['jid'] ?? $g['groupId'] ?? '');
				if ($jid !== '' && strpos($jid, '@') === false) {
					$jid .= '@g.us';
				}
				$jid = EvolutionApiService::normalizarDestino($jid);
				if (!EvolutionApiService::isJidGrupoOuLista($jid) || isset($vistos[$jid])) {
					continue;
				}
				$vistos[$jid] = true;
				$itens[] = [
					'jid'  => $jid,
					'nome' => (string)($g['subject'] ?? $g['name'] ?? $g['pushName'] ?? $jid),
					'kind' => 'grupo',
				];
			}
		}

		$chats = $api->findChats($instance);
		if (is_array($chats)) {
			$rows = isset($chats[0]) || $chats === [] ? $chats : ($chats['chats'] ?? $chats['data'] ?? [$chats]);
			foreach ($rows as $c) {
				if (!is_array($c)) {
					continue;
				}
				$jid = (string)($c['id'] ?? $c['remoteJid'] ?? $c['jid'] ?? '');
				$jid = EvolutionApiService::normalizarDestino($jid);
				if ($jid === '' || isset($vistos[$jid])) {
					continue;
				}
				if (strpos(strtolower($jid), '@broadcast') === false && strpos(strtolower($jid), '@g.us') === false) {
					continue;
				}
				$vistos[$jid] = true;
				$kind = strpos(strtolower($jid), '@broadcast') !== false ? 'lista' : 'grupo';
				$itens[] = [
					'jid'  => $jid,
					'nome' => (string)($c['name'] ?? $c['pushName'] ?? $c['subject'] ?? $jid),
					'kind' => $kind,
				];
			}
		}

		usort($itens, static function ($a, $b) {
			return strcasecmp($a['nome'], $b['nome']);
		});

		return [
			'ok' => true,
			'itens' => $itens,
			'message' => count($itens)
				? count($itens).' destino(s) encontrados.'
				: 'Nenhum grupo/lista retornado. Confira se a Evolution tem permissão de grupos e se há listas no aparelho.',
		];
	}

	/** Checklist operacional (UI Comunicação). */
	public static function checklist(int $idAdmin): array {
		$status = self::status($idAdmin);
		$api = EvolutionApiService::fromEnv();
		$itens = [];

		$itens[] = [
			'ok' => !empty($status['configurado_env']),
			'label' => 'Credenciais EVOLUTION_URL / EVOLUTION_API_KEY no .env',
		];
		$itens[] = [
			'ok' => !empty($status['colunas_ok']),
			'label' => 'Colunas Evolution em escola_integracoes',
		];
		$itens[] = [
			'ok' => !empty($status['tabelas_ok']),
			'label' => 'Tabelas whatsapp_conversas / whatsapp_mensagens',
		];
		$itens[] = [
			'ok' => !empty($status['conectado']),
			'label' => 'Instância conectada (status open)',
			'detalhe' => 'Status atual: '.($status['status'] ?? '—'),
		];
		$itens[] = [
			'ok' => !empty($status['webhook_url']),
			'label' => 'URL do webhook configurável',
			'detalhe' => (string)($status['webhook_url'] ?? ''),
		];
		$itens[] = [
			'ok' => !empty($status['numero']),
			'label' => 'Número pareado preenchido',
			'detalhe' => (string)($status['numero'] ?? 'ainda vazio'),
		];

		$fora = self::estaForaExpediente($idAdmin);
		$itens[] = [
			'ok' => true,
			'label' => 'Horário de atendimento',
			'detalhe' => $fora['configurado']
				? ($fora['fora'] ? 'Fora do expediente agora' : 'Dentro do expediente agora')
				: 'Não configurado (atende 24h)',
		];

		$itens[] = [
			'ok' => true,
			'label' => 'Se travar em Connecting',
			'detalhe' => 'Use “Trocar número”, delete a instância no painel Evolution se preciso, escaneie o QR sem mexer no webhook.',
		];
		$itens[] = [
			'ok' => true,
			'label' => 'Cron / fila de campanhas',
			'detalhe' => 'php worker/campanhas.php (e-mail + WhatsApp). Cobrança: php worker/cobranca.php',
		];

		return [
			'conectado' => !empty($status['conectado']),
			'status' => $status,
			'itens' => $itens,
			'api_ok' => $api->isConfigured(),
		];
	}

	/**
	 * @return array{fora:bool,configurado:bool,mensagem:string}
	 */
	public static function estaForaExpediente(int $idAdmin): array {
		$cfg = EscolaIntegracoes::getByIdAdmin($idAdmin);
		$out = ['fora' => false, 'configurado' => false, 'mensagem' => ''];
		if (!$cfg instanceof EscolaIntegracoes || !EscolaIntegracoes::temColunasHorarioWhatsapp()) {
			return $out;
		}

		$inicio = trim((string)($cfg->whatsapp_horario_inicio ?? ''));
		$fim = trim((string)($cfg->whatsapp_horario_fim ?? ''));
		$dias = trim((string)($cfg->whatsapp_dias ?? ''));
		if ($inicio === '' || $fim === '') {
			return $out;
		}

		$out['configurado'] = true;
		$out['mensagem'] = trim((string)($cfg->whatsapp_msg_fora ?? ''))
			?: 'Olá! Nosso atendimento pelo WhatsApp funciona em horário comercial. Retornaremos assim que possível.';

		$diaSemana = (int)date('N'); // 1=seg … 7=dom
		$diasOk = [];
		foreach (preg_split('/[^0-9]+/', $dias) ?: [] as $p) {
			$n = (int)$p;
			if ($n >= 1 && $n <= 7) {
				$diasOk[] = $n;
			}
		}
		if ($diasOk && !in_array($diaSemana, $diasOk, true)) {
			$out['fora'] = true;
			return $out;
		}

		$agora = date('H:i');
		$ini = substr($inicio, 0, 5);
		$fi = substr($fim, 0, 5);
		if ($ini <= $fi) {
			$out['fora'] = ($agora < $ini || $agora >= $fi);
		} else {
			// cruza meia-noite
			$out['fora'] = ($agora < $ini && $agora >= $fi);
		}
		return $out;
	}

	/**
	 * Envio de texto sem conversa de inbox (campanhas, cobrança, aniversário, teste).
	 * @return array{ok:bool,message:string}
	 */
	public static function enviarTexto(int $idAdmin, string $telefone, string $mensagem): array {
		$status = self::status($idAdmin);
		if (!$status['conectado']) {
			return ['ok' => false, 'message' => 'WhatsApp não está conectado.'];
		}

		$texto = trim($mensagem);
		if ($texto === '') {
			return ['ok' => false, 'message' => 'Mensagem vazia.'];
		}

		$api = EvolutionApiService::fromEnv();
		$res = $api->sendText($status['instance'], $telefone, $texto);

		if ($res === null || $api->getLastHttpCode() >= 400) {
			return ['ok' => false, 'message' => $api->getLastError() ?: 'Falha ao enviar mensagem.'];
		}

		return ['ok' => true, 'message' => 'Mensagem enviada.'];
	}

	/**
	 * Envio de campanha WhatsApp (texto e/ou mídia) para telefone, grupo ou lista.
	 * @param array{tipo?:string,path?:string,nome?:string,mime?:string}|null $midia
	 * @return array{ok:bool,message:string}
	 */
	public static function enviarCampanha(int $idAdmin, string $destino, string $texto, ?array $midia = null): array {
		$status = self::status($idAdmin);
		if (empty($status['conectado'])) {
			return ['ok' => false, 'message' => 'WhatsApp não está conectado.'];
		}

		$instance = (string)$status['instance'];
		$api = EvolutionApiService::fromEnv();
		$destino = EvolutionApiService::normalizarDestino($destino);
		if ($destino === '') {
			return ['ok' => false, 'message' => 'Destino inválido.'];
		}

		$texto = trim($texto);
		$tipoMidia = strtolower((string)($midia['tipo'] ?? ''));
		$pathRel = ltrim(str_replace('\\', '/', (string)($midia['path'] ?? '')), '/');
		$pathAbs = $pathRel !== '' ? self::caminhoUploadAbsoluto($pathRel) : null;
		$mime = $midia['mime'] ?? null;
		$nome = $midia['nome'] ?? ($pathAbs ? basename($pathAbs) : null);

		if (in_array($tipoMidia, ['image', 'document', 'audio'], true)) {
			if ($pathAbs === null || !is_file($pathAbs)) {
				return ['ok' => false, 'message' => 'Arquivo de mídia da campanha não encontrado no servidor.'];
			}

			if ($tipoMidia === 'audio') {
				$res = $api->sendAudio($instance, $destino, $pathAbs, is_string($mime) ? $mime : null);
				if ($res === null || $api->getLastHttpCode() >= 400) {
					return ['ok' => false, 'message' => $api->getLastError() ?: 'Falha ao enviar áudio.'];
				}
				if ($texto !== '') {
					$api->sendText($instance, $destino, $texto);
				}
				return ['ok' => true, 'message' => 'Áudio enviado.'];
			}

			$mediatype = $tipoMidia === 'document' ? 'document' : 'image';
			$res = $api->sendMedia(
				$instance,
				$destino,
				$pathAbs,
				$mediatype,
				is_string($mime) ? $mime : null,
				$texto !== '' ? $texto : null,
				is_string($nome) ? $nome : null
			);
			if ($res === null || $api->getLastHttpCode() >= 400) {
				return ['ok' => false, 'message' => $api->getLastError() ?: 'Falha ao enviar mídia.'];
			}
			return ['ok' => true, 'message' => 'Mídia enviada.'];
		}

		if ($texto === '') {
			return ['ok' => false, 'message' => 'Mensagem vazia.'];
		}

		$res = $api->sendText($instance, $destino, $texto);
		if ($res === null || $api->getLastHttpCode() >= 400) {
			return ['ok' => false, 'message' => $api->getLastError() ?: 'Falha ao enviar mensagem.'];
		}
		return ['ok' => true, 'message' => 'Mensagem enviada.'];
	}

	private static function caminhoUploadAbsoluto(string $relative): ?string {
		$relative = ltrim(str_replace(['..', '\\'], ['', '/'], $relative), '/');
		if ($relative === '' || strpos($relative, 'uploads/') !== 0) {
			return null;
		}
		$root = rtrim(str_replace('\\', '/', realpath(__DIR__.'/../../../') ?: (__DIR__.'/../../..')), '/');
		$abs = $root.'/'.$relative;
		return is_file($abs) ? $abs : null;
	}

	/**
	 * @param bool $apagarInstancia true = remove na Evolution (necessário para trocar número com certeza)
	 */
	public static function desconectar(int $idAdmin, bool $apagarInstancia = false): array {
		$api = EvolutionApiService::fromEnv();
		$integracao = EscolaIntegracoes::getByIdAdmin($idAdmin);
		$instance = ($integracao instanceof EscolaIntegracoes && !empty($integracao->evolution_instance))
			? (string)$integracao->evolution_instance
			: EvolutionApiService::nomeInstancia($idAdmin);

		$api->logout($instance);
		$msgLogout = $api->getLastError();

		if ($apagarInstancia) {
			$api->deleteInstance($instance);
		}

		self::persistirStatus($idAdmin, $instance, 'disconnected', $integracao, 0, '');

		return [
			'ok' => true,
			'message' => $apagarInstancia
				? 'Instância removida na Evolution. Clique em “Conectar / QR” para parear um novo número.'
				: 'Sessão desconectada no WhatsApp. Se ainda aparecer conectada na Evolution, use “Trocar número”.'
				.($msgLogout && $api->getLastHttpCode() >= 400 ? ' (aviso: '.$msgLogout.')' : ''),
		];
	}

	public static function atualizarStatusConexao(int $idAdmin, string $estado, ?string $numero = null): void {
		$integracao = EscolaIntegracoes::getByIdAdmin($idAdmin);
		$instance = ($integracao instanceof EscolaIntegracoes && !empty($integracao->evolution_instance))
			? (string)$integracao->evolution_instance
			: EvolutionApiService::nomeInstancia($idAdmin);
		$ativo = in_array($estado, ['open', 'connected'], true) ? 1 : null;
		self::persistirStatus($idAdmin, $instance, $estado, $integracao, $ativo, $numero);

		if (in_array($estado, ['open', 'connected'], true)) {
			$api = EvolutionApiService::fromEnv();
			if ($api->isConfigured()) {
				self::garantirWebhook($api, $instance, $idAdmin);
			}
		}
	}

	private static function persistirStatus(
		int $idAdmin,
		string $instance,
		string $estado,
		$integracao = null,
		?int $ativo = null,
		?string $numero = null
	): void {
		if (!EscolaIntegracoes::temColunasEvolution()) {
			return;
		}

		$ob = $integracao instanceof EscolaIntegracoes ? $integracao : new EscolaIntegracoes;
		$ob->id_admin = $idAdmin;
		$ob->touchEvolution = true;
		$ob->smtp_pass = null;
		$ob->evolution_instance = $instance;
		$ob->evolution_status = $estado;
		if ($ativo !== null) {
			$ob->evolution_ativo = $ativo;
		} elseif (!($integracao instanceof EscolaIntegracoes)) {
			$ob->evolution_ativo = 1;
		}
		// null = não altera; '' = limpa número
		if ($numero !== null) {
			$ob->evolution_numero = $numero;
		}
		if (!isset($ob->whatsapp_delay_segundos)) {
			$ob->whatsapp_delay_segundos = 5;
		}
		if (!isset($ob->whatsapp_max_hora)) {
			$ob->whatsapp_max_hora = 40;
		}
		$ob->salvar();

		WhatsappNumero::syncFromIntegracao(
			$idAdmin,
			$instance,
			$estado,
			($numero !== null && $numero !== '') ? $numero : null
		);
	}
}

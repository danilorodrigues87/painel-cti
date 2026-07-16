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
			'webhook_url'     => EvolutionApiService::webhookUrl($idAdmin),
			'conectado'       => false,
			'qrcode'          => null,
			'erro'            => null,
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
		} elseif ($api->getLastHttpCode() === 404) {
			$out['status'] = 'not_created';
			$out['erro'] = 'Instância não existe na Evolution. Use “Conectar / QR” ou “Trocar número”.';
			self::persistirStatus($idAdmin, $instance, 'not_created', $integracao, 0, '');
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
		$logs[] = 'state:HTTP '.$httpState;

		if ($forcarRecriar || !$existe) {
			if ($existe || $forcarRecriar) {
				$api->logout($instance);
				$logs[] = 'logout:HTTP '.$api->getLastHttpCode();
				$api->deleteInstance($instance);
				$logs[] = 'delete:HTTP '.$api->getLastHttpCode().' '.($api->getLastError() ?: 'ok');
				// pequena pausa para a Evolution liberar o nome
				usleep(700000);
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
		} else {
			$created = null;
			$estadoAtual = EvolutionApiService::extrairEstado($state);
			// Se já está open, só informa; senão pede QR
			if (in_array($estadoAtual, ['open', 'connected'], true) && !$forcarRecriar) {
				self::persistirStatus($idAdmin, $instance, $estadoAtual, $integracao, 1);
				$api->setWebhook($instance, $webhook);
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
		}

		$connect = $api->obterQrComRetry($instance);
		$logs[] = 'connect:HTTP '.$api->getLastHttpCode();

		// Se connect falhou porque a instância sumiu, cria e tenta de novo
		if (($connect === null || $api->getLastHttpCode() >= 400)
			&& ($api->getLastHttpCode() === 404 || stripos((string)$api->getLastError(), 'not found') !== false)
		) {
			$created = self::criarInstanciaComRetry($api, $instance, $logs);
			$connect = $api->obterQrComRetry($instance);
			$logs[] = 'connect2:HTTP '.$api->getLastHttpCode();
		}

		$qr = EvolutionApiService::montarQrParaExibicao($connect)
			?? EvolutionApiService::montarQrParaExibicao($created);

		$api->setWebhook($instance, $webhook);
		$logs[] = 'webhook:HTTP '.$api->getLastHttpCode();

		$estado = EvolutionApiService::extrairEstado($connect)
			?: EvolutionApiService::extrairEstado($created)
			?: 'connecting';

		// Limpa número antigo ao gerar novo QR (ainda não pareado)
		self::persistirStatus($idAdmin, $instance, $estado, $integracao, 1, $forcarRecriar ? '' : null);

		$conectado = in_array($estado, ['open', 'connected'], true);

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
				: 'Escaneie o QR Code no WhatsApp do celular (Aparelhos conectados).',
			'instance' => $instance,
			'status'   => $estado,
			'qrcode'   => $qr,
			'conectado'=> $conectado,
			'webhook_url' => $webhook,
		];
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
			usleep(900000);
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
		if ($api->getLastHttpCode() === 404) {
			return self::criarOuConectar($idAdmin);
		}

		$connect = $api->obterQrComRetry($instance);
		if ($connect === null && $api->getLastHttpCode() >= 400) {
			// Instância sumiu no meio do caminho
			if ($api->getLastHttpCode() === 404) {
				return self::criarOuConectar($idAdmin);
			}
			return ['ok' => false, 'message' => $api->getLastError() ?: 'Falha ao obter QR.'];
		}

		$qr = EvolutionApiService::montarQrParaExibicao($connect);
		$estado = EvolutionApiService::extrairEstado($connect);
		self::persistirStatus($idAdmin, $instance, $estado ?: 'connecting', $integracao);

		$conectado = in_array($estado, ['open', 'connected'], true);

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

		if (!$ob->salvar()) {
			return ['ok' => false, 'message' => EscolaIntegracoes::getUltimoErro() ?: 'Falha ao salvar.'];
		}

		return ['ok' => true, 'message' => 'Configurações de WhatsApp salvas.'];
	}

	public static function testarEnvio(int $idAdmin, string $telefone, string $mensagem = ''): array {
		$status = self::status($idAdmin);
		if (!$status['conectado']) {
			return ['ok' => false, 'message' => 'WhatsApp não está conectado. Gere o QR e escaneie antes.'];
		}

		$api = EvolutionApiService::fromEnv();
		$texto = trim($mensagem) !== '' ? trim($mensagem) : 'Teste de WhatsApp — Painel CTI.';
		$res = $api->sendText($status['instance'], $telefone, $texto);

		if ($res === null || $api->getLastHttpCode() >= 400) {
			return ['ok' => false, 'message' => $api->getLastError() ?: 'Falha ao enviar mensagem.'];
		}

		return ['ok' => true, 'message' => 'Mensagem de teste enviada.'];
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
		self::persistirStatus($idAdmin, $instance, $estado, $integracao, null, $numero);
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

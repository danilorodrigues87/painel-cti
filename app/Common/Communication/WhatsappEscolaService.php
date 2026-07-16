<?php

namespace App\Common\Communication;

use App\Model\Entity\EscolaIntegracoes;

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
			$out['erro'] = null;
		} else {
			$out['erro'] = $api->getLastError();
		}

		return $out;
	}

	public static function criarOuConectar(int $idAdmin): array {
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

		$state = $api->connectionState($instance);
		$existe = $state !== null && $api->getLastHttpCode() < 400;
		$created = null;

		if (!$existe) {
			$created = $api->createInstance($instance, $webhook);
			if ($created === null || $api->getLastHttpCode() >= 400) {
				$msg = $api->getLastError() ?: 'Falha ao criar instância.';
				if (stripos($msg, 'already') === false && stripos($msg, 'exist') === false) {
					return ['ok' => false, 'message' => $msg];
				}
			}
		}

		$api->setWebhook($instance, $webhook);

		$connect = $api->connect($instance);
		$qr = EvolutionApiService::extrairQrBase64($connect)
			?? EvolutionApiService::extrairQrBase64($created);

		$estado = EvolutionApiService::extrairEstado($connect)
			?: EvolutionApiService::extrairEstado($state)
			?: 'connecting';

		self::persistirStatus($idAdmin, $instance, $estado, $integracao, 1);

		return [
			'ok'       => true,
			'message'  => $qr ? 'Escaneie o QR Code no WhatsApp do celular.' : 'Instância pronta. Atualize o status.',
			'instance' => $instance,
			'status'   => $estado,
			'qrcode'   => $qr,
			'conectado'=> in_array($estado, ['open', 'connected'], true),
			'webhook_url' => $webhook,
		];
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

		$connect = $api->connect($instance);
		if ($connect === null && $api->getLastHttpCode() >= 400) {
			return ['ok' => false, 'message' => $api->getLastError() ?: 'Falha ao obter QR.'];
		}

		$qr = EvolutionApiService::extrairQrBase64($connect);
		$estado = EvolutionApiService::extrairEstado($connect);
		self::persistirStatus($idAdmin, $instance, $estado ?: 'connecting', $integracao);

		return [
			'ok'     => true,
			'qrcode' => $qr,
			'status' => $estado ?: 'connecting',
			'conectado' => in_array($estado, ['open', 'connected'], true),
			'message'=> $qr ? 'QR atualizado.' : 'Sem QR no momento (já conectado ou aguardando).',
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

	public static function desconectar(int $idAdmin): array {
		$api = EvolutionApiService::fromEnv();
		$integracao = EscolaIntegracoes::getByIdAdmin($idAdmin);
		$instance = ($integracao instanceof EscolaIntegracoes && !empty($integracao->evolution_instance))
			? (string)$integracao->evolution_instance
			: EvolutionApiService::nomeInstancia($idAdmin);

		$api->logout($instance);
		self::persistirStatus($idAdmin, $instance, 'disconnected', $integracao, 0);

		return ['ok' => true, 'message' => 'Sessão desconectada. Gere um novo QR para reconectar.'];
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
		if ($numero !== null && $numero !== '') {
			$ob->evolution_numero = $numero;
		}
		if (!isset($ob->whatsapp_delay_segundos)) {
			$ob->whatsapp_delay_segundos = 5;
		}
		if (!isset($ob->whatsapp_max_hora)) {
			$ob->whatsapp_max_hora = 40;
		}
		$ob->salvar();
	}
}

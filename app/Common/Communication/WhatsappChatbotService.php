<?php

namespace App\Common\Communication;

use App\Model\Entity\WhatsappConversa;
use App\Model\Entity\WhatsappMensagem;
use App\Model\Entity\WhatsappNumero;
use App\Model\Entity\WhatsappSetor;
use App\Model\Entity\EscolaIntegracoes;

/**
 * Chatbot simples: menu de setores → fila humana.
 */
class WhatsappChatbotService {

	private static $lastError = null;

	public static function getLastError(): ?string {
		return self::$lastError;
	}

	public static function aoReceberMensagem(WhatsappConversa $conversa, ?string $texto, bool $fromMe): void {
		if ($fromMe) {
			return;
		}
		if (!WhatsappConversa::temColunasChatbot()) {
			return;
		}
		if (!WhatsappSetor::tabelaExiste()) {
			return;
		}

		$estado = (string)($conversa->chatbot_estado ?: 'novo');
		$status = (string)($conversa->status ?: '');

		// Atendimento humano ativo: não interferir
		if ($estado === 'humano' && $status !== 'fechada') {
			return;
		}
		if ($conversa->id_atendente && $estado !== 'encerrado' && $status !== 'fechada') {
			return;
		}

		$texto = trim((string)$texto);
		$idAdmin = (int)$conversa->id_admin;

		WhatsappSetor::garantirPadroes($idAdmin);
		$setores = WhatsappSetor::listarAtivos($idAdmin);

		// Após encerrar: qualquer nova mensagem do cliente reinicia o fluxo (menu)
		if ($estado === 'encerrado' || $status === 'fechada') {
			self::reiniciarAtendimento($conversa, $setores);
			return;
		}

		if ($estado === 'novo' || $estado === '' || $estado === 'aguardando_setor') {
			if ($estado === 'aguardando_setor') {
				// Imagem/áudio sem texto: só aguarda o número do setor
				if ($texto === '') {
					return;
				}
				$escolha = self::interpretarEscolha($texto, $setores);
				if ($escolha !== null) {
					self::enviarParaSetor($conversa, $escolha);
					return;
				}
				if (self::pedeMenu($texto)) {
					self::enviarMenu($conversa, $setores);
					return;
				}
				self::enviarTexto(
					$conversa,
					"Opção inválida. Digite o *número* do setor ou *menu* para ver as opções novamente."
				);
				self::enviarMenu($conversa, $setores);
				return;
			}

			self::enviarMenu($conversa, $setores);
			return;
		}

		if ($estado === 'fila' && self::pedeMenu($texto)) {
			$conversa->atualizar([
				'chatbot_estado' => 'aguardando_setor',
				'setor_id'       => null,
				'status'         => 'aberta',
			]);
			self::enviarMenu($conversa, $setores);
		}
	}

	private static function reiniciarAtendimento(WhatsappConversa $conversa, array $setores): void {
		$conversa->atualizar([
			'chatbot_estado' => 'novo',
			'status'         => 'aberta',
			'setor_id'       => null,
			'id_atendente'   => null,
			'assigned_at'    => null,
		]);
		self::enviarMenu($conversa, $setores);
	}

	private static function pedeMenu(string $texto): bool {
		$t = mb_strtolower(trim($texto), 'UTF-8');
		return in_array($t, ['menu', '0', 'inicio', 'início', 'oi', 'olá', 'ola', 'bom dia', 'boa tarde', 'boa noite'], true);
	}

	private static function interpretarEscolha(string $texto, array $setores): ?array {
		$t = trim($texto);
		if (preg_match('/^(\d{1,2})$/', $t, $m)) {
			$idx = (int)$m[1] - 1;
			if (isset($setores[$idx])) {
				return $setores[$idx];
			}
		}

		$tNorm = self::normalizar($t);
		foreach ($setores as $s) {
			if (self::normalizar((string)$s['nome']) === $tNorm
				|| self::normalizar((string)$s['slug']) === $tNorm) {
				return $s;
			}
		}
		return null;
	}

	private static function normalizar(string $s): string {
		$s = mb_strtolower(trim($s), 'UTF-8');
		$s = preg_replace('/\s+/', '', $s) ?? $s;
		return $s;
	}

	private static function enviarMenu(WhatsappConversa $conversa, array $setores): void {
		if (!$setores) {
			self::enviarTexto($conversa, 'Olá! No momento não há setores configurados. Aguarde um atendente.');
			$conversa->atualizar(['chatbot_estado' => 'fila', 'status' => 'aberta']);
			return;
		}

		$linhas = ["Olá! Sou o assistente virtual. Escolha o setor digitando o *número*:\n"];
		foreach ($setores as $i => $s) {
			$linhas[] = '*'.($i + 1).'* - '.$s['nome'];
		}
		$linhas[] = "\nDigite *menu* a qualquer momento para ver as opções novamente.";

		self::enviarTexto($conversa, implode("\n", $linhas));
		$conversa->atualizar([
			'chatbot_estado' => 'aguardando_setor',
			'status'         => 'aberta',
			'setor_id'       => null,
			'id_atendente'   => null,
		]);
	}

	private static function enviarParaSetor(WhatsappConversa $conversa, array $setor): void {
		$msg = trim((string)($setor['mensagem_fila'] ?? ''));
		if ($msg === '') {
			$msg = 'Você foi direcionado para *'.$setor['nome'].'*. Aguarde, em breve um atendente irá responder.';
		}

		$conversa->atualizar([
			'chatbot_estado' => 'fila',
			'setor_id'       => (int)$setor['id'],
			'status'         => 'aberta',
			'id_atendente'   => null,
		]);

		self::enviarTexto($conversa, $msg);
	}

	public static function enviarTexto(WhatsappConversa $conversa, string $texto): bool {
		self::$lastError = null;
		$idAdmin = (int)$conversa->id_admin;
		$instance = self::instanceDaConversa($conversa);
		if ($instance === '') {
			self::$lastError = 'Instância WhatsApp não encontrada.';
			return false;
		}

		$api = EvolutionApiService::fromEnv();
		$res = $api->sendText($instance, (string)$conversa->telefone, $texto);
		$ok = $res !== null && $api->getLastHttpCode() < 400;
		if (!$ok) {
			self::$lastError = $api->getLastError() ?: 'Falha ao enviar texto.';
			return false;
		}

		WhatsappMensagem::registrar([
			'id_admin'      => $idAdmin,
			'conversa_id'   => (int)$conversa->id,
			'direction'     => 'out',
			'tipo'          => 'text',
			'corpo'         => $texto,
			'wa_message_id' => $res['key']['id'] ?? ($res['message']['key']['id'] ?? null),
			'status'        => 'sent',
		]);

		$conversa->tocarUltimaMensagem();
		return true;
	}

	/**
	 * @param array{relative:string,url:string,mimetype?:?string} $arquivo
	 */
	public static function enviarImagem(WhatsappConversa $conversa, array $arquivo, ?string $caption = null): bool {
		return self::enviarArquivoMidia($conversa, $arquivo, 'image', $caption, null);
	}

	/**
	 * @param array{relative:string,url:string,mimetype?:?string} $arquivo
	 */
	public static function enviarDocumento(WhatsappConversa $conversa, array $arquivo, ?string $caption = null, ?string $fileName = null): bool {
		return self::enviarArquivoMidia($conversa, $arquivo, 'document', $caption, $fileName);
	}

	/**
	 * @param array{relative:string,url:string,mimetype?:?string} $arquivo
	 */
	public static function enviarAudio(WhatsappConversa $conversa, array $arquivo): bool {
		self::$lastError = null;
		$instance = self::instanceDaConversa($conversa);
		if ($instance === '') {
			self::$lastError = 'Instância WhatsApp não encontrada.';
			return false;
		}

		$path = self::caminhoAbsoluto($arquivo);
		if ($path === null) {
			self::$lastError = 'Arquivo de áudio não encontrado no servidor.';
			return false;
		}

		$api = EvolutionApiService::fromEnv();
		$mime = $arquivo['mimetype'] ?? null;
		$res = $api->sendAudio($instance, (string)$conversa->telefone, $path, $mime);
		$ok = $res !== null && $api->getLastHttpCode() < 400;
		if (!$ok) {
			self::$lastError = $api->getLastError() ?: 'Falha ao enviar áudio.';
			return false;
		}

		WhatsappMensagem::registrar([
			'id_admin'      => (int)$conversa->id_admin,
			'conversa_id'   => (int)$conversa->id,
			'direction'     => 'out',
			'tipo'          => 'audio',
			'corpo'         => null,
			'media_url'     => $arquivo['relative'] ?? null,
			'wa_message_id' => $res['key']['id'] ?? ($res['message']['key']['id'] ?? null),
			'status'        => 'sent',
		]);
		$conversa->tocarUltimaMensagem();
		return true;
	}

	/**
	 * @param array{relative:string,url:string,mimetype?:?string} $arquivo
	 */
	private static function enviarArquivoMidia(
		WhatsappConversa $conversa,
		array $arquivo,
		string $tipo,
		?string $caption,
		?string $fileName
	): bool {
		self::$lastError = null;
		$instance = self::instanceDaConversa($conversa);
		if ($instance === '') {
			self::$lastError = 'Instância WhatsApp não encontrada.';
			return false;
		}

		$path = self::caminhoAbsoluto($arquivo);
		if ($path === null) {
			self::$lastError = 'Arquivo de mídia não encontrado no servidor.';
			return false;
		}

		$mime = $arquivo['mimetype'] ?? null;
		if (!$fileName) {
			$fileName = basename((string)($arquivo['relative'] ?? $path));
		}

		$api = EvolutionApiService::fromEnv();
		$res = $api->sendMedia(
			$instance,
			(string)$conversa->telefone,
			$path,
			$tipo,
			$mime,
			$caption,
			$fileName
		);
		$ok = $res !== null && $api->getLastHttpCode() < 400;
		if (!$ok) {
			self::$lastError = $api->getLastError() ?: ('Falha ao enviar '.$tipo.'.');
			return false;
		}

		WhatsappMensagem::registrar([
			'id_admin'      => (int)$conversa->id_admin,
			'conversa_id'   => (int)$conversa->id,
			'direction'     => 'out',
			'tipo'          => $tipo,
			'corpo'         => $caption ?: ($tipo === 'document' ? $fileName : null),
			'media_url'     => $arquivo['relative'] ?? null,
			'wa_message_id' => $res['key']['id'] ?? ($res['message']['key']['id'] ?? null),
			'status'        => 'sent',
		]);
		$conversa->tocarUltimaMensagem();
		return true;
	}

	/** @param array{relative?:string} $arquivo */
	private static function caminhoAbsoluto(array $arquivo): ?string {
		$root = rtrim(str_replace('\\', '/', realpath(__DIR__.'/../../../') ?: (__DIR__.'/../../..')), '/');
		$relative = ltrim((string)($arquivo['relative'] ?? ''), '/');
		if ($relative === '') {
			return null;
		}
		$path = $root.'/'.$relative;
		return is_file($path) ? $path : null;
	}

	public static function instanceDaConversa(WhatsappConversa $conversa): string {
		if (!empty($conversa->numero_id) && WhatsappNumero::tabelaExiste()) {
			$row = (new \App\Model\Db\Database('whatsapp_numeros'))
				->select('id = '.(int)$conversa->numero_id, null, 1)
				->fetch(\PDO::FETCH_ASSOC);
			if (!empty($row['evolution_instance'])) {
				return (string)$row['evolution_instance'];
			}
		}

		$num = WhatsappNumero::getDefault((int)$conversa->id_admin);
		if ($num && !empty($num->evolution_instance)) {
			return (string)$num->evolution_instance;
		}

		$int = EscolaIntegracoes::getByIdAdmin((int)$conversa->id_admin);
		if ($int && !empty($int->evolution_instance)) {
			return (string)$int->evolution_instance;
		}

		return EvolutionApiService::nomeInstancia((int)$conversa->id_admin);
	}
}

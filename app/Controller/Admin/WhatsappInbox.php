<?php

namespace App\Controller\Admin;

use App\Utils\View;
use App\Session\User\Login as SessionUser;
use App\Common\Helpers\TenantHelper;
use App\Common\Communication\WhatsappChatbotService;
use App\Model\Entity\WhatsappConversa;
use App\Model\Entity\WhatsappMensagem;
use App\Model\Entity\WhatsappSetor;
use App\Model\Entity\WhatsappAtendente;
use App\Model\Entity\User;
use App\Model\Db\Database;

class WhatsappInbox extends Page {

	private static function user(): array {
		return SessionUser::getUserLogedData();
	}

	private static function idAdmin(): int {
		return (int)TenantHelper::getIdAdmin();
	}

	private static function isDiretor(): bool {
		return (self::user()['usuario']['nivel'] ?? '') === 'Diretor';
	}

	public static function index($request) {
		$content = View::render('admin/modules/whatsapp/inbox', []);
		return parent::getPanel('WhatsApp', $content, 'whatsapp', $request);
	}

	public static function getInfo($request) {
		$post = $request->getPostVars();
		$acao = $post['acao'] ?? '';

		$map = [
			'listar'           => 'listar',
			'mensagens'        => 'mensagens',
			'enviar'           => 'enviar',
			'assumir'          => 'assumir',
			'transferir'       => 'transferir',
			'fechar'           => 'fechar',
			'setores_listar'   => 'setoresListar',
			'setor_salvar'     => 'setorSalvar',
			'atendentes_listar'=> 'atendentesListar',
			'atendente_vincular' => 'atendenteVincular',
			'atendente_remover'=> 'atendenteRemover',
			'usuarios_lista'   => 'usuariosLista',
		];

		if (!isset($map[$acao])) {
			return json_encode(['success' => false, 'message' => 'Ação inválida.']);
		}

		$method = $map[$acao];
		return self::$method($post);
	}

	private static function listar(array $post): string {
		$idAdmin = self::idAdmin();
		$user = self::user();
		$uid = (int)($user['usuario']['id'] ?? 0);
		$nivel = (string)($user['usuario']['nivel'] ?? '');
		$setores = WhatsappAtendente::setoresDoUsuario($idAdmin, $uid);

		$lista = WhatsappConversa::listarInbox($idAdmin, $uid, $nivel, $setores);

		return json_encode([
			'success' => true,
			'conversas' => $lista,
			'meta' => [
				'is_diretor' => self::isDiretor(),
				'chatbot_ok' => WhatsappConversa::temColunasChatbot(),
				'setores_ok' => WhatsappSetor::tabelaExiste(),
				'atendentes_ok' => WhatsappAtendente::tabelaExiste(),
			],
		]);
	}

	private static function mensagens(array $post): string {
		$idAdmin = self::idAdmin();
		$id = (int)($post['conversa_id'] ?? 0);
		$conv = WhatsappConversa::getById($id, $idAdmin);
		if (!$conv || !self::podeVer($conv)) {
			return json_encode(['success' => false, 'message' => 'Conversa não encontrada.']);
		}

		$rows = (new Database('whatsapp_mensagens'))
			->select('conversa_id = '.$id.' AND id_admin = '.$idAdmin, 'id ASC', '200')
			->fetchAll(\PDO::FETCH_ASSOC);

		return json_encode([
			'success' => true,
			'conversa' => [
				'id' => (int)$conv->id,
				'telefone' => $conv->telefone,
				'nome_contato' => $conv->nome_contato,
				'status' => $conv->status,
				'setor_id' => $conv->setor_id,
				'id_atendente' => $conv->id_atendente,
				'chatbot_estado' => $conv->chatbot_estado ?? null,
			],
			'mensagens' => $rows ?: [],
		]);
	}

	private static function enviar(array $post): string {
		$idAdmin = self::idAdmin();
		$user = self::user();
		$uid = (int)($user['usuario']['id'] ?? 0);
		$id = (int)($post['conversa_id'] ?? 0);
		$texto = trim((string)($post['texto'] ?? ''));

		if ($texto === '') {
			return json_encode(['success' => false, 'message' => 'Digite uma mensagem.']);
		}

		$conv = WhatsappConversa::getById($id, $idAdmin);
		if (!$conv || !self::podeVer($conv)) {
			return json_encode(['success' => false, 'message' => 'Conversa não encontrada.']);
		}

		if (!self::isDiretor() && (int)$conv->id_atendente !== $uid) {
			if (!(int)$conv->id_atendente) {
				$conv->atualizar([
					'id_atendente'   => $uid,
					'status'         => 'em_atendimento',
					'chatbot_estado' => 'humano',
					'assigned_at'    => date('Y-m-d H:i:s'),
				]);
			} else {
				return json_encode(['success' => false, 'message' => 'Assuma a conversa antes de responder.']);
			}
		} else {
			$upd = ['chatbot_estado' => 'humano', 'status' => 'em_atendimento'];
			if (!(int)$conv->id_atendente) {
				$upd['id_atendente'] = $uid;
				$upd['assigned_at'] = date('Y-m-d H:i:s');
			}
			$conv->atualizar($upd);
		}

		$ok = WhatsappChatbotService::enviarTexto($conv, $texto);
		if (!$ok) {
			return json_encode(['success' => false, 'message' => 'Falha ao enviar pelo WhatsApp. Verifique a conexão.']);
		}

		return json_encode(['success' => true, 'message' => 'Enviado.']);
	}

	private static function assumir(array $post): string {
		$idAdmin = self::idAdmin();
		$uid = (int)(self::user()['usuario']['id'] ?? 0);
		$id = (int)($post['conversa_id'] ?? 0);
		$conv = WhatsappConversa::getById($id, $idAdmin);
		if (!$conv || !self::podeVer($conv)) {
			return json_encode(['success' => false, 'message' => 'Conversa não encontrada.']);
		}

		if ((int)$conv->id_atendente && (int)$conv->id_atendente !== $uid && !self::isDiretor()) {
			return json_encode(['success' => false, 'message' => 'Conversa já está com outro atendente.']);
		}

		$conv->atualizar([
			'id_atendente'   => $uid,
			'status'         => 'em_atendimento',
			'chatbot_estado' => 'humano',
			'assigned_at'    => date('Y-m-d H:i:s'),
		]);

		return json_encode(['success' => true, 'message' => 'Conversa assumida.']);
	}

	private static function transferir(array $post): string {
		$idAdmin = self::idAdmin();
		$id = (int)($post['conversa_id'] ?? 0);
		$setorId = (int)($post['setor_id'] ?? 0);
		$conv = WhatsappConversa::getById($id, $idAdmin);
		if (!$conv || !self::podeVer($conv)) {
			return json_encode(['success' => false, 'message' => 'Conversa não encontrada.']);
		}

		$setor = WhatsappSetor::getById($setorId, $idAdmin);
		if (!$setor) {
			return json_encode(['success' => false, 'message' => 'Setor inválido.']);
		}

		$conv->atualizar([
			'setor_id'       => $setorId,
			'id_atendente'   => null,
			'status'         => 'aberta',
			'chatbot_estado' => 'fila',
			'assigned_at'    => null,
		]);

		$msg = $setor->mensagem_fila ?: ('Você foi transferido para *'.$setor->nome.'*. Aguarde um atendente.');
		WhatsappChatbotService::enviarTexto($conv, $msg);

		return json_encode(['success' => true, 'message' => 'Transferido para '.$setor->nome.'.']);
	}

	private static function fechar(array $post): string {
		$idAdmin = self::idAdmin();
		$id = (int)($post['conversa_id'] ?? 0);
		$conv = WhatsappConversa::getById($id, $idAdmin);
		if (!$conv || !self::podeVer($conv)) {
			return json_encode(['success' => false, 'message' => 'Conversa não encontrada.']);
		}

		$conv->atualizar([
			'status'         => 'fechada',
			'chatbot_estado' => 'encerrado',
		]);

		return json_encode(['success' => true, 'message' => 'Conversa encerrada.']);
	}

	private static function setoresListar(array $post): string {
		$idAdmin = self::idAdmin();
		if (!WhatsappSetor::tabelaExiste()) {
			return json_encode(['success' => false, 'message' => 'Execute o SQL de setores no phpMyAdmin.']);
		}
		WhatsappSetor::garantirPadroes($idAdmin);
		return json_encode(['success' => true, 'setores' => WhatsappSetor::listarTodos($idAdmin)]);
	}

	private static function setorSalvar(array $post): string {
		if (!self::isDiretor()) {
			return json_encode(['success' => false, 'message' => 'Apenas o Diretor gerencia setores.']);
		}
		$idAdmin = self::idAdmin();
		$id = (int)($post['id'] ?? 0);
		$nome = trim((string)($post['nome'] ?? ''));
		if ($nome === '') {
			return json_encode(['success' => false, 'message' => 'Informe o nome do setor.']);
		}

		$slug = trim((string)($post['slug'] ?? ''));
		if ($slug === '') {
			$slug = preg_replace('/[^a-z0-9]+/', '-', strtolower(iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $nome) ?: $nome));
			$slug = trim((string)$slug, '-') ?: 'setor';
		}

		$ob = null;
		if ($id) {
			$ob = WhatsappSetor::getById($id, $idAdmin);
			if (!$ob) {
				return json_encode(['success' => false, 'message' => 'Setor não encontrado.']);
			}
		} else {
			$ob = new WhatsappSetor;
		}
		$ob->id_admin = $idAdmin;
		$ob->nome = $nome;
		$ob->slug = $slug;
		$ob->ordem = (int)($post['ordem'] ?? 0);
		$ob->ativo = !empty($post['ativo']) ? 1 : 0;
		$ob->mensagem_fila = trim((string)($post['mensagem_fila'] ?? ''));
		$ob->salvar();

		return json_encode(['success' => true, 'message' => 'Setor salvo.']);
	}

	private static function atendentesListar(array $post): string {
		$idAdmin = self::idAdmin();
		if (!WhatsappAtendente::tabelaExiste()) {
			return json_encode(['success' => false, 'message' => 'Execute o SQL de atendentes.']);
		}
		return json_encode(['success' => true, 'atendentes' => WhatsappAtendente::listarPorEscola($idAdmin)]);
	}

	private static function atendenteVincular(array $post): string {
		if (!self::isDiretor()) {
			return json_encode(['success' => false, 'message' => 'Apenas o Diretor vincula atendentes.']);
		}
		$idAdmin = self::idAdmin();
		$usuarioId = (int)($post['usuario_id'] ?? 0);
		$setorId = (int)($post['setor_id'] ?? 0);
		if (!$usuarioId || !$setorId) {
			return json_encode(['success' => false, 'message' => 'Selecione usuário e setor.']);
		}
		WhatsappAtendente::vincular($idAdmin, $usuarioId, $setorId);
		return json_encode(['success' => true, 'message' => 'Atendente vinculado.']);
	}

	private static function atendenteRemover(array $post): string {
		if (!self::isDiretor()) {
			return json_encode(['success' => false, 'message' => 'Apenas o Diretor remove vínculos.']);
		}
		WhatsappAtendente::desvincular(self::idAdmin(), (int)($post['id'] ?? 0));
		return json_encode(['success' => true, 'message' => 'Vínculo removido.']);
	}

	private static function usuariosLista(array $post): string {
		$idAdmin = self::idAdmin();
		$rows = User::getUser(
			'id_admin = '.$idAdmin." AND nivel NOT IN ('Cliente','Empresa') AND ativo = 's'",
			'nome ASC',
			null,
			'id, nome, nivel'
		)->fetchAll(\PDO::FETCH_ASSOC);

		return json_encode(['success' => true, 'usuarios' => $rows ?: []]);
	}

	private static function podeVer(WhatsappConversa $conv): bool {
		if (self::isDiretor()) {
			return true;
		}
		$user = self::user();
		$uid = (int)($user['usuario']['id'] ?? 0);
		if ((int)$conv->id_atendente === $uid) {
			return true;
		}
		$setores = WhatsappAtendente::setoresDoUsuario(self::idAdmin(), $uid);
		if ($conv->setor_id && in_array((int)$conv->setor_id, $setores, true) && !(int)$conv->id_atendente) {
			return true;
		}
		$estado = (string)($conv->chatbot_estado ?? '');
		if (in_array($estado, ['novo', 'aguardando_setor'], true) && !(int)$conv->id_atendente) {
			return true;
		}
		return false;
	}
}

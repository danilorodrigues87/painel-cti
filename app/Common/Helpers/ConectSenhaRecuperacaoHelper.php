<?php

namespace App\Common\Helpers;

use App\Common\Communication\Email;
use App\Model\Entity\User;

class ConectSenhaRecuperacaoHelper {

	private const MSG_SOLICITACAO_OK =
		'Se o e-mail estiver cadastrado, enviamos um código de 6 dígitos para redefinir sua senha.';

	public static function generateCode(): string {
		return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
	}

	/**
	 * @return array{ok:bool,message:string,code?:int}
	 */
	public static function solicitar(string $email, string $contexto = ''): array {
		$email = strtolower(trim($email));
		if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
			return ['ok' => false, 'message' => 'Informe um e-mail válido.', 'code' => 400];
		}
		if (preg_match('/\r|\n/', $email)) {
			return ['ok' => false, 'message' => 'E-mail inválido.', 'code' => 400];
		}
		if ($contexto !== '' && !in_array($contexto, ['candidato', 'empresa'], true)) {
			return ['ok' => false, 'message' => 'Tipo de conta inválido.', 'code' => 400];
		}
		if (!User::temColunaRecCode()) {
			error_log('[ConectRecuperacaoSenha] Coluna usuarios.recCode ausente no banco.');
			return ['ok' => false, 'message' => 'Recuperação de senha indisponível no servidor. Contate o suporte.', 'code' => 503];
		}

		$user = User::getUserByEmailNormalized($email);
		if (!$user instanceof User || !self::podeRecuperarConect($user)) {
			return ['ok' => true, 'message' => self::MSG_SOLICITACAO_OK];
		}
		if (isset($user->ativo) && (int)$user->ativo !== 1) {
			return ['ok' => true, 'message' => self::MSG_SOLICITACAO_OK];
		}

		$code = self::generateCode();
		$obRec = new User();
		$obRec->id = (int)$user->id;
		$obRec->code = $code;
		if (!$obRec->setRecCode()) {
			error_log('[ConectRecuperacaoSenha] Falha ao gravar recCode para usuário #'.(int)$user->id);
			return ['ok' => false, 'message' => 'Não foi possível gerar o código. Tente novamente.', 'code' => 500];
		}

		$enviado = self::enviarEmailCodigo($user, $code);
		if (!$enviado) {
			return ['ok' => false, 'message' => 'Não foi possível enviar o e-mail. Verifique a caixa de spam ou tente novamente.', 'code' => 500];
		}

		return ['ok' => true, 'message' => self::MSG_SOLICITACAO_OK];
	}

	/**
	 * @return array{ok:bool,message:string,code?:int}
	 */
	public static function redefinir(string $codigo, string $nova, string $confirma, string $contexto = '', int $minLen = 6): array {
		$codigo = trim($codigo);
		if (!preg_match('/^\d{6}$/', $codigo)) {
			return ['ok' => false, 'message' => 'Código inválido.', 'code' => 400];
		}
		if ($contexto !== '' && !in_array($contexto, ['candidato', 'empresa'], true)) {
			return ['ok' => false, 'message' => 'Tipo de conta inválido.', 'code' => 400];
		}
		if ($nova === '' || $confirma === '') {
			return ['ok' => false, 'message' => 'Informe e confirme a nova senha.', 'code' => 400];
		}
		if (strlen($nova) < $minLen) {
			return [
				'ok'      => false,
				'message' => 'A nova senha deve ter pelo menos '.$minLen.' caracteres.',
				'code'    => 400,
			];
		}
		if ($nova !== $confirma) {
			return ['ok' => false, 'message' => 'As senhas não coincidem.', 'code' => 400];
		}

		$user = User::getUserByCode($codigo);
		if (!$user instanceof User || !self::podeRecuperarConect($user)) {
			return ['ok' => false, 'message' => 'Código inválido ou expirado.', 'code' => 400];
		}

		$obUser = new User();
		$obUser->id = (int)$user->id;
		$obUser->senha = password_hash($nova, PASSWORD_DEFAULT);
		if (!$obUser->resetSenha()) {
			return ['ok' => false, 'message' => 'Não foi possível redefinir a senha.', 'code' => 500];
		}
		$obUser->clearRecCode();

		return ['ok' => true, 'message' => 'Senha redefinida com sucesso. Faça login com a nova senha.'];
	}

	private static function podeRecuperarConect(User $user): bool {
		if (($user->nivel ?? '') === 'Empresa') {
			return true;
		}
		return ConectCandidatoAuthHelper::podeAcessarConect($user);
	}

	private static function enviarEmailCodigo(User $user, string $code): bool {
		$destino = trim((string)$user->email);
		if ($destino === '' || !filter_var($destino, FILTER_VALIDATE_EMAIL)) {
			error_log('[ConectRecuperacaoSenha] E-mail inválido no cadastro do usuário #'.(int)$user->id);
			return false;
		}

		$nivel = (string)($user->nivel ?? '');
		$portal = $nivel === 'Empresa' ? 'Conecta Jovem — Empresa' : 'Conecta Jovem';
		$subject = 'Recuperação de senha — '.$portal;
		$body = '<p>Seu código de recuperação de senha é:</p>'
			.'<p style="font-size:24px;font-weight:bold;letter-spacing:4px;">'.htmlspecialchars($code, ENT_QUOTES, 'UTF-8').'</p>'
			.'<p>Informe este código no portal para definir uma nova senha. Se você não solicitou, ignore este e-mail.</p>';

		$idAdmin = (int)($user->id_admin ?? 0);
		if ($idAdmin > 0) {
			$escola = Email::escola($idAdmin);
			if (!$escola->isUsandoSistema()) {
				if ($escola->sendEmail($destino, $subject, $body)) {
					return true;
				}
				error_log('[ConectRecuperacaoSenha] SMTP escola falhou (id_admin='.$idAdmin.'): '.$escola->getError());
			}
		}

		$sistema = Email::sistema();
		if ($sistema->sendEmail($destino, $subject, $body)) {
			return true;
		}

		error_log('[ConectRecuperacaoSenha] SMTP sistema falhou: '.$sistema->getError().' destino='.$destino);
		return false;
	}
}

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
	public static function solicitar(string $email, string $contexto): array {
		$email = strtolower(trim($email));
		if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
			return ['ok' => false, 'message' => 'Informe um e-mail válido.', 'code' => 400];
		}
		if (preg_match('/\r|\n/', $email)) {
			return ['ok' => false, 'message' => 'E-mail inválido.', 'code' => 400];
		}
		if (!in_array($contexto, ['candidato', 'empresa'], true)) {
			return ['ok' => false, 'message' => 'Tipo de conta inválido.', 'code' => 400];
		}

		$user = User::getUserByEmail($email);
		if (!$user instanceof User || !self::usuarioPermitido($user, $contexto)) {
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
			return ['ok' => false, 'message' => 'Não foi possível gerar o código. Tente novamente.', 'code' => 500];
		}

		if (!self::enviarEmailCodigo($user, $email, $code, $contexto)) {
			return ['ok' => false, 'message' => 'Não foi possível enviar o e-mail. Tente novamente em instantes.', 'code' => 500];
		}

		return ['ok' => true, 'message' => self::MSG_SOLICITACAO_OK];
	}

	/**
	 * @return array{ok:bool,message:string,code?:int}
	 */
	public static function redefinir(string $codigo, string $nova, string $confirma, string $contexto, int $minLen = 6): array {
		$codigo = trim($codigo);
		if (!preg_match('/^\d{6}$/', $codigo)) {
			return ['ok' => false, 'message' => 'Código inválido.', 'code' => 400];
		}
		if (!in_array($contexto, ['candidato', 'empresa'], true)) {
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
		if (!$user instanceof User || !self::usuarioPermitido($user, $contexto)) {
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

	private static function usuarioPermitido(User $user, string $contexto): bool {
		if ($contexto === 'empresa') {
			return ($user->nivel ?? '') === 'Empresa';
		}
		return ConectCandidatoAuthHelper::podeAcessarConect($user);
	}

	private static function enviarEmailCodigo(User $user, string $email, string $code, string $contexto): bool {
		$portal = $contexto === 'empresa' ? 'Conecta Jovem — Empresa' : 'Conecta Jovem';
		$subject = 'Recuperação de senha — '.$portal;
		$body = '<p>Seu código de recuperação de senha é:</p>'
			.'<p style="font-size:24px;font-weight:bold;letter-spacing:4px;">'.$code.'</p>'
			.'<p>Informe este código no portal para definir uma nova senha. Se você não solicitou, ignore este e-mail.</p>';

		$idAdmin = (int)($user->id_admin ?? 0);
		if ($idAdmin > 0) {
			$obEmail = Email::escola($idAdmin);
			if ($obEmail->sendEmail($email, $subject, $body)) {
				return true;
			}
		}

		$obEmail = Email::sistema();
		return (bool) $obEmail->sendEmail($email, $subject, $body);
	}
}

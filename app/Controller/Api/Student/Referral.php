<?php

namespace App\Controller\Api\Student;

use App\Common\Helpers\ModuleGateHelper;
use App\Model\Entity\CrmLeads;
use App\Model\Entity\CrmHistorico;
use App\Model\Db\Database;

/**
 * Indicação de amigos → CRM da escola (se módulo Leads liberado).
 * Conquista sec_indicar é liberada MANUALMENTE pela escola após matrícula.
 */
class Referral {

	private static function ok($data, int $code = 200): array {
		return [
			'code' => $code,
			'json' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
		];
	}

	private static function err(string $msg, int $code = 400): array {
		return self::ok(['message' => $msg, 'ok' => false], $code);
	}

	public static function status($request): array {
		$u = $request->user;
		return self::ok([
			'enabled' => self::crmDisponivel((int)$u->id_admin),
		]);
	}

	public static function submit($request): array {
		$u = $request->user;
		$idAdmin = (int)$u->id_admin;
		$idAluno = (int)$u->id;

		if (!self::crmDisponivel($idAdmin)) {
			return self::err('Indicação não disponível nesta escola.', 403);
		}

		$post = $request->getPostVars() ?: [];
		$nome = trim((string)($post['nome'] ?? $post['name'] ?? ''));
		$whatsapp = preg_replace('/\D+/', '', (string)($post['whatsapp'] ?? $post['phone'] ?? ''));
		$email = trim((string)($post['email'] ?? ''));
		$curso = trim((string)($post['curso'] ?? $post['curso_interesse'] ?? ''));

		if ($nome === '' || mb_strlen($nome) < 3) {
			return self::err('Informe o nome completo do indicado.');
		}
		if (strlen($whatsapp) < 10 || strlen($whatsapp) > 13) {
			return self::err('Informe um WhatsApp válido com DDD.');
		}
		if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
			return self::err('E-mail inválido.');
		}

		if (!self::dentroDoLimite($idAdmin, $idAluno)) {
			return self::err('Limite de indicações do dia atingido. Tente amanhã.');
		}

		$indicador = trim((string)($u->nome ?? 'Aluno'));
		$obs = 'Indicação via portal EAD. Indicado por: '.$indicador.' (ID '.$idAluno.').';

		$lead = new CrmLeads();
		$lead->id_admin = $idAdmin;
		$lead->usuario_id = null;
		$lead->visibilidade = 'publico';
		$lead->funil_id = null;
		$lead->nome = $nome;
		$lead->whatsapp = $whatsapp;
		$lead->curso_interesse = $curso !== '' ? $curso : null;
		$lead->origem = 'Indicação portal';
		$lead->email = $email !== '' ? $email : null;
		$lead->status = 'novo';
		$lead->data_cadastro = date('Y-m-d H:i:s');
		$lead->cadastrar();

		if (!empty($lead->id)) {
			try {
				$hist = new CrmHistorico();
				$hist->lead_id = (int)$lead->id;
				$hist->usuario_id = null;
				$hist->acao = 'Indicação portal';
				$hist->observacao = $obs;
				$hist->cadastrar();
			} catch (\Throwable $e) {
				// histórico opcional
			}
		}

		return self::ok([
			'ok' => true,
			'message' => 'Indicação enviada! A escola entrará em contato. A conquista é liberada após a matrícula do indicado.',
			'leadId' => (int)($lead->id ?? 0),
		]);
	}

	private static function crmDisponivel(int $idAdmin): bool {
		if ($idAdmin <= 0) {
			return false;
		}
		try {
			$stmt = (new Database())->execute("SHOW TABLES LIKE 'crm_leads'");
			if (!$stmt || $stmt->rowCount() === 0) {
				return false;
			}
		} catch (\Throwable $e) {
			return false;
		}
		$labels = ModuleGateHelper::getModulosEscola($idAdmin);
		return in_array('Leads', $labels, true);
	}

	/** Máx. 5 indicações / aluno / dia. */
	private static function dentroDoLimite(int $idAdmin, int $idAluno): bool {
		try {
			$stmt = (new Database())->execute(
				'SELECT COUNT(*) AS c FROM crm_historico h
				INNER JOIN crm_leads l ON l.id = h.lead_id
				WHERE l.id_admin = '.(int)$idAdmin.'
				AND h.acao = "Indicação portal"
				AND h.observacao LIKE "%(ID '.(int)$idAluno.')%"
				AND DATE(h.data_registro) = CURDATE()'
			);
			$row = $stmt ? $stmt->fetch(\PDO::FETCH_ASSOC) : null;
			return ((int)($row['c'] ?? 0)) < 5;
		} catch (\Throwable $e) {
			return true;
		}
	}
}

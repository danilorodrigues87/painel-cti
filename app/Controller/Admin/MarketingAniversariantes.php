<?php

namespace App\Controller\Admin;

use App\Utils\View;
use App\Session\User\Login as SessionUser;
use App\Common\Helpers\TenantHelper;
use App\Common\Helpers\ModuleGateHelper;
use App\Common\Helpers\AniversariantesHelper;
use App\Common\Helpers\CampanhaSegmentoHelper;
use App\Common\Helpers\EmailValidator;
use App\Common\Communication\AniversarioEmailService;
use App\Common\Communication\Email;
use App\Common\Communication\WhatsappEscolaService;
use App\Model\Entity\EmailAniversarioLog;
use App\Model\Entity\EscolaIntegracoes;
use App\Model\Entity\EscolasAssinantes;

class MarketingAniversariantes extends Page {

	private static function assertAcesso($request, bool $api = false): bool {
		$user = SessionUser::getUserLogedData();
		$idAdmin = (int)($user['usuario']['id_admin'] ?? 0);
		$mods = ModuleGateHelper::getModulosEfetivos($idAdmin, $user['usuario']['acesso'] ?? []);
		if (!in_array('Campanhas', $mods, true)) {
			if (!$api) {
				$request->getRouter()->redirect('/painel');
			}
			return false;
		}
		return true;
	}

	private static function json(array $data): string {
		return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}

	public static function index($request) {
		if (!self::assertAcesso($request)) {
			return '';
		}
		$content = View::render('admin/modules/marketing/aniversariantes', []);
		return parent::getPanel('Campanhas', $content, 'marketing', $request);
	}

	public static function getInfo($request) {
		if (!self::assertAcesso($request, true)) {
			return self::json(['success' => false, 'message' => 'Acesso negado.']);
		}

		TenantHelper::getIdAdmin();
		if (session_status() === PHP_SESSION_ACTIVE) {
			session_write_close();
		}

		$post = $request->getPostVars();
		$acao = (string)($post['acao'] ?? '');

		switch ($acao) {
			case 'listar':
				return self::listar($post);
			case 'enviar':
				return self::enviar($post);
			case 'meta':
				return self::meta();
			default:
				return self::json(['success' => false, 'message' => 'Ação inválida.']);
		}
	}

	private static function listar(array $post): string {
		$idAdmin = TenantHelper::getIdAdmin();
		$periodo = (string)($post['periodo'] ?? 'mes');
		$busca = trim((string)($post['busca'] ?? ''));
		$mes = isset($post['mes']) ? (int)$post['mes'] : null;

		$lista = AniversariantesHelper::listar($idAdmin, $periodo, $busca, $mes);

		return self::json([
			'success' => true,
			'lista' => $lista,
			'total' => count($lista),
			'periodo' => $periodo,
			'mes' => $mes ?? (int)date('m'),
		]);
	}

	private static function meta(): string {
		$idAdmin = TenantHelper::getIdAdmin();
		$config = EscolaIntegracoes::getByIdAdmin($idAdmin);
		$tpl = AniversarioEmailService::getTemplatePadrao();
		$waStatus = WhatsappEscolaService::status($idAdmin);

		return self::json([
			'success' => true,
			'template' => [
				'assunto' => trim($config->aniversario_assunto ?? '') ?: $tpl['assunto'],
				'mensagem' => trim($config->aniversario_mensagem ?? '') ?: $tpl['mensagem'],
			],
			'whatsapp_conectado' => !empty($waStatus['conectado']),
			'log_ok' => EmailAniversarioLog::tabelaExiste(),
		]);
	}

	private static function enviar(array $post): string {
		$idAdmin = TenantHelper::getIdAdmin();
		$canal = ($post['canal'] ?? '') === 'whatsapp' ? 'whatsapp' : 'email';
		$ids = array_values(array_filter(array_map('intval', (array)($post['ids'] ?? []))));

		if (!$ids) {
			return self::json(['success' => false, 'message' => 'Selecione ao menos um aniversariante.']);
		}

		$assuntoCustom = trim((string)($post['assunto'] ?? ''));
		$mensagemCustom = trim((string)($post['mensagem'] ?? ''));
		$config = EscolaIntegracoes::getByIdAdmin($idAdmin);
		$tpl = AniversarioEmailService::getTemplatePadrao();
		$assuntoTpl = $assuntoCustom !== '' ? $assuntoCustom
			: (trim($config->aniversario_assunto ?? '') ?: $tpl['assunto']);
		$msgTpl = $mensagemCustom !== '' ? $mensagemCustom
			: (trim($config->aniversario_mensagem ?? '') ?: $tpl['mensagem']);

		if ($canal === 'email' && ($assuntoTpl === '' || $msgTpl === '')) {
			return self::json(['success' => false, 'message' => 'Informe assunto e mensagem do e-mail.']);
		}
		if ($canal === 'whatsapp' && $msgTpl === '') {
			return self::json(['success' => false, 'message' => 'Informe a mensagem do WhatsApp.']);
		}

		if ($canal === 'whatsapp') {
			$wa = WhatsappEscolaService::status($idAdmin);
			if (empty($wa['conectado'])) {
				return self::json([
					'success' => false,
					'message' => 'WhatsApp não conectado. Conecte em Configurações → Comunicação.',
				]);
			}
		}

		$nomeEscola = self::nomeEscola($idAdmin);
		$ano = (int)date('Y');
		$delayEmail = max(1, (int)($config->email_delay_segundos ?? 3));
		$delayWa = max(1, (int)($config->whatsapp_delay_segundos ?? 5));
		$mailer = $canal === 'email' ? Email::escola($idAdmin) : null;

		$itens = AniversariantesHelper::buscarPorIds($idAdmin, $ids);
		$enviados = 0;
		$erros = 0;
		$detalhes = [];

		foreach ($itens as $item) {
			$vars = [
				'nome' => $item['nome'],
				'email' => $item['email'],
				'whatsapp' => $item['whatsapp'],
				'contato' => $canal === 'whatsapp' ? $item['whatsapp'] : $item['email'],
				'curso' => $item['curso'],
				'escola' => $nomeEscola,
			];

			if ($canal === 'email') {
				if ($item['email'] === '' || !EmailValidator::isValido($item['email'])) {
					$erros++;
					$detalhes[] = ['id' => $item['id'], 'erro' => 'E-mail inválido ou ausente'];
					continue;
				}
				$assunto = CampanhaSegmentoHelper::aplicarVariaveis($assuntoTpl, $vars);
				$corpo = CampanhaSegmentoHelper::aplicarVariaveis($msgTpl, $vars);
				$ok = $mailer->sendEmail($item['email'], $assunto, $corpo);
				if ($ok) {
					EmailAniversarioLog::registrar($idAdmin, (int)$item['id'], $ano, $item['email']);
					$enviados++;
					sleep($delayEmail);
				} else {
					$erros++;
					$detalhes[] = [
						'id' => $item['id'],
						'erro' => $mailer->getError() ?: 'Falha no envio',
					];
				}
				continue;
			}

			if ($item['whatsapp'] === '') {
				$erros++;
				$detalhes[] = ['id' => $item['id'], 'erro' => 'WhatsApp ausente'];
				continue;
			}
			$texto = CampanhaSegmentoHelper::textoParaWhatsapp(
				CampanhaSegmentoHelper::aplicarVariaveis($msgTpl, $vars)
			);
			if ($texto === '') {
				$erros++;
				$detalhes[] = ['id' => $item['id'], 'erro' => 'Mensagem vazia'];
				continue;
			}
			$r = WhatsappEscolaService::enviarTexto($idAdmin, $item['whatsapp'], $texto);
			if (!empty($r['ok'])) {
				EmailAniversarioLog::registrar($idAdmin, (int)$item['id'], $ano, 'wa:'.$item['whatsapp']);
				$enviados++;
				sleep($delayWa);
			} else {
				$erros++;
				$detalhes[] = [
					'id' => $item['id'],
					'erro' => $r['message'] ?? 'Falha no WhatsApp',
				];
			}
		}

		return self::json([
			'success' => $enviados > 0 || $erros === 0,
			'enviados' => $enviados,
			'erros' => $erros,
			'detalhes' => $detalhes,
			'message' => $enviados > 0
				? $enviados.' mensagem(ns) enviada(s).'.($erros ? ' '.$erros.' falha(s).' : '')
				: 'Nenhuma mensagem enviada.',
		]);
	}

	private static function nomeEscola(int $idAdmin): string {
		$escola = EscolasAssinantes::getEscolaById($idAdmin);
		return ($escola instanceof EscolasAssinantes) ? (string)($escola->nome ?? '') : '';
	}
}

<?php

namespace App\Controller\Admin;

use App\Utils\View;
use App\Session\User\Login as SessionUser;
use App\Common\Helpers\TenantHelper;
use App\Common\Helpers\ContratoTemplateHelper;
use App\Model\Entity\EscolasAssinantes;

class ConfigContrato extends Page {

	private static function assertDiretor($request, bool $api = false): bool {
		$user = SessionUser::getUserLogedData();
		if (($user['usuario']['nivel'] ?? '') !== 'Diretor') {
			if (!$api) {
				$request->getRouter()->redirect('/painel');
			}
			return false;
		}
		return true;
	}

	public static function index($request) {
		if (!self::assertDiretor($request)) {
			return '';
		}
		$content = View::render('admin/modules/config/contrato', []);
		return parent::getPanel('Modelo de contrato', $content, 'config');
	}

	public static function getInfo($request) {
		if (!self::assertDiretor($request, true)) {
			return json_encode(['success' => false, 'message' => 'Acesso negado.']);
		}

		$postVars = $request->getPostVars();
		$acao = $postVars['acao'] ?? '';

		if ($acao === 'carregar') {
			return self::carregar();
		}
		if ($acao === 'salvar') {
			return self::salvar($postVars);
		}
		if ($acao === 'restaurar') {
			return self::restaurar();
		}
		if ($acao === 'salvar_certificado') {
			return self::salvarCertificado($postVars);
		}

		return json_encode(['success' => false, 'message' => 'Ação inválida.']);
	}

	private static function carregar(): string {
		$idAdmin = TenantHelper::getIdAdmin();
		$escola = EscolasAssinantes::getEscolaById($idAdmin);
		$colOk = EscolasAssinantes::temColunaModeloContrato();
		$fraseOk = EscolasAssinantes::temColunaCertificadoFrase();

		$custom = '';
		$usandoPadrao = true;
		if ($escola instanceof EscolasAssinantes && $colOk) {
			$custom = trim((string)($escola->modelo_contrato_html ?? ''));
			$usandoPadrao = ($custom === '');
		}

		$htmlEditor = $usandoPadrao
			? ContratoTemplateHelper::modeloPadrao()
			: $custom;

		$frase = 'Concluiu com louvor o curso de';
		$fraseCustom = false;
		if ($escola instanceof EscolasAssinantes && $fraseOk) {
			$f = trim((string)($escola->certificado_frase_conclusao ?? ''));
			if ($f !== '') {
				$frase = $f;
				$fraseCustom = true;
			}
		}

		$vars = [];
		foreach (ContratoTemplateHelper::catalogoVariaveis() as $k => $desc) {
			$vars[] = ['chave' => $k, 'descricao' => $desc];
		}

		return json_encode([
			'success'         => true,
			'coluna_ok'       => $colOk,
			'frase_coluna_ok' => $fraseOk,
			'usando_padrao'   => $usandoPadrao,
			'html'            => $htmlEditor,
			'html_padrao'     => ContratoTemplateHelper::modeloPadrao(),
			'variaveis'       => $vars,
			'certificado'     => [
				'frase_conclusao' => $frase,
				'usando_padrao'   => !$fraseCustom,
			],
		], JSON_UNESCAPED_UNICODE);
	}

	private static function salvar(array $postVars): string {
		if (!EscolasAssinantes::temColunaModeloContrato()) {
			return json_encode([
				'success' => false,
				'message' => 'Execute o SQL database/escolas_modelo_contrato.sql no phpMyAdmin.',
			]);
		}
		$idAdmin = TenantHelper::getIdAdmin();
		$escola = EscolasAssinantes::getEscolaById($idAdmin);
		if (!$escola instanceof EscolasAssinantes) {
			return json_encode(['success' => false, 'message' => 'Escola não encontrada.']);
		}

		$html = (string)($postVars['html'] ?? '');
		if (trim($html) === '') {
			return json_encode(['success' => false, 'message' => 'Informe o HTML do contrato ou use Restaurar padrão.']);
		}

		if (!EscolasAssinantes::salvarModeloContrato((int)$escola->id, $html)) {
			return json_encode(['success' => false, 'message' => 'Falha ao salvar.']);
		}

		return json_encode([
			'success' => true,
			'message' => 'Modelo de contrato salvo. Novos “Ver Contrato” usarão este texto.',
		]);
	}

	private static function restaurar(): string {
		if (!EscolasAssinantes::temColunaModeloContrato()) {
			return json_encode([
				'success' => false,
				'message' => 'Execute o SQL database/escolas_modelo_contrato.sql no phpMyAdmin.',
			]);
		}
		$idAdmin = TenantHelper::getIdAdmin();
		$escola = EscolasAssinantes::getEscolaById($idAdmin);
		if (!$escola instanceof EscolasAssinantes) {
			return json_encode(['success' => false, 'message' => 'Escola não encontrada.']);
		}

		EscolasAssinantes::salvarModeloContrato((int)$escola->id, null);

		return json_encode([
			'success' => true,
			'message' => 'Padrão CTI restaurado (mesmo texto da escola 1 / Capão Bonito).',
			'html'    => ContratoTemplateHelper::modeloPadrao(),
		], JSON_UNESCAPED_UNICODE);
	}

	private static function salvarCertificado(array $postVars): string {
		if (!EscolasAssinantes::temColunaCertificadoFrase()) {
			return json_encode([
				'success' => false,
				'message' => 'Execute o SQL database/escolas_modelo_contrato.sql no phpMyAdmin.',
			]);
		}
		$idAdmin = TenantHelper::getIdAdmin();
		$escola = EscolasAssinantes::getEscolaById($idAdmin);
		if (!$escola instanceof EscolasAssinantes) {
			return json_encode(['success' => false, 'message' => 'Escola não encontrada.']);
		}

		$frase = trim((string)($postVars['frase_conclusao'] ?? ''));
		$padrao = 'Concluiu com louvor o curso de';
		$salvar = ($frase === '' || $frase === $padrao) ? null : $frase;

		if (!EscolasAssinantes::salvarFraseCertificado((int)$escola->id, $salvar)) {
			return json_encode(['success' => false, 'message' => 'Falha ao salvar.']);
		}

		return json_encode([
			'success' => true,
			'message' => $salvar === null
				? 'Frase do certificado restaurada ao padrão.'
				: 'Frase do certificado salva.',
		]);
	}
}

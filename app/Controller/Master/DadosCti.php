<?php

namespace App\Controller\Master;

use App\Utils\View;
use App\Session\User\Login as SessionUser;
use App\Common\Helpers\ConectCnpjHelper;
use App\Common\Helpers\EmailValidator;
use App\Common\Helpers\MasterStaffHelper;
use App\Common\Helpers\SaasEmpresaCtiHelper;
use App\Model\Entity\EstadoCidades;
use App\Model\Entity\SaasEmpresaCti;

class DadosCti extends Page {

	public static function index($request) {
		if (!SaasEmpresaCti::tabelaExiste()) {
			$content = View::render('master/modules/dados_cti/sql', []);
			return parent::getPanel('Dados jurídicos CTI', $content, 'dados_cti');
		}

		MasterStaffHelper::bootstrapSuperAdmins();

		$estados = [];
		$results = EstadoCidades::getEstados(null, 'nome ASC');
		while ($e = $results->fetchObject()) {
			$estados[] = [
				'id'    => (int)$e->id,
				'nome'  => (string)$e->nome,
				'sigla' => (string)($e->sigla ?? ''),
			];
		}

		$content = View::render('master/modules/dados_cti/index', [
			'estados_json' => json_encode($estados, JSON_UNESCAPED_UNICODE),
		]);
		return parent::getPanel('Dados jurídicos CTI', $content, 'dados_cti');
	}

	public static function getInfo($request) {
		if (!SaasEmpresaCti::tabelaExiste()) {
			return json_encode([
				'success' => false,
				'message' => 'Execute database/saas_empresa_cti.sql no phpMyAdmin.',
			]);
		}

		$post = $request->getPostVars();
		$acao = $post['acao'] ?? '';

		switch ($acao) {
			case 'carregar':
				return self::carregar();
			case 'salvar':
				return self::salvar($post);
			case 'cidades':
				return self::cidades($post);
			default:
				return json_encode(['success' => false, 'message' => 'Ação inválida.']);
		}
	}

	private static function carregar(): string {
		$emp = SaasEmpresaCtiHelper::getOuDefaults();
		$check = SaasEmpresaCtiHelper::checarCompleto($emp);

		return json_encode([
			'success'          => true,
			'dados'            => self::formatar($emp),
			'usuarios_master'  => MasterStaffHelper::listarParaSelect(),
			'completo'         => $check['ok'],
			'faltando'         => $check['faltando'],
		], JSON_UNESCAPED_UNICODE);
	}

	private static function salvar(array $post): string {
		$emp = SaasEmpresaCtiHelper::getOuDefaults();

		$emp->razao_social = trim((string)($post['razao_social'] ?? ''));
		$emp->nome_fantasia = trim((string)($post['nome_fantasia'] ?? ''));
		$emp->cnpj = (string)($post['cnpj'] ?? '');
		$emp->endereco = trim((string)($post['endereco'] ?? ''));
		$emp->numero = trim((string)($post['numero'] ?? ''));
		$emp->bairro = trim((string)($post['bairro'] ?? ''));
		$emp->cep = (string)($post['cep'] ?? '');
		$emp->estado = (int)($post['estado'] ?? 0);
		$emp->cidade = (int)($post['cidade'] ?? 0);
		$emp->email = EmailValidator::normalizar($post['email'] ?? '');
		$emp->telefone = trim((string)($post['telefone'] ?? ''));
		$emp->site = trim((string)($post['site'] ?? ''));
		$emp->rep_cargo = trim((string)($post['rep_cargo'] ?? '')) ?: 'Administrador';
		$emp->foro_comarca = trim((string)($post['foro_comarca'] ?? ''));

		$repUserId = (int)($post['rep_legal_usuario_id'] ?? 0);
		if (SaasEmpresaCti::temColunaRepLegalUsuarioId()) {
			if ($repUserId <= 0 || !MasterStaffHelper::pertenceStaffMaster($repUserId)) {
				return json_encode(['success' => false, 'message' => 'Selecione um representante legal válido (usuário Master).']);
			}
			$emp->rep_legal_usuario_id = $repUserId;
		} else {
			$emp->rep_nome = trim((string)($post['rep_nome'] ?? ''));
			$emp->rep_cpf = (string)($post['rep_cpf'] ?? '');
			$emp->rep_rg = (string)($post['rep_rg'] ?? '');
			$cpf = preg_replace('/\D+/', '', (string)$emp->rep_cpf);
			if ($cpf !== '' && strlen($cpf) !== 11) {
				return json_encode(['success' => false, 'message' => 'CPF do representante inválido.']);
			}
		}

		if ($emp->razao_social === '' || $emp->nome_fantasia === '') {
			return json_encode(['success' => false, 'message' => 'Informe razão social e nome fantasia.']);
		}

		$cnpj = preg_replace('/\D+/', '', (string)$emp->cnpj);
		if ($cnpj !== '' && !ConectCnpjHelper::validar($cnpj)) {
			return json_encode(['success' => false, 'message' => 'CNPJ inválido.']);
		}

		if (!SaasEmpresaCti::salvar($emp)) {
			return json_encode(['success' => false, 'message' => 'Falha ao salvar.']);
		}

		$saved = SaasEmpresaCti::get();
		$check = SaasEmpresaCtiHelper::checarCompleto($saved);

		return json_encode([
			'success'  => true,
			'message'  => 'Dados jurídicos da CTI salvos.',
			'dados'    => self::formatar($saved instanceof SaasEmpresaCti ? $saved : $emp),
			'completo' => $check['ok'],
			'faltando' => $check['faltando'],
		], JSON_UNESCAPED_UNICODE);
	}

	private static function cidades(array $post): string {
		$idEstado = (int)($post['estado'] ?? 0);
		if ($idEstado <= 0) {
			return json_encode(['success' => true, 'cidades' => []]);
		}
		$out = [];
		$results = EstadoCidades::getCidades('estado = '.$idEstado, 'nome ASC');
		while ($c = $results->fetchObject()) {
			$out[] = ['id' => (int)$c->id, 'nome' => (string)$c->nome];
		}
		return json_encode(['success' => true, 'cidades' => $out], JSON_UNESCAPED_UNICODE);
	}

	/** @return array<string,mixed> */
	private static function formatar(SaasEmpresaCti $emp): array {
		$rep = SaasEmpresaCtiHelper::resolverRepresentanteLegal($emp);
		return [
			'razao_social'           => (string)$emp->razao_social,
			'nome_fantasia'          => (string)$emp->nome_fantasia,
			'cnpj'                   => SaasEmpresaCtiHelper::formatCnpj($emp->cnpj ?? ''),
			'cnpj_raw'               => preg_replace('/\D+/', '', (string)($emp->cnpj ?? '')),
			'endereco'               => (string)($emp->endereco ?? ''),
			'numero'                 => (string)($emp->numero ?? ''),
			'bairro'                 => (string)($emp->bairro ?? ''),
			'cep'                    => (string)($emp->cep ?? ''),
			'estado'                 => (int)($emp->estado ?? 0),
			'cidade'                 => (int)($emp->cidade ?? 0),
			'email'                  => (string)($emp->email ?? ''),
			'telefone'               => (string)($emp->telefone ?? ''),
			'site'                   => (string)($emp->site ?? ''),
			'rep_legal_usuario_id'   => (int)($emp->rep_legal_usuario_id ?? 0),
			'rep_nome'               => $rep ? ($rep['nome'] ?? '') : '',
			'rep_cpf'                => ($rep && isset($rep['cpf'])) ? SaasEmpresaCtiHelper::formatCpf($rep['cpf']) : '',
			'rep_rg'                 => $rep ? ($rep['rg'] ?? '') : '',
			'rep_cargo'              => (string)($emp->rep_cargo ?? 'Administrador'),
			'foro_comarca'           => (string)($emp->foro_comarca ?? ''),
			'endereco_completo'      => SaasEmpresaCtiHelper::resolverEndereco($emp),
			'tem_coluna_rep_usuario' => SaasEmpresaCti::temColunaRepLegalUsuarioId(),
		];
	}
}

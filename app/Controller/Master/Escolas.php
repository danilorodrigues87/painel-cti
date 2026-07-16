<?php

namespace App\Controller\Master;

use App\Utils\View;
use App\Common\SystemModules;
use App\Common\Helpers\ModuleGateHelper;
use App\Model\Entity\EscolasAssinantes;
use App\Model\Entity\PlanosAssinatura;
use App\Model\Entity\User as EntityUser;
use App\Session\User\Login as SessionUser;

class Escolas extends Page {

	public static function index($request) {
		$content = View::render('master/modules/escolas/index', [
			'modulos_json' => json_encode(self::catalogoModulos(), JSON_UNESCAPED_UNICODE),
			'planos_json'  => json_encode(Planos::listarAtivosResumo(), JSON_UNESCAPED_UNICODE),
			'tem_plan_id'  => EscolasAssinantes::temColunaPlanId() ? '1' : '0',
		]);
		return parent::getPanel('Escolas — Master', $content, 'escolas');
	}

	public static function getInfo($request) {
		$post = $request->getPostVars();
		$acao = $post['acao'] ?? '';

		switch ($acao) {
			case 'listar':
				return self::listar();
			case 'detalhes':
				return self::detalhes($post);
			case 'salvar':
				return self::salvar($post);
			case 'toggle_ativo':
				return self::toggleAtivo($post);
			case 'reset_diretor':
				return self::resetDiretor($post);
			case 'impersonar':
				return self::impersonar($request, $post);
			default:
				return json_encode(['success' => false, 'message' => 'Ação inválida.']);
		}
	}

	/** @return array<int, array{slug:string,label:string}> */
	private static function catalogoModulos(): array {
		$out = [];
		foreach (SystemModules::getCatalog() as $slug => $label) {
			$out[] = ['slug' => $slug, 'label' => $label];
		}
		return $out;
	}

	private static function listar(): string {
		$results = EscolasAssinantes::getEscolas(null, 'nome ASC');
		$lista = [];
		while ($e = $results->fetchObject(EscolasAssinantes::class)) {
			$lista[] = self::formatar($e);
		}
		return json_encode(['success' => true, 'escolas' => $lista]);
	}

	private static function detalhes(array $post): string {
		$id = (int)($post['id'] ?? 0);
		$e = EscolasAssinantes::getEscolaById($id);
		if (!$e instanceof EscolasAssinantes) {
			return json_encode(['success' => false, 'message' => 'Escola não encontrada.']);
		}
		$data = self::formatar($e, true);
		$data['diretores'] = self::listarDiretores((int)$e->id);
		return json_encode(['success' => true, 'escola' => $data]);
	}

	private static function salvar(array $post): string {
		$id = (int)($post['id'] ?? 0);
		$nome = trim((string)($post['nome'] ?? ''));
		$email = trim((string)($post['email'] ?? ''));
		$telefone = trim((string)($post['telefone'] ?? ''));
		$cpfCnpj = trim((string)($post['cpf_cnpj'] ?? ''));
		$ativo = !empty($post['ativo']) ? 's' : 'n';
		$diretorNome = trim((string)($post['diretor_nome'] ?? ''));
		$diretorEmail = trim((string)($post['diretor_email'] ?? ''));
		$planId = (int)($post['plan_id'] ?? 0);

		if ($nome === '') {
			return json_encode(['success' => false, 'message' => 'Informe o nome da escola.']);
		}

		[$modulosJson, $planIdSalvar, $erroMods] = self::resolverModulosEPlano($post, $planId);
		if ($erroMods !== null) {
			return json_encode(['success' => false, 'message' => $erroMods]);
		}

		if ($id > 0) {
			$ob = EscolasAssinantes::getEscolaById($id);
			if (!$ob instanceof EscolasAssinantes) {
				return json_encode(['success' => false, 'message' => 'Escola não encontrada.']);
			}
			self::preencherDadosEscola($ob, $post, $nome, $email, $telefone, $cpfCnpj, $ativo, $modulosJson, $planIdSalvar);
			$ob->id_admin = (int)$ob->id;
			$ob->atualizar();
			ModuleGateHelper::limparCache((int)$ob->id);

			return json_encode([
				'success' => true,
				'message' => 'Escola atualizada.',
				'escola'  => self::formatar($ob, true),
			]);
		}

		if ($diretorNome === '' || $diretorEmail === '') {
			return json_encode(['success' => false, 'message' => 'Informe nome e e-mail do Diretor da escola.']);
		}
		if (!filter_var($diretorEmail, FILTER_VALIDATE_EMAIL)) {
			return json_encode(['success' => false, 'message' => 'E-mail do Diretor inválido.']);
		}
		if (EntityUser::getUserByEmail($diretorEmail) instanceof EntityUser) {
			return json_encode(['success' => false, 'message' => 'Este e-mail do Diretor já está cadastrado.']);
		}

		$ob = new EscolasAssinantes;
		self::preencherDadosEscola($ob, $post, $nome, $email !== '' ? $email : $diretorEmail, $telefone, $cpfCnpj, $ativo, $modulosJson, $planIdSalvar);
		$ob->logo = null;
		$ob->instagram = null;
		$ob->youtube = null;
		$ob->id_admin = 0;
		$ob->cadastrar();

		if ((int)$ob->id <= 0) {
			return json_encode(['success' => false, 'message' => 'Falha ao criar a escola.']);
		}

		ModuleGateHelper::limparCache((int)$ob->id);

		$senhaTemp = self::gerarSenhaTemporaria();
		$labelsAcesso = ($modulosJson === null)
			? SystemModules::getPermissions()
			: SystemModules::slugsParaLabels(json_decode($modulosJson, true) ?: []);

		$diretor = new EntityUser;
		$diretor->nome = $diretorNome;
		$diretor->email = $diretorEmail;
		$diretor->nivel = 'Diretor';
		$diretor->senha = password_hash($senhaTemp, PASSWORD_DEFAULT);
		$diretor->whatsapp = $telefone;
		$diretor->rg = '';
		$diretor->cpf = '';
		$diretor->nascimento = '';
		$diretor->endereco = $ob->endereco ?? '';
		$diretor->numero = $ob->numero ?? '';
		$diretor->bairro = $ob->bairro ?? '';
		$diretor->uf = $ob->estado ?? '';
		$diretor->cidade = $ob->cidade ?? '';
		$diretor->ativo = 's';
		$diretor->acesso = json_encode(array_values($labelsAcesso), JSON_UNESCAPED_UNICODE);
		$diretor->id_admin = (int)$ob->id;
		$diretor->cadastrar();

		return json_encode([
			'success' => true,
			'message' => 'Escola criada com sucesso.',
			'escola'  => self::formatar($ob, true),
			'diretor' => [
				'nome'  => $diretorNome,
				'email' => $diretorEmail,
				'senha' => $senhaTemp,
			],
		]);
	}

	private static function preencherDadosEscola(
		EscolasAssinantes $ob,
		array $post,
		string $nome,
		string $email,
		string $telefone,
		string $cpfCnpj,
		$ativo,
		$modulosJson,
		$planIdSalvar
	): void {
		$ob->nome = $nome;
		$ob->email = $email !== '' ? $email : null;
		$ob->telefone = $telefone !== '' ? $telefone : null;
		$ob->cpf_cnpj = $cpfCnpj !== '' ? $cpfCnpj : null;
		$ob->site = trim((string)($post['site'] ?? '')) ?: null;
		$ob->endereco = trim((string)($post['endereco'] ?? '')) ?: null;
		$ob->numero = trim((string)($post['numero'] ?? '')) ?: null;
		$ob->bairro = trim((string)($post['bairro'] ?? '')) ?: null;
		$ob->cidade = trim((string)($post['cidade'] ?? '')) ?: null;
		$ob->estado = trim((string)($post['estado'] ?? '')) ?: null;
		$ob->cep = trim((string)($post['cep'] ?? '')) ?: null;
		$ob->ativo = $ativo;
		$ob->modulos_liberados = $modulosJson;
		$ob->plan_id = $planIdSalvar;
	}

	/**
	 * @return array{0:?string,1:?int,2:?string} [modulosJson, planId, erro]
	 */
	private static function resolverModulosEPlano(array $post, int $planId): array {
		if ($planId > 0 && PlanosAssinatura::tabelaExiste()) {
			$plano = PlanosAssinatura::getById($planId);
			if (!$plano instanceof PlanosAssinatura) {
				return [null, null, 'Plano inválido.'];
			}
			if (!(int)$plano->ativo) {
				return [null, null, 'Este plano está inativo.'];
			}
			return [$plano->modulosParaEscola(), $planId, null];
		}

		$todosModulos = !empty($post['todos_modulos']);
		if ($todosModulos) {
			return [null, null, null];
		}
		$slugs = self::parseSlugs($post['modulos_json'] ?? '[]');
		if (empty($slugs)) {
			return [null, null, 'Selecione um plano, módulos, ou marque “Todos os módulos”.'];
		}
		return [json_encode($slugs, JSON_UNESCAPED_UNICODE), null, null];
	}

	private static function toggleAtivo(array $post): string {
		$id = (int)($post['id'] ?? 0);
		$ob = EscolasAssinantes::getEscolaById($id);
		if (!$ob instanceof EscolasAssinantes) {
			return json_encode(['success' => false, 'message' => 'Escola não encontrada.']);
		}
		$ob->ativo = $ob->isAtiva() ? 'n' : 's';
		$ob->atualizar();
		ModuleGateHelper::limparCache($id);

		return json_encode([
			'success' => true,
			'message' => $ob->isAtiva() ? 'Escola ativada.' : 'Escola desativada.',
			'escola'  => self::formatar($ob),
		]);
	}

	private static function resetDiretor(array $post): string {
		$escolaId = (int)($post['id'] ?? 0);
		$usuarioId = (int)($post['usuario_id'] ?? 0);
		$escola = EscolasAssinantes::getEscolaById($escolaId);
		if (!$escola instanceof EscolasAssinantes) {
			return json_encode(['success' => false, 'message' => 'Escola não encontrada.']);
		}

		$idAdmin = (int)$escola->id;
		if ($usuarioId > 0) {
			$user = EntityUser::getUser(
				'id = '.$usuarioId.' AND id_admin = '.$idAdmin.' AND nivel = "Diretor"'
			)->fetchObject(EntityUser::class);
		} else {
			$user = EntityUser::getUser(
				'id_admin = '.$idAdmin.' AND nivel = "Diretor" AND ativo = "s"',
				'id ASC',
				'1'
			)->fetchObject(EntityUser::class);
		}

		if (!$user instanceof EntityUser) {
			return json_encode(['success' => false, 'message' => 'Nenhum Diretor encontrado nesta escola.']);
		}

		$senhaTemp = self::gerarSenhaTemporaria();
		$user->senha = password_hash($senhaTemp, PASSWORD_DEFAULT);
		$user->resetSenha();

		return json_encode([
			'success' => true,
			'message' => 'Senha do Diretor redefinida.',
			'diretor' => [
				'id'    => (int)$user->id,
				'nome'  => $user->nome,
				'email' => $user->email,
				'senha' => $senhaTemp,
			],
		]);
	}

	private static function impersonar($request, array $post): string {
		$escolaId = (int)($post['id'] ?? 0);
		$escola = EscolasAssinantes::getEscolaById($escolaId);
		if (!$escola instanceof EscolasAssinantes) {
			return json_encode(['success' => false, 'message' => 'Escola não encontrada.']);
		}
		if (!$escola->isAtiva()) {
			return json_encode(['success' => false, 'message' => 'Ative a escola antes de entrar nela.']);
		}

		$diretor = EntityUser::getUser(
			'id_admin = '.(int)$escola->id.' AND nivel = "Diretor" AND ativo = "s"',
			'id ASC',
			'1'
		)->fetchObject(EntityUser::class);

		if (!$diretor instanceof EntityUser) {
			return json_encode(['success' => false, 'message' => 'Nenhum Diretor ativo nesta escola.']);
		}

		if (!SessionUser::iniciarImpersonate($diretor, $escola)) {
			return json_encode(['success' => false, 'message' => 'Não foi possível iniciar o acesso.']);
		}

		return json_encode([
			'success'  => true,
			'message'  => 'Entrando no painel da escola...',
			'redirect' => rtrim((string)URL, '/').'/painel',
		]);
	}

	/** @return array<int, array{id:int,nome:string,email:string,ativo:string}> */
	private static function listarDiretores(int $idAdmin): array {
		$out = [];
		$results = EntityUser::getUser(
			'id_admin = '.$idAdmin.' AND nivel = "Diretor"',
			'nome ASC',
			null,
			'id, nome, email, ativo'
		);
		while ($u = $results->fetchObject(EntityUser::class)) {
			$out[] = [
				'id'    => (int)$u->id,
				'nome'  => $u->nome,
				'email' => $u->email,
				'ativo' => $u->ativo,
			];
		}
		return $out;
	}

	private static function formatar(EscolasAssinantes $e, bool $completo = false): array {
		$raw = $e->modulos_liberados ?? null;
		$todos = ($raw === null || $raw === '');
		$slugs = [];
		if (!$todos) {
			$decoded = json_decode((string)$raw, true);
			if (is_array($decoded)) {
				$validos = array_flip(SystemModules::getSlugs());
				foreach ($decoded as $s) {
					$s = (string)$s;
					if (isset($validos[$s])) {
						$slugs[] = $s;
					}
				}
			}
		}

		$planId = EscolasAssinantes::temColunaPlanId() ? (int)($e->plan_id ?? 0) : 0;
		$planoNome = null;
		if ($planId > 0) {
			$plano = PlanosAssinatura::getById($planId);
			if ($plano instanceof PlanosAssinatura) {
				$planoNome = $plano->nome;
			}
		}

		$out = [
			'id'            => (int)$e->id,
			'id_admin'      => (int)($e->id_admin ?: $e->id),
			'nome'          => $e->nome,
			'email'         => $e->email,
			'telefone'      => $e->telefone,
			'cpf_cnpj'      => $e->cpf_cnpj,
			'ativo'         => $e->isAtiva() ? 1 : 0,
			'todos_modulos' => $todos,
			'modulos'       => $slugs,
			'modulos_qtd'   => $todos ? count(SystemModules::getSlugs()) : count($slugs),
			'plan_id'       => $planId ?: null,
			'plano_nome'    => $planoNome,
		];

		if ($completo) {
			$out['site'] = $e->site;
			$out['endereco'] = $e->endereco;
			$out['numero'] = $e->numero;
			$out['bairro'] = $e->bairro;
			$out['cidade'] = $e->cidade;
			$out['estado'] = $e->estado;
			$out['cep'] = $e->cep;
		}

		return $out;
	}

	/** @return string[] */
	private static function parseSlugs($raw): array {
		$arr = is_array($raw) ? $raw : (json_decode((string)$raw, true) ?: []);
		$validos = array_flip(SystemModules::getSlugs());
		$out = [];
		foreach ($arr as $s) {
			$s = (string)$s;
			if (isset($validos[$s])) {
				$out[$s] = true;
			}
		}
		return array_keys($out);
	}

	private static function gerarSenhaTemporaria(): string {
		return 'Ct'.substr(bin2hex(random_bytes(4)), 0, 6).'!';
	}
}

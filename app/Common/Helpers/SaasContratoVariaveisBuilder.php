<?php

namespace App\Common\Helpers;

use App\Common\SystemModules;
use App\Model\Entity\EscolasAssinantes;
use App\Model\Entity\PlanosAssinatura;
use App\Model\Entity\SaasEmpresaCti;
use App\Model\Entity\User as EntityUser;
use App\Model\Entity\LmsCurso;
use App\Model\Entity\LmsVitrineAssinatura;
use App\Model\Entity\LmsVitrineConfig;

class SaasContratoVariaveisBuilder {

	/**
	 * @return array<string,string>
	 */
	public static function montarFromEscola(EscolasAssinantes $escola): array {
		return self::montar($escola, null);
	}

	/**
	 * @param array<string,mixed> $opts id_escola, id_plano
	 * @return array<string,string>
	 */
	public static function dadosExemplo(array $opts = []): array {
		$idEscola = (int)($opts['id_escola'] ?? 0);
		$idPlano = (int)($opts['id_plano'] ?? 0);

		if ($idEscola > 0) {
			$escola = EscolasAssinantes::getEscolaById($idEscola);
			if ($escola instanceof EscolasAssinantes) {
				return self::montar($escola, $idPlano > 0 ? $idPlano : null);
			}
		}

		return self::montarExemploFicticio($idPlano);
	}

	/**
	 * @return array{ok:bool,faltando:string[],vars:array<string,string>}
	 */
	public static function montarComMeta(EscolasAssinantes $escola): array {
		$vars = self::montarFromEscola($escola);
		$faltando = self::listarPendencias($escola);
		return [
			'ok'       => empty($faltando),
			'faltando' => $faltando,
			'vars'     => $vars,
		];
	}

	/** @return string[] */
	public static function listarPendencias(?EscolasAssinantes $escola = null): array {
		$faltando = [];
		$emp = SaasEmpresaCti::get();
		$check = SaasEmpresaCtiHelper::checarCompleto($emp);
		if (!$check['ok']) {
			$faltando = array_merge($faltando, $check['faltando']);
		}
		if ($escola instanceof EscolasAssinantes) {
			$cnpj = preg_replace('/\D+/', '', (string)($escola->cpf_cnpj ?? ''));
			if ($cnpj === '') {
				$faltando[] = 'CNPJ da escola';
			}
			$dir = self::buscarDiretor((int)$escola->id);
			if (!$dir instanceof EntityUser) {
				$faltando[] = 'Diretor ativo da escola';
			} elseif (strlen(preg_replace('/\D+/', '', (string)($dir->cpf ?? ''))) !== 11) {
				$faltando[] = 'CPF do Diretor (Perfil)';
			}
		}
		return array_values(array_unique($faltando));
	}

	/**
	 * @return array<string,string>
	 */
	private static function montar(EscolasAssinantes $escola, ?int $planoOverride): array {
		$plano = self::resolverPlano($escola, $planoOverride);
		$cidadeUf = self::cidadeUfEscola($escola);
		$baseUrl = rtrim((string)(defined('URL') ? URL : ''), '/');
		$emp = SaasEmpresaCtiHelper::getOuDefaults();
		$diretor = self::buscarDiretor((int)$escola->id);

		return [
			'URL'                        => $baseUrl,
			'licenciante'                => self::htmlLicenciante($emp),
			'representante_licenciante'  => self::htmlRepresentanteLicenciante($emp),
			'licenciada'                 => self::htmlLicenciada($escola, $cidadeUf),
			'representante_licenciada'   => self::htmlRepresentanteLicenciada($diretor),
			'plano_resumo'               => self::htmlPlanoResumo($escola, $plano),
			'plano_descricao'            => self::htmlPlanoDescricao($plano),
			'modulos_contratados'        => self::htmlModulos($escola, $plano),
			'condicoes_financeiras'      => self::htmlCondicoesFinanceiras($escola, $plano),
			'clausula_vitrine'           => self::htmlClausulaVitrine((int)$escola->id),
			'foro'                       => htmlspecialchars(SaasEmpresaCtiHelper::resolverForo($emp), ENT_QUOTES, 'UTF-8'),
			'assinaturas'                => self::htmlAssinaturas($emp, $diretor, (string)$escola->nome),
			'trial_dias'                 => (string)SaasAssinaturaService::TRIAL_DIAS_DEFAULT,
			'grace_dias'                 => (string)SaasAssinaturaService::GRACE_DIAS,
			'url_privacidade'            => $baseUrl.'/privacidade',
			'data_contrato'              => self::htmlDataContrato(date('Y-m-d'), $cidadeUf),
		];
	}

	/** @return array<string,string> */
	private static function montarExemploFicticio(int $idPlano): array {
		$escola = new EscolasAssinantes();
		$escola->id = 0;
		$escola->nome = 'Escola Modelo Ltda.';
		$escola->cpf_cnpj = '00000000000191';
		$escola->email = 'diretor@escolamodelo.com.br';
		$escola->telefone = '(14) 99999-0000';
		$escola->endereco = 'Rua Exemplo';
		$escola->numero = '100';
		$escola->bairro = 'Centro';
		$escola->cep = '18300000';
		$escola->plan_id = $idPlano > 0 ? $idPlano : 2;
		$escola->dia_vencimento_assinatura = 10;
		$escola->assinatura_status = 'trial';
		$escola->trial_ate = date('Y-m-d', strtotime('+'.SaasAssinaturaService::TRIAL_DIAS_DEFAULT.' days'));

		$vars = self::montar($escola, $idPlano > 0 ? $idPlano : null);
		$vars['representante_licenciada'] = '<p><strong>Representante legal da LICENCIADA:</strong> '
			.'Maria Silva, CPF 123.456.789-00, na qualidade de Diretor(a) da escola.</p>';
		return $vars;
	}

	private static function resolverPlano(EscolasAssinantes $escola, ?int $override): ?PlanosAssinatura {
		$planId = $override !== null && $override > 0
			? $override
			: (EscolasAssinantes::temColunaPlanId() ? (int)($escola->plan_id ?? 0) : 0);
		if ($planId <= 0) {
			return null;
		}
		$plano = PlanosAssinatura::getById($planId);
		return $plano instanceof PlanosAssinatura ? $plano : null;
	}

	private static function cidadeUfEscola(EscolasAssinantes $escola): string {
		return ContratoVariaveisBuilder::resolverCidadeUf(
			(int)($escola->cidade ?? 0),
			(int)($escola->estado ?? 0)
		);
	}

	private static function buscarDiretor(int $idAdmin): ?EntityUser {
		if ($idAdmin <= 0) {
			return null;
		}
		$row = EntityUser::getUser(
			'id_admin = '.$idAdmin.' AND nivel = "Diretor" AND ativo = "s"',
			'id ASC',
			'1'
		)->fetchObject(EntityUser::class);
		return $row instanceof EntityUser ? $row : null;
	}

	private static function htmlLicenciante(SaasEmpresaCti $emp): string {
		$razao = htmlspecialchars((string)$emp->razao_social, ENT_QUOTES, 'UTF-8');
		$fantasia = htmlspecialchars((string)$emp->nome_fantasia, ENT_QUOTES, 'UTF-8');
		$cnpj = htmlspecialchars(SaasEmpresaCtiHelper::formatCnpj($emp->cnpj ?? ''), ENT_QUOTES, 'UTF-8');
		$end = htmlspecialchars(SaasEmpresaCtiHelper::resolverEndereco($emp), ENT_QUOTES, 'UTF-8');
		$site = htmlspecialchars(trim((string)($emp->site ?? '')) ?: 'https://ctieducacional.com.br', ENT_QUOTES, 'UTF-8');
		$email = htmlspecialchars(trim((string)($emp->email ?? '')) ?: '—', ENT_QUOTES, 'UTF-8');
		$tel = htmlspecialchars(trim((string)($emp->telefone ?? '')) ?: '—', ENT_QUOTES, 'UTF-8');

		return '<p><strong>LICENCIANTE:</strong> '.$razao.' — '.$fantasia
			.', pessoa jurídica de direito privado, inscrita no CNPJ sob nº <strong>'.$cnpj
			.'</strong>, com sede em '.$end.', operadora da plataforma SaaS Painel CTI Educacional, site '
			.'<a href="'.$site.'">'.$site.'</a>, e-mail '.$email.', telefone '.$tel.'.</p>';
	}

	private static function htmlRepresentanteLicenciante(SaasEmpresaCti $emp): string {
		$rep = SaasEmpresaCtiHelper::resolverRepresentanteLegal($emp);
		if (!$rep || trim($rep['nome']) === '') {
			return '<p><em>Representante legal da LICENCIANTE: pendente de cadastro em Master → Dados jurídicos CTI.</em></p>';
		}
		$nome = htmlspecialchars($rep['nome'], ENT_QUOTES, 'UTF-8');
		$cpf = htmlspecialchars(SaasEmpresaCtiHelper::formatCpf($rep['cpf'] ?? ''), ENT_QUOTES, 'UTF-8');
		$cargo = htmlspecialchars($rep['cargo'] ?: 'Administrador', ENT_QUOTES, 'UTF-8');
		$rg = trim((string)($rep['rg'] ?? ''));
		$rgHtml = $rg !== '' ? ', RG '.htmlspecialchars($rg, ENT_QUOTES, 'UTF-8') : '';

		return '<p><strong>Representante legal da LICENCIANTE:</strong> '.$nome
			.', CPF '.$cpf.$rgHtml.', na qualidade de '.$cargo.' da CTI Educacional.</p>';
	}

	private static function htmlLicenciada(EscolasAssinantes $escola, string $cidadeUf): string {
		$nome = htmlspecialchars((string)$escola->nome, ENT_QUOTES, 'UTF-8');
		$cnpjRaw = preg_replace('/\D+/', '', (string)($escola->cpf_cnpj ?? ''));
		$cnpj = htmlspecialchars(
			strlen($cnpjRaw) === 14 ? SaasEmpresaCtiHelper::formatCnpj($cnpjRaw)
				: (trim((string)($escola->cpf_cnpj ?? '')) ?: '—'),
			ENT_QUOTES,
			'UTF-8'
		);
		$email = htmlspecialchars(trim((string)($escola->email ?? '')) ?: '—', ENT_QUOTES, 'UTF-8');
		$tel = htmlspecialchars(trim((string)($escola->telefone ?? '')) ?: '—', ENT_QUOTES, 'UTF-8');
		$end = htmlspecialchars(
			ContratoVariaveisBuilder::montarEnderecoEscola((array)$escola, $cidadeUf),
			ENT_QUOTES,
			'UTF-8'
		);

		return '<p><strong>LICENCIADA:</strong> '.$nome
			.', inscrita no CNPJ/CPF sob nº <strong>'.$cnpj
			.'</strong>, com endereço em '.$end
			.', e-mail '.$email.', telefone '.$tel.'.</p>';
	}

	private static function htmlRepresentanteLicenciada(?EntityUser $diretor): string {
		if (!$diretor instanceof EntityUser) {
			return '<p><em>Representante legal da LICENCIADA: cadastre um Diretor ativo na escola.</em></p>';
		}
		$nome = htmlspecialchars(trim((string)$diretor->nome), ENT_QUOTES, 'UTF-8');
		$cpf = htmlspecialchars(SaasEmpresaCtiHelper::formatCpf($diretor->cpf ?? ''), ENT_QUOTES, 'UTF-8');
		$email = htmlspecialchars(trim((string)($diretor->email ?? '')), ENT_QUOTES, 'UTF-8');

		return '<p><strong>Representante legal da LICENCIADA:</strong> '.$nome
			.', CPF '.$cpf.', e-mail '.$email.', na qualidade de Diretor(a) da escola.</p>';
	}

	private static function htmlPlanoResumo(EscolasAssinantes $escola, ?PlanosAssinatura $plano): string {
		$valor = SaasAssinaturaService::resolverValorMensal($escola);
		$valorBr = $valor > 0 ? 'R$ '.number_format($valor, 2, ',', '.') : 'conforme proposta comercial';
		$custom = EscolasAssinantes::temColunaValorMensalCustom()
			&& (float)($escola->valor_mensal_custom ?? 0) > 0;

		$nome = $plano instanceof PlanosAssinatura
			? htmlspecialchars((string)$plano->nome, ENT_QUOTES, 'UTF-8')
			: 'Personalizado';

		$statusLabel = self::labelStatusAssinatura(
			EscolasAssinantes::temColunasAssinatura() ? (string)($escola->assinatura_status ?? 'ativa') : 'ativa',
			$escola
		);
		$customNota = $custom
			? ' <em>(valor mensal customizado acordado com a CTI, prevalecendo sobre a tabela do plano)</em>.'
			: '.';

		return '<p><strong>Plano contratado:</strong> '.$nome.'<br>'
			.'<strong>Valor mensal de referência:</strong> '.$valorBr.$customNota.'<br>'
			.'<strong>Situação da assinatura:</strong> '.$statusLabel.'</p>';
	}

	private static function htmlPlanoDescricao(?PlanosAssinatura $plano): string {
		if (!$plano instanceof PlanosAssinatura) {
			return '<p><strong>Descrição do plano:</strong> Plano personalizado negociado individualmente com a CTI Educacional, '
				.'com escopo e funcionalidades definidos em proposta comercial.</p>';
		}

		$nome = htmlspecialchars((string)$plano->nome, ENT_QUOTES, 'UTF-8');
		$detalhada = PlanosAssinatura::getDescricaoDetalhada($plano);
		$resumo = trim((string)($plano->descricao ?? ''));

		$html = '<p><strong>Plano:</strong> '.$nome.'</p>';

		if ($detalhada !== '') {
			$html .= '<div class="plano-descricao-detalhada"><strong>Funcionalidades e escopo contratados:</strong>'
				.self::textoParaHtml($detalhada).'</div>';
		} elseif ($resumo !== '') {
			$html .= '<p><strong>Resumo:</strong> '.htmlspecialchars($resumo, ENT_QUOTES, 'UTF-8').'</p>';
		} else {
			$html .= '<p><em>Descrição detalhada do plano não cadastrada. Consulte a lista de módulos abaixo.</em></p>';
		}

		return $html;
	}

	private static function textoParaHtml(string $texto): string {
		$linhas = preg_split('/\r\n|\r|\n/', trim($texto)) ?: [];
		$items = [];
		$paragrafos = [];
		foreach ($linhas as $linha) {
			$linha = trim($linha);
			if ($linha === '') {
				continue;
			}
			if (preg_match('/^[-*•]\s+/', $linha)) {
				$items[] = '<li>'.htmlspecialchars(preg_replace('/^[-*•]\s+/', '', $linha), ENT_QUOTES, 'UTF-8').'</li>';
			} else {
				if (!empty($items)) {
					$paragrafos[] = '<ul>'.implode('', $items).'</ul>';
					$items = [];
				}
				$paragrafos[] = '<p>'.htmlspecialchars($linha, ENT_QUOTES, 'UTF-8').'</p>';
			}
		}
		if (!empty($items)) {
			$paragrafos[] = '<ul>'.implode('', $items).'</ul>';
		}
		return implode('', $paragrafos);
	}

	private static function labelStatusAssinatura(string $status, EscolasAssinantes $escola): string {
		if (SaasAssinaturaService::emTrialAtivo($escola)) {
			$ate = EscolasAssinantes::temColunaTrialAte() ? trim((string)($escola->trial_ate ?? '')) : '';
			$ateBr = $ate !== '' ? DateTimeHelper::databr($ate) : '—';
			return 'Período de experiência (trial) até '.$ateBr;
		}
		$map = [
			'ativa'     => 'Ativa',
			'suspensa'  => 'Suspensa por inadimplência',
			'trial'     => 'Trial encerrado — aguardando regularização',
			'cancelada' => 'Cancelada',
		];
		return $map[$status] ?? ucfirst($status);
	}

	private static function htmlModulos(?EscolasAssinantes $escola, ?PlanosAssinatura $plano): string {
		$slugs = [];
		if ($plano instanceof PlanosAssinatura) {
			if ($plano->temTodosModulos()) {
				return '<p><strong>Módulos contratados:</strong> todos os módulos disponíveis no catálogo '
					.'comercial do Painel CTI Educacional (plano Completo ou equivalente).</p>';
			}
			$slugs = $plano->getSlugs();
		} elseif ($escola instanceof EscolasAssinantes && !empty($escola->modulos_liberados)) {
			$decoded = json_decode((string)$escola->modulos_liberados, true);
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

		if (empty($slugs)) {
			return '<p><strong>Módulos contratados:</strong> conforme liberação registrada no cadastro da escola '
				.'no Painel Master CTI.</p>';
		}

		$items = '';
		foreach ($slugs as $slug) {
			$label = SystemModules::slugParaLabel($slug) ?: $slug;
			$items .= '<li>'.htmlspecialchars($label, ENT_QUOTES, 'UTF-8').'</li>';
		}

		return '<p><strong>Módulos liberados no painel:</strong></p><ul>'.$items.'</ul>';
	}

	private static function htmlCondicoesFinanceiras(EscolasAssinantes $escola, ?PlanosAssinatura $plano): string {
		$valor = SaasAssinaturaService::resolverValorMensal($escola);
		$valorBr = $valor > 0 ? 'R$ '.number_format($valor, 2, ',', '.') : 'valor definido em proposta';
		$dia = EscolasAssinantes::temColunasAssinatura()
			? max(1, min(28, (int)($escola->dia_vencimento_assinatura ?? 10)))
			: 10;
		$trial = SaasAssinaturaService::TRIAL_DIAS_DEFAULT;
		$grace = SaasAssinaturaService::GRACE_DIAS;

		$planoNome = $plano instanceof PlanosAssinatura
			? htmlspecialchars((string)$plano->nome, ENT_QUOTES, 'UTF-8')
			: 'Personalizado';

		return '<p>As condições comerciais abaixo complementam o plano <strong>'.$planoNome.'</strong>:</p>'
			.'<ul>'
			.'<li><strong>Periodicidade:</strong> mensal, por competência (mês/ano), vencimento todo dia '.$dia.';</li>'
			.'<li><strong>Valor base:</strong> '.$valorBr.' por mês (+ licenças EAD/taxas quando aplicável);</li>'
			.'<li><strong>Pagamento:</strong> PIX via Mercado Pago CTI na área Assinatura;</li>'
			.'<li><strong>Trial:</strong> '.$trial.' dias quando concedido;</li>'
			.'<li><strong>Tolerância:</strong> '.$grace.' dias após vencimento antes da suspensão.</li>'
			.'</ul>';
	}

	private static function htmlClausulaVitrine(int $idAdmin): string {
		if ($idAdmin <= 0 || !LmsVitrineAssinatura::tabelaExiste()) {
			return '<p>Sem licenças ativas na Vitrine EAD no momento. Licenças futuras serão faturadas mensalmente.</p>';
		}
		$licencas = LmsVitrineAssinatura::listAtivasEscola($idAdmin);
		if (empty($licencas)) {
			return '<p>Sem licenças EAD ativas na vitrine na data deste instrumento.</p>';
		}
		$taxa = LmsVitrineConfig::taxaCtiMensal();
		$taxaBr = $taxa > 0 ? 'R$ '.number_format($taxa, 2, ',', '.') : 'conforme tabela vigente';
		$items = '';
		foreach ($licencas as $ass) {
			$curso = LmsCurso::getById((int)$ass->id_curso);
			if (!$curso instanceof LmsCurso) {
				continue;
			}
			$preco = round((float)($curso->vitrine_preco_mensal ?? 0), 2);
			$precoBr = $preco > 0 ? 'R$ '.number_format($preco, 2, ',', '.').'/mês' : '—';
			$items .= '<li>'.htmlspecialchars($curso->nomeExibicao(), ENT_QUOTES, 'UTF-8').' — '.$precoBr.'</li>';
		}
		return '<p><strong>Vitrine EAD</strong> — licenças mensais:</p><ul>'.$items.'</ul>'
			.'<p>Taxa CTI de intermediação: <strong>'.$taxaBr.'</strong> por licença ativa, quando aplicável.</p>';
	}

	private static function htmlAssinaturas(SaasEmpresaCti $emp, ?EntityUser $diretor, string $nomeEscola): string {
		$repData = SaasEmpresaCtiHelper::resolverRepresentanteLegal($emp);
		$repCti = ($repData && trim($repData['nome']) !== '')
			? $repData['nome']
			: 'Representante CTI Educacional';
		$repEsc = ($diretor instanceof EntityUser && trim((string)$diretor->nome) !== '')
			? (string)$diretor->nome
			: 'Representante legal — '.trim($nomeEscola);

		$repCti = htmlspecialchars($repCti, ENT_QUOTES, 'UTF-8');
		$repEsc = htmlspecialchars($repEsc, ENT_QUOTES, 'UTF-8');

		return '<div class="assinaturas"><table><tr>'
			.'<td><div class="linha-ass"></div><strong>LICENCIANTE</strong><br>CTI Educacional<br>'.$repCti.'</td>'
			.'<td><div class="linha-ass"></div><strong>LICENCIADA</strong><br>'
			.htmlspecialchars(trim($nomeEscola), ENT_QUOTES, 'UTF-8').'<br>'.$repEsc.'</td>'
			.'</tr></table></div>';
	}

	private static function htmlDataContrato(string $data, string $cidadeUf): string {
		$dia = DateTimeHelper::extraiDia($data);
		$ano = DateTimeHelper::extraiAno($data);
		$mesSemZero = ltrim(DateTimeHelper::extraiMes($data), '0');
		$mes = DateTimeHelper::imprimeMes($mesSemZero);
		$local = $cidadeUf !== '' ? htmlspecialchars($cidadeUf, ENT_QUOTES, 'UTF-8') : 'Local';

		return '<p style="text-align: right;"><strong>'.$local.'</strong>, '.$dia.' de '.$mes.' de '.$ano.'.</p>';
	}
}

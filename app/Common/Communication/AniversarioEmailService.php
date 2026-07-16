<?php

namespace App\Common\Communication;

use App\Common\Helpers\CampanhaSegmentoHelper;
use App\Common\Helpers\EmailValidator;
use App\Model\Entity\EmailAniversarioLog;
use App\Model\Entity\EscolaIntegracoes;
use App\Model\Entity\EscolasAssinantes;

class AniversarioEmailService {

	public static function getTemplatePadrao(): array {
		return [
			'assunto' => 'Feliz aniversário, {nome}! — {escola}',
			'mensagem' => '<p>Olá <strong>{nome}</strong>,</p>'
				.'<p>A equipe da <strong>{escola}</strong> deseja a você um excelente aniversário!</p>'
				.'<p>Que este novo ciclo seja repleto de conquistas e aprendizado.</p>'
				.'<p>Um abraço,<br><strong>{escola}</strong></p>',
		];
	}

	public static function preview(int $idAdmin, array $override = []): array {
		if (!EmailAniversarioLog::tabelaExiste()) {
			return [
				'ok' => false,
				'message' => 'Execute o SQL da tabela email_aniversario_log.',
				'itens' => [],
				'total' => 0,
			];
		}

		if (!EscolaIntegracoes::temColunasAniversario()) {
			return [
				'ok' => false,
				'message' => 'Execute o SQL das colunas de aniversário em escola_integracoes.',
				'itens' => [],
				'total' => 0,
			];
		}

		$config = self::montarConfig($idAdmin, $override);
		$itens = self::coletarPendentes($idAdmin, $config, true);

		return [
			'ok' => true,
			'ativo' => (int)$config->aniversario_ativo === 1,
			'itens' => $itens,
			'total' => count($itens),
			'enviados_hoje' => EmailAniversarioLog::contarHoje($idAdmin),
		];
	}

	public static function processar(int $idAdmin = 0, bool $dryRun = false): array {
		$resumo = [
			'escolas' => 0,
			'enviados' => 0,
			'erros' => 0,
			'ignorados' => 0,
			'detalhes' => [],
		];

		if (!EmailAniversarioLog::tabelaExiste() || !EscolaIntegracoes::temColunasAniversario()) {
			$resumo['erro'] = 'Tabelas/colunas de aniversário não existem.';
			return $resumo;
		}

		foreach (self::listarEscolasAtivas($idAdmin) as $escolaId) {
			$config = EscolaIntegracoes::getByIdAdmin($escolaId);
			if (!$config instanceof EscolaIntegracoes) {
				continue;
			}

			$resumo['escolas']++;
			$nomeEscola = self::nomeEscola($escolaId);
			$email = Email::escola($escolaId);
			$delay = max(1, (int)$config->email_delay_segundos);
			$tpl = self::getTemplatePadrao();
			$assuntoTpl = trim($config->aniversario_assunto ?? '') ?: $tpl['assunto'];
			$msgTpl = trim($config->aniversario_mensagem ?? '') ?: $tpl['mensagem'];
			$ano = (int)date('Y');

			foreach (self::coletarPendentes($escolaId, $config, false) as $item) {
				if ($dryRun) {
					$resumo['ignorados']++;
					continue;
				}

				$vars = [
					'nome' => $item['nome'],
					'contato' => $item['contato'],
					'curso' => $item['curso'] ?? '',
					'escola' => $nomeEscola,
				];
				$assunto = CampanhaSegmentoHelper::aplicarVariaveis($assuntoTpl, $vars);
				$corpo = CampanhaSegmentoHelper::aplicarVariaveis($msgTpl, $vars);

				$ok = $email->sendEmail($item['contato'], $assunto, $corpo);
				if ($ok) {
					EmailAniversarioLog::registrar($escolaId, (int)$item['destinatario_id'], $ano, $item['contato']);
					$resumo['enviados']++;
					sleep($delay);
				} else {
					$resumo['erros']++;
					$resumo['detalhes'][] = [
						'usuario_id' => $item['destinatario_id'],
						'erro' => $email->getError() ?: 'Falha no envio',
					];
				}
			}
		}

		return $resumo;
	}

	private static function listarEscolasAtivas(int $idAdminFiltro): array {
		if ($idAdminFiltro > 0) {
			$config = EscolaIntegracoes::getByIdAdmin($idAdminFiltro);
			if ($config instanceof EscolaIntegracoes && (int)($config->aniversario_ativo ?? 0) === 1) {
				return [$idAdminFiltro];
			}
			return [];
		}

		$results = EscolaIntegracoes::get('aniversario_ativo = 1');
		$ids = [];
		while ($row = $results->fetchObject(EscolaIntegracoes::class)) {
			$ids[] = (int)$row->id_admin;
		}
		return $ids;
	}

	private static function montarConfig(int $idAdmin, array $override): EscolaIntegracoes {
		$config = EscolaIntegracoes::getByIdAdmin($idAdmin);
		if (!$config instanceof EscolaIntegracoes) {
			$config = new EscolaIntegracoes;
			$config->id_admin = $idAdmin;
		}

		if (isset($override['aniversario_ativo'])) {
			$config->aniversario_ativo = (int)$override['aniversario_ativo'];
		}
		if (isset($override['aniversario_apenas_matriculados'])) {
			$config->aniversario_apenas_matriculados = (int)$override['aniversario_apenas_matriculados'];
		}

		return $config;
	}

	private static function coletarPendentes(int $idAdmin, EscolaIntegracoes $config, bool $incluirJaEnviados): array {
		$apenasMatriculados = (int)($config->aniversario_apenas_matriculados ?? 1) === 1;
		$destinatarios = CampanhaSegmentoHelper::resolverDestinatarios($idAdmin, [
			'tipo' => $apenasMatriculados ? 'aniversariantes_dia_matriculados' : 'aniversariantes_dia',
		]);

		$ano = (int)date('Y');
		$lista = [];

		foreach ($destinatarios as $item) {
			$idUser = (int)($item['destinatario_id'] ?? 0);
			if ($idUser <= 0 || empty($item['contato']) || !EmailValidator::isValido($item['contato'])) {
				continue;
			}
			if (!$incluirJaEnviados && EmailAniversarioLog::jaEnviou($idUser, $ano)) {
				continue;
			}
			$lista[] = $item;
		}

		return $lista;
	}

	private static function nomeEscola(int $idAdmin): string {
		$escola = EscolasAssinantes::getEscolaById($idAdmin);
		return ($escola instanceof EscolasAssinantes) ? ($escola->nome ?? '') : '';
	}
}

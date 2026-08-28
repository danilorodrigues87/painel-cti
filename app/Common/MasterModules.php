<?php

namespace App\Common;

/** Catálogo de módulos/permissões do Painel Master (slugs estáveis → labels UI). */
class MasterModules {

	/** @var array<string,string> */
	private static $catalog = [
		'escolas'        => 'Escolas',
		'planos'         => 'Planos',
		'assinaturas'    => 'Assinaturas',
		'contrato_saas'  => 'Contrato SaaS',
		'dados_cti'      => 'Dados jurídicos CTI',
		'chamados'       => 'Chamados',
		'ead_cursos'     => 'Cursos CTI',
		'conquistas'     => 'Conquistas',
		'portal'         => 'Portal do aluno',
		'site_branding'  => 'Site CTI',
		'documentacao'   => 'Documentação',
		'conect'         => 'Conecta Jovem',
		'bunny'          => 'Bunny',
		'prospeccao'     => 'Prospecção',
		'usuarios'       => 'Usuários Master',
	];

	/** @return array<string,string> */
	public static function getCatalog(): array {
		return self::$catalog;
	}

	/** @return string[] */
	public static function getSlugs(): array {
		return array_keys(self::$catalog);
	}

	public static function slugParaLabel(string $slug): ?string {
		return self::$catalog[$slug] ?? null;
	}

	public static function campoPermissao(string $slug): string {
		return 'perm_master_'.preg_replace('/[^a-z0-9_]/', '', $slug);
	}

	/** @param array<int|string,mixed> $post */
	public static function slugsFromPost(array $post): array {
		$out = [];
		foreach (self::getSlugs() as $slug) {
			$campo = self::campoPermissao($slug);
			if (!empty($post[$campo])) {
				$out[] = $slug;
			}
		}
		return array_values(array_unique($out));
	}

	/** @param array<int,string> $slugs */
	public static function htmlCheckboxes(array $slugsMarcados): string {
		$html = '';
		foreach (self::getCatalog() as $slug => $label) {
			$checked = in_array($slug, $slugsMarcados, true) ? ' checked' : '';
			$campo = self::campoPermissao($slug);
			$html .= '<div class="col-md-4 col-sm-6"><div class="form-check">'
				.'<input class="form-check-input chk-master-perm" type="checkbox" id="'.$campo.'" name="'.$campo.'" value="1"'.$checked.'>'
				.'<label class="form-check-label" for="'.$campo.'">'.htmlspecialchars($label, ENT_QUOTES, 'UTF-8').'</label>'
				.'</div></div>';
		}
		return $html;
	}
}

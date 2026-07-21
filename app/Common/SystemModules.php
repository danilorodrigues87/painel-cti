<?php

namespace App\Common;

class SystemModules {

	/**
	 * Catálogo de módulos: slug (estável/planos) => label (permissão na UI e usuarios.acesso).
	 */
	private static $catalog = [
		'funcionarios'    => 'Funcionários',
		'responsaveis'    => 'Responsáveis',
		'leads'           => 'Leads',
		'tarefas'         => 'Tarefas',
		'comunicacao'     => 'Comunicação',
		'campanhas'       => 'Campanhas',
		'whatsapp'        => 'WhatsApp',
		'dados_escola'    => 'Dados da escola',
		'vouchers'        => 'Vouchers',
		'categorias'      => 'Categorias',
		'trilhas'         => 'Trilhas',
		'ead'             => 'Cursos Online',
		'certificacoes'   => 'Certificações',
		'alunos'          => 'Alunos',
		'matriculas'      => 'Matriculas',
		'contratos'       => 'Modelo de contrato',
		'entrada'         => 'Entrada',
		'saida'           => 'Saída',
		'carnes'          => 'Carnês',
		'pagamentos'      => 'Pagamentos',
		'vendas'          => 'Vendas',
		'recorrente'      => 'Recorrente',
		'relatorios'      => 'Relatórios',
		'escolas'         => 'Escolas',
		'agendamentos'    => 'Agendamentos',
		'laboratorios'    => 'Laboratórios',
		'horarios'        => 'Horários',
		'diario'          => 'Diário',
	];

	/** Labels legados aceitos na leitura de usuarios.acesso */
	private static $labelAliases = [
		'Laboratório' => 'Agendamentos',
		'Contratos'   => 'Modelo de contrato',
	];

	public static function getCatalog(): array {
		return self::$catalog;
	}

	public static function getSlugs(): array {
		return array_keys(self::$catalog);
	}

	public static function getPermissions(): array {
		return array_values(self::$catalog);
	}

	public static function slugParaLabel(string $slug): ?string {
		return self::$catalog[$slug] ?? null;
	}

	public static function labelParaSlug(string $label): ?string {
		$label = self::normalizarLabel($label);
		if ($label === null) {
			return null;
		}
		$slug = array_search($label, self::$catalog, true);
		return $slug !== false ? $slug : null;
	}

	/**
	 * Nome seguro de campo HTML/POST para permissão (slug).
	 * Evita espaços: PHP converte "cursos Online" em "cursos_Online" e o save falhava.
	 */
	public static function campoPermissao(?string $label): string {
		$slug = self::labelParaSlug((string)$label);
		if ($slug !== null) {
			return 'perm_'.$slug;
		}
		$safe = preg_replace('/[^a-z0-9_]+/i', '_', (string)$label);
		return 'perm_'.strtolower((string)$safe);
	}

	public static function normalizarLabel(?string $label): ?string {
		if ($label === null || $label === '' || $label === '0') {
			return null;
		}
		$label = (string)$label;
		if (isset(self::$labelAliases[$label])) {
			$label = self::$labelAliases[$label];
		}
		if (!in_array($label, self::$catalog, true)) {
			return null;
		}
		return $label;
	}

	public static function labelsParaSlugs(array $labels): array {
		$slugs = [];
		foreach ($labels as $label) {
			$slug = self::labelParaSlug((string)$label);
			if ($slug !== null) {
				$slugs[] = $slug;
			}
		}
		return array_values(array_unique($slugs));
	}

	public static function slugsParaLabels(array $slugs): array {
		$labels = [];
		foreach ($slugs as $slug) {
			$label = self::slugParaLabel((string)$slug);
			if ($label !== null) {
				$labels[] = $label;
			}
		}
		return array_values(array_unique($labels));
	}

	private static $modules = [

		'Dashboard' => [
			'label' => 'Dashboard',
			'link' => URL.'/painel',
			'icon' => 'fas fa-tachometer-alt'
		],
		'users' => [
			'label' => 'Usuários',
			'icon' => 'fas fa-users',
			'subsections' => [
				'name' => 'Layouts-users',
				'icon' => 'fas fa-caret-down',
				'items' => [
					[
						'label' => 'Funcionários',
						'link' => URL.'/painel/user'
					],
					[
						'label' => 'Alunos',
						'link' => URL.'/painel/clientes'
					],
					[
						'label' => 'Responsáveis',
						'link' => URL.'/painel/responsavel'
					]
				]
			]
		],
		'pedagogico' => [
			'label' => 'Pedagógico',
			'icon' => 'fa-solid fa-graduation-cap',
			'subsections' => [
				'name' => 'Layouts-pedagogico',
				'icon' => 'fas fa-caret-down',
				'items' => [
					[
						'label' => 'Matriculas',
						'link' => URL.'/painel/matriculas'
					],
					[
						'label' => 'Trilhas',
						'link' => URL.'/painel/trilhas'
					],
					[
						'label' => 'Cursos Online',
						'link' => URL.'/painel/ead'
					],
					[
						'label' => 'Categorias',
						'link' => URL.'/painel/categoria/cursos'
					],
					[
						'label' => 'Certificações',
						'link' => URL.'/painel/certificados'
					]
				]
			]
		],
		'CRM' => [
			'label' => 'CRM',
			'icon' => 'fa-regular fa-address-book',
			'subsections' => [
				'name' => 'Layouts-CRM',
				'icon' => 'fas fa-caret-down',
				'items' => [
					[
						'label' => 'Leads',
						'link' => URL.'/painel/crm'
					],
					[
						'label' => 'Tarefas',
						'link' => URL.'/painel/crm/tarefas'
					]
				]
			]
		],
		'whatsapp' => [
			'label' => 'WhatsApp',
			'link' => URL.'/painel/whatsapp',
			'icon' => 'fab fa-whatsapp'
		],
		'campanhas' => [
			'label' => 'Campanhas',
			'link' => URL.'/painel/campanhas',
			'icon' => 'fa-solid fa-bullhorn'
		],
		'Financeiro' => [
			'label' => 'Financeiro',
			'icon' => 'fa-solid fa-coins',
			'subsections' => [
				'name' => 'Layouts-Financeiro',
				'icon' => 'fas fa-caret-down',
				'items' => [
					[
						'label' => 'Assinatura',
						'link' => URL.'/painel/assinatura'
					],
					[
						'label' => 'Carnês',
						'link' => URL.'/painel/carnes'
					],
					[
						'label' => 'Entrada',
						'link' => URL.'/painel/caixa/entrada'
					],
					[
						'label' => 'Saída',
						'link' => URL.'/painel/caixa/saida'
					],
					[
						'label' => 'Relatórios',
						'link' => URL.'/painel/caixa/relatorio'
					]
				]
			]
		],
		'agenda' => [
			'label' => 'Agenda',
			'icon' => 'fa-regular fa-calendar-check',
			'subsections' => [
				'name' => 'Layouts-agenda',
				'icon' => 'fas fa-caret-down',
				'items' => [
					[
						'label' => 'Laboratórios',
						'link' => URL.'/painel/agenda/laboratorios'
					],
					[
						'label' => 'Horários',
						'link' => URL.'/painel/agenda/horarios'
					],
					[
						'label' => 'Agendamentos',
						'link' => URL.'/painel/agenda/laboratorio'
					],
					[
						'label' => 'Diário',
						'link' => URL.'/painel/agenda/diario'
					]
				]
			]
		],
		'config' => [
			'label' => 'Configurações',
			'icon' => 'fa-solid fa-gear',
			'subsections' => [
				'name' => 'Layouts-config',
				'icon' => 'fas fa-caret-down',
				'items' => [
					[
						'label' => 'Dados da escola',
						'link' => URL.'/painel/config/escola'
					],
					[
						'label' => 'Comunicação',
						'link' => URL.'/painel/config/comunicacao'
					],
					[
						'label' => 'Pagamentos',
						'link' => URL.'/painel/config/pagamentos'
					],
					[
						'label' => 'IA Pedagógica',
						'link' => URL.'/painel/config/ia'
					],
					[
						'label' => 'Modelo de contrato',
						'link' => URL.'/painel/config/contrato'
					]
				]
			]
		],
		'Termos de Uso' => [
			'label' => 'Termos de Uso',
			'link' => URL.'/painel/termos-de-uso',
			'icon' => 'fa-solid fa-file-circle-check'
		]

	];

	public static function getModules(){
		return self::$modules;
	}

}

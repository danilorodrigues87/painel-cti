<?php

namespace App\Common\Helpers;

use App\Utils\View;

class MasterMenuHelper {

	/** @return list<array<string,mixed>> */
	private static function grupos(): array {
		return [
			[
				'id'    => 'dashboard',
				'label' => 'Dashboard',
				'icon'  => 'fas fa-tachometer-alt',
				'items' => [
					['id' => 'home', 'label' => 'Dashboard', 'url' => '/master', 'icon' => 'fas fa-tachometer-alt'],
				],
			],
			[
				'id'    => 'negocio',
				'label' => 'Escolas & negócio',
				'icon'  => 'fas fa-school',
				'items' => [
					['id' => 'escolas', 'label' => 'Escolas', 'url' => '/master/escolas', 'icon' => 'fas fa-school'],
					['id' => 'planos', 'label' => 'Planos', 'url' => '/master/planos', 'icon' => 'fas fa-box-open'],
					['id' => 'assinaturas', 'label' => 'Assinaturas', 'url' => '/master/assinaturas', 'icon' => 'fas fa-file-invoice-dollar'],
					['id' => 'chamados', 'label' => 'Chamados', 'url' => '/master/chamados', 'icon' => 'fas fa-headset'],
				],
			],
			[
				'id'    => 'conteudo',
				'label' => 'Conteúdo CTI',
				'icon'  => 'fas fa-book-open',
				'items' => [
					['id' => 'ead_cursos', 'label' => 'Cursos CTI', 'url' => '/master/ead-cursos', 'icon' => 'fas fa-book-open'],
					['id' => 'conquistas', 'label' => 'Conquistas', 'url' => '/master/conquistas', 'icon' => 'fas fa-medal'],
					['id' => 'portal', 'label' => 'Portal do aluno', 'url' => '/master/portal-branding', 'icon' => 'fas fa-palette'],
					['id' => 'site-branding', 'label' => 'Site CTI', 'url' => '/master/site-branding', 'icon' => 'fas fa-globe'],
					['id' => 'documentacao', 'label' => 'Documentação', 'url' => '/master/documentacao', 'icon' => 'fas fa-book'],
				],
			],
			[
				'id'    => 'conect',
				'label' => 'Conecta Jovem',
				'icon'  => 'fas fa-briefcase',
				'items' => [
					['id' => 'conect', 'label' => 'Moderação', 'url' => '/master/conect', 'icon' => 'fas fa-briefcase'],
					['id' => 'conect-branding', 'label' => 'Marca', 'url' => '/master/conect-branding', 'icon' => 'fas fa-paint-brush'],
					['id' => 'conect-blog', 'label' => 'Blog', 'url' => '/master/conect-blog', 'icon' => 'fas fa-newspaper'],
					['id' => 'conect-depoimentos', 'label' => 'Depoimentos', 'url' => '/master/conect-depoimentos', 'icon' => 'fas fa-quote-left'],
					['id' => 'conect-relatorios', 'label' => 'Relatórios', 'url' => '/master/conect-relatorios', 'icon' => 'fas fa-chart-bar'],
				],
			],
			[
				'id'    => 'infra',
				'label' => 'Infra',
				'icon'  => 'fas fa-cloud',
				'items' => [
					['id' => 'bunny', 'label' => 'Bunny', 'url' => '/master/bunny', 'icon' => 'fas fa-cloud'],
				],
			],
			[
				'id'    => 'prospeccao',
				'label' => 'Prospecção',
				'icon'  => 'fas fa-search-location',
				'items' => [
					['id' => 'prospeccao-empresas', 'label' => 'Empresas (Maps)', 'url' => '/master/prospeccao-empresas', 'icon' => 'fas fa-building'],
				],
			],
			[
				'id'    => 'conta',
				'label' => 'Conta',
				'icon'  => 'fas fa-user-cog',
				'items' => [
					['id' => 'perfil', 'label' => 'Meu perfil', 'url' => '/master/perfil', 'icon' => 'fas fa-user-cog'],
				],
			],
		];
	}

	private static function grupoDoItem(string $menuAtivo): ?string {
		foreach (self::grupos() as $grupo) {
			foreach ($grupo['items'] as $item) {
				if (($item['id'] ?? '') === $menuAtivo) {
					return (string)$grupo['id'];
				}
			}
		}
		return null;
	}

	public static function render(string $menuAtivo): string {
		$grupoAtivo = self::grupoDoItem($menuAtivo);
		$html = '<div class="nav"><div class="sb-sidenav-menu-heading">Master</div>';

		foreach (self::grupos() as $grupo) {
			$gid = (string)$grupo['id'];
			$items = $grupo['items'] ?? [];
			if (count($items) === 1 && $gid === 'dashboard') {
				$item = $items[0];
				$active = $menuAtivo === ($item['id'] ?? '') ? 'active' : '';
				$html .= '<a class="nav-link '.$active.'" href="'.URL.($item['url'] ?? '').'">'
					.'<div class="sb-nav-link-icon"><i class="'.htmlspecialchars((string)$item['icon']).'"></i></div>'
					.htmlspecialchars((string)$item['label']).'</a>';
				continue;
			}

			$expanded = $grupoAtivo === $gid ? 'true' : 'false';
			$show = $grupoAtivo === $gid ? 'show' : '';
			$current = $grupoAtivo === $gid ? 'active' : '';

			$subLinks = '';
			foreach ($items as $item) {
				$subActive = $menuAtivo === ($item['id'] ?? '') ? 'active' : '';
				$subLinks .= '<a class="nav-link '.$subActive.'" href="'.URL.($item['url'] ?? '').'">'
					.htmlspecialchars((string)$item['label']).'</a>';
			}

			$html .= View::render('master/menu/dropdown', [
				'label'    => (string)$grupo['label'],
				'icon'     => (string)$grupo['icon'],
				'name'     => $gid,
				'subLinks' => $subLinks,
				'current'  => $current,
				'expanded' => $expanded,
				'show'     => $show,
			]);
		}

		$html .= '</div>';
		return $html;
	}
}

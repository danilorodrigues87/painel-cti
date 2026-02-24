<?php 

namespace App\Controller\Admin;

use \App\Utils\View;
use \App\Session\User\Login as SessionUser;
use \App\Model\Entity\User;
use \App\Common\SystemModules;

class Page {

	

	public static function getDefaultModules($termosAceito){

    $defaultModules = ["Termos de Uso"];

    if($termosAceito){
        $defaultModules[] = "Dashboard";
    }

    return $defaultModules;
}


	public static function getIdAdmin(){
		return SessionUser::getUserLogedData();
	}


	// RETORNA O CONTEUDO (VIEW) ESTRUTURA GENERICA PAGINA PAINEL
	public static function getPage($title,$content){
		$userLogedData = SessionUser::getUserLogedData();

		return View::render('admin/page',[
			'title' => $title,
			'content' => $content,
			'user' => $userLogedData['usuario']['nome'],
			'company' => $userLogedData['empresa']['nome'],
			'logo' => $userLogedData['empresa']['logo'] ?? ''
		]);
	}


public static function getMenu($currentSessionMenu, $permittedModules) {


    // LINKS DO MENU
    $links = '';

    // ITERA OS MODULOS
    foreach (SystemModules::getModules() as $hash => $module) {
        $includeModule = false;
        $subLinks = '';

        // Verifica se o módulo principal está na lista de itens permitidos
        if (in_array($module['label'], $permittedModules)) {
            $includeModule = true;
        }

        // Verifica se há subseções e se alguma delas está na lista de itens permitidos
        if (isset($module['subsections'])) {
            foreach ($module['subsections']['items'] as $subSection) {
                if (in_array($subSection['label'], $permittedModules)) {
                    $includeModule = true;

                    // Adiciona subseção ao subLinks
                    $subLinks .= View::render('admin/menu/sub_link', [
                        'label' => $subSection['label'],
                        'link' => $subSection['link']
                    ]);
                }
            }
        }

        // Renderiza o módulo principal se ele ou alguma de suas subseções estiver permitida
        if ($includeModule) {
            $isActive = $hash == $currentSessionMenu;
            $currentClass = $isActive ? 'active' : '';
            $expanded = $isActive ? 'true' : 'false';
            $showClass = $isActive ? 'show' : '';

            if ($subLinks) {
                $links .= View::render('admin/menu/dropdown', [
                    'label' => $module['label'],
                    'icon' => $module['icon'],
                    'subLinks' => $subLinks,
                    'name' => $module['subsections']['name'] ?? '',
                    'current' => $currentClass,
                    'expanded' => $expanded,
                    'show' => $showClass
                ]);
            } else {
                $links .= View::render('admin/menu/link', [
                    'label' => $module['label'],
                    'link' => $module['link'],
                    'icon' => $module['icon'],
                    'current' => $currentClass
                ]);
            }
        }
    }

    // RETORNA A RENDERIZAÇÃO DO MENU
    return View::render('admin/menu/box', [
        'links' => $links
    ]);
}






	// RENDERIZA A VIEW DO PAINEL COM CONTEUDO DINAMICO
	public static function getPanel($currentModule,$content,$currentSessionMenu,$request=null){

		$userLogedData = SessionUser::getUserLogedData();

		$termosAceito = User::getUser('id = ' . (int)$userLogedData['usuario']['id'],$order = null,$limit = null,'termos_uso')->fetchObject()->termos_uso;

		$permittedModules = array();


		if($termosAceito){
			$permittedModules = $userLogedData['usuario']['acesso'];
		} 


		$defaultModules = self::getDefaultModules($termosAceito);

		$allPermittedModules = array_merge($defaultModules, $permittedModules);


		if (in_array($currentModule,$allPermittedModules)) {

		$contentPanel = View::render('admin/panel',[
			'menu' => self::getMenu($currentSessionMenu,$allPermittedModules),
			'content' => $content
		]);

		return self::getPage($currentModule, $contentPanel);

	} else {

		$request->getRouter()->redirect('/painel/termos-de-uso');
	}


	}


	private static function getPaginationLink($postVars, $page, $label = null) {
    // ALTERA A PÁGINA
    $postVars['page'] = $page['page'];

     // Obtém o filtro, se existir
    $filtro = isset($postVars['filtro']) ? $postVars['filtro'] : null;

    // Garante que o filtro seja passado corretamente como string
    $filtroJs = $filtro !== null ? "'$filtro'" : 'null';

    // VIEW
    $viewLink = '<li class="page-item ' . ($page['current'] ? 'active' : '') . '">
        <a class="page-link" onclick="listar(' . $filtroJs . ',' . $postVars['page'] . ')" href="#">' . ($label ?? $page['page']) . '</a>
    </li>';
    return $viewLink;
}



// RENDERIZA O LAYOUT DE PAGINAÇÃO
	public static function getPagination($request, $obPagination) {
    // PÁGINAS
		$pages = $obPagination->getPages();

    // VERIFICA A QUANTIDADE DE PÁGINAS
		if (count($pages) <= 1) return '';

    // POST
		$postVars = $request->getPostVars();

    // PÁGINA ATUAL
		$currentPage = $postVars['page'] ?? 1;

    // LIMITE DE PÁGINA
		$limit = getenv('PAGINATION_LIMIT');

    // MEIO DA PAGINAÇÃO
		$middle = ceil($limit/2);

    // INÍCIO DA PAGINAÇÃO
		$start = $middle > $currentPage ? 0 : $currentPage - $middle;

    // AJUSTA O FINAL DA PAGINAÇÃO
		$limit = $limit + $start;

    // AJUSTA O INÍCIO DA PAGINAÇÃO
		if ($limit > count($pages)) {
			$diff = $limit - count($pages);
			$start = $start - $diff;
		}

    // LINKS DE PAGINAÇÃO
		$links = '';

    // LINK INICIAL
		if ($start > 0) {
			$links .= self::getPaginationLink($postVars, reset($pages), '<<');
		}

    // RENDERIZA OS ITENS
		foreach ($pages as $page) {
        // VERIFICA O START DA PAGINAÇÃO
			if ($page['page'] <= $start) continue;

        // VERIFICA O LIMITE DA PAGINAÇÃO
			if ($page['page'] > $limit) {
				$links .= self::getPaginationLink($postVars, end($pages), '>>');
				break;
			}

			$links .= self::getPaginationLink($postVars, $page);
		}

    // RENDERIZAÇÃO BOX DE PAGINAÇÃO
		$paginacao = 
		'<nav>
		<ul class="pagination">
		' . $links . '        
		</ul>
		</nav>';

		return $paginacao;
	}


}

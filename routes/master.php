<?php

use App\Http\Response;
use App\Controller\Master;

$obRouter->get('/master', [
	'middlewares' => ['required-master-login'],
	function ($request) {
		return new Response(200, Master\Home::index($request));
	}
]);

$obRouter->get('/master/escolas', [
	'middlewares' => ['required-master-login'],
	function ($request) {
		return new Response(200, Master\Escolas::index($request));
	}
]);

$obRouter->post('/master/escolas', [
	'middlewares' => ['required-master-login'],
	function ($request) {
		return new Response(200, Master\Escolas::getInfo($request));
	}
]);

$obRouter->get('/master/planos', [
	'middlewares' => ['required-master-login'],
	function ($request) {
		return new Response(200, Master\Planos::index($request));
	}
]);

$obRouter->post('/master/planos', [
	'middlewares' => ['required-master-login'],
	function ($request) {
		return new Response(200, Master\Planos::getInfo($request));
	}
]);

$obRouter->get('/master/ead-cursos', [
	'middlewares' => ['required-master-login'],
	function ($request) {
		return new Response(200, Master\EadCursos::index($request));
	}
]);

$obRouter->post('/master/ead-cursos', [
	'middlewares' => ['required-master-login'],
	function ($request) {
		return new Response(200, Master\EadCursos::getInfo($request), 'application/json');
	}
]);

$obRouter->get('/master/ead-cursos/editor/{idCurso}', [
	'middlewares' => ['required-master-login'],
	function ($request, $idCurso) {
		return new Response(200, Master\EadCursos::editor($request, $idCurso));
	}
]);

$obRouter->post('/master/ead-cursos/editor/{idCurso}', [
	'middlewares' => ['required-master-login'],
	function ($request, $idCurso) {
		return new Response(200, Master\EadCursos::editorApi($request, $idCurso), 'application/json');
	}
]);

$obRouter->get('/master/assinaturas', [
	'middlewares' => ['required-master-login'],
	function ($request) {
		return new Response(200, Master\Assinaturas::index($request));
	}
]);

$obRouter->post('/master/assinaturas', [
	'middlewares' => ['required-master-login'],
	function ($request) {
		return new Response(200, Master\Assinaturas::getInfo($request), 'application/json');
	}
]);

$obRouter->get('/master/conquistas', [
	'middlewares' => ['required-master-login'],
	function ($request) {
		return new Response(200, Master\Conquistas::index($request));
	}
]);

$obRouter->post('/master/conquistas', [
	'middlewares' => ['required-master-login'],
	function ($request) {
		return new Response(200, Master\Conquistas::getInfo($request), 'application/json');
	}
]);

$obRouter->get('/master/portal-branding', [
	'middlewares' => ['required-master-login'],
	function ($request) {
		return new Response(200, Master\PortalBranding::index($request));
	}
]);

$obRouter->post('/master/portal-branding', [
	'middlewares' => ['required-master-login'],
	function ($request) {
		return new Response(200, Master\PortalBranding::salvar($request), 'application/json');
	}
]);

$obRouter->get('/master/bunny', [
	'middlewares' => ['required-master-login'],
	function ($request) {
		return new Response(200, Master\Bunny::index($request));
	}
]);

$obRouter->post('/master/bunny', [
	'middlewares' => ['required-master-login'],
	function ($request) {
		return new Response(200, Master\Bunny::api($request), 'application/json');
	}
]);

$obRouter->get('/master/conect', [
	'middlewares' => ['required-master-login'],
	function ($request) {
		return new Response(200, Master\ConectJovem::index($request));
	}
]);

$obRouter->get('/master/conect-branding', [
	'middlewares' => ['required-master-login'],
	function ($request) {
		return new Response(200, Master\ConectBranding::index($request));
	}
]);

$obRouter->post('/master/conect-branding', [
	'middlewares' => ['required-master-login'],
	function ($request) {
		return new Response(200, Master\ConectBranding::salvar($request), 'application/json');
	}
]);

$obRouter->get('/master/site-branding', [
	'middlewares' => ['required-master-login'],
	function ($request) {
		return new Response(200, Master\SiteBranding::index($request));
	}
]);

$obRouter->post('/master/site-branding', [
	'middlewares' => ['required-master-login'],
	function ($request) {
		return new Response(200, Master\SiteBranding::salvar($request), 'application/json');
	}
]);

$obRouter->post('/master/conect/empresa/{id}/aprovar', [
	'middlewares' => ['required-master-login'],
	function ($request, $id) {
		Master\ConectJovem::aprovarEmpresa($request, (int)$id);
		return new Response(302, '', 'text/html');
	}
]);

$obRouter->post('/master/conect/vaga/{id}/aprovar', [
	'middlewares' => ['required-master-login'],
	function ($request, $id) {
		Master\ConectJovem::aprovarVaga($request, (int)$id);
		return new Response(302, '', 'text/html');
	}
]);

$obRouter->post('/master/conect/vaga/{id}/reprovar', [
	'middlewares' => ['required-master-login'],
	function ($request, $id) {
		Master\ConectJovem::reprovarVaga($request, (int)$id);
		return new Response(302, '', 'text/html');
	}
]);

$obRouter->get('/master/conect-blog', [
	'middlewares' => ['required-master-login'],
	function ($request) {
		return new Response(200, Master\ConectBlog::index($request));
	}
]);

$obRouter->get('/master/conect-blog/editar/{id}', [
	'middlewares' => ['required-master-login'],
	function ($request, $id) {
		return new Response(200, Master\ConectBlog::editar($request, (int)$id));
	}
]);

$obRouter->post('/master/conect-blog/salvar', [
	'middlewares' => ['required-master-login'],
	function ($request) {
		return new Response(200, Master\ConectBlog::salvar($request), 'application/json');
	}
]);

$obRouter->post('/master/conect-blog/upload-imagem', [
	'middlewares' => ['required-master-login'],
	function ($request) {
		return new Response(200, Master\ConectBlog::uploadImagem($request), 'application/json');
	}
]);

$obRouter->post('/master/conect-blog/post/{id}/publicar', [
	'middlewares' => ['required-master-login'],
	function ($request, $id) {
		Master\ConectBlog::publicar($request, (int)$id);
		return new Response(302, '', 'text/html');
	}
]);

$obRouter->post('/master/conect-blog/post/{id}/despublicar', [
	'middlewares' => ['required-master-login'],
	function ($request, $id) {
		Master\ConectBlog::despublicar($request, (int)$id);
		return new Response(302, '', 'text/html');
	}
]);

$obRouter->post('/master/conect-blog/post/{id}/excluir', [
	'middlewares' => ['required-master-login'],
	function ($request, $id) {
		Master\ConectBlog::excluirPost($request, (int)$id);
		return new Response(302, '', 'text/html');
	}
]);

$obRouter->post('/master/conect-blog/comentario/{id}/remover', [
	'middlewares' => ['required-master-login'],
	function ($request, $id) {
		Master\ConectBlog::removerComentario($request, (int)$id);
		return new Response(302, '', 'text/html');
	}
]);

$obRouter->get('/master/conect-depoimentos', [
	'middlewares' => ['required-master-login'],
	function ($request) {
		return new Response(200, Master\ConectDepoimentos::index($request));
	}
]);

$obRouter->get('/master/conect-depoimentos/editar/{id}', [
	'middlewares' => ['required-master-login'],
	function ($request, $id) {
		return new Response(200, Master\ConectDepoimentos::editar($request, (int)$id));
	}
]);

$obRouter->post('/master/conect-depoimentos/salvar', [
	'middlewares' => ['required-master-login'],
	function ($request) {
		return new Response(200, Master\ConectDepoimentos::salvar($request), 'application/json');
	}
]);

$obRouter->post('/master/conect-depoimentos/buscar', [
	'middlewares' => ['required-master-login'],
	function ($request) {
		return new Response(200, Master\ConectDepoimentos::buscar($request), 'application/json');
	}
]);

$obRouter->post('/master/conect-depoimentos/{id}/excluir', [
	'middlewares' => ['required-master-login'],
	function ($request, $id) {
		Master\ConectDepoimentos::excluir($request, (int)$id);
		return new Response(302, '', 'text/html');
	}
]);

$obRouter->get('/master/conect-anuncios', [
	'middlewares' => ['required-master-login'],
	function ($request) {
		return new Response(200, Master\ConectAnuncios::index($request));
	}
]);

$obRouter->get('/master/conect-anuncios/editar/{id}', [
	'middlewares' => ['required-master-login'],
	function ($request, $id) {
		return new Response(200, Master\ConectAnuncios::editar($request, (int)$id));
	}
]);

$obRouter->post('/master/conect-anuncios/cidades', [
	'middlewares' => ['required-master-login'],
	function ($request) {
		return new Response(200, Master\ConectAnuncios::cidades($request), 'application/json');
	}
]);

$obRouter->post('/master/conect-anuncios/salvar', [
	'middlewares' => ['required-master-login'],
	function ($request) {
		return new Response(200, Master\ConectAnuncios::salvar($request), 'application/json');
	}
]);

$obRouter->post('/master/conect-anuncios/config', [
	'middlewares' => ['required-master-login'],
	function ($request) {
		return new Response(200, Master\ConectAnuncios::salvarConfig($request), 'application/json');
	}
]);

$obRouter->post('/master/conect-anuncios/{id}/aprovar', [
	'middlewares' => ['required-master-login'],
	function ($request, $id) {
		Master\ConectAnuncios::aprovar($request, (int)$id);
		return new Response(302, '', 'text/html');
	}
]);

$obRouter->post('/master/conect-anuncios/{id}/reprovar', [
	'middlewares' => ['required-master-login'],
	function ($request, $id) {
		return new Response(200, Master\ConectAnuncios::reprovar($request, (int)$id), 'application/json');
	}
]);

$obRouter->post('/master/conect-anuncios/{id}/excluir', [
	'middlewares' => ['required-master-login'],
	function ($request, $id) {
		Master\ConectAnuncios::excluir($request, (int)$id);
		return new Response(302, '', 'text/html');
	}
]);

$obRouter->post('/master/conect-anuncios/planos/salvar', [
	'middlewares' => ['required-master-login'],
	function ($request) {
		return new Response(200, Master\ConectAnuncios::salvarPlano($request), 'application/json');
	}
]);

$obRouter->get('/master/conect-relatorios', [
	'middlewares' => ['required-master-login'],
	function ($request) {
		return new Response(200, Master\ConectRelatorios::index($request));
	}
]);

$obRouter->post('/master/conect-relatorios', [
	'middlewares' => ['required-master-login'],
	function ($request) {
		return new Response(200, Master\ConectRelatorios::getInfo($request), 'application/json');
	}
]);

$obRouter->post('/master/conect-relatorios/export', [
	'middlewares' => ['required-master-login'],
	function ($request) {
		return Master\ConectRelatorios::exportCsv($request);
	}
]);

$obRouter->get('/master/prospeccao-empresas', [
	'middlewares' => ['required-master-login'],
	function ($request) {
		return new Response(200, Master\ProspeccaoEmpresas::index($request));
	}
]);

$obRouter->post('/master/prospeccao-empresas', [
	'middlewares' => ['required-master-login'],
	function ($request) {
		return new Response(200, Master\ProspeccaoEmpresas::getInfo($request), 'application/json');
	}
]);

$obRouter->post('/master/prospeccao-empresas/export', [
	'middlewares' => ['required-master-login'],
	function ($request) {
		return Master\ProspeccaoEmpresas::exportCsv($request);
	}
]);

$obRouter->get('/master/documentacao', [
	'middlewares' => ['required-master-login'],
	function ($request) {
		return new Response(200, Master\Documentacao::index($request));
	}
]);

$obRouter->get('/master/chamados', [
	'middlewares' => ['required-master-login'],
	function ($request) {
		return new Response(200, Master\Chamados::index($request));
	}
]);

$obRouter->post('/master/chamados', [
	'middlewares' => ['required-master-login'],
	function ($request) {
		return new Response(200, Master\Chamados::getInfo($request), 'application/json');
	}
]);

$obRouter->get('/master/chamados/anexo/{id}', [
	'middlewares' => ['required-master-login'],
	function ($request, $id) {
		return Master\Chamados::downloadAnexo($request, $id);
	}
]);

$obRouter->post('/master/documentacao', [
	'middlewares' => ['required-master-login'],
	function ($request) {
		return new Response(200, Master\Documentacao::salvar($request), 'application/json');
	}
]);

$obRouter->get('/master/perfil', [
	'middlewares' => ['required-master-login'],
	function ($request) {
		return new Response(200, Master\Perfil::index($request));
	}
]);

$obRouter->post('/master/perfil/salvar', [
	'middlewares' => ['required-master-login'],
	function ($request) {
		return new Response(200, Master\Perfil::salvar($request));
	}
]);

$obRouter->post('/master/perfil/senha', [
	'middlewares' => ['required-master-login'],
	function ($request) {
		return new Response(200, Master\Perfil::alterarSenha($request));
	}
]);

// Voltar do impersonate (usa sessão do diretor + snapshot master)
$obRouter->get('/master/voltar', [
	'middlewares' => ['required-admin-login'],
	function ($request) {
		return new Response(200, Master\Impersonate::voltar($request));
	}
]);

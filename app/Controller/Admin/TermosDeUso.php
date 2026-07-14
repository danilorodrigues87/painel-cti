<?php 

namespace App\Controller\Admin;
use \App\Utils\View;
use \App\Model\Entity\User as EntityUser;
use \App\Session\User\Login as SessionUser;
use \App\Model\Entity\EstadoCidades;

class TermosDeUso extends Page{

	//RETORNA O FORMULARIO
	public static function index($request){
		//CONTEÚDO DE FORMULÁRIO
		$content = View::render('admin/modules/termos_uso/index',[
			'termos' => self::getTermos()
		]);

		//RETORNA A PÁGINA COMPLETA
		/**
         * TITULO DA PAGINA
         * CONTEUDO
         * CURRENTSESSION SESSÃO ATUAL
         * REQUEST SE NESCESSÁRIO
         */
		return parent::getPanel('Termos de Uso',$content,'Termos de Uso');
	}

	

	public static function getTermos(){

		$userLogedData = SessionUser::getUserLogedData();

		$dadosEscola = $userLogedData['escola'];

		$id_user = (int)$userLogedData['usuario']['id'];

		$dadosUser = (array) EntityUser::getUserById($id_user);

		$termosAceito = $dadosUser['termos_uso'];

		$checkDisabled = ($termosAceito) ? 'checked disabled' : '';

		$cidade = EstadoCidades::getCidades('id = ' . $userLogedData['escola']['cidade'])->fetchObject();
		$estado = EstadoCidades::getEstados('id = ' . $userLogedData['escola']['estado'])->fetchObject();

		$enderecoUser = $dadosUser['endereco'].', '.$dadosUser['numero'].' '.$dadosUser['bairro'].' '.$cidade->nome.'/'.$estado->sigla;


		$declaracao = 'Declaro estar ciente e concordar com os termos deste documento, assumindo total responsabilidade pelo uso adequado dos dados dos clientes da escola <b>'.$dadosEscola['nome'].'.</b>';

		$termo = '
		<div class="row mt-5">
		<div class="container col-9">
		<h2>Termo de Responsabilidade do Funcionário</h2><br>

		<p>
		Eu, <strong>'.$dadosUser['nome'].'</strong>, inscrito(a) no CPF sob o nº <strong class="mascara-cpf">'.$dadosUser['cpf'].'</strong>, residente e domiciliado(a) em <strong>'.$enderecoUser.'</strong>, funcionário(a) da escola <b>'.$dadosEscola['nome'].'</b> com CNPJ <b class="mascara-cnpj">'.$dadosEscola['cpf_cnpj'].'</b> em que presto serviços e assumo total responsabilidade pelo cadastramento e utilização dos dados pessoais dos clientes nesta plataforma online.
		</p>

		<h4>1. Dados Pessoais Cadastrados</h4>
		<p>
		Comprometo-me a cadastrar apenas os dados pessoais básicos de identificação dos clientes, incluindo nome, documentos de identidade, telefone, email e endereço, conforme autorizado pelos próprios clientes.
		</p>

		<h4>2. Uso Adequado dos Dados</h4>
		<p>
		Comprometo-me a utilizar os dados cadastrados exclusivamente para fins relacionados às atividades da escola <b>'.$dadosEscola['nome'].'.</b>, não compartilhando, divulgando ou utilizando esses dados de forma indevida ou para benefício próprio.
		</p>

		<h4>3. Proteção e Segurança dos Dados</h4>
		<p>
		Comprometo-me a adotar as medidas necessárias para proteger e manter em segurança os dados dos clientes, evitando acessos não autorizados, perdas ou vazamentos de informações.
		</p>

		<h4>4. Responsabilidade Legal</h4>
		<p>
		Estou ciente de que a violação desta responsabilidade poderá resultar em medidas disciplinares, legais e financeiras, conforme estabelecido pelas leis aplicáveis e pela política interna da escola <b>'.$dadosEscola['nome'].'.</b>.
		</p> 
		<div class="form-check my-5">
		<input onchange="ativaBtn()" class="form-check-input" type="checkbox" '.$checkDisabled.' id="termo_uso">
		<label class="form-check-label" for="termo_uso">
		'.$declaracao.'
		</label>
		</div>
		<button disabled onclick="termos('.$id_user.')" id="btn-termo" class="btn btn-primary mb-5">Autorizar</button>
		</div>
		</div> 
		';

		return $termo;
		
	}

	public static function aceitaTermo($request){

		$id_user = $request->getPostVars()['id_user'];

		//NOVA INSTANCIA
		$obUsers = new EntityUser;
		$obUsers->id = $id_user;
		$obUsers->termos_uso = 1;
		$obUsers->termoAceito();

		if($obUsers){
			return true;
		} else {
			return 'Erro ao aceitar termos';
		}

	}

	

}
<?php 

namespace App\Controller\Admin;
use \App\Utils\View;
use \App\Model\Entity\Horarios;
use \App\Model\Entity\AgendaAula;
use \App\Model\Entity\Trilhas as EntityTrilhas;
use \App\Model\Entity\Matriculas as EntityMatri;
use \App\Model\Db\Pagination;

class AgendaLaboratorio extends Page{

	private static $vagas = 10;

	//RETORNA O FORMULARIO
	public static function index($request){
		//CONTEÚDO DE FORMULÁRIO
		$content = View::render('admin/modules/agenda/ag_laboratorio',[]);

		//RETORNA A PÁGINA COMPLETA
		/**
         * TITULO DA PAGINA
         * CONTEUDO
         * CURRENTSESSION SESSÃO ATUAL
         * REQUEST SE NESCESSÁRIO
         */
		return parent::getPanel('Laboratório',$content,'agenda');
	}

	private static function getDadosItem($request, &$obPagination) {
    // Aqui podemos usar a data atual ou qualquer outra data no formato Ano-mês-dia (2014-02-28)
		$data = date('Y-m-d');

    // Variável que recebe o dia da semana (0 = Domingo, 1 = Segunda ...)
		$dia = date('w', strtotime($data));
		if ($dia == 0) {
			$dia = 1;
		}

     // DADOS DO ADMIN
		$id_admin = parent::getIdAdmin()['usuario']['id_admin'];

    // PAGINA ATUAL
		$postVars = $request->getPostVars();
		$paginaAtual = $postVars['page'] ?? 1;
		if($postVars['filtro'] != null){
			$filtro = $postVars['filtro'];
		} else {
			$filtro = $dia;
		}



		$where = " AND dia_semana = " . $filtro;

    // QUANTIDADE TOTAL DE REGISTROS
		$quantidadeTotal = Horarios::getHorarios('id_admin = ' . (int)$id_admin . $where, null, null, 'COUNT(*) as qtd')->fetchObject()->qtd;

    // INSTANCIA DE PAGINAÇÃO
		$obPagination = new Pagination($quantidadeTotal, $paginaAtual, 6);


    // RESULTADOS DA PAGINA
		$results = Horarios::getHorarios('id_admin = ' . (int)$id_admin . $where, 'id ASC', $obPagination->getLimit());

		$itens='';

    // RENDERIZA O ITEM
		while ($obDados = $results->fetchObject(Horarios::class)) {
			$itens .= '<tr>
			<td>' . $obDados->inicio . '</td>
			<td>' . $obDados->final . '</td>
			<td>' . $obDados->vagas_ocupadas . ' / '  .self::$vagas. '</td>
			<td> 
			<a class="btn btn-secondary btn-sm" href="#" onclick="ver_info(' . $obDados->id . ', \'editar\')"><i class="fa-solid fa-user-clock fa-lg"></i> Ver</a>
			</td>
			</tr>';
		}

		$table = '<div class="card-body">
		<div class="table-responsive">
		<table class="table table-striped" id="dataTable" width="100%" cellspacing="0">
		<thead>
		<tr>
		<th>Inicia</th>
		<th>Termina</th>
		<th>Vagas Ocupadas </th>
		<th>Info</th>
		</tr>
		</thead>
		<tbody>' . $itens . '</tbody>
		</table>
		</div>
		</div>
		';

    // RETORNA
		$dados = [
			"table" => $table,
			"filtro" => $filtro
		];
		return $dados;
	}

	public static function getInfo($request) {
		$dados = self::getDadosItem($request, $obPagination);
    // CONTEÚDO
		$conteudo = [
			'itens' => $dados['table'],
			'filtro' => $dados['filtro'],
			'pagination' => parent::getPagination($request, $obPagination)
		];

		return json_encode($conteudo);
	}



	public static function verDados($request) {
    $postVars = $request->getPostVars();

    if (isset($postVars['funcao']) && $postVars['funcao'] == 'editar') {
        
        // Ajustando o INNER JOIN
        $innerJoin = 'INNER JOIN usuarios ON agenda_aula.id_aluno = usuarios.id ';
        $innerJoin .= 'INNER JOIN trilhas ON agenda_aula.id_trilha = trilhas.id ';

        // Definindo os campos
        $fields = 'agenda_aula.id as id_agenda,usuarios.nome as aluno, trilhas.nome as trilha, agenda_aula.id';

        // Chamando o método para obter agendamentos
        $results = AgendaAula::getAgendamentos('id_horario = ' . (int)$postVars['id'], 'id ASC', null, $fields, $innerJoin);

       // Inicializa a variável de itens para o conteúdo da tabela
	$listaItens = '';

	// Verifica se há resultados para serem processados
	while ($obDados = $results->fetchObject(AgendaAula::class)) {
	    // Gera cada linha da tabela dinamicamente com os dados obtidos
	    $listaItens .= '
	        <tr>
	            <td>' . htmlspecialchars($obDados->aluno, ENT_QUOTES, 'UTF-8') . '</td>
	            <td>' . htmlspecialchars($obDados->trilha, ENT_QUOTES, 'UTF-8') . '</td>
	            <td>
	                <div class="dropdown">
			<button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
				<i class="far fa-edit fa-lg"></i>
			</button>
			<ul class="dropdown-menu">
				<li>
					<a class="dropdown-item" href="#" onclick="editar(' . $obDados->id_agenda . ', \'editar\')"><i class="far fa-edit fa-lg"></i> Editar</a>
				</li>
				<li>
					<a class="dropdown-item" href="#" onclick="excluir('.$obDados->id_agenda.')" ><i class="far fa-trash-alt fa-lg"></i> Excluir</a>
				</li>
			</ul>
		</div>
	            </td>
	        </tr>
	    ';
	}

    }

    // Criação do formulário
    $form = 
    '
    <div class="modal-header">
        <h1 class="modal-title fs-5" id="exampleModalLabel">Agendamentos</h1>
        <button type="button" class="btn-close" id="btn-fechar" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <div class="modal-body">
        <div id="response"></div>
        <div class="table-responsive">
            <table class="table table-striped" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>Aluno</th>
                        <th>Curso</th>    
                        <th>Opções</th>    
                    </tr>
                </thead>
                <tbody>
                    '.$listaItens.'
                </tbody>
            </table>
        </div>
    </div>';

    return $form;
}

public static function editar($request){

	$postVars = $request->getPostVars();
	$dia_semana = 1;


	if ($postVars['funcao'] == 'editar') {

		$dadosAgenda = (array) AgendaAula::getAgendaById($postVars['id_agenda']);
		$obHorarios = (array) Horarios::getHorarioById($dadosAgenda['id_horario']);
		$dia_semana = $obHorarios['dia_semana'];

	}

// DADOS DO ADMIN
$id_admin = parent::getIdAdmin()['usuario']['id_admin'];

// Ajustando o INNER JOIN
$innerJoin = 'INNER JOIN usuarios ON matriculas.id_aluno = usuarios.id';

// Definindo os campos
$fields = 'DISTINCT usuarios.id, usuarios.nome AS aluno';

// Chamando o método para obter agendamentos
// Especificando a tabela "matriculas" para o campo id_admin na condição
$resultsUser = EntityMatri::getMatriculas("status = 0 AND matriculas.id_admin = $id_admin", 'aluno ASC', null, $fields, $innerJoin);

// Carrega o SELECT dos Alunos
$optSlqUsers = '<select class="form-control" name="id_aluno">
                   <option value="0">Selecione um aluno</option>';

while ($obAlunos = $resultsUser->fetchObject(EntityMatri::class)) {
    $userSelected = (isset($dadosAgenda['id_aluno']) && $dadosAgenda['id_aluno'] == $obAlunos->id) ? 'selected' : '';
    $optSlqUsers .= '<option ' . $userSelected . ' value="' . (int)$obAlunos->id . '">' . $obAlunos->aluno . '</option>';
}
$optSlqUsers .= '</select>';


// Chamando o método para obter Trilhas
$resultsTrilhas = EntityTrilhas::getTrilha("id_admin = $id_admin", 'nome ASC');

// Carrega o SELECT das Trilhas
$optSlqTrilhas = '<select class="form-control" name="id_trilha">
                     <option value="0">Selecione a trilha</option>';

while ($obTrilha = $resultsTrilhas->fetchObject(EntityTrilhas::class)) {
    $trilhaSelected = (isset($dadosAgenda['id_trilha']) && $dadosAgenda['id_trilha'] == $obTrilha->id) ? 'selected' : '';
    $optSlqTrilhas .= '<option ' . $trilhaSelected . ' value="' . (int)$obTrilha->id . '">' . $obTrilha->nome . '</option>';
}
$optSlqTrilhas .= '</select>';

$sql = '';
if ($dia_semana != 0) {
    $sql = " AND dia_semana = " . (int)$dia_semana;
}
$where = "id_admin = " . (int)$id_admin . $sql;

$resultHoras = Horarios::getHorarios($where, 'inicio ASC');

$optHorarios = '<select class="form-control" name="id_horario">
                     <option value="0">Selecione a horario</option>';

while ($obHorarios = $resultHoras->fetchObject(Horarios::class)) {
    $horaSelected = (isset($dadosAgenda['id_horario']) && $dadosAgenda['id_horario'] == $obHorarios->id) ? 'selected' : '';
    $optHorarios .= '<option ' . $horaSelected . ' value="' . (int)$obHorarios->id . '">' . $obHorarios->inicio . ' as ' . $obHorarios->final . '</option>';
}
$optHorarios .= '</select>';



		$form = 
    '<form id="form" method="POST">
        <div class="modal-header">
            <h1 class="modal-title fs-5" id="exampleModalLabel">Detalhes do agendamento</h1>
        	<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
        	</button>
        </div>
        <div class="modal-body">
            <div class="row">
            <div id="response"></div>
                <div class="form-group col-md-6">
                    <label>Nome do Aluno</label>
                    ' . $optSlqUsers . '
                </div>
                <div class="form-group col-md-6">
                    <label>Trilhas</label>
                    ' . $optSlqTrilhas . '
                </div>
                <div class="form-group col-md-6">
			    <label>Dia da Semana</label>
			    <select onchange="select_dia_semana('.(int)@$dadosAgenda['id_horario'].')" name="dia_semana" id="dia_semana" class="form-control">
			        <option ' . ((@$dia_semana == 1) ? 'selected' : '') . ' value="1">Segunda-feira</option>
			        <option ' . ((@$dia_semana == 2) ? 'selected' : '') . ' value="2">Terça-feira</option>
			        <option ' . ((@$dia_semana == 3) ? 'selected' : '') . ' value="3">Quarta-feira</option>
			        <option ' . ((@$dia_semana == 4) ? 'selected' : '') . ' value="4">Quinta-feira</option>
			        <option ' . ((@$dia_semana == 5) ? 'selected' : '') . ' value="5">Sexta-feira</option>
			        <option ' . ((@$dia_semana == 6) ? 'selected' : '') . ' value="6">Sábado</option>
			    </select>
				</div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label>Horário</label>
                        <div id="horarios"></div>
                    </div>
                </div>

            </div>

        </div>
        <div class="modal-footer">
        	<input value="' . (isset($dadosAgenda['id_horario']) ? $dadosAgenda['id_horario'] : '') . '" type="hidden" name="id_horario_antigo">
        	<input value="' . (isset($dadosAgenda['id']) ? $dadosAgenda['id'] : '') . '" type="hidden" name="id">
		    <button type="button" id="btn-fechar-ag" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
    		<button type="submit" class="btn btn-primary">Salvar</button>
		</div>

    </form>'; 

  	$dadosCompletos = [

  		"form" => $form,
  		"id_horario" => $dadosAgenda['id_horario'] ?? 0

  	];

  	return json_encode($dadosCompletos);



}

public static function listarHorarios($request) {

	$postVars = $request->getPostVars();

	$dia_semana = $postVars['dia_semana'];
	$id_horario = $postVars['id_horario'];

    $resultHoras = Horarios::getHorarios("dia_semana = " . (int)$dia_semana, 'inicio ASC');
    $optHorarios = '<select class="form-control" name="id_horario">
                         <option value="0">Selecione o horário</option>';

    // Gerando as opções do select
    while ($obHorarios = $resultHoras->fetchObject(Horarios::class)) {
        $horaSelected = (isset($id_horario) && $id_horario == $obHorarios->id) ? 'selected' : '';
        $optHorarios .= '<option ' . $horaSelected . ' value="' . (int)$obHorarios->id . '">' . $obHorarios->inicio . ' às ' . $obHorarios->final . '</option>';
    }

    $optHorarios .= '</select>';

    return $optHorarios;
}



	public static function salvar($request) {
    $vagas = self::$vagas;

    // DADOS DO ADMIN
    $id_admin = parent::getIdAdmin()['usuario']['id_admin'];

    // Obtém os dados da requisição
    $postVars = $request->getPostVars();

    // Verificações de campos obrigatórios
    if (empty($postVars['id_aluno'])) {
        echo "Erro ao selecionar aluno";
        exit;
    }

    if (empty($postVars['id_trilha'])) {
        echo "Erro ao selecionar trilha";
        exit;
    }

    if (empty($postVars['dia_semana'])) {
        echo "Erro ao selecionar dia da semana";
        exit;
    }


    if (empty($postVars['id_horario'])) {
        echo "Erro ao selecionar horário";
        exit;
    }

    // VERIFICA SE O ALUNO JÁ ESTÁ CADASTRADO NESSE HORÁRIO
    $qtd = AgendaAula::getAgendamentos("id_horario = " . (int)$postVars['id_horario'] . " AND id_aluno = " . (int)$postVars['id_aluno'], null, null, 'COUNT(*) as qtd')->fetchObject(self::class);
    $quantidade = $qtd->qtd ?? 0;

    if ($quantidade > 0) {
        echo "O aluno já está cadastrado nesse horário";
        exit;
    }

    // VERIFICA A DISPONIBILIDADE DE VAGAS
    $horario = Horarios::getHorarios("id = " . (int)$postVars['id_horario'], null, null, 'vagas_ocupadas')->fetchObject();
    $vagas_ocupadas = $horario->vagas_ocupadas ?? 0;
    $vagas_disponiveis = $vagas - $vagas_ocupadas;

    if ($vagas_disponiveis <= 0) {
        echo "Não há vagas disponíveis para esse horário.";
        exit;
    }

 	$vaga_horario_anterior=0;
    // VERIFICA A QTD VAGS NO HORARIO ANTERIOR
    if (!empty($postVars['id_horario_antigo'])) {

    $h_anterior = Horarios::getHorarios("id = " . (int)$postVars['id_horario_antigo'], null, null, 'vagas_ocupadas')->fetchObject();
    $vaga_horario_anterior = $h_anterior->vagas_ocupadas ?? 0;

	}

    // Se houver ID, atualiza, senão cadastra
    $obData = new AgendaAula;
    if (!empty($postVars['id'])) {
        $obData->id = (int)$postVars['id'];
    }
    $obData->id_aluno = (int)$postVars['id_aluno'];
    $obData->id_trilha = (int)$postVars['id_trilha'];
    $obData->dia_semana = $postVars['dia_semana'];
    $obData->id_horario = (int)$postVars['id_horario'];

    if (!empty($postVars['id'])) {
        $obData->atualizar();
    } else {
        $obData->cadastrar();
    }

    if (!$obData) {
        echo "Erro ao cadastrar trilha.";
        exit;
    }

    // Atualiza as vagas ocupadas caso o horário tenha sido alterado
    if (isset($postVars['id_horario_antigo']) && $postVars['id_horario'] != $postVars['id_horario_antigo']) {
        $obHorarioAntigo = new Horarios;
        $obHorarioAntigo->id = (int)$postVars['id_horario_antigo'];
        $obHorarioAntigo->vagas_ocupadas = $vaga_horario_anterior - 1;
        $obHorarioAntigo->atualizarVaga();
    }

    $obHorarioNovo = new Horarios;
    $obHorarioNovo->id = (int)$postVars['id_horario'];
    $obHorarioNovo->vagas_ocupadas = $vagas_ocupadas + 1;
    $obHorarioNovo->atualizarVaga();

    if (!$obHorarioNovo) {
        echo "Erro ao atualizar horários.";
        exit;
    }

    echo "Salvo";
}



	public static function excluir($request){

		$postVars = $request->getPostVars();

		//NOVA INSTANCIA
		$obData = new AgendaAula;
		$obData->id = $postVars['id'];
		$obData->excluir();

		if($obData){
			return true;
		} else {
			return 'Erro ao excluir essa trilha';
		}

		
	}



}
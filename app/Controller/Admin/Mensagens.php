<?php 

namespace App\Controller\Admin;
use \App\Utils\View;
use \App\Model\Entity\WhatsappMsg;
use \App\Model\Entity\User as EntityUser;
use \App\Model\Db\Pagination;
use Netflie\WhatsAppCloudApi\WhatsAppCloudApi;

class Mensagens extends Page {

    // RETORNA O FORMULARIO
    public static function index($request) {

        // CONTEÚDO DE FORMULÁRIO
        $content = View::render('admin/modules/whatsappatm/mensagem',[
            'title' => 'Suporte',
            'info_cliente' => self::getInfo($request),
            'chat' => self::getChat($request),
            'msgBox' => self::msgBox($request)
        ]);

        // RETORNA A PÁGINA COMPLETA
        return parent::getPanel('Suporte', $content, 'Suporte');
    }

    public static function msgBox($request){

         $postVars = $request->getPostVars();
         $id='';
        if (isset($postVars['id_cliente'])) {
            $id_enviado = $postVars['id_cliente'];
            $id= 'id_cliente';
        } else if (isset($postVars['id_atm'])) {
            $id= 'id_atm';
            $id_enviado = $postVars['id_atm'];
        }
        $box = 
        '<li class="bg-white mb-3">
                            <div data-mdb-input-init class="form-outline">
                                <textarea class="form-control bg-body-tertiary" name="mensagem" id="mensagem" rows="4"></textarea>
                                <label class="form-label" for="mensagem">Mensagem</label>
                            </div>
                        </li>
                        <input type="hidden" name="'.$id.'" value="'.$id_enviado .'" >';

                        return $box;

    }

    public static function gerenciaPost($request) {
        $postVars = $request->getPostVars();
        $dados = [];

        if (isset($postVars['id_cliente'])) {
            $dados = (array) EntityUser::getUserById($postVars['id_cliente']);
        } else if (isset($postVars['id_atm'])) {
            $dados = (array) WhatsappMsg::getMessageById($postVars['id_atm']);
        }

        return $dados;
    }

    public static function getInfo($request) {
        $postVars = $request->getPostVars();
        $dados = self::gerenciaPost($request);

        function formatarDataHoraBrasileira($dataHora) {
            // Converte a string de data e hora para um timestamp Unix
            $timestamp = strtotime($dataHora);
            
            // Verifica se a conversão foi bem-sucedida
            if ($timestamp === false) {
                return '';
            }
            
            // Formata a data e hora no padrão brasileiro sem os segundos
            return date('d/m/Y \à\s H:i', $timestamp);
        }

        $dataHoraFormatad = formatarDataHoraBrasileira(@$dados['data_criacao']);
        $dataHoraFormatad = ($dataHoraFormatad ? 'Iniciado em ' . $dataHoraFormatad : 'Não iniciado');
        $chatStatus = (@$dados['status'] == 0 ? '' : 'checked');
        $disabled = (@$dados['status'] == 0 ? 'disabled' : '');

        $info = 
        '<div class="card-header">Informações do Cliente</div>
         <div class="card-body">
             <h5 class="card-title">' . htmlspecialchars($dados['nome']) . '</h5>
             <ul class="list-group list-group-flush">
                 <li class="list-group-item text-bg-danger">' . htmlspecialchars(@$dados['whatsapp']) . '</li>
                 <li class="list-group-item text-bg-danger">' . $dataHoraFormatad . '</li>
             </ul>
         </div> 
         <div class="card-footer">
             <div class="form-check form-switch">
                 <input class="form-check-input" ' . $chatStatus . ' ' . $disabled . ' type="checkbox" role="switch" id="chatStatus" >
                 <label class="form-check-label" for="chatStatus">Finalizar atendimento</label>
             </div>
         </div>';

        return $info;
    }

    // FUNÇÃO QUE RETORNA TODAS AS MENSAGENS
    private static function getChat($request) {
        $postVars = $request->getPostVars();
        $dados = [];

        if (isset($postVars['id_atm'])) {
            $dados = (array) WhatsappMsg::getMessageById($postVars['id_atm']);
        }

        $id_atm = @$postVars['id_atm'];

        // RESULTADOS DA PAGINA
        $results = WhatsappMsg::getListChat("id_wm = '$id_atm'", 'id ASC');

        $chat = '';
        $hasMessages = false; // Verificação se há mensagens

        // RENDERIZA O ITEM
        while ($obDados = $results->fetchObject(WhatsappMsg::class)) {
            $hasMessages = true; // Mensagem encontrada, define como verdadeiro

            $img_user = '<img src="' . URL . '/resources/assets/img/icons/' . 
                        ($obDados->tipo_usuario == 'cliente' ? 'cliente' : 'suporte') . 
                        '.png" alt="avatar" class="rounded-circle d-flex align-self-start me-3 shadow-1-strong" width="60">';

            $chat .= '<li class="d-flex justify-content-between mb-4">';
            if ($obDados->tipo_usuario == 'cliente') {
                $chat .= $img_user;
            }
            $chat .= '<div class="card">
                        <div class="card-header d-flex justify-content-between p-3">
                            <p class="fw-bold mb-0">' . htmlspecialchars($dados['nome']) . '</p>
                            <p class="text-muted small mb-0"><i class="far fa-clock"></i> '
                            . htmlspecialchars($obDados->data_hora) .
                            '</p>
                        </div>
                        <div class="card-body">
                            <p class="mb-0">'
                                . htmlspecialchars($obDados->mensagem) .
                            '</p>
                        </div>
                      </div>';
            if ($obDados->tipo_usuario == 'suporte') {
                $chat .= $img_user;
            }
            $chat .= '</li>';
        }

        // Se não houver mensagens
        if (!$hasMessages) {
            $chat = 
            '<div class="alert alert-primary mb-5" role="alert">
                Envie uma mensagem para iniciar uma conversa!
            </div>';
        }

        return $chat;
    }

    public static function enviarMensagem($request){

      // Instantiate the WhatsAppCloudApi super class.
$whatsapp_cloud_api = new WhatsAppCloudApi([
    'from_phone_number_id' => '358763543980966',
    'access_token' => 'EAAOQWlkpBxIBO9SFBDZAiTztcjMyuUBzAlSlZAqAwtZBmrBA5C2ZCXlCaePo2XLWHWAiZC0mEtN8LMM13Wqx09jMsI14HX49OORjGDkut3RmvU4CToEhM7TYrTr0S428oLqA5KmgAPMMc5JLFRMgVURwKwW77rmBLhRwZAUG2SsESMYCZAkwwQX1KpQPp3UHyhsJa0Mwx71amGlmSyXi9RgtnZCzgDVDQSKwk44ZD',
]);

$whatsapp_cloud_api->sendTextMessage('5515998464457', 'olá, essa é a minha primeira mensagem enviada pela minha plataforma');
   
  
    }

}

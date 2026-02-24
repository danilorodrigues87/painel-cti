<?php

namespace App\Services\AI;
use App\Model\Db\Database;

class ChatContext {
    public $user_info;
    public $messages_count;
    private $whatsapp;
    private $messages;

    public function __construct($whatsapp) {
        $this->whatsapp = $whatsapp;
        $this->loadUserInfo();
        $this->loadMessages();
    }

    /**
     * Carrega informações do usuário
     */
    private function loadUserInfo() {
        $db = new Database('usuarios');
        $this->user_info = $db->select("whatsapp = '{$this->whatsapp}'")->fetchObject();
        
        // Se não encontrou usuário, cria um objeto padrão
        if (!$this->user_info) {
            $this->user_info = (object)[
                'nome' => 'Visitante',
                'whatsapp' => $this->whatsapp
            ];
        }
    }

    /**
     * Carrega mensagens do chat
     */
    private function loadMessages() {
        $db = new Database('chat');
        $this->messages = $db->select(
            "id_wm IN (SELECT id FROM whatsapp_messages WHERE whatsapp = '{$this->whatsapp}')",
            'data_hora DESC',
            '10'
        )->fetchAll(\PDO::FETCH_ASSOC);

        $this->messages_count = count($this->messages);
    }

    /**
     * Retorna últimas mensagens
     */
    public function getLastMessages($limit = 5) {
        return array_slice(array_reverse($this->messages), 0, $limit);
    }
} 
<?php

namespace App\Services\Chat;
use App\Model\Db\Database;
use App\Common\Logger;
use App\Model\Entity\User;

class ConversationManager {
    private $phone;
    private $db_chat;
    private $db_whatsapp;
    private $whatsapp_message_id;
    private $user;
    
    public function __construct($phone) {
        $this->phone = $phone;
        $this->db_chat = new Database('chat');
        $this->db_whatsapp = new Database('whatsapp_messages');
        $this->user = $this->getUser();
        $this->whatsapp_message_id = $this->getOrCreateWhatsappMessage();
    }

    private function getUser() {
        return (new User)::getUser("whatsapp = '{$this->phone}'")->fetchObject();
    }

    private function getOrCreateWhatsappMessage() {
        // Busca conversa ativa
        $message = $this->db_whatsapp->select(
            "whatsapp = '{$this->phone}' AND status != '2'",
            'id DESC',
            '1'
        )->fetchAll(\PDO::FETCH_ASSOC);

        if (empty($message)) {
            // Cria nova conversa
            $this->db_whatsapp->insert([
                'id_admin' => 1, // ajuste conforme necessário
                'nome' => $this->user ? $this->user->nome : 'Visitante',
                'whatsapp' => $this->phone,
                'id_chat' => 0,
                'status' => '0',
                'data_inicio' => date('Y-m-d H:i:s'),
                'data_criacao' => date('Y-m-d H:i:s')
            ]);
            
            $message = $this->db_whatsapp->select(
                "whatsapp = '{$this->phone}'",
                'id DESC',
                '1'
            )->fetchAll(\PDO::FETCH_ASSOC);
        }

        return $message[0]['id'] ?? null;
    }

    public function saveMessage($message, $type = 'assistant') {
        if (!$this->whatsapp_message_id) return false;

        return $this->db_chat->insert([
            'id_wm' => $this->whatsapp_message_id,
            'mensagem' => $message,
            'tipo_usuario' => $type,
            'id_usuario' => $this->user ? $this->user->id : null,
            'data_hora' => date('Y-m-d H:i:s'),
            'origem' => ($type == 'user') ? 'user' : 'assistant',
            'status' => '1'
        ]);
    }

    public function getRecentMessages($limit = 5) {
        if (!$this->whatsapp_message_id) return [];

        return $this->db_chat->select(
            "id_wm = {$this->whatsapp_message_id}",
            'data_hora ASC',
            $limit
        )->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function isNewConversation() {
        if (!$this->whatsapp_message_id) return true;

        $lastMessage = $this->db_chat->select(
            "id_wm = {$this->whatsapp_message_id}",
            'data_hora DESC',
            '1'
        )->fetchAll(\PDO::FETCH_ASSOC);

        if (empty($lastMessage)) return true;

        // Considera nova conversa se última mensagem > 30 minutos
        $thirtyMinutesAgo = date('Y-m-d H:i:s', time() - (30 * 60));
        return $lastMessage[0]['data_hora'] < $thirtyMinutesAgo;
    }

    public function getCurrentAgentType() {
        if (!$this->whatsapp_message_id) return 'virtual';

        $message = $this->db_whatsapp->select(
            "id = {$this->whatsapp_message_id}"
        )->fetchAll(\PDO::FETCH_ASSOC);

        // Assumindo que status '0' é virtual e '1' é humano
        return ($message[0]['status'] ?? '0') == '0' ? 'virtual' : 'human';
    }

    public function requestHumanTransfer() {
        if (!$this->whatsapp_message_id) return false;

        // Atualiza status da conversa para aguardando atendente
        return $this->db_whatsapp->update(
            "id = {$this->whatsapp_message_id}",
            [
                'status' => '1',
                'data_criacao' => date('Y-m-d H:i:s')
            ]
        );
    }
} 
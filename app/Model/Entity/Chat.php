<?php

namespace App\Model\Entity;
use App\Model\Db\Database;

class Chat {
    public $id;
    public $id_wm;
    public $mensagem;
    public $id_mensage;
    public $data_hora;
    public $tipo_usuario;
    public $id_usuario;

    /**
     * Salva mensagem no chat
     */
    public function cadastrar() {
        $db = new Database('chat');
        $this->id = $db->insert([
            'id_wm' => $this->id_wm,
            'mensagem' => $this->mensagem,
            'id_mensage' => $this->id_mensage,
            'data_hora' => date('Y-m-d H:i:s'),
            'tipo_usuario' => $this->tipo_usuario,
            'id_usuario' => $this->id_usuario
        ]);
        return true;
    }

    /**
     * Busca histórico do chat
     */
    public static function getHistorico($id_wm, $limit = null) {
        return (new Database('chat'))->select(
            'id_wm = ?', 
            'data_hora DESC', 
            $limit,
            '*',
            [$id_wm]
        );
    }
} 
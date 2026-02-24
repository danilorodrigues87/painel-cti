<?php

namespace App\Model\Entity\Estoque;

use App\Model\Db\Database;

class Stq_Fornecedores
{
    public $id;
    public $nome;
    public $cnpj;
    public $telefone;
    public $email;
    public $contato;
    public $status;
    public $created_at;
    public $updated_at;

    /* ==========================
     * BUSCAS
     * ========================== */

    public static function getById(int $id)
    {
        return (new Database('stq_fornecedores'))
            ->select('id = ' . $id)
            ->fetchObject(self::class);
    }

    public static function getAtivos()
    {
        return (new Database('stq_fornecedores'))
            ->select('status = 1');
    }

    /* ==========================
     * CADASTRO
     * ========================== */

    public function cadastrar(): bool
    {
        $this->id = (new Database('stq_fornecedores'))->insert([
            'nome'       => $this->nome,
            'cnpj'       => $this->cnpj,
            'telefone'   => $this->telefone,
            'email'      => $this->email,
            'contato'    => $this->contato,
            'status'     => 1,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        return true;
    }

    /* ==========================
     * ATUALIZAÇÃO
     * ========================== */

    public function atualizar(): bool
    {
        return (new Database('stq_fornecedores'))->update(
            'id = ' . $this->id,
            [
                'nome'       => $this->nome,
                'cnpj'       => $this->cnpj,
                'telefone'   => $this->telefone,
                'email'      => $this->email,
                'contato'    => $this->contato,
                'updated_at' => date('Y-m-d H:i:s')
            ]
        );
    }

    /* ==========================
     * STATUS
     * ========================== */

    public function inativar(): bool
    {
        return (new Database('stq_fornecedores'))->update(
            'id = ' . $this->id,
            ['status' => 0]
        );
    }

    public function ativar(): bool
    {
        return (new Database('stq_fornecedores'))->update(
            'id = ' . $this->id,
            ['status' => 1]
        );
    }
}

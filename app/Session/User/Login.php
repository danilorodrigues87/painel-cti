<?php

namespace App\Session\User;

use \App\Model\Entity\User as EntityUser;
use \App\Model\Entity\EscolasAssinantes;
use \App\Common\Helpers\ModuleGateHelper;

class Login {

    private static $sessionSynced = false;

    //INICIA A SESSÃO COM TEMPO ESTENDIDO
    private static function init() {
        if (session_status() != PHP_SESSION_ACTIVE) {
            // Define o tempo de vida da sessão (Ex: 1 dia = 86400 segundos)
            $tempo_sessao = 86400; 

            // Configura o cookie da sessão para durar no navegador do usuário
            session_set_cookie_params($tempo_sessao);

            // Informa ao servidor para não deletar o arquivo de sessão tão cedo
            ini_set('session.gc_maxlifetime', $tempo_sessao);

            $caminhoSessao = __DIR__.'/../../sessions'; // Ajuste o caminho conforme sua estrutura
            if(!is_dir($caminhoSessao)) mkdir($caminhoSessao, 0777, true);
            session_save_path($caminhoSessao);

            session_start();
        }
    }

    //CRIA O LOGIN DO USUÁRIO
    public static function login($obUser): bool {
        self::init();

        if (!isset($obUser->id, $obUser->email)) {
            return false;
        }

        //DEFINE A SESSÃO DO USUÁRIO
        $_SESSION['usuario-mvc-1'] = [
            'id'    => $obUser->id,
            'id_admin' => $obUser->id_admin,
            'nome'  => $obUser->nome,
            'email' => $obUser->email,
            'nivel' => $obUser->nivel,
            'termos_uso' => $obUser->termos_uso,
            'acesso' => json_decode($obUser->acesso, true) ?? []
        ];

        //SUCESSO
        return true;
    }

    /**
     * Atualiza dados da sessão com o banco (permissões, nível, status).
     * Executa no máximo uma vez por requisição.
     */
    public static function syncSessionFromDatabase(): bool {
        self::init();

        if (self::$sessionSynced) {
            return isset($_SESSION['usuario-mvc-1']);
        }
        self::$sessionSynced = true;

        if (!isset($_SESSION['usuario-mvc-1']['id'])) {
            return false;
        }

        $obUser = EntityUser::getUser(
            'id = '.(int)$_SESSION['usuario-mvc-1']['id'],
            null,
            1,
            'id, nome, email, nivel, id_admin, termos_uso, acesso, ativo'
        )->fetchObject(EntityUser::class);

        if (!$obUser) {
            unset($_SESSION['usuario-mvc-1']);
            return false;
        }

        if ($obUser->ativo !== 's') {
            unset($_SESSION['usuario-mvc-1']);
            return false;
        }

        if ($obUser->nivel === 'Cliente' || $obUser->nivel === 'Empresa') {
            unset($_SESSION['usuario-mvc-1']);
            return false;
        }

        $_SESSION['usuario-mvc-1']['nome']       = $obUser->nome;
        $_SESSION['usuario-mvc-1']['email']      = $obUser->email;
        $_SESSION['usuario-mvc-1']['nivel']      = $obUser->nivel;
        $_SESSION['usuario-mvc-1']['id_admin']   = $obUser->id_admin;
        $_SESSION['usuario-mvc-1']['termos_uso'] = (int)$obUser->termos_uso;
        $_SESSION['usuario-mvc-1']['acesso']     = json_decode($obUser->acesso, true) ?? [];

        return true;
    }

    // VERIFICA SE O USUÁRIO ESTÁ LOGADO E SE É UM ADMIN
    public static function isUserLogged() {
        // INICIA A SESSÃO
        self::init();

        // RETORNA A VERIFICAÇÃO
        return isset($_SESSION['usuario-mvc-1']);
    }

    public static function getUserLogedData(): ?array {
    self::init();

    if (!self::isUserLogged()) {
        return null;
    }

    if (!self::syncSessionFromDatabase()) {
        return null;
    }

    if (!isset($_SESSION['usuario-mvc-1']['id_admin'])) {
        return null;
    }

    $dadosEscola = EscolasAssinantes::getEscolaById(
        $_SESSION['usuario-mvc-1']['id_admin']
    );

    $usuario = $_SESSION['usuario-mvc-1'];
    $usuario['acesso'] = ModuleGateHelper::getModulosEfetivos(
        (int)$usuario['id_admin'],
        $usuario['acesso'] ?? []
    );

    return [
        'usuario' => $usuario,
        'escola'  => $dadosEscola ? (array) $dadosEscola : null
    ];
}


    //EXECUTA O LOGOUT
    public static function logout() {
        //INICIA A SESSÃO
        self::init();

        //DESLOGA O USUÁRIO
        unset($_SESSION['usuario-mvc-1']);

        //SUCESSO
        return true;
    }

}

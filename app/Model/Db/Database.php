<?php 

namespace App\Model\Db;
use \App\Common\Environment;
use \PDO;
use \PDOException;

Environment::load(__DIR__);

define('DB_HOST', getenv('DB_HOST'));
define('DB_NAME', getenv('DB_NAME'));
define('DB_USER', getenv('DB_USER'));
define('DB_PASS', getenv('DB_PASS'));

class Database{

    const HOST = DB_HOST;
    const NAME = DB_NAME;
    const USER = DB_USER;
    const PASS = DB_PASS;

    private $table;
    private $connection;

    public function __construct($table = null){
        $this->table = $table;
        $this->setConnection();
    }

     private function setConnection(){
    try{
        $this->connection = new PDO(
            'mysql:host='.self::HOST.';dbname='.self::NAME.';charset=utf8mb4',  // Incluindo o charset na string de conexão
            self::USER,
            self::PASS
        );
        $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC); // Configuração opcional para o modo de busca padrão
    }catch(PDOException $e){
        //SUBSTITUIR POR MENSAGEM AMIGÁVEL
        die('ERROR: '.$e->getMessage());
    }
}

    //EXECUTA AS QUERYS DENTRO DO BANCO DE DADOS
    public function execute($query, $params = []){
        try{
            $statement = $this->connection->prepare($query);
            $statement->execute($params);
            return $statement;
        }catch(PDOException $e){
            //SUBSTITUIR POR MENSAGEM AMIGAVEL
            die('ERROR: '.$e->getMessage());
        }
    }

    //INSERIR OS DADOS NO BANCO DE DADOS
    public function insert($values){
        //DADOS DA QUERY
        $fields = array_keys($values);
        $binds = array_pad([], count($fields), '?');

        //MONTA A QUERY DINAMICAMENTE
        $query = 'INSERT INTO '.$this->table.' ('.implode(',', $fields).') VALUES('.implode(',', $binds).')';
        
        //EXECUTA O INSERT
        $this->execute($query, array_values($values));

        //RETORNA O ID INSERIDO
        return $this->connection->lastInsertId();
    }

    // EXECUTA CONSULTAS NO BANCO
public function select($where = null, $order = null, $limit = null, $fields = '*', $innerJoin = null, $group = null) {
    // DADOS DA QUERY
    $where = is_string($where) && strlen($where) ? 'WHERE ' . $where : '';
    $order = is_string($order) && strlen($order) ? 'ORDER BY ' . $order : '';
    $limit = is_string($limit) && strlen($limit) ? 'LIMIT ' . $limit : '';
    $innerJoin = is_string($innerJoin) && strlen($innerJoin) ? $innerJoin : '';
    $group = is_string($group) && strlen($group) ? 'GROUP BY ' . $group : '';

    // MONTA A QUERY
    $query = 'SELECT ' . $fields . ' FROM ' . $this->table . ' ' . $innerJoin . ' ' . $where . ' ' . $group . ' ' . $order . ' ' . $limit;

    // EXECUTA A QUERY 
    return $this->execute($query);
}


    //ATUALIZA DADOS NO BANCO
    public function update($where, $values){
        //DADOS DA QUERY
        $fields = array_keys($values);

        //MONTA A QUERY
        $query = 'UPDATE '.$this->table.' SET '.implode('=?,', $fields).'=? WHERE '.$where;
        
        //EXECUTA O INSERT
        $this->execute($query, array_values($values));

        //RETORNA SUCESSO
        return true;
    }

    //EXCLUIR DADOS DO BANCO
    public function delete($where){
        //MONTA A QUERY
        $query = 'DELETE FROM '.$this->table.' WHERE '.$where;

        //EXECUTA A QUERY
        $this->execute($query);

        //RETORNA SUCESSO
        return true;
    }
}

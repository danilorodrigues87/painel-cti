<?php

namespace App\Common\Gateways\BancoInter;

class Historico {

    public static function Limpar(){

$directory = __DIR__ . '/boletos/';

$expirationDays = 0; // Número de dias para expiração
$currentDate = time();

// Verifica todos os arquivos no diretório
foreach (glob($directory . "*.pdf") as $file) {
    // Obtém a data de modificação do arquivo
    $fileModificationTime = filemtime($file);
    
    // Se o arquivo estiver mais velho do que o limite definido, exclui
    if (($currentDate - $fileModificationTime) > ($expirationDays * 86400)) {
        unlink($file); // Remove o arquivo
        echo "Arquivo excluído: " . $file . PHP_EOL;
    }
}

}


}
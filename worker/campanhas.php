#!/usr/bin/env php
<?php

/**
 * Worker de campanhas de e-mail.
 * Uso: php worker/campanhas.php [id_admin] [limite]
 * Cron Linux: * * * * * php /caminho/painel-cti/worker/campanhas.php
 */

require __DIR__.'/../includes/app.php';

use App\Common\Communication\CampanhaWorker;
use App\Model\Entity\Campanhas;

if (!Campanhas::tabelaExiste()) {
	fwrite(STDERR, "Tabelas de campanha não existem.\n");
	exit(1);
}

$idAdmin = isset($argv[1]) ? (int)$argv[1] : 0;
$limite  = isset($argv[2]) ? (int)$argv[2] : 15;

$resumo = CampanhaWorker::processar($idAdmin, $limite, true);

echo json_encode($resumo, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT).PHP_EOL;

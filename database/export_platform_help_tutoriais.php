<?php
/**
 * Gera database/platform_help_tutoriais.sql (tutoriais completos, vídeo vazio).
 *
 * Uso no XAMPP (navegador):
 *   http://localhost/pjt/painel-cti/database/export_platform_help_tutoriais.php
 *
 * Uso no terminal:
 *   php database/export_platform_help_tutoriais.php
 *
 * Pré-requisito: tabelas de database/platform_help.sql
 */
declare(strict_types=1);

$root = dirname(__DIR__);
$autoload = $root.'/vendor/autoload.php';
if (!is_file($autoload)) {
	fwrite(STDERR, "Autoload não encontrado. Rode composer install.\n");
	exit(1);
}
require $autoload;

use App\Common\Help\PlatformHelpSeed;

$sql = PlatformHelpSeed::gerarSql();
$dest = __DIR__.'/platform_help_tutoriais.sql';
file_put_contents($dest, $sql);

$bytes = strlen($sql);
$arts = count(PlatformHelpSeed::exportArtigos());
$cats = count(PlatformHelpSeed::exportCategorias());
$msg = "OK: {$dest}\nBytes: {$bytes}\nCategorias: {$cats}\nArtigos: {$arts}\n";

$isCli = (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg');
if ($isCli) {
	echo $msg;
	exit(0);
}

$download = isset($_GET['download']) || !isset($_GET['view']);
if ($download) {
	header('Content-Type: application/sql; charset=utf-8');
	header('Content-Disposition: attachment; filename="platform_help_tutoriais.sql"');
	header('Content-Length: '.$bytes);
	echo $sql;
	exit;
}

header('Content-Type: text/html; charset=utf-8');
echo '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="utf-8"><title>Export ajuda</title></head><body>';
echo '<pre>'.htmlspecialchars($msg, ENT_QUOTES, 'UTF-8').'</pre>';
echo '<p><a href="?download=1">Baixar platform_help_tutoriais.sql</a></p>';
echo '</body></html>';

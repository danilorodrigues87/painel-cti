<?php
// Test bootstrap + DB credentials when loading Database like web app
require __DIR__ . '/includes/app.php';
echo 'DB_USER env=' . var_export(getenv('DB_USER'), true) . PHP_EOL;
echo 'DB_USER const=' . var_export(defined('DB_USER') ? DB_USER : 'undef', true) . PHP_EOL;
try {
    $db = new \App\Model\Db\Database('horarios');
    echo "Database connection OK\n";
} catch (Throwable $e) {
    echo "ERR: ".$e->getMessage()."\n";
}

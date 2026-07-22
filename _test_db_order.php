<?php
// Test Database.php loaded WITHOUT root Environment::load (autoload only)
require __DIR__ . '/vendor/autoload.php';
// simulate wrong order: load Database class first
require __DIR__ . '/app/Model/Db/Database.php';
echo 'DB_USER=' . var_export(getenv('DB_USER'), true) . ' const=' . var_export(DB_USER, true) . PHP_EOL;

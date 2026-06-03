<?php

$dbHost = getenv('DB_HOST') ?: 'postgres';
$dbPort = getenv('DB_PORT') ?: '5432';
$dbUser = getenv('DB_USERNAME') ?: 'rebelo';
$dbPass = getenv('DB_PASSWORD') ?: 'rebelo_secret';
$testDb = getenv('DB_DATABASE') ?: 'rebelo_test';

$dsn = "pgsql:host={$dbHost};port={$dbPort};dbname=postgres";

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $pdo->exec("CREATE DATABASE \"{$testDb}\"");
} catch (PDOException $e) {
    // Database already exists or other error — proceed
}

$pdo = null;

require_once __DIR__.'/../vendor/autoload.php';

<?php
declare(strict_types=1);

/**
 * Termux-friendly PDO configuration.
 *
 * Environment variables are optional for local development:
 * SMART_WALLET_DB_HOST
 * SMART_WALLET_DB_PORT
 * SMART_WALLET_DB_NAME
 * SMART_WALLET_DB_USER
 * SMART_WALLET_DB_PASS
 */
$dbHost = getenv('SMART_WALLET_DB_HOST') ?: '127.0.0.1';
$dbPort = getenv('SMART_WALLET_DB_PORT') ?: '3306';
$dbName = getenv('SMART_WALLET_DB_NAME') ?: 'smart_wallet';
$dbUser = getenv('SMART_WALLET_DB_USER') ?: 'root';
$dbPass = getenv('SMART_WALLET_DB_PASS');

if ($dbPass === false) {
    $dbPass = '';
}

ini_set('display_errors', '0');
ini_set('log_errors', '1');

$dsn = sprintf(
    'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
    $dbHost,
    $dbPort,
    $dbName
);

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(503);
    exit('قاعدة البيانات غير متاحة حاليًا. شغّل MariaDB ثم أعد المحاولة.');
}

$pdo->exec("SET time_zone = '+00:00'");
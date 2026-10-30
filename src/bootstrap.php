<?php
declare(strict_types=1);

$racine = dirname(__DIR__);
$fichierConfig = $racine . '/config.php';
if (!is_file($fichierConfig)) {
    http_response_code(500);
    exit('Fichier config.php manquant : copiez config.example.php en config.php.');
}
$config = require $fichierConfig;

date_default_timezone_set($config['fuseau'] ?? 'Europe/Paris');

session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => !empty($_SERVER['HTTPS']),
]);
session_start();

require __DIR__ . '/helpers.php';
require __DIR__ . '/schema.php';
require __DIR__ . '/contrats.php';

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        global $config;
        $pdo = new PDO($config['db_dsn'], $config['db_user'] ?? null, $config['db_pass'] ?? null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
            $pdo->exec('PRAGMA foreign_keys = ON');
        }
        creer_tables($pdo);
    }
    return $pdo;
}

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

if (PHP_SAPI !== 'cli') {
    // Session gardée 30 jours, dans le dossier de l'application, pour ouvrir les liens
    // de l'e-mail du matin sans se reconnecter à chaque fois
    $dureeSession = 30 * 24 * 3600;
    $dossierSessions = $racine . '/data/sessions';
    if (!is_dir($dossierSessions)) {
        mkdir($dossierSessions, 0700, true);
    }
    session_save_path($dossierSessions);
    ini_set('session.gc_maxlifetime', (string) $dureeSession);
    session_set_cookie_params([
        'lifetime' => $dureeSession,
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']),
    ]);
    session_start();
}

if (is_file($racine . '/vendor/autoload.php')) {
    require $racine . '/vendor/autoload.php';
}
require __DIR__ . '/helpers.php';
require __DIR__ . '/schema.php';
require __DIR__ . '/contrats.php';
require __DIR__ . '/rappels.php';
require __DIR__ . '/tableur.php';

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

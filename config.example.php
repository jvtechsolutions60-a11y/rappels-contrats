<?php
// Copier ce fichier en config.php et l'adapter.
return [
    // Nom affiché en haut de l'application
    'nom_appli' => 'Rappels de contrats',

    // Base de données. Sur O2switch : MySQL créée depuis cPanel.
    'db_dsn'  => 'mysql:host=localhost;dbname=NOMBASE;charset=utf8mb4',
    'db_user' => 'UTILISATEUR',
    'db_pass' => 'MOTDEPASSE',

    // Pour tester sur un poste sans MySQL :
    // 'db_dsn' => 'sqlite:' . __DIR__ . '/data/rappels.sqlite',

    'fuseau' => 'Europe/Paris',
];

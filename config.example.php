<?php
// Copier ce fichier en config.php et l'adapter.
return [
    // Nom affiché en haut de l'application et comme expéditeur des e-mails
    'nom_appli' => 'Rappels de contrats',

    // Adresse de l'application, pour les liens dans l'e-mail du matin
    'url_appli' => 'https://rappels.mondomaine.fr',

    // Base de données. Sur O2switch : MySQL créée depuis cPanel.
    'db_dsn'  => 'mysql:host=localhost;dbname=NOMBASE;charset=utf8mb4',
    'db_user' => 'UTILISATEUR',
    'db_pass' => 'MOTDEPASSE',

    // Pour tester sur un poste sans MySQL :
    // 'db_dsn' => 'sqlite:' . __DIR__ . '/data/rappels.sqlite',

    // Boîte e-mail qui envoie les rappels. Sur O2switch : une boîte créée dans cPanel,
    // serveur sortant = nom du serveur indiqué dans cPanel, port 465.
    'smtp' => [
        'hote'         => 'mail.mondomaine.fr',
        'port'         => 465,
        'utilisateur'  => 'rappels@mondomaine.fr',
        'mot_de_passe' => 'MOTDEPASSE',
        'expediteur'   => 'rappels@mondomaine.fr',
    ],

    'fuseau' => 'Europe/Paris',
];

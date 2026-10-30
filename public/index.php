<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';
require dirname(__DIR__) . '/src/pages.php';

$page = (string) ($_GET['p'] ?? 'contrats');

// Premier lancement : création du compte
$aucunCompte = !db()->query('SELECT COUNT(*) FROM utilisateurs')->fetchColumn();
if ($aucunCompte) {
    page_premier_compte();
    exit;
}

if ($page === 'connexion') {
    page_connexion();
    exit;
}

if (empty($_SESSION['utilisateur_id'])) {
    rediriger('connexion');
}

if (est_post()) {
    verifier_csrf();
}

maj_expires();

$pages = [
    'deconnexion'    => 'page_deconnexion',
    'contrats'       => 'page_contrats',
    'contrat'        => 'page_contrat',
    'contrat_form'   => 'page_contrat_form',
    'contrat_action' => 'page_contrat_action',
    'clients'        => 'page_clients',
    'client'         => 'page_client',
    'client_form'    => 'page_client_form',
    'reglages'       => 'page_reglages',
    'type_form'      => 'page_type_form',
];

if (!isset($pages[$page])) {
    http_response_code(404);
    afficher('introuvable', ['titre' => 'Page introuvable']);
    exit;
}
$pages[$page]();

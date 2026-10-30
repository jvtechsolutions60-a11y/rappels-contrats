<?php
// Envoi de l'e-mail du matin. À lancer chaque jour à 7 h par une tâche cron :
//   php /chemin/vers/l-application/bin/rappels-du-matin.php
// Options :
//   --apercu  affiche l'e-mail du jour sans l'envoyer ni rien noter
//   --forcer  envoie même le week-end
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}
require dirname(__DIR__) . '/src/bootstrap.php';

if (in_array('--apercu', $argv, true)) {
    maj_expires();
    [$sujet, , $texte] = construire_email(calculer_rappels());
    echo "À : " . email_destinataire() . "\nSujet : $sujet\n\n$texte";
    exit(0);
}

$compteRendu = envoyer_rappels_du_jour(in_array('--forcer', $argv, true));
echo date('Y-m-d H:i') . ' ' . $compteRendu . "\n";
exit(str_starts_with($compteRendu, 'ÉCHEC') ? 1 : 0);

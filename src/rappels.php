<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;

const RUBRIQUES = [
    'a_envoyer'  => 'Devis à envoyer',
    'en_attente' => 'En attente de signature',
    'en_retard'  => 'En retard (date de fin dépassée, pas signé)',
];

function reglage(string $cle, ?string $defaut = null): ?string
{
    $st = db()->prepare('SELECT valeur FROM reglages WHERE cle = ?');
    $st->execute([$cle]);
    $valeur = $st->fetchColumn();
    return $valeur === false ? $defaut : $valeur;
}

function ecrire_reglage(string $cle, ?string $valeur): void
{
    db()->prepare('DELETE FROM reglages WHERE cle = ?')->execute([$cle]);
    db()->prepare('INSERT INTO reglages (cle, valeur) VALUES (?, ?)')->execute([$cle, $valeur]);
}

function email_destinataire(): string
{
    return reglage('email_rappels') ?: (string) db()->query('SELECT email FROM utilisateurs ORDER BY id LIMIT 1')->fetchColumn();
}

/**
 * Ce que l'e-mail du jour doit contenir, rangé par rubrique.
 * Un contrat en cours remonte le jour où il atteint un jalon (une seule fois par jalon) ;
 * s'il en a sauté plusieurs (saisie tardive, week-end), seul le plus récent compte.
 * Un contrat en retard remonte tous les jours.
 */
function calculer_rappels(): array
{
    $rubriques = array_fill_keys(array_keys(RUBRIQUES), []);
    $contrats = db()->query(
        "SELECT c.*, cl.nom AS client_nom, t.nom AS type_nom, t.jalons
         FROM contrats c
         JOIN clients cl ON cl.id = c.client_id
         JOIN types_contrat t ON t.id = c.type_id
         WHERE c.statut IN ('a_envoyer', 'envoye', 'expire')
         ORDER BY c.date_fin, cl.nom"
    )->fetchAll();
    $dejaEnvoye = db()->prepare('SELECT 1 FROM rappels_envoyes WHERE contrat_id = ? AND jalon = ? AND date_fin = ?');

    foreach ($contrats as $c) {
        $c['jours'] = jours_restants($c['date_fin']);
        if ($c['statut'] === 'expire' || $c['jours'] < 0) {
            $c['jalon'] = null;
            $rubriques['en_retard'][] = $c;
            continue;
        }
        $atteints = array_filter(array_map('intval', explode(',', $c['jalons'])), fn ($j) => $c['jours'] <= $j);
        if (!$atteints) {
            continue;
        }
        $c['jalon'] = min($atteints);
        $dejaEnvoye->execute([$c['id'], $c['jalon'], $c['date_fin']]);
        if ($dejaEnvoye->fetchColumn()) {
            continue;
        }
        $rubriques[$c['statut'] === 'envoye' ? 'en_attente' : 'a_envoyer'][] = $c;
    }
    return $rubriques;
}

function noter_rappels_envoyes(array $rubriques): void
{
    $ins = db()->prepare('INSERT INTO rappels_envoyes (contrat_id, jalon, date_fin, envoye_le) VALUES (?, ?, ?, ?)');
    foreach (['a_envoyer', 'en_attente'] as $rubrique) {
        foreach ($rubriques[$rubrique] as $c) {
            $ins->execute([$c['id'], $c['jalon'], $c['date_fin'], maintenant()]);
            journaliser((int) $c['id'], 'rappel', 'Rappel J-' . $c['jalon'] . ' envoyé : ' . mb_strtolower(RUBRIQUES[$rubrique]));
        }
    }
}

function resume_rappels(array $rubriques): string
{
    $morceaux = [];
    $n = count($rubriques['a_envoyer']);
    if ($n) {
        $morceaux[] = $n . ' devis à envoyer';
    }
    $n = count($rubriques['en_attente']);
    if ($n) {
        $morceaux[] = $n . ' en attente de signature';
    }
    $n = count($rubriques['en_retard']);
    if ($n) {
        $morceaux[] = $n . ' en retard';
    }
    return implode(', ', $morceaux);
}

function lien_appli(array $params = []): ?string
{
    global $config;
    $base = rtrim((string) ($config['url_appli'] ?? ''), '/');
    return $base === '' ? null : $base . '/' . ($params ? '?' . http_build_query($params) : '');
}

/** Renvoie [sujet, html, texte] */
function construire_email(array $rubriques, bool $test = false): array
{
    global $config;
    $resume = resume_rappels($rubriques);
    $prefixe = $test ? '[TEST] ' : '';
    $sujet = $prefixe . ($resume !== '' ? 'Rappels du jour : ' . $resume : 'Rappels du jour : rien à faire');

    $html = '<div style="font-family:Arial,sans-serif;font-size:15px;color:#1f2328;max-width:640px">';
    $texte = '';
    if ($test) {
        $html .= '<p style="background:#fff1d6;padding:8px">E-mail de test : voici ce que contiendrait l\'e-mail du matin aujourd\'hui. Rien n\'est noté comme envoyé.</p>';
        $texte .= "E-mail de test : voici ce que contiendrait l'e-mail du matin aujourd'hui.\n\n";
    }
    if ($resume === '') {
        $html .= '<p>Aucun contrat à traiter aujourd\'hui.</p>';
        $texte .= "Aucun contrat à traiter aujourd'hui.\n";
    } else {
        $html .= '<p><strong>' . e(ucfirst($resume)) . '.</strong></p>';
        $texte .= ucfirst($resume) . ".\n";
    }

    foreach (RUBRIQUES as $cle => $titre) {
        if (!$rubriques[$cle]) {
            continue;
        }
        $html .= '<h3 style="margin:20px 0 6px;font-size:16px">' . e($titre) . '</h3><ul style="padding-left:18px;margin:0">';
        $texte .= "\n" . mb_strtoupper($titre) . "\n";
        foreach ($rubriques[$cle] as $c) {
            $ligne = $c['client_nom'] . ' – ' . $c['produit'] . ' (' . $c['type_nom'] . ')';
            $quand = 'fin le ' . date_fr($c['date_fin']) . ', ' . texte_jours($c['jours']);
            if ($cle === 'en_attente' && $c['date_envoi_devis']) {
                $quand .= ', devis envoyé le ' . date_fr($c['date_envoi_devis']);
            }
            $lien = lien_appli(['p' => 'contrat', 'id' => $c['id']]);
            $html .= '<li style="margin-bottom:6px">'
                . ($lien ? '<a href="' . e($lien) . '">' . e($ligne) . '</a>' : e($ligne))
                . '<br><span style="color:#626a73">' . e($quand) . '</span></li>';
            $texte .= '- ' . $ligne . ' : ' . $quand . ($lien ? "\n  " . $lien : '') . "\n";
        }
        $html .= '</ul>';
    }

    $accueil = lien_appli();
    if ($accueil) {
        $html .= '<p style="margin-top:24px"><a href="' . e($accueil) . '">Ouvrir ' . e($config['nom_appli'] ?? 'les rappels') . '</a></p>';
        $texte .= "\nOuvrir l'application : " . $accueil . "\n";
    }
    $html .= '<p style="color:#626a73;font-size:13px">Pour arrêter les rappels d\'un contrat, ouvrez-le et cliquez « Signé » ou « Le client ne renouvelle pas ».</p></div>';
    return [$sujet, $html, $texte];
}

function envoyer_email(string $destinataire, string $sujet, string $html, string $texte): void
{
    global $config;
    if (!class_exists(PHPMailer::class)) {
        throw new RuntimeException('Bibliothèque d\'envoi absente : lancez « composer install » dans le dossier de l\'application.');
    }
    $smtp = $config['smtp'] ?? [];
    $mail = new PHPMailer(true);
    $mail->CharSet = PHPMailer::CHARSET_UTF8;
    if (!empty($smtp['hote'])) {
        $mail->isSMTP();
        $mail->Host = $smtp['hote'];
        $mail->Port = (int) ($smtp['port'] ?? 465);
        // 465 = SSL (O2switch), 587 = STARTTLS ; 'securite' => '' uniquement pour un serveur de test local
        $mail->SMTPSecure = $smtp['securite'] ?? ($mail->Port === 587 ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS);
        $mail->SMTPAuth = ($smtp['utilisateur'] ?? '') !== '';
        $mail->Username = $smtp['utilisateur'] ?? '';
        $mail->Password = $smtp['mot_de_passe'] ?? '';
    }
    $mail->setFrom($smtp['expediteur'] ?? ($smtp['utilisateur'] ?? 'rappels@localhost'), $config['nom_appli'] ?? 'Rappels de contrats');
    $mail->addAddress($destinataire);
    $mail->Subject = $sujet;
    $mail->isHTML(true);
    $mail->Body = $html;
    $mail->AltBody = $texte;
    $mail->send();
}

/**
 * L'envoi du matin. Renvoie un compte rendu d'une ligne.
 * $forcer : envoie même le week-end. Les rappels ne sont notés qu'une fois l'e-mail parti.
 */
function envoyer_rappels_du_jour(bool $forcer = false): string
{
    if (!$forcer && (int) date('N') > 5) {
        return 'Week-end : pas d\'envoi, les rappels partiront lundi.';
    }
    maj_expires();
    $rubriques = calculer_rappels();
    if (resume_rappels($rubriques) === '') {
        $compteRendu = 'Rien à signaler, pas d\'e-mail envoyé.';
    } else {
        [$sujet, $html, $texte] = construire_email($rubriques);
        try {
            envoyer_email(email_destinataire(), $sujet, $html, $texte);
        } catch (Throwable $e) {
            $compteRendu = 'ÉCHEC de l\'envoi : ' . $e->getMessage();
            ecrire_reglage('dernier_envoi', date('d/m/Y H:i') . ' – ' . $compteRendu);
            return $compteRendu;
        }
        noter_rappels_envoyes($rubriques);
        $compteRendu = 'E-mail envoyé à ' . email_destinataire() . ' : ' . resume_rappels($rubriques) . '.';
    }
    ecrire_reglage('dernier_envoi', date('d/m/Y H:i') . ' – ' . $compteRendu);
    return $compteRendu;
}

<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;

// Alertes avant la date limite de préavis : à 30 jours, 7 jours et le jour même
const JALONS_PREAVIS = [30, 7, 0];

const RUBRIQUES = [
    'preavis'    => 'Date limite de préavis proche',
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

    // Préavis : noté dans rappels_envoyes avec un jalon négatif (-1 - jours) pour ne pas le confondre
    foreach ($contrats as $c) {
        $limite = date_preavis($c);
        if (!$limite || $c['statut'] === 'expire') {
            continue;
        }
        $c['jours'] = jours_restants($c['date_fin']);
        $c['jours_preavis'] = jours_restants($limite);
        $c['date_preavis'] = $limite;
        if ($c['jours_preavis'] < 0) {
            continue;
        }
        $atteints = array_filter(JALONS_PREAVIS, fn ($j) => $c['jours_preavis'] <= $j);
        if (!$atteints) {
            continue;
        }
        $c['jalon'] = -1 - min($atteints);
        $dejaEnvoye->execute([$c['id'], $c['jalon'], $c['date_fin']]);
        if (!$dejaEnvoye->fetchColumn()) {
            $rubriques['preavis'][] = $c;
        }
    }
    return $rubriques;
}

function noter_rappels_envoyes(array $rubriques): void
{
    $ins = db()->prepare('INSERT INTO rappels_envoyes (contrat_id, jalon, date_fin, envoye_le) VALUES (?, ?, ?, ?)');
    foreach (['a_envoyer', 'en_attente', 'preavis'] as $rubrique) {
        foreach ($rubriques[$rubrique] as $c) {
            $ins->execute([$c['id'], $c['jalon'], $c['date_fin'], maintenant()]);
            $j = $c['jalon'] < 0 ? -1 - $c['jalon'] : $c['jalon'];
            journaliser((int) $c['id'], 'rappel', 'Rappel J-' . $j . ' envoyé : ' . mb_strtolower(RUBRIQUES[$rubrique]));
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
    $n = count($rubriques['preavis']);
    if ($n) {
        $morceaux[] = $n . ' préavis à surveiller';
    }
    return implode(', ', $morceaux);
}

// ---------- Boutons d'action dans l'e-mail (sans se connecter) ----------

const DUREE_LIEN_ACTION = 30; // jours de validité d'un bouton reçu par e-mail

function cle_secrete(): string
{
    $cle = reglage('cle_secrete');
    if (!$cle) {
        $cle = bin2hex(random_bytes(32));
        ecrire_reglage('cle_secrete', $cle);
    }
    return $cle;
}

// Le jeton lie le contrat, l'action, la date de fin et le jour d'émission : il ne sert qu'à ça
function jeton_action(int $id, string $action, string $dateFin, string $emis): string
{
    return hash_hmac('sha256', "$id|$action|$dateFin|$emis", cle_secrete());
}

function lien_action(array $c, string $action): ?string
{
    $emis = aujourdhui();
    return lien_appli(['p' => 'action_mail', 'id' => $c['id'], 'a' => $action, 'e' => $emis,
                       't' => jeton_action((int) $c['id'], $action, $c['date_fin'], $emis)]);
}

// Vérifie un lien d'action reçu par e-mail ; renvoie le contrat ou un message d'erreur
function verifier_lien_action(array $p): array|string
{
    $id = (int) ($p['id'] ?? 0);
    $action = (string) ($p['a'] ?? '');
    $emis = (string) ($p['e'] ?? '');
    $c = trouver_contrat($id);
    if (!$c || !in_array($action, ['envoye', 'signe'], true) || !lire_date($emis)) {
        return 'Ce lien n\'est pas valable.';
    }
    if (!hash_equals(jeton_action($id, $action, $c['date_fin'], $emis), (string) ($p['t'] ?? ''))) {
        return 'Ce lien n\'est plus valable (le contrat a peut-être été modifié depuis).';
    }
    if (jours_restants($emis) < -DUREE_LIEN_ACTION) {
        return 'Ce lien a expiré. Ouvrez le contrat dans l\'application.';
    }
    return $c;
}

function boutons_email(array $c): array
{
    $boutons = [];
    if ($c['statut'] === 'a_envoyer') {
        $boutons['Devis envoyé'] = lien_action($c, 'envoye');
    }
    $boutons['Signé'] = lien_action($c, 'signe');
    return array_filter($boutons);
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
            $quand = texte_compteur($c['jours']) . ' : fin le ' . date_fr($c['date_fin']) . ', ' . texte_jours($c['jours']);
            if ($cle === 'preavis') {
                $quand = 'Résilier avant le ' . date_fr($c['date_preavis']) . ' (' . texte_jours($c['jours_preavis'])
                    . ') si le client ne veut pas reconduire. Fin le ' . date_fr($c['date_fin']) . '.';
            }
            if ($cle === 'en_attente' && $c['date_envoi_devis']) {
                $quand .= ', devis envoyé le ' . date_fr($c['date_envoi_devis']);
            }
            $lien = lien_appli(['p' => 'contrat', 'id' => $c['id']]);
            $boutons = $cle === 'preavis' ? [] : boutons_email($c);
            $htmlBoutons = '';
            foreach ($boutons as $libelle => $url) {
                $couleur = $libelle === 'Signé' ? '#1a7f45' : '#1f5fbf';
                $htmlBoutons .= '<a href="' . e($url) . '" style="display:inline-block;margin:4px 6px 0 0;padding:5px 12px;border-radius:5px;background:'
                    . $couleur . ';color:#fff;text-decoration:none;font-size:14px">' . e($libelle) . '</a>';
            }
            $html .= '<li style="margin-bottom:12px">'
                . ($lien ? '<a href="' . e($lien) . '">' . e($ligne) . '</a>' : e($ligne))
                . '<br><span style="color:#626a73">' . e($quand) . '</span>'
                . ($htmlBoutons ? '<br>' . $htmlBoutons : '') . '</li>';
            $texte .= '- ' . $ligne . ' : ' . $quand . ($lien ? "\n  " . $lien : '') . "\n";
            foreach ($boutons as $libelle => $url) {
                $texte .= '  ' . $libelle . ' : ' . $url . "\n";
            }
        }
        $html .= '</ul>';
    }

    $accueil = lien_appli();
    if ($accueil) {
        $html .= '<p style="margin-top:24px"><a href="' . e($accueil) . '">Ouvrir ' . e($config['nom_appli'] ?? 'les rappels') . '</a></p>';
        $texte .= "\nOuvrir l'application : " . $accueil . "\n";
    }
    $html .= '<p style="color:#626a73;font-size:13px">Les boutons « Devis envoyé » et « Signé » marchent sans vous connecter, pendant 30 jours. Pour un client qui ne renouvelle pas, ouvrez le contrat.</p></div>';
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

<?php
declare(strict_types=1);

// ---------- Connexion ----------

function page_premier_compte(): void
{
    $erreur = null;
    if (est_post()) {
        verifier_csrf();
        $email = trim((string) ($_POST['email'] ?? ''));
        $mdp = (string) ($_POST['mot_de_passe'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erreur = 'Adresse e-mail invalide.';
        } elseif (mb_strlen($mdp) < 10) {
            $erreur = 'Le mot de passe doit faire au moins 10 caractères.';
        } else {
            db()->prepare('INSERT INTO utilisateurs (email, mot_de_passe, cree_le) VALUES (?, ?, ?)')
                ->execute([$email, password_hash($mdp, PASSWORD_DEFAULT), maintenant()]);
            session_regenerate_id(true);
            $_SESSION['utilisateur_id'] = (int) db()->lastInsertId();
            flash('Compte créé. Vous pouvez saisir votre premier contrat.');
            rediriger('contrat_form');
        }
    }
    afficher('premier_compte', ['titre' => 'Création du compte', 'erreur' => $erreur, 'sansMenu' => true]);
}

function page_connexion(): void
{
    $erreur = null;
    if (est_post()) {
        verifier_csrf();
        $st = db()->prepare('SELECT * FROM utilisateurs WHERE email = ?');
        $st->execute([trim((string) ($_POST['email'] ?? ''))]);
        $u = $st->fetch();
        if ($u && password_verify((string) ($_POST['mot_de_passe'] ?? ''), $u['mot_de_passe'])) {
            session_regenerate_id(true);
            $_SESSION['utilisateur_id'] = (int) $u['id'];
            rediriger('contrats');
        }
        sleep(1); // freine les essais de mots de passe en série
        $erreur = 'E-mail ou mot de passe incorrect.';
    }
    afficher('connexion', ['titre' => 'Connexion', 'erreur' => $erreur, 'sansMenu' => true]);
}

function page_deconnexion(): void
{
    $_SESSION = [];
    session_destroy();
    rediriger('connexion');
}

// ---------- Contrats ----------

function page_contrats(): void
{
    $q = trim((string) ($_GET['q'] ?? ''));
    $statut = (string) ($_GET['statut'] ?? '');
    $typeId = (int) ($_GET['type'] ?? 0);

    $where = [];
    $params = [];
    if ($q !== '') {
        $where[] = '(LOWER(cl.nom) LIKE LOWER(?) OR LOWER(c.produit) LIKE LOWER(?))';
        $params[] = "%$q%";
        $params[] = "%$q%";
    }
    if ($statut === 'ouverts') {
        $where[] = "c.statut IN ('a_envoyer', 'envoye', 'expire')";
    } elseif (isset(STATUTS[$statut])) {
        $where[] = 'c.statut = ?';
        $params[] = $statut;
    }
    if ($typeId) {
        $where[] = 'c.type_id = ?';
        $params[] = $typeId;
    }
    $sql = "SELECT c.*, cl.nom AS client_nom, t.nom AS type_nom
            FROM contrats c
            JOIN clients cl ON cl.id = c.client_id
            JOIN types_contrat t ON t.id = c.type_id"
        . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
        . " ORDER BY CASE WHEN c.statut IN ('a_envoyer', 'envoye', 'expire') THEN 0 ELSE 1 END, c.date_fin";
    $st = db()->prepare($sql);
    $st->execute($params);

    afficher('contrats', [
        'titre'    => 'Contrats',
        'contrats' => $st->fetchAll(),
        'types'    => types_contrat(),
        'q'        => $q,
        'statut'   => $statut,
        'typeId'   => $typeId,
    ]);
}

function page_contrat(): void
{
    $contrat = trouver_contrat((int) ($_GET['id'] ?? 0));
    if (!$contrat) {
        flash('Ce contrat n\'existe pas ou a été supprimé.');
        rediriger('contrats');
    }
    $st = db()->prepare('SELECT * FROM historique WHERE contrat_id = ? ORDER BY cree_le DESC, id DESC');
    $st->execute([$contrat['id']]);
    afficher('contrat', [
        'titre'      => $contrat['client_nom'] . ' – ' . $contrat['produit'],
        'contrat'    => $contrat,
        'historique' => $st->fetchAll(),
    ]);
}

function page_contrat_form(): void
{
    $id = (int) ($_GET['id'] ?? 0);
    $existant = $id ? trouver_contrat($id) : null;
    if ($id && !$existant) {
        rediriger('contrats');
    }
    $types = types_contrat();
    $valeurs = $existant ?: [
        'client_nom' => (string) ($_GET['client'] ?? ''),
        'type_id'    => $types[0]['id'] ?? 0,
        'produit'    => $types[0]['produit_defaut'] ?? '',
        'date_fin'   => '',
        'montant'    => null,
        'note'       => '',
        'statut'     => 'a_envoyer',
    ];
    $erreurs = [];

    if (est_post()) {
        $valeurs = [
            'client_nom' => trim((string) ($_POST['client'] ?? '')),
            'type_id'    => (int) ($_POST['type_id'] ?? 0),
            'produit'    => trim((string) ($_POST['produit'] ?? '')),
            'date_fin'   => (string) ($_POST['date_fin'] ?? ''),
            'montant'    => (string) ($_POST['montant'] ?? ''),
            'note'       => trim((string) ($_POST['note'] ?? '')),
            'statut'     => (string) ($_POST['statut'] ?? ($existant['statut'] ?? 'a_envoyer')),
        ];
        $dateFin = lire_date($valeurs['date_fin']);
        $montant = lire_montant($valeurs['montant']);
        $typeIds = array_column($types, 'id');

        if ($valeurs['client_nom'] === '') {
            $erreurs['client'] = 'Indiquez le client.';
        }
        if (!in_array($valeurs['type_id'], array_map('intval', $typeIds), true)) {
            $erreurs['type_id'] = 'Choisissez un type de contrat.';
        }
        if ($valeurs['produit'] === '') {
            $erreurs['produit'] = 'Indiquez le produit.';
        }
        if (!$dateFin) {
            $erreurs['date_fin'] = 'Date de fin invalide (format JJ/MM/AAAA).';
        }
        if ($valeurs['montant'] !== '' && $montant === null) {
            $erreurs['montant'] = 'Montant invalide.';
        }
        if (!isset(STATUTS[$valeurs['statut']])) {
            $erreurs['statut'] = 'Statut inconnu.';
        }

        if (!$erreurs) {
            $clientId = trouver_ou_creer_client($valeurs['client_nom']);
            $donnees = [
                'client_id' => $clientId,
                'type_id'   => $valeurs['type_id'],
                'produit'   => $valeurs['produit'],
                'date_fin'  => $dateFin,
                'montant'   => $montant,
                'note'      => $valeurs['note'] !== '' ? $valeurs['note'] : null,
            ];
            if ($existant) {
                modifier_contrat($existant, $donnees);
                changer_statut($existant['id'], $valeurs['statut']);
                flash('Contrat enregistré.');
                rediriger('contrat', ['id' => $existant['id']]);
            }
            $nouveauId = enregistrer_contrat($donnees);
            if (isset($_POST['encore'])) {
                flash('Contrat enregistré. Saisissez le suivant.');
                rediriger('contrat_form');
            }
            flash('Contrat enregistré.');
            rediriger('contrat', ['id' => $nouveauId]);
        }
    }

    $clients = db()->query('SELECT nom FROM clients ORDER BY nom')->fetchAll(PDO::FETCH_COLUMN);
    afficher('contrat_form', [
        'titre'    => $existant ? 'Modifier le contrat' : 'Nouveau contrat',
        'existant' => $existant,
        'valeurs'  => $valeurs,
        'erreurs'  => $erreurs,
        'types'    => $types,
        'clients'  => $clients,
    ]);
}

function modifier_contrat(array $avant, array $d): void
{
    $libelles = ['client_id' => 'client', 'type_id' => 'type', 'produit' => 'produit',
                 'date_fin' => 'date de fin', 'montant' => 'montant', 'note' => 'note'];
    $changes = [];
    $normaliser = fn ($champ, $v) => $champ === 'montant' && $v !== null ? number_format((float) $v, 2, '.', '') : (string) $v;
    foreach ($libelles as $champ => $libelle) {
        if ($normaliser($champ, $avant[$champ]) !== $normaliser($champ, $d[$champ])) {
            $changes[] = $libelle;
        }
    }
    if (!$changes) {
        return;
    }
    db()->prepare(
        'UPDATE contrats SET client_id = ?, type_id = ?, produit = ?, date_fin = ?, montant = ?, note = ?, modifie_le = ?
         WHERE id = ?'
    )->execute([$d['client_id'], $d['type_id'], $d['produit'], $d['date_fin'], $d['montant'], $d['note'], maintenant(), $avant['id']]);
    $detail = 'Modifié : ' . implode(', ', $changes);
    if (in_array('date de fin', $changes, true)) {
        $detail .= ' (' . date_fr($avant['date_fin']) . ' → ' . date_fr($d['date_fin']) . ')';
    }
    journaliser((int) $avant['id'], 'modification', $detail);
}

function page_contrat_action(): void
{
    if (!est_post()) {
        rediriger('contrats');
    }
    $id = (int) ($_POST['id'] ?? 0);
    $action = (string) ($_POST['action'] ?? '');
    $retour = (string) ($_POST['retour'] ?? 'contrat');
    $contrat = trouver_contrat($id);
    if (!$contrat) {
        rediriger('contrats');
    }

    switch ($action) {
        case 'envoye':
            changer_statut($id, 'envoye');
            flash('Devis noté comme envoyé : ' . $contrat['client_nom'] . ' – ' . $contrat['produit'] . '.');
            break;
        case 'signe':
            changer_statut($id, 'signe');
            flash('Contrat signé, les rappels sont arrêtés.');
            rediriger('contrat', ['id' => $id]); // la fiche propose de créer le contrat suivant
        case 'non_renouvele':
            changer_statut($id, 'non_renouvele');
            flash('Contrat noté comme non renouvelé, les rappels sont arrêtés.');
            break;
        case 'rouvrir':
            changer_statut($id, $contrat['date_fin'] < aujourdhui() ? 'expire' : ($contrat['date_envoi_devis'] ? 'envoye' : 'a_envoyer'));
            flash('Contrat rouvert, les rappels reprennent.');
            break;
        case 'suivant':
            $nouveauId = creer_contrat_suivant($id);
            flash('Contrat de la période suivante créé. Vérifiez la date de fin et le montant.');
            rediriger('contrat', ['id' => $nouveauId]);
        case 'supprimer':
            db()->prepare('UPDATE contrats SET contrat_suivant_id = NULL WHERE contrat_suivant_id = ?')->execute([$id]);
            db()->prepare('DELETE FROM historique WHERE contrat_id = ?')->execute([$id]);
            db()->prepare('DELETE FROM contrats WHERE id = ?')->execute([$id]);
            flash('Contrat supprimé.');
            rediriger('contrats');
    }
    $retour === 'contrats' ? rediriger('contrats') : rediriger('contrat', ['id' => $id]);
}

// ---------- Clients ----------

function page_clients(): void
{
    $clients = db()->query(
        "SELECT cl.*, COUNT(c.id) AS nb_contrats,
                SUM(CASE WHEN c.statut IN ('a_envoyer', 'envoye', 'expire') THEN 1 ELSE 0 END) AS nb_ouverts
         FROM clients cl LEFT JOIN contrats c ON c.client_id = cl.id
         GROUP BY cl.id, cl.nom, cl.contact, cl.email, cl.telephone, cl.note, cl.cree_le
         ORDER BY cl.nom"
    )->fetchAll();
    afficher('clients', ['titre' => 'Clients', 'clients' => $clients]);
}

function page_client(): void
{
    $st = db()->prepare('SELECT * FROM clients WHERE id = ?');
    $st->execute([(int) ($_GET['id'] ?? 0)]);
    $client = $st->fetch();
    if (!$client) {
        rediriger('clients');
    }
    $st = db()->prepare(
        'SELECT c.*, t.nom AS type_nom FROM contrats c JOIN types_contrat t ON t.id = c.type_id
         WHERE c.client_id = ? ORDER BY c.date_fin DESC'
    );
    $st->execute([$client['id']]);
    afficher('client', ['titre' => $client['nom'], 'client' => $client, 'contrats' => $st->fetchAll()]);
}

function page_client_form(): void
{
    $id = (int) ($_GET['id'] ?? 0);
    $client = ['nom' => '', 'contact' => '', 'email' => '', 'telephone' => '', 'note' => ''];
    if ($id) {
        $st = db()->prepare('SELECT * FROM clients WHERE id = ?');
        $st->execute([$id]);
        $client = $st->fetch() ?: rediriger('clients');
    }
    $erreurs = [];
    if (est_post()) {
        foreach (['nom', 'contact', 'email', 'telephone', 'note'] as $champ) {
            $client[$champ] = trim((string) ($_POST[$champ] ?? ''));
        }
        if ($client['nom'] === '') {
            $erreurs['nom'] = 'Indiquez le nom du client.';
        }
        if ($client['email'] !== '' && !filter_var($client['email'], FILTER_VALIDATE_EMAIL)) {
            $erreurs['email'] = 'Adresse e-mail invalide.';
        }
        $st = db()->prepare('SELECT id FROM clients WHERE LOWER(nom) = LOWER(?) AND id <> ?');
        $st->execute([$client['nom'], $id]);
        if ($st->fetchColumn()) {
            $erreurs['nom'] = 'Un client porte déjà ce nom.';
        }
        if (!$erreurs) {
            $vals = array_map(fn ($v) => $v === '' ? null : $v, [$client['nom'], $client['contact'], $client['email'], $client['telephone'], $client['note']]);
            if ($id) {
                db()->prepare('UPDATE clients SET nom = ?, contact = ?, email = ?, telephone = ?, note = ? WHERE id = ?')
                    ->execute([...$vals, $id]);
            } else {
                db()->prepare('INSERT INTO clients (nom, contact, email, telephone, note, cree_le) VALUES (?, ?, ?, ?, ?, ?)')
                    ->execute([...$vals, maintenant()]);
                $id = (int) db()->lastInsertId();
            }
            flash('Client enregistré.');
            rediriger('client', ['id' => $id]);
        }
    }
    afficher('client_form', ['titre' => $id ? 'Modifier le client' : 'Nouveau client', 'client' => $client, 'id' => $id, 'erreurs' => $erreurs]);
}

// ---------- Réglages ----------

function page_reglages(): void
{
    $erreur = null;
    if (est_post() && ($_POST['action'] ?? '') === 'mot_de_passe') {
        $st = db()->prepare('SELECT * FROM utilisateurs WHERE id = ?');
        $st->execute([$_SESSION['utilisateur_id']]);
        $u = $st->fetch();
        $nouveau = (string) ($_POST['nouveau'] ?? '');
        if (!$u || !password_verify((string) ($_POST['actuel'] ?? ''), $u['mot_de_passe'])) {
            $erreur = 'Mot de passe actuel incorrect.';
        } elseif (mb_strlen($nouveau) < 10) {
            $erreur = 'Le nouveau mot de passe doit faire au moins 10 caractères.';
        } else {
            db()->prepare('UPDATE utilisateurs SET mot_de_passe = ? WHERE id = ?')
                ->execute([password_hash($nouveau, PASSWORD_DEFAULT), $u['id']]);
            flash('Mot de passe modifié.');
            rediriger('reglages');
        }
    }
    $types = db()->query(
        'SELECT t.*, (SELECT COUNT(*) FROM contrats c WHERE c.type_id = t.id) AS nb FROM types_contrat t ORDER BY position, nom'
    )->fetchAll();
    afficher('reglages', ['titre' => 'Réglages', 'types' => $types, 'erreur' => $erreur]);
}

function page_type_form(): void
{
    $id = (int) ($_GET['id'] ?? 0);
    $type = ['nom' => '', 'jalons' => '90,30,0', 'duree_mois' => 12, 'produit_defaut' => ''];
    if ($id) {
        $st = db()->prepare('SELECT * FROM types_contrat WHERE id = ?');
        $st->execute([$id]);
        $type = $st->fetch() ?: rediriger('reglages');
    }
    $erreurs = [];
    if (est_post()) {
        $type['nom'] = trim((string) ($_POST['nom'] ?? ''));
        $type['produit_defaut'] = trim((string) ($_POST['produit_defaut'] ?? ''));
        $type['duree_mois'] = (int) ($_POST['duree_mois'] ?? 12);
        $jalons = lire_jalons((string) ($_POST['jalons'] ?? ''));
        $type['jalons'] = $jalons ?? (string) ($_POST['jalons'] ?? '');
        if ($type['nom'] === '') {
            $erreurs['nom'] = 'Indiquez un nom.';
        }
        if ($jalons === null) {
            $erreurs['jalons'] = 'Indiquez des nombres de jours séparés par des virgules, par exemple 90, 30, 0.';
        }
        if ($type['duree_mois'] < 1 || $type['duree_mois'] > 120) {
            $erreurs['duree_mois'] = 'Durée entre 1 et 120 mois.';
        }
        if (!$erreurs) {
            $vals = [$type['nom'], $jalons, $type['duree_mois'], $type['produit_defaut'] ?: null];
            if ($id) {
                db()->prepare('UPDATE types_contrat SET nom = ?, jalons = ?, duree_mois = ?, produit_defaut = ? WHERE id = ?')
                    ->execute([...$vals, $id]);
            } else {
                $pos = (int) db()->query('SELECT COALESCE(MAX(position), 0) + 1 FROM types_contrat')->fetchColumn();
                db()->prepare('INSERT INTO types_contrat (nom, jalons, duree_mois, produit_defaut, position) VALUES (?, ?, ?, ?, ?)')
                    ->execute([...$vals, $pos]);
            }
            flash('Type de contrat enregistré.');
            rediriger('reglages');
        }
    }
    afficher('type_form', ['titre' => $id ? 'Modifier le type de contrat' : 'Nouveau type de contrat', 'type' => $type, 'id' => $id, 'erreurs' => $erreurs]);
}

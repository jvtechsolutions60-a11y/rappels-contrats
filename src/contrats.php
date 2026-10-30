<?php
declare(strict_types=1);

const STATUTS = [
    'a_envoyer'     => 'À envoyer',
    'envoye'        => 'Envoyé',
    'signe'         => 'Signé',
    'non_renouvele' => 'Non renouvelé',
    'expire'        => 'Expiré',
];

// Statuts pour lesquels les rappels continuent
const STATUTS_OUVERTS = ['a_envoyer', 'envoye', 'expire'];

function libelle_statut(string $statut): string
{
    return STATUTS[$statut] ?? $statut;
}

function journaliser(int $contratId, string $evenement, ?string $detail = null): void
{
    db()->prepare('INSERT INTO historique (contrat_id, cree_le, evenement, detail) VALUES (?, ?, ?, ?)')
        ->execute([$contratId, maintenant(), $evenement, $detail]);
}

function trouver_contrat(int $id): ?array
{
    $st = db()->prepare(
        'SELECT c.*, cl.nom AS client_nom, t.nom AS type_nom, t.duree_mois, t.jalons
         FROM contrats c
         JOIN clients cl ON cl.id = c.client_id
         JOIN types_contrat t ON t.id = c.type_id
         WHERE c.id = ?'
    );
    $st->execute([$id]);
    return $st->fetch() ?: null;
}

function changer_statut(int $id, string $nouveau): void
{
    $contrat = trouver_contrat($id);
    if (!$contrat || !isset(STATUTS[$nouveau]) || $contrat['statut'] === $nouveau) {
        return;
    }
    $champs = ['statut = ?', 'modifie_le = ?'];
    $valeurs = [$nouveau, maintenant()];
    if ($nouveau === 'envoye' && !$contrat['date_envoi_devis']) {
        $champs[] = 'date_envoi_devis = ?';
        $valeurs[] = aujourdhui();
    }
    if ($nouveau === 'signe') {
        $champs[] = 'date_signature = ?';
        $valeurs[] = aujourdhui();
    }
    $valeurs[] = $id;
    db()->prepare('UPDATE contrats SET ' . implode(', ', $champs) . ' WHERE id = ?')->execute($valeurs);
    journaliser($id, 'statut', libelle_statut($contrat['statut']) . ' → ' . libelle_statut($nouveau));
}

// Passe en « Expiré » les contrats dont la date de fin est dépassée sans signature
function maj_expires(): void
{
    $st = db()->prepare("SELECT id FROM contrats WHERE statut IN ('a_envoyer', 'envoye') AND date_fin < ?");
    $st->execute([aujourdhui()]);
    foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $id) {
        changer_statut((int) $id, 'expire');
    }
}

function ajouter_mois(string $date, int $mois): string
{
    $d = new DateTime($date);
    $jour = (int) $d->format('d');
    $d->modify('first day of this month')->modify("+$mois months");
    $dernier = (int) $d->format('t');
    $d->setDate((int) $d->format('Y'), (int) $d->format('m'), min($jour, $dernier));
    return $d->format('Y-m-d');
}

// Crée le contrat de la période suivante (même client, même produit) et renvoie son id
function creer_contrat_suivant(int $id): ?int
{
    $c = trouver_contrat($id);
    if (!$c || $c['contrat_suivant_id']) {
        return $c['contrat_suivant_id'] ?? null;
    }
    $nouvelleFin = ajouter_mois($c['date_fin'], (int) $c['duree_mois']);
    $nouveauId = enregistrer_contrat([
        'client_id' => $c['client_id'],
        'type_id'   => $c['type_id'],
        'produit'   => $c['produit'],
        'date_fin'  => $nouvelleFin,
        'montant'   => $c['montant'],
        'note'      => null,
    ], 'Créé comme suite du contrat #' . $id);
    db()->prepare('UPDATE contrats SET contrat_suivant_id = ? WHERE id = ?')->execute([$nouveauId, $id]);
    journaliser($id, 'suivant', 'Contrat suivant créé : #' . $nouveauId . ', fin le ' . date_fr($nouvelleFin));
    return $nouveauId;
}

function enregistrer_contrat(array $d, string $origine = 'Saisie'): int
{
    db()->prepare(
        'INSERT INTO contrats (client_id, type_id, produit, date_fin, statut, montant, note, cree_le, modifie_le)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    )->execute([
        $d['client_id'], $d['type_id'], $d['produit'], $d['date_fin'],
        $d['statut'] ?? 'a_envoyer', $d['montant'], $d['note'], maintenant(), maintenant(),
    ]);
    $id = (int) db()->lastInsertId();
    journaliser($id, 'creation', $origine);
    return $id;
}

function trouver_ou_creer_client(string $nom): int
{
    $nom = trim(preg_replace('/\s+/', ' ', $nom));
    $st = db()->prepare('SELECT id FROM clients WHERE LOWER(nom) = LOWER(?)');
    $st->execute([$nom]);
    $id = $st->fetchColumn();
    if ($id) {
        return (int) $id;
    }
    db()->prepare('INSERT INTO clients (nom, cree_le) VALUES (?, ?)')->execute([$nom, maintenant()]);
    return (int) db()->lastInsertId();
}

function types_contrat(): array
{
    return db()->query('SELECT * FROM types_contrat ORDER BY position, nom')->fetchAll();
}

// Vérifie et normalise une liste de jalons saisie (« 90, 60, 30 ») ; null si invalide
function lire_jalons(string $saisie): ?string
{
    $jalons = [];
    foreach (preg_split('/[\s,;]+/', trim($saisie), -1, PREG_SPLIT_NO_EMPTY) as $morceau) {
        $morceau = ltrim(strtoupper($morceau), 'J-');
        if (!ctype_digit($morceau) || (int) $morceau > 730) {
            return null;
        }
        $jalons[] = (int) $morceau;
    }
    if (!$jalons) {
        return null;
    }
    $jalons = array_unique($jalons);
    rsort($jalons);
    return implode(',', $jalons);
}

function texte_jalons(string $jalons): string
{
    return implode(', ', array_map(fn ($j) => 'J-' . $j, explode(',', $jalons)));
}

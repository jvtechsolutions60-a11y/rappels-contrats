<?php
declare(strict_types=1);

const VERSION_SCHEMA = 2;

// Crée les tables au premier lancement (ou ajoute les nouvelles après une mise à jour), puis ne fait plus rien.
function creer_tables(PDO $pdo): void
{
    $sqlite = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite';
    $id = $sqlite ? 'INTEGER PRIMARY KEY AUTOINCREMENT' : 'INT AUTO_INCREMENT PRIMARY KEY';
    $suffixe = $sqlite ? '' : ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';

    $tables = [
        'utilisateurs' => "CREATE TABLE IF NOT EXISTS utilisateurs (
            id $id,
            email VARCHAR(190) NOT NULL UNIQUE,
            mot_de_passe VARCHAR(255) NOT NULL,
            cree_le DATETIME NOT NULL
        )$suffixe",
        'clients' => "CREATE TABLE IF NOT EXISTS clients (
            id $id,
            nom VARCHAR(190) NOT NULL,
            contact VARCHAR(190) NULL,
            email VARCHAR(190) NULL,
            telephone VARCHAR(50) NULL,
            note TEXT NULL,
            cree_le DATETIME NOT NULL
        )$suffixe",
        'types_contrat' => "CREATE TABLE IF NOT EXISTS types_contrat (
            id $id,
            nom VARCHAR(100) NOT NULL,
            jalons VARCHAR(100) NOT NULL,
            duree_mois INT NOT NULL DEFAULT 12,
            produit_defaut VARCHAR(190) NULL,
            position INT NOT NULL DEFAULT 0
        )$suffixe",
        'contrats' => "CREATE TABLE IF NOT EXISTS contrats (
            id $id,
            client_id INT NOT NULL,
            type_id INT NOT NULL,
            produit VARCHAR(190) NOT NULL,
            date_fin DATE NOT NULL,
            statut VARCHAR(20) NOT NULL DEFAULT 'a_envoyer',
            montant DECIMAL(10,2) NULL,
            note TEXT NULL,
            date_envoi_devis DATE NULL,
            date_signature DATE NULL,
            contrat_suivant_id INT NULL,
            cree_le DATETIME NOT NULL,
            modifie_le DATETIME NOT NULL,
            FOREIGN KEY (client_id) REFERENCES clients(id),
            FOREIGN KEY (type_id) REFERENCES types_contrat(id)
        )$suffixe",
        'historique' => "CREATE TABLE IF NOT EXISTS historique (
            id $id,
            contrat_id INT NOT NULL,
            cree_le DATETIME NOT NULL,
            evenement VARCHAR(50) NOT NULL,
            detail TEXT NULL,
            FOREIGN KEY (contrat_id) REFERENCES contrats(id) ON DELETE CASCADE
        )$suffixe",
        'reglages' => "CREATE TABLE IF NOT EXISTS reglages (
            cle VARCHAR(100) PRIMARY KEY,
            valeur TEXT NULL
        )$suffixe",
        // Un rappel par contrat, par jalon et par date de fin : si la date de fin change, les rappels repartent
        'rappels_envoyes' => "CREATE TABLE IF NOT EXISTS rappels_envoyes (
            id $id,
            contrat_id INT NOT NULL,
            jalon INT NOT NULL,
            date_fin DATE NOT NULL,
            envoye_le DATETIME NOT NULL,
            UNIQUE (contrat_id, jalon, date_fin),
            FOREIGN KEY (contrat_id) REFERENCES contrats(id) ON DELETE CASCADE
        )$suffixe",
    ];

    // Une seule vérification par requête : le numéro de version du schéma
    try {
        $version = (int) $pdo->query("SELECT valeur FROM reglages WHERE cle = 'version_schema'")->fetchColumn();
    } catch (PDOException $e) {
        $version = 0; // tables absentes
    }
    if ($version >= VERSION_SCHEMA) {
        return;
    }

    foreach ($tables as $sql) {
        $pdo->exec($sql);
    }
    $pdo->prepare('DELETE FROM reglages WHERE cle = ?')->execute(['version_schema']);
    $pdo->prepare('INSERT INTO reglages (cle, valeur) VALUES (?, ?)')->execute(['version_schema', (string) VERSION_SCHEMA]);
    if ($pdo->query('SELECT COUNT(*) FROM types_contrat')->fetchColumn()) {
        return; // mise à jour d'une installation existante : les types sont déjà là
    }

    // Types de contrat proposés par défaut (validés le 30/10/2026)
    $types = [
        ['Antivirus', '90,60,30,15,0', 12, 'Licence antivirus'],
        ['Maintenance', '90,30,0', 12, 'Contrat de maintenance'],
        ['Garantie', '60,0', 12, 'Extension de garantie'],
    ];
    $ins = $pdo->prepare('INSERT INTO types_contrat (nom, jalons, duree_mois, produit_defaut, position) VALUES (?, ?, ?, ?, ?)');
    foreach ($types as $i => $t) {
        $ins->execute([$t[0], $t[1], $t[2], $t[3], $i]);
    }
}

<?php
declare(strict_types=1);

// Lecture des fichiers d'import (Excel ou CSV) et création du modèle Excel à télécharger.
// Fait sans bibliothèque : un fichier .xlsx est un zip de fichiers XML.

/** Renvoie le contenu du fichier sous forme de tableau de lignes (la première = les titres). */
function lire_tableur(string $chemin, string $nomOrigine): array
{
    $debut = (string) file_get_contents($chemin, false, null, 0, 4);
    if (str_starts_with($debut, "PK\x03\x04")) {
        return lire_xlsx($chemin);
    }
    if (preg_match('/\.(xls|ods)$/i', $nomOrigine)) {
        throw new RuntimeException('Format non reconnu : enregistrez le fichier au format Excel (.xlsx) ou CSV.');
    }
    return lire_csv((string) file_get_contents($chemin));
}

function lire_csv(string $contenu): array
{
    if (!mb_check_encoding($contenu, 'UTF-8')) {
        $contenu = mb_convert_encoding($contenu, 'UTF-8', 'Windows-1252'); // CSV enregistré par Excel
    }
    $contenu = (string) preg_replace('/^\x{FEFF}/u', '', $contenu);
    $premiereLigne = (string) strtok($contenu, "\n");
    $separateur = substr_count($premiereLigne, ';') >= substr_count($premiereLigne, ',') ? ';' : ',';

    $flux = fopen('php://memory', 'r+');
    fwrite($flux, $contenu);
    rewind($flux);
    $lignes = [];
    while (($champs = fgetcsv($flux, null, $separateur, '"', '')) !== false) {
        $lignes[] = $champs;
    }
    fclose($flux);
    return $lignes;
}

function lire_xlsx(string $chemin): array
{
    if (!class_exists(ZipArchive::class)) {
        throw new RuntimeException('Lecture des fichiers Excel impossible sur ce serveur : enregistrez le fichier en CSV.');
    }
    $zip = new ZipArchive();
    if ($zip->open($chemin) !== true) {
        throw new RuntimeException('Fichier Excel illisible.');
    }
    $lire = function (string $nom) use ($zip): ?SimpleXMLElement {
        $xml = $zip->getFromName($nom);
        return $xml === false ? null : (simplexml_load_string($xml, SimpleXMLElement::class, LIBXML_NONET) ?: null);
    };

    // Textes partagés
    $textes = [];
    if ($sst = $lire('xl/sharedStrings.xml')) {
        foreach ($sst->si as $si) {
            $textes[] = isset($si->t) ? (string) $si->t : implode('', array_map('strval', $si->xpath('.//*[local-name()="t"]')));
        }
    }

    // Première feuille du classeur
    $classeur = $lire('xl/workbook.xml');
    $liens = $lire('xl/_rels/workbook.xml.rels');
    $feuille = 'xl/worksheets/sheet1.xml';
    if ($classeur && $liens) {
        $premiere = $classeur->sheets->sheet[0] ?? null;
        $rid = $premiere ? (string) $premiere->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'] : '';
        foreach ($liens->Relationship as $rel) {
            if ((string) $rel['Id'] === $rid) {
                $cible = ltrim((string) $rel['Target'], '/');
                $feuille = str_starts_with($cible, 'xl/') ? $cible : 'xl/' . $cible;
            }
        }
    }
    $xml = $lire($feuille);
    $zip->close();
    if (!$xml) {
        throw new RuntimeException('Aucune feuille trouvée dans le fichier Excel.');
    }

    $lignes = [];
    foreach ($xml->sheetData->row as $row) {
        $ligne = [];
        foreach ($row->c as $c) {
            preg_match('/^([A-Z]+)/', (string) $c['r'], $m);
            $col = 0;
            foreach (str_split($m[1] ?? 'A') as $lettre) {
                $col = $col * 26 + (ord($lettre) - 64);
            }
            $type = (string) $c['t'];
            if ($type === 's') {
                $valeur = $textes[(int) $c->v] ?? '';
            } elseif ($type === 'inlineStr') {
                $valeur = (string) $c->is->t;
            } else {
                $valeur = (string) $c->v;
            }
            $ligne[$col - 1] = $valeur;
        }
        if ($ligne) {
            $max = max(array_keys($ligne));
            $lignes[] = array_replace(array_fill(0, $max + 1, ''), $ligne);
        }
    }
    return $lignes;
}

/** Modèle Excel vide avec listes déroulantes pour le type et le statut. */
function modele_xlsx(array $nomsTypes): string
{
    $x = fn (string $t) => htmlspecialchars($t, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    $titres = ['Client', 'Type de contrat', 'Produit', 'Date de fin', 'Statut', 'Montant HT (€)'];
    $statuts = ['À envoyer', 'Envoyé', 'Signé', 'Non renouvelé'];
    $liste = fn (array $valeurs) => $x('"' . implode(',', array_map(fn ($v) => str_replace([',', '"'], ' ', $v), $valeurs)) . '"');

    $entete = '';
    foreach ($titres as $i => $titre) {
        $entete .= '<c r="' . chr(65 + $i) . '1" t="inlineStr" s="1"><is><t>' . $x($titre) . '</t></is></c>';
    }
    $feuille = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        . '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
        . '<cols><col min="1" max="1" width="30" customWidth="1"/><col min="2" max="2" width="18" customWidth="1"/>'
        . '<col min="3" max="3" width="32" customWidth="1"/><col min="4" max="4" width="14" customWidth="1" style="2"/>'
        . '<col min="5" max="5" width="16" customWidth="1"/><col min="6" max="6" width="16" customWidth="1"/></cols>'
        . '<sheetData><row r="1">' . $entete . '</row></sheetData>'
        . '<dataValidations count="3">'
        . '<dataValidation type="list" allowBlank="1" showErrorMessage="1" errorTitle="Type inconnu" error="Choisissez un type dans la liste." sqref="B2:B1000"><formula1>' . $liste($nomsTypes) . '</formula1></dataValidation>'
        . '<dataValidation type="date" operator="greaterThan" allowBlank="1" showErrorMessage="1" errorTitle="Date invalide" error="Saisissez une date, par exemple 31/12/2026." sqref="D2:D1000"><formula1>36526</formula1></dataValidation>'
        . '<dataValidation type="list" allowBlank="1" showErrorMessage="1" errorTitle="Statut inconnu" error="Choisissez un statut dans la liste, ou laissez vide." sqref="E2:E1000"><formula1>' . $liste($statuts) . '</formula1></dataValidation>'
        . '</dataValidations></worksheet>';

    $aide = [
        'Une ligne par contrat, sur la feuille « Contrats ». Ne changez pas la première ligne.',
        'Client : le nom de l\'entreprise, écrit toujours de la même façon.',
        'Type de contrat : à choisir dans la liste.',
        'Produit : ce qui est renouvelé, par exemple « Licence antivirus 10 postes ».',
        'Date de fin : JJ/MM/AAAA. Une date approximative suffit, vous la corrigerez dans l\'outil.',
        'Statut : à laisser vide si le devis n\'est pas encore envoyé. Montant : facultatif.',
        'Ensuite : menu « Import » de l\'outil, choisissez ce fichier tel quel (pas besoin de l\'enregistrer en CSV).',
    ];
    $lignesAide = '';
    foreach ($aide as $i => $texte) {
        $lignesAide .= '<row r="' . ($i + 1) . '"><c r="A' . ($i + 1) . '" t="inlineStr"><is><t>' . $x($texte) . '</t></is></c></row>';
    }
    $feuilleAide = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        . '<cols><col min="1" max="1" width="110" customWidth="1"/></cols><sheetData>' . $lignesAide . '</sheetData></worksheet>';

    $fichiers = [
        '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet2.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>',
        '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>',
        'xl/workbook.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="Contrats" sheetId="1" r:id="rId1"/><sheet name="Aide" sheetId="2" r:id="rId2"/></sheets></workbook>',
        'xl/_rels/workbook.xml.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet2.xml"/>'
            . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>',
        'xl/styles.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<numFmts count="1"><numFmt numFmtId="164" formatCode="dd/mm/yyyy"/></numFmts>'
            . '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts>'
            . '<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFDDE7F5"/></patternFill></fill></fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="3"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/>'
            . '<xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/></cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>',
        'xl/worksheets/sheet1.xml' => $feuille,
        'xl/worksheets/sheet2.xml' => $feuilleAide,
    ];

    $temp = tempnam(sys_get_temp_dir(), 'modele');
    $zip = new ZipArchive();
    $zip->open($temp, ZipArchive::OVERWRITE);
    foreach ($fichiers as $nom => $contenu) {
        $zip->addFromString($nom, $contenu);
    }
    $zip->close();
    $binaire = (string) file_get_contents($temp);
    unlink($temp);
    return $binaire;
}

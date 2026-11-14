<?php
declare(strict_types=1);

function e(?string $texte): string
{
    return htmlspecialchars((string) $texte, ENT_QUOTES, 'UTF-8');
}

function url(string $page, array $params = []): string
{
    return '?' . http_build_query(['p' => $page] + $params);
}

function rediriger(string $page, array $params = []): never
{
    header('Location: ' . url($page, $params));
    exit;
}

function maintenant(): string
{
    return date('Y-m-d H:i:s');
}

function aujourdhui(): string
{
    return date('Y-m-d');
}

function date_fr(?string $date): string
{
    if (!$date) {
        return '';
    }
    $d = DateTime::createFromFormat('Y-m-d', substr($date, 0, 10));
    return $d ? $d->format('d/m/Y') : $date;
}

function jours_restants(string $dateFin): int
{
    $fin = new DateTime($dateFin);
    $auj = new DateTime(aujourdhui());
    return (int) $auj->diff($fin)->format('%r%a');
}

function texte_jours(int $jours): string
{
    if ($jours === 0) {
        return "aujourd'hui";
    }
    if ($jours < 0) {
        return 'dépassé de ' . abs($jours) . ' j';
    }
    return 'dans ' . $jours . ' j';
}

// Niveau d'urgence pour la couleur du compteur : urgent (dépassé ou < 15 j), bientot (< 60 j), loin, clos
function niveau_urgence(int $jours, string $statut): string
{
    if (!in_array($statut, STATUTS_OUVERTS, true)) {
        return 'clos';
    }
    if ($jours < 15) {
        return 'urgent';
    }
    return $jours < 60 ? 'bientot' : 'loin';
}

function texte_compteur(int $jours): string
{
    if ($jours === 0) {
        return 'Auj.';
    }
    return $jours < 0 ? 'J+' . abs($jours) : 'J-' . $jours;
}

// Messages affichés une fois après une action
function flash(string $message): void
{
    $_SESSION['flash'][] = $message;
}

function lire_flash(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

// Protection des formulaires
function jeton_csrf(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function champ_csrf(): string
{
    return '<input type="hidden" name="csrf" value="' . e(jeton_csrf()) . '">';
}

function verifier_csrf(): void
{
    if (!hash_equals(jeton_csrf(), (string) ($_POST['csrf'] ?? ''))) {
        http_response_code(400);
        exit('Formulaire expiré, revenez en arrière et réessayez.');
    }
}

function est_post(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

function afficher(string $vue, array $donnees = []): void
{
    global $config;
    extract($donnees);
    $vueFichier = dirname(__DIR__) . '/views/' . $vue . '.php';
    require dirname(__DIR__) . '/views/layout.php';
}

// Convertit une date saisie (JJ/MM/AAAA ou AAAA-MM-JJ) en AAAA-MM-JJ, ou null si invalide
function lire_date(string $saisie): ?string
{
    $saisie = trim($saisie);
    // « 130627 » ou « 13062027 » : chiffres seuls
    if (preg_match('/^(\d{2})(\d{2})(\d{2}|\d{4})$/', $saisie, $m)) {
        $saisie = "$m[1]/$m[2]/$m[3]";
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $saisie)) {
        $saisie = preg_replace('/[\s.\-]+/', '/', $saisie);
    }
    // Année sur deux chiffres : toujours 20xx (une fin de contrat n'est jamais au siècle dernier)
    $saisie = preg_replace('#^(\d{1,2}/\d{1,2}/)(\d{2})$#', '${1}20$2', $saisie);
    foreach (['Y-m-d', 'd/m/Y', 'j/n/Y'] as $format) {
        $d = DateTime::createFromFormat('!' . $format, $saisie);
        if ($d && $d->format($format) === $saisie) {
            return $d->format('Y-m-d');
        }
    }
    return null;
}

// Une date de fin passée ou à plus de 5 ans est sans doute une faute de frappe
function date_suspecte(string $date): bool
{
    return $date < aujourdhui() || $date > date('Y-m-d', strtotime('+5 years'));
}

// Convertit un montant saisi (« 1 234,50 ») en nombre, ou null
function lire_montant(string $saisie): ?string
{
    $saisie = str_replace([' ', "\u{00A0}", '€'], '', trim($saisie));
    if ($saisie === '') {
        return null;
    }
    $saisie = str_replace(',', '.', $saisie);
    return is_numeric($saisie) ? number_format((float) $saisie, 2, '.', '') : null;
}

function montant_fr(?string $montant): string
{
    return $montant === null || $montant === '' ? '' : number_format((float) $montant, 2, ',', ' ') . ' €';
}

// Couleurs des graphiques (donuts, légendes)
const PALETTE = ['#36b99a', '#e8604c', '#8e6fd8', '#f0904a', '#e8b83e', '#2e8bc9', '#b5c43c', '#5a9e6f'];

// Donut SVG : $parts = [['valeur' => float, 'couleur' => '#hex'], ...]
function donut_svg(array $parts, string $centre = ''): string
{
    $total = array_sum(array_column($parts, 'valeur'));
    $r = 40;
    $tour = 2 * M_PI * $r;
    $svg = '<svg class="donut" viewBox="0 0 120 120" role="img" aria-hidden="true">';
    if ($total <= 0) {
        return $svg . '<circle cx="60" cy="60" r="' . $r . '" fill="none" stroke="#e6e9ee" stroke-width="28"/></svg>';
    }
    $cumul = 0.0;
    $textes = '';
    foreach ($parts as $p) {
        if ($p['valeur'] <= 0) {
            continue;
        }
        $part = $p['valeur'] / $total;
        $long = $part * $tour;
        $svg .= sprintf('<circle cx="60" cy="60" r="%d" fill="none" stroke="%s" stroke-width="28" stroke-dasharray="%.3f %.3f" stroke-dashoffset="%.3f" transform="rotate(-90 60 60)"/>',
            $r, e($p['couleur']), max(0, $long - 0.8), $tour, -$cumul * $tour);
        if ($part >= 0.08) {
            $angle = 2 * M_PI * ($cumul + $part / 2) - M_PI / 2;
            $textes .= sprintf('<text x="%.2f" y="%.2f">%s</text>', 60 + $r * cos($angle), 60 + $r * sin($angle) + 2.4,
                number_format($part * 100, $part < 0.1 ? 1 : 0, ',', '') . ' %');
        }
        $cumul += $part;
    }
    $svg .= '<g class="donut-pct">' . $textes . '</g>';
    if ($centre !== '') {
        $svg .= '<text class="donut-centre" x="60" y="64">' . e($centre) . '</text>';
    }
    return $svg . '</svg>';
}

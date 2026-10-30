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
    foreach (['Y-m-d', 'd/m/Y', 'd/m/y', 'd-m-Y', 'd.m.Y'] as $format) {
        $d = DateTime::createFromFormat('!' . $format, $saisie);
        if ($d && $d->format($format) === $saisie) {
            return $d->format('Y-m-d');
        }
    }
    return null;
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

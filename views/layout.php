<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($titre ?? '') ?> · <?= e($config['nom_appli'] ?? 'Rappels de contrats') ?></title>
<link rel="preload" href="fonts/inter.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="style.css?v=<?= @filemtime(__DIR__ . '/../public/style.css') ?>">
</head>
<body>
<?php if (empty($sansMenu)): ?>
<?php $pageActive = (string) ($_GET['p'] ?? 'accueil'); ?>
<header class="entete">
  <a class="marque" href="<?= url('accueil') ?>"><span class="marque-logo" aria-hidden="true"></span><?= e($config['nom_appli'] ?? 'Rappels de contrats') ?></a>
  <nav>
    <?php foreach (['accueil' => 'Tableau de bord', 'contrats' => 'Contrats', 'echeancier' => 'Échéancier', 'clients' => 'Clients', 'import' => 'Import', 'reglages' => 'Réglages'] as $pg => $lib): ?>
      <a href="<?= url($pg) ?>"<?= $pageActive === $pg ? ' class="actif"' : '' ?>><?= e($lib) ?></a>
    <?php endforeach; ?>
    <a class="bouton principal" href="<?= url('contrat_form') ?>">+ Nouveau contrat</a>
  </nav>
</header>
<?php endif; ?>
<main>
<?php foreach (lire_flash() as $message): ?>
  <p class="flash"><?= e($message) ?></p>
<?php endforeach; ?>
<?php require $vueFichier; ?>
</main>
<?php if (empty($sansMenu)): ?>
<footer><a href="<?= url('deconnexion') ?>">Se déconnecter</a></footer>
<?php endif; ?>
<script src="app.js?v=<?= @filemtime(__DIR__ . '/../public/app.js') ?>"></script>
</body>
</html>

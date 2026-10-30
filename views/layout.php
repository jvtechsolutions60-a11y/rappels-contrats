<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($titre ?? '') ?> · <?= e($config['nom_appli'] ?? 'Rappels de contrats') ?></title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<?php if (empty($sansMenu)): ?>
<header class="entete">
  <a class="marque" href="<?= url('accueil') ?>"><?= e($config['nom_appli'] ?? 'Rappels de contrats') ?></a>
  <nav>
    <a href="<?= url('accueil') ?>">À faire</a>
    <a href="<?= url('contrats') ?>">Contrats</a>
    <a href="<?= url('clients') ?>">Clients</a>
    <a href="<?= url('import') ?>">Import</a>
    <a href="<?= url('reglages') ?>">Réglages</a>
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
<script src="app.js"></script>
</body>
</html>

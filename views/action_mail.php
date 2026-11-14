<section class="carte etroite">
  <h1><?= e($config['nom_appli'] ?? 'Rappels de contrats') ?></h1>
  <?php if ($erreur): ?>
    <p class="erreur"><?= e($erreur) ?></p>
  <?php else: ?>
    <p><strong><?= e($c['client_nom']) ?></strong> – <?= e($c['produit']) ?><br>
      <small>Fin le <?= date_fr($c['date_fin']) ?></small></p>
    <?php if ($fait): ?>
      <p class="flash"><?= e($fait) ?></p>
    <?php else: ?>
      <form method="post" action="?p=action_mail">
        <?php foreach (['id', 'a', 'e', 't'] as $cle): ?>
          <input type="hidden" name="<?= $cle ?>" value="<?= e((string) ($params[$cle] ?? '')) ?>">
        <?php endforeach; ?>
        <button class="<?= $action === 'signe' ? 'succes' : 'principal' ?>">Confirmer : <?= e(mb_strtolower($libelle)) ?></button>
      </form>
    <?php endif; ?>
  <?php endif; ?>
  <p><a href="<?= url('accueil') ?>">Ouvrir l'application</a></p>
</section>

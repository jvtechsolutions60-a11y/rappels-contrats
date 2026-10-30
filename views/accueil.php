<?php
$titresBlocs = [
    'en_retard'  => ['En retard', 'Date de fin dépassée sans signature.'],
    'a_envoyer'  => ['Devis à envoyer', 'Le premier rappel est atteint.'],
    'en_attente' => ['En attente de signature', 'Devis envoyé, pas encore signé.'],
    'a_venir'    => ['À venir', 'Rien à faire pour l\'instant.'],
];
$dernierEnvoi = reglage('dernier_envoi');
$rienAFaire = !$blocs['en_retard'] && !$blocs['a_envoyer'] && !$blocs['en_attente'];
?>
<h1>À faire</h1>
<?php if ($dernierEnvoi && str_contains($dernierEnvoi, 'ÉCHEC')): ?>
  <p class="alerte">Le dernier e-mail du matin n'est pas parti (<?= e($dernierEnvoi) ?>). Vérifiez la boîte d'envoi, puis utilisez « Envoyer un e-mail de test » dans les <a href="<?= url('reglages') ?>">réglages</a>.</p>
<?php endif; ?>

<?php if ($aucunContrat): ?>
  <section class="carte">
    <p>Aucun contrat pour l'instant. Deux façons de commencer :</p>
    <p class="liens">
      <a class="bouton principal" href="<?= url('contrat_form') ?>">Saisir un contrat</a>
      <a class="bouton" href="<?= url('import') ?>">Importer un fichier CSV</a>
    </p>
  </section>
<?php else: ?>
  <?php if ($rienAFaire): ?><p class="flash">Rien d'urgent : aucun devis à envoyer ni à relancer.</p><?php endif; ?>

  <?php foreach ($titresBlocs as $cle => [$titreBloc, $aide]):
      if (!$blocs[$cle]) continue; ?>
  <section class="bloc bloc-<?= e($cle) ?>">
    <h2><?= e($titreBloc) ?> <span class="compte"><?= count($blocs[$cle]) ?></span></h2>
    <p class="note"><?= e($aide) ?></p>
    <ul class="taches">
    <?php foreach ($blocs[$cle] as $c): ?>
      <li>
        <div>
          <a href="<?= url('contrat', ['id' => $c['id']]) ?>"><strong><?= e($c['client_nom']) ?></strong> – <?= e($c['produit']) ?></a>
          <br><small class="<?= $c['jours'] < 0 ? 'retard' : '' ?>">Fin le <?= date_fr($c['date_fin']) ?>, <?= e(texte_jours($c['jours'])) ?>
          <?php if ($cle === 'en_attente' && $c['date_envoi_devis']): ?> · devis envoyé le <?= date_fr($c['date_envoi_devis']) ?><?php endif; ?>
          <?php if ($cle === 'a_venir'): ?> · premier rappel le <?= date_fr($c['premier_rappel']) ?><?php endif; ?></small>
        </div>
        <?php if ($cle !== 'a_venir'): ?>
        <div class="actions"><?php $retour = 'accueil'; require __DIR__ . '/_actions.php'; ?></div>
        <?php endif; ?>
      </li>
    <?php endforeach; ?>
    </ul>
  </section>
  <?php endforeach; ?>
<?php endif; ?>

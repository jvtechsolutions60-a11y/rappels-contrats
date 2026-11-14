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
  <div class="chiffres">
    <a class="chiffre" href="<?= url('contrats', ['periode' => '30']) ?>">
      <span class="chiffre-valeur"><?= $chiffres['a_renouveler_total'] > 0 ? e(montant_fr($chiffres['a_renouveler_total'])) : $chiffres['a_renouveler_nb'] ?></span>
      <span class="chiffre-libelle">à renouveler dans les 90 jours<?= $chiffres['a_renouveler_total'] > 0 ? ' (' . $chiffres['a_renouveler_nb'] . ' contrat' . ($chiffres['a_renouveler_nb'] > 1 ? 's' : '') . ')' : ' contrat' . ($chiffres['a_renouveler_nb'] > 1 ? 's' : '') ?></span>
    </a>
    <a class="chiffre <?= $chiffres['retard_nb'] ? 'chiffre-alerte' : '' ?>" href="<?= url('contrats', ['statut' => 'expire']) ?>">
      <span class="chiffre-valeur"><?= $chiffres['retard_nb'] ?></span>
      <span class="chiffre-libelle">en retard<?= $chiffres['retard_total'] > 0 ? ', ' . e(montant_fr($chiffres['retard_total'])) . ' en jeu' : '' ?></span>
    </a>
    <div class="chiffre">
      <span class="chiffre-valeur"><?= $chiffres['taux'] === null ? '–' : $chiffres['taux'] . ' %' ?></span>
      <span class="chiffre-libelle">de renouvellement sur 12 mois<?= $chiffres['taux_base'] ? ' (' . $chiffres['taux_base'] . ' contrat' . ($chiffres['taux_base'] > 1 ? 's' : '') . ')' : '' ?></span>
    </div>
  </div>

  <?php if ($preavis): ?>
  <section class="bloc bloc-preavis">
    <h2>Préavis à surveiller <span class="compte"><?= count($preavis) ?></span></h2>
    <p class="note">Date limite pour résilier un contrat à reconduction tacite.</p>
    <ul class="taches">
    <?php foreach ($preavis as $p): ?>
      <li>
        <div class="compteur compteur-<?= $p['jours_preavis'] < 15 ? 'urgent' : 'bientot' ?>"><span class="compteur-jours"><?= e(texte_compteur($p['jours_preavis'])) ?></span><span class="compteur-date"><?= date_fr($p['date_preavis']) ?></span></div>
        <div class="tache-texte">
          <a href="<?= url('contrat', ['id' => $p['id']]) ?>"><strong><?= e($p['client_nom']) ?></strong> – <?= e($p['produit']) ?></a>
          <br><small>Résilier avant le <?= date_fr($p['date_preavis']) ?> · fin le <?= date_fr($p['date_fin']) ?></small>
        </div>
      </li>
    <?php endforeach; ?>
    </ul>
  </section>
  <?php endif; ?>

  <?php if ($rienAFaire): ?><p class="flash">Rien d'urgent : aucun devis à envoyer ni à relancer.</p><?php endif; ?>

  <?php foreach ($titresBlocs as $cle => [$titreBloc, $aide]):
      if (!$blocs[$cle]) continue; ?>
  <section class="bloc bloc-<?= e($cle) ?>">
    <h2><?= e($titreBloc) ?> <span class="compte"><?= count($blocs[$cle]) ?></span></h2>
    <p class="note"><?= e($aide) ?></p>
    <ul class="taches">
    <?php foreach ($blocs[$cle] as $c): ?>
      <li>
        <?php require __DIR__ . '/_compteur.php'; ?>
        <div class="tache-texte">
          <a href="<?= url('contrat', ['id' => $c['id']]) ?>"><strong><?= e($c['client_nom']) ?></strong> – <?= e($c['produit']) ?></a>
          <br><small><?= e($c['type_nom']) ?>
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

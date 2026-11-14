<h1>Contrats</h1>
<?php $dernierEnvoi = reglage('dernier_envoi'); if ($dernierEnvoi && str_contains($dernierEnvoi, 'ÉCHEC')): ?>
  <p class="alerte">Le dernier e-mail du matin n'est pas parti (<?= e($dernierEnvoi) ?>). Vérifiez la boîte d'envoi, puis utilisez « Envoyer un e-mail de test » dans les <a href="<?= url('reglages') ?>">réglages</a>.</p>
<?php endif; ?>

<p class="pastilles">
  <a class="pastille <?= $periode === '' ? 'active' : '' ?>" href="<?= url('contrats', array_filter(['q' => $q, 'type' => $typeId ?: null])) ?>">Tout</a>
  <?php foreach (PERIODES as $cle => $libelle): ?>
    <a class="pastille <?= $periode === (string) $cle ? 'active' : '' ?>" href="<?= url('contrats', array_filter(['periode' => $cle, 'q' => $q, 'type' => $typeId ?: null])) ?>"><?= e($libelle) ?></a>
  <?php endforeach; ?>
</p>

<form method="get" class="filtres">
  <?php if ($periode !== ''): ?><input type="hidden" name="periode" value="<?= e($periode) ?>"><?php endif; ?>
  <input type="hidden" name="p" value="contrats">
  <input type="search" name="q" placeholder="Client ou produit" value="<?= e($q) ?>">
  <select name="statut">
    <option value="">Tous les statuts</option>
    <option value="ouverts" <?= $statut === 'ouverts' ? 'selected' : '' ?>>En cours (rappels actifs)</option>
    <?php foreach (STATUTS as $cle => $libelle): ?>
      <option value="<?= e($cle) ?>" <?= $statut === $cle ? 'selected' : '' ?>><?= e($libelle) ?></option>
    <?php endforeach; ?>
  </select>
  <select name="type">
    <option value="0">Tous les types</option>
    <?php foreach ($types as $t): ?>
      <option value="<?= (int) $t['id'] ?>" <?= $typeId === (int) $t['id'] ? 'selected' : '' ?>><?= e($t['nom']) ?></option>
    <?php endforeach; ?>
  </select>
  <button>Filtrer</button>
</form>

<?php if ($contrats && array_filter(array_column($contrats, 'montant'))): ?>
  <p class="note"><?= count($contrats) ?> contrat<?= count($contrats) > 1 ? 's' : '' ?>, total <?= e(montant_fr((string) array_sum(array_column($contrats, 'montant')))) ?> HT</p>
<?php endif; ?>
<?php if (!$contrats): ?>
  <p class="vide">Aucun contrat<?= $q || $statut || $typeId || $periode ? ' pour ces critères' : '' ?>.
    <a href="<?= url('contrat_form') ?>">Saisir un contrat</a></p>
<?php else: ?>
<table class="liste">
  <thead>
    <tr><th>Échéance</th><th>Client</th><th>Produit</th><th>Type</th><th>Statut</th><th></th></tr>
  </thead>
  <tbody>
  <?php foreach ($contrats as $c):
      $jours = jours_restants($c['date_fin']);
      $ouvert = in_array($c['statut'], STATUTS_OUVERTS, true); ?>
    <tr class="<?= $ouvert ? '' : 'clos' ?>">
      <td class="col-compteur"><?php require __DIR__ . '/_compteur.php'; ?></td>
      <td data-label="Client"><a href="<?= url('contrat', ['id' => $c['id']]) ?>"><?= e($c['client_nom']) ?></a></td>
      <td data-label="Produit"><?= e($c['produit']) ?></td>
      <td data-label="Type"><?= e($c['type_nom']) ?></td>
      <td data-label="Statut"><span class="statut statut-<?= e($c['statut']) ?>"><?= e(libelle_statut($c['statut'])) ?></span></td>
      <td><div class="actions"><?php $retour = 'contrats'; require __DIR__ . '/_actions.php'; ?></div></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>

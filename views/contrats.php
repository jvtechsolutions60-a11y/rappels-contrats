<h1>Contrats</h1>

<form method="get" class="filtres">
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

<?php if (!$contrats): ?>
  <p class="vide">Aucun contrat<?= $q || $statut || $typeId ? ' pour ces critères' : '' ?>.
    <a href="<?= url('contrat_form') ?>">Saisir un contrat</a></p>
<?php else: ?>
<table class="liste">
  <thead>
    <tr><th>Client</th><th>Produit</th><th>Type</th><th>Fin</th><th>Statut</th><th></th></tr>
  </thead>
  <tbody>
  <?php foreach ($contrats as $c):
      $jours = jours_restants($c['date_fin']);
      $ouvert = in_array($c['statut'], STATUTS_OUVERTS, true); ?>
    <tr class="<?= $ouvert ? '' : 'clos' ?>">
      <td data-label="Client"><a href="<?= url('contrat', ['id' => $c['id']]) ?>"><?= e($c['client_nom']) ?></a></td>
      <td data-label="Produit"><?= e($c['produit']) ?></td>
      <td data-label="Type"><?= e($c['type_nom']) ?></td>
      <td data-label="Fin"><?= date_fr($c['date_fin']) ?><?php if ($ouvert): ?><br><small class="<?= $jours < 0 ? 'retard' : '' ?>"><?= e(texte_jours($jours)) ?></small><?php endif; ?></td>
      <td data-label="Statut"><span class="statut statut-<?= e($c['statut']) ?>"><?= e(libelle_statut($c['statut'])) ?></span></td>
      <td><div class="actions"><?php $retour = 'contrats'; require __DIR__ . '/_actions.php'; ?></div></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>

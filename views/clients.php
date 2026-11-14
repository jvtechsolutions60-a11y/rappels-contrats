<h1>Clients</h1>
<p><a class="bouton" href="<?= url('client_form') ?>">+ Nouveau client</a></p>
<?php if (!$clients): ?>
  <p class="vide">Aucun client. Ils sont aussi créés automatiquement quand vous saisissez un contrat.</p>
<?php else: ?>
<table class="liste">
  <thead><tr><th>Prochaine échéance</th><th>Client</th><th>Contact</th><th>Contrats en cours</th></tr></thead>
  <tbody>
  <?php foreach ($clients as $cl): ?>
    <tr class="<?= $cl['prochain'] ? '' : 'clos' ?>">
      <td class="col-compteur"><?php if ($cl['prochain']): $c = $cl['prochain']; require __DIR__ . '/_compteur.php'; else: ?><span class="note">Aucun en cours</span><?php endif; ?></td>
      <td data-label="Client"><a href="<?= url('client', ['id' => $cl['id']]) ?>"><strong><?= e($cl['nom']) ?></strong></a>
        <?php if ($cl['prochain']): ?><br><small><?= e($cl['prochain']['produit']) ?></small><?php endif; ?></td>
      <td data-label="Contact"><?= e($cl['contact']) ?></td>
      <td data-label="Contrats en cours"><?= (int) $cl['nb_ouverts'] ?> / <?= (int) $cl['nb_contrats'] ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>

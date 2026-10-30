<h1>Clients</h1>
<p><a class="bouton" href="<?= url('client_form') ?>">+ Nouveau client</a></p>
<?php if (!$clients): ?>
  <p class="vide">Aucun client. Ils sont aussi créés automatiquement quand vous saisissez un contrat.</p>
<?php else: ?>
<table class="liste">
  <thead><tr><th>Client</th><th>Contact</th><th>Contrats</th><th>En cours</th></tr></thead>
  <tbody>
  <?php foreach ($clients as $cl): ?>
    <tr>
      <td data-label="Client"><a href="<?= url('client', ['id' => $cl['id']]) ?>"><?= e($cl['nom']) ?></a></td>
      <td data-label="Contact"><?= e($cl['contact']) ?></td>
      <td data-label="Contrats"><?= (int) $cl['nb_contrats'] ?></td>
      <td data-label="En cours"><?= (int) $cl['nb_ouverts'] ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>

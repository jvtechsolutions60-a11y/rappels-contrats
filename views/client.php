<p class="fil"><a href="<?= url('clients') ?>">← Clients</a></p>
<h1><?= e($client['nom']) ?></h1>

<section class="carte">
  <dl class="infos">
    <?php if ($client['contact']): ?><dt>Contact</dt><dd><?= e($client['contact']) ?></dd><?php endif; ?>
    <?php if ($client['email']): ?><dt>E-mail</dt><dd><a href="mailto:<?= e($client['email']) ?>"><?= e($client['email']) ?></a></dd><?php endif; ?>
    <?php if ($client['telephone']): ?><dt>Téléphone</dt><dd><a href="tel:<?= e($client['telephone']) ?>"><?= e($client['telephone']) ?></a></dd><?php endif; ?>
    <?php if ($client['note']): ?><dt>Note</dt><dd><?= nl2br(e($client['note'])) ?></dd><?php endif; ?>
  </dl>
  <p class="liens">
    <a class="bouton" href="<?= url('client_form', ['id' => $client['id']]) ?>">Modifier</a>
    <a class="bouton principal" href="<?= url('contrat_form', ['client' => $client['nom']]) ?>">+ Contrat pour ce client</a>
  </p>
</section>

<h2>Contrats</h2>
<?php if (!$contrats): ?>
  <p class="vide">Aucun contrat pour ce client.</p>
<?php else: ?>
<table class="liste">
  <thead><tr><th>Produit</th><th>Type</th><th>Fin</th><th>Statut</th></tr></thead>
  <tbody>
  <?php foreach ($contrats as $c): ?>
    <tr class="<?= in_array($c['statut'], STATUTS_OUVERTS, true) ? '' : 'clos' ?>">
      <td data-label="Produit"><a href="<?= url('contrat', ['id' => $c['id']]) ?>"><?= e($c['produit']) ?></a></td>
      <td data-label="Type"><?= e($c['type_nom']) ?></td>
      <td data-label="Fin"><?= date_fr($c['date_fin']) ?></td>
      <td data-label="Statut"><span class="statut statut-<?= e($c['statut']) ?>"><?= e(libelle_statut($c['statut'])) ?></span></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>

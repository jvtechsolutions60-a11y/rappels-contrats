<h1>Import CSV</h1>

<?php if ($lignes === null): ?>
<section class="carte formulaire">
  <p>Chargez d'un coup vos contrats existants depuis un fichier CSV (Excel : « Enregistrer sous », format CSV point-virgule).</p>
  <p>Colonnes attendues sur la première ligne : <code>client;type;produit;date_fin;statut;montant</code>. Les deux dernières sont facultatives.</p>
  <ul class="note">
    <li>Type : <?= e(implode(', ', array_column($types, 'nom'))) ?>.</li>
    <li>Date de fin : JJ/MM/AAAA.</li>
    <li>Statut : à envoyer (par défaut), envoyé, signé, non renouvelé.</li>
  </ul>
  <?php if ($erreur): ?><p class="erreur"><?= e($erreur) ?></p><?php endif; ?>
  <form method="post" enctype="multipart/form-data">
    <?= champ_csrf() ?>
    <input type="hidden" name="action" value="analyser">
    <label>Fichier <input type="file" name="fichier" accept=".csv,text/csv" required></label>
    <div class="boutons"><button class="principal">Vérifier le fichier</button></div>
  </form>
  <p class="note">Rien n'est enregistré à cette étape : vous verrez d'abord un aperçu.</p>
</section>
<?php else:
    $ok = count(array_filter($lignes, fn ($l) => !$l['erreurs'] && !$l['doublon']));
    $enErreur = count(array_filter($lignes, fn ($l) => (bool) $l['erreurs']));
    $doublons = count(array_filter($lignes, fn ($l) => $l['doublon'])); ?>
<section class="carte">
  <p><strong><?= $ok ?> contrat<?= $ok > 1 ? 's' : '' ?> prêt<?= $ok > 1 ? 's' : '' ?> à importer</strong>
    <?php if ($enErreur): ?>, <span class="retard"><?= $enErreur ?> ligne<?= $enErreur > 1 ? 's' : '' ?> en erreur (ignorée<?= $enErreur > 1 ? 's' : '' ?>)</span><?php endif; ?>
    <?php if ($doublons): ?>, <?= $doublons ?> déjà présent<?= $doublons > 1 ? 's' : '' ?> (ignoré<?= $doublons > 1 ? 's' : '' ?>)<?php endif; ?>.</p>
  <div class="boutons">
    <?php if ($ok): ?>
    <form method="post">
      <?= champ_csrf() ?>
      <input type="hidden" name="action" value="confirmer">
      <button class="principal">Importer <?= $ok ?> contrat<?= $ok > 1 ? 's' : '' ?></button>
    </form>
    <?php endif; ?>
    <a class="bouton" href="<?= url('import') ?>">Choisir un autre fichier</a>
  </div>
</section>

<table class="liste">
  <thead><tr><th>Ligne</th><th>Client</th><th>Type</th><th>Produit</th><th>Fin</th><th>Résultat</th></tr></thead>
  <tbody>
  <?php foreach ($lignes as $l): ?>
    <tr class="<?= $l['erreurs'] || $l['doublon'] ? 'clos' : '' ?>">
      <td data-label="Ligne"><?= (int) $l['numero'] ?></td>
      <td data-label="Client"><?= e($l['client']) ?></td>
      <td data-label="Type"><?= e($l['type']) ?></td>
      <td data-label="Produit"><?= e($l['produit']) ?></td>
      <td data-label="Fin"><?= $l['date_fin'] ? date_fr($l['date_fin']) : e($l['date_fin_saisie']) ?></td>
      <td data-label="Résultat">
        <?php if ($l['erreurs']): ?><span class="retard"><?= e(ucfirst(implode(', ', $l['erreurs']))) ?></span>
        <?php elseif ($l['doublon']): ?>Déjà présent
        <?php else: ?><span class="statut statut-<?= e($l['statut']) ?>"><?= e(libelle_statut($l['statut'])) ?></span><?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>

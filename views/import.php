<h1>Importer des contrats</h1>

<?php if ($lignes === null): ?>
<section class="carte">
  <h2>1. Remplir le modèle</h2>
  <p>Une ligne par contrat. Le type et le statut se choisissent dans une liste.</p>
  <p><a class="bouton principal" href="<?= url('modele') ?>">Télécharger le modèle Excel</a></p>
</section>

<section class="carte formulaire">
  <h2>2. Envoyer le fichier</h2>
  <?php if ($erreur): ?><p class="erreur"><?= e($erreur) ?></p><?php endif; ?>
  <form method="post" enctype="multipart/form-data">
    <?= champ_csrf() ?>
    <input type="hidden" name="action" value="analyser">
    <label>Fichier Excel (.xlsx) ou CSV <input type="file" name="fichier" accept=".xlsx,.csv,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required></label>
    <div class="boutons"><button class="principal">Vérifier le fichier</button></div>
  </form>
  <p class="note">Rien n'est enregistré à cette étape : vous verrez d'abord un aperçu, ligne par ligne.</p>
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

<p class="fil"><a href="<?= url('reglages') ?>">← Réglages</a></p>
<h1><?= e($titre) ?></h1>
<form method="post" class="carte formulaire">
  <?= champ_csrf() ?>
  <label>Nom <input name="nom" required autofocus value="<?= e($type['nom']) ?>" placeholder="Ex. Antivirus">
    <?php if (isset($erreurs['nom'])): ?><span class="erreur"><?= e($erreurs['nom']) ?></span><?php endif; ?></label>
  <label>Rappels, en jours avant la fin <input name="jalons" required value="<?= e(str_replace(',', ', ', $type['jalons'])) ?>" placeholder="90, 60, 30, 15, 0">
    <small>Le plus grand nombre est la date limite d'envoi du devis. 0 = le jour de la fin.</small>
    <?php if (isset($erreurs['jalons'])): ?><span class="erreur"><?= e($erreurs['jalons']) ?></span><?php endif; ?></label>
  <label>Durée habituelle du contrat (mois) <input type="number" name="duree_mois" min="1" max="120" required value="<?= (int) $type['duree_mois'] ?>">
    <small>Sert à calculer la date de fin du contrat suivant.</small>
    <?php if (isset($erreurs['duree_mois'])): ?><span class="erreur"><?= e($erreurs['duree_mois']) ?></span><?php endif; ?></label>
  <label>Produit proposé par défaut <input name="produit_defaut" value="<?= e($type['produit_defaut']) ?>" placeholder="Ex. Licence antivirus"></label>
  <div class="boutons"><button class="principal">Enregistrer</button></div>
</form>

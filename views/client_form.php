<p class="fil"><a href="<?= $id ? url('client', ['id' => $id]) : url('clients') ?>">← Retour</a></p>
<h1><?= e($titre) ?></h1>
<form method="post" class="carte formulaire">
  <?= champ_csrf() ?>
  <label>Nom <input name="nom" required autofocus value="<?= e($client['nom']) ?>">
    <?php if (isset($erreurs['nom'])): ?><span class="erreur"><?= e($erreurs['nom']) ?></span><?php endif; ?></label>
  <label>Contact <input name="contact" value="<?= e($client['contact']) ?>"></label>
  <label>E-mail <input type="email" name="email" value="<?= e($client['email']) ?>">
    <?php if (isset($erreurs['email'])): ?><span class="erreur"><?= e($erreurs['email']) ?></span><?php endif; ?></label>
  <label>Téléphone <input type="tel" name="telephone" value="<?= e($client['telephone']) ?>"></label>
  <label>Note <textarea name="note" rows="3"><?= e($client['note']) ?></textarea></label>
  <div class="boutons"><button class="principal">Enregistrer</button></div>
</form>

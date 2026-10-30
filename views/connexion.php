<section class="carte etroite formulaire">
  <h1><?= e($config['nom_appli'] ?? 'Rappels de contrats') ?></h1>
  <?php if ($erreur): ?><p class="erreur"><?= e($erreur) ?></p><?php endif; ?>
  <form method="post">
    <?= champ_csrf() ?>
    <label>E-mail <input type="email" name="email" required autofocus value="<?= e($_POST['email'] ?? '') ?>"></label>
    <label>Mot de passe <input type="password" name="mot_de_passe" required></label>
    <button class="principal">Se connecter</button>
  </form>
</section>

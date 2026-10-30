<section class="carte etroite">
  <h1>Bienvenue</h1>
  <p>Créez le compte du gérant. C'est cette adresse qui recevra les rappels.</p>
  <?php if ($erreur): ?><p class="erreur"><?= e($erreur) ?></p><?php endif; ?>
  <form method="post">
    <?= champ_csrf() ?>
    <label>E-mail <input type="email" name="email" required autofocus value="<?= e($_POST['email'] ?? '') ?>"></label>
    <label>Mot de passe (10 caractères minimum) <input type="password" name="mot_de_passe" required minlength="10"></label>
    <button class="principal">Créer le compte</button>
  </form>
</section>

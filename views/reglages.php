<h1>Réglages</h1>

<section class="carte">
  <h2>Types de contrat et rythme des rappels</h2>
  <p>Chaque type a ses propres jalons : un rappel part quand la date de fin est à ce nombre de jours.</p>
  <table class="liste">
    <thead><tr><th>Type</th><th>Rappels</th><th>Durée</th><th>Contrats</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($types as $t): ?>
      <tr>
        <td data-label="Type"><?= e($t['nom']) ?></td>
        <td data-label="Rappels"><?= e(texte_jalons($t['jalons'])) ?></td>
        <td data-label="Durée"><?= (int) $t['duree_mois'] ?> mois</td>
        <td data-label="Contrats"><?= (int) $t['nb'] ?></td>
        <td><a href="<?= url('type_form', ['id' => $t['id']]) ?>">Modifier</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <p><a class="bouton" href="<?= url('type_form') ?>">+ Nouveau type</a></p>
</section>

<section class="carte formulaire">
  <h2>Mot de passe</h2>
  <?php if ($erreur): ?><p class="erreur"><?= e($erreur) ?></p><?php endif; ?>
  <form method="post">
    <?= champ_csrf() ?>
    <input type="hidden" name="action" value="mot_de_passe">
    <label>Mot de passe actuel <input type="password" name="actuel" required></label>
    <label>Nouveau mot de passe (10 caractères minimum) <input type="password" name="nouveau" required minlength="10"></label>
    <div class="boutons"><button>Changer le mot de passe</button></div>
  </form>
</section>

<p class="note">L'adresse qui reçoit l'e-mail du matin et le bouton « e-mail de test » arrivent à l'étape 2.</p>

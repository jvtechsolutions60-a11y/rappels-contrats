<?php
// Boutons d'action en un clic. Attend $c (contrat) et $retour ('contrats' ou 'contrat').
$boutons = match ($c['statut']) {
    'a_envoyer' => ['envoye' => 'Devis envoyé', 'signe' => 'Signé'],
    'envoye', 'expire' => ['signe' => 'Signé'],
    default => [],
};
?>
<?php foreach ($boutons as $action => $libelle): ?>
<form method="post" action="<?= url('contrat_action') ?>" class="enligne">
  <?= champ_csrf() ?>
  <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
  <input type="hidden" name="action" value="<?= e($action) ?>">
  <input type="hidden" name="retour" value="<?= e($retour) ?>">
  <button class="<?= $action === 'signe' ? 'succes' : '' ?>"><?= e($libelle) ?></button>
</form>
<?php endforeach; ?>

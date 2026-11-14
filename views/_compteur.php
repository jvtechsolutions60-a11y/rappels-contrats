<?php
// Compteur de jours avant la fin. Attend $c (date_fin, statut) ; $compteurGros optionnel.
$jc = jours_restants($c['date_fin']);
$niveau = niveau_urgence($jc, $c['statut']);
?>
<div class="compteur compteur-<?= e($niveau) ?><?= !empty($compteurGros) ? ' compteur-gros' : '' ?>" title="Fin le <?= date_fr($c['date_fin']) ?>, <?= e(texte_jours($jc)) ?>">
  <?php if ($niveau === 'clos'): ?>
    <span class="compteur-jours"><?= e(libelle_statut($c['statut'])) ?></span>
  <?php else: ?>
    <span class="compteur-jours"><?= e(texte_compteur($jc)) ?></span>
  <?php endif; ?>
  <span class="compteur-date"><?= $jc < 0 && $niveau !== 'clos' ? 'dépassé' : date_fr($c['date_fin']) ?></span>
</div>

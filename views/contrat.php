<?php $c = $contrat; $jours = jours_restants($c['date_fin']); $ouvert = in_array($c['statut'], STATUTS_OUVERTS, true); ?>
<p class="fil"><a href="<?= url('contrats') ?>">← Contrats</a></p>
<h1><?= e($c['client_nom']) ?> – <?= e($c['produit']) ?></h1>

<section class="carte">
  <p class="statut-gros"><span class="statut statut-<?= e($c['statut']) ?>"><?= e(libelle_statut($c['statut'])) ?></span>
    <?php if ($ouvert): ?><span class="<?= $jours < 0 ? 'retard' : '' ?>">Fin <?= e(texte_jours($jours)) ?></span><?php endif; ?></p>
  <div class="actions"><?php $retour = 'contrat'; require __DIR__ . '/_actions.php'; ?></div>

  <?php if ($c['statut'] === 'signe' && !$c['contrat_suivant_id']): ?>
    <div class="proposition">
      <p>Créer tout de suite le contrat de la période suivante, avec une fin au <strong><?= date_fr(ajouter_mois($c['date_fin'], (int) $c['duree_mois'])) ?></strong> ?</p>
      <form method="post" action="<?= url('contrat_action') ?>">
        <?= champ_csrf() ?>
        <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
        <input type="hidden" name="action" value="suivant">
        <button class="principal">Créer le contrat suivant</button>
      </form>
    </div>
  <?php elseif ($c['contrat_suivant_id']): ?>
    <p>Suite : <a href="<?= url('contrat', ['id' => $c['contrat_suivant_id']]) ?>">contrat de la période suivante</a></p>
  <?php endif; ?>

  <dl class="infos">
    <dt>Client</dt><dd><a href="<?= url('client', ['id' => $c['client_id']]) ?>"><?= e($c['client_nom']) ?></a></dd>
    <dt>Type</dt><dd><?= e($c['type_nom']) ?> <small>(rappels <?= e(texte_jalons($c['jalons'])) ?>)</small></dd>
    <dt>Produit</dt><dd><?= e($c['produit']) ?></dd>
    <dt>Date de fin</dt><dd><?= date_fr($c['date_fin']) ?></dd>
    <?php if ($c['montant'] !== null): ?><dt>Montant</dt><dd><?= e(montant_fr($c['montant'])) ?></dd><?php endif; ?>
    <?php if ($c['date_envoi_devis']): ?><dt>Devis envoyé le</dt><dd><?= date_fr($c['date_envoi_devis']) ?></dd><?php endif; ?>
    <?php if ($c['date_signature']): ?><dt>Signé le</dt><dd><?= date_fr($c['date_signature']) ?></dd><?php endif; ?>
    <?php if ($c['note']): ?><dt>Note</dt><dd><?= nl2br(e($c['note'])) ?></dd><?php endif; ?>
  </dl>

  <p class="liens">
    <a class="bouton" href="<?= url('contrat_form', ['id' => $c['id']]) ?>">Modifier</a>
    <?php if ($ouvert): ?>
    <form method="post" action="<?= url('contrat_action') ?>" class="enligne">
      <?= champ_csrf() ?>
      <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
      <input type="hidden" name="action" value="non_renouvele">
      <button>Le client ne renouvelle pas</button>
    </form>
    <?php elseif (in_array($c['statut'], ['signe', 'non_renouvele'], true)): ?>
    <form method="post" action="<?= url('contrat_action') ?>" class="enligne">
      <?= champ_csrf() ?>
      <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
      <input type="hidden" name="action" value="rouvrir">
      <button>Rouvrir (reprendre les rappels)</button>
    </form>
    <?php endif; ?>
  </p>
</section>

<section>
  <h2>Historique</h2>
  <ul class="historique">
  <?php foreach ($historique as $h): ?>
    <li><time><?= date_fr($h['cree_le']) ?> <?= substr($h['cree_le'], 11, 5) ?></time> <?= e($h['detail'] ?? $h['evenement']) ?></li>
  <?php endforeach; ?>
  </ul>
</section>

<form method="post" action="<?= url('contrat_action') ?>" class="zone-danger" data-confirmer="Supprimer définitivement ce contrat et son historique ?">
  <?= champ_csrf() ?>
  <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
  <input type="hidden" name="action" value="supprimer">
  <button class="danger">Supprimer ce contrat</button>
</form>

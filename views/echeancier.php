<?php
$nomsMois = [1 => 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
$totalAnnee = array_sum(array_column($mois, 'total'));
$maxMois = max(1.0, max(array_column($mois, 'total')));
?>
<h1>Échéancier</h1>
<p class="note">Les contrats qui arrivent à échéance sur les 12 prochains mois. Montant à renouveler sur la période :
  <strong><?= e(montant_fr((string) $totalAnnee)) ?> HT</strong> (contrats pas encore signés).</p>

<?php
$groupes = [];
if ($retard['contrats']) {
    $groupes['Déjà dépassés'] = $retard;
}
foreach ($mois as $cle => $g) {
    [$a, $m] = explode('-', $cle);
    $groupes[ucfirst($nomsMois[(int) $m]) . ' ' . $a] = $g + ['ancre' => 'm-' . $cle];
}
foreach ($groupes as $titreMois => $g): ?>
<section class="mois <?= $g['contrats'] ? '' : 'mois-vide' ?>"<?= isset($g['ancre']) ? ' id="' . e($g['ancre']) . '"' : '' ?>>
  <h2><?= e($titreMois) ?>
    <span class="mois-total"><?= $g['nb_ouverts'] ? $g['nb_ouverts'] . ' à renouveler' . ($g['total'] > 0 ? ' · ' . e(montant_fr((string) $g['total'])) : '') : ($g['contrats'] ? 'tout est signé' : 'rien') ?></span></h2>
  <?php if ($g['total'] > 0): ?><div class="jauge"><span style="width: <?= (int) round(100 * $g['total'] / $maxMois) ?>%"></span></div><?php endif; ?>
  <?php if ($g['contrats']): ?>
  <ul class="taches">
    <?php foreach ($g['contrats'] as $c): ?>
    <li>
      <?php require __DIR__ . '/_compteur.php'; ?>
      <div class="tache-texte">
        <a href="<?= url('contrat', ['id' => $c['id']]) ?>"><strong><?= e($c['client_nom']) ?></strong> – <?= e($c['produit']) ?></a>
        <br><small><?= e($c['type_nom']) ?><?= $c['montant'] !== null ? ' · ' . e(montant_fr($c['montant'])) : '' ?></small>
      </div>
      <span class="statut statut-<?= e($c['statut']) ?>"><?= e(libelle_statut($c['statut'])) ?></span>
    </li>
    <?php endforeach; ?>
  </ul>
  <?php endif; ?>
</section>
<?php endforeach; ?>

<?php
// Bloc « Finances » de l'accueil. Attend $finances et $chiffres.
$f = $finances;
$m = fn (float $v) => number_format($v, 0, ',', ' ') . ' €';
$pct = $f['objectif'] > 0 ? (int) round(100 * $f['portefeuille'] / $f['objectif']) : null;
$nomsMois = [1 => 'janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];
$maxMois = max(1.0, ...array_map('array_sum', array_values($f['mois'])));
$libellesStatut = ['signe' => 'Signé', 'envoye' => 'Devis envoyé', 'a_envoyer' => 'À faire', 'expire' => 'En retard'];
$nbClients = count($f['clients']);
$pluriel = fn (int $n, string $mot) => $n . ' ' . $mot . ($n > 1 ? 's' : '');

// Répartition par type (seulement les types utilisés)
$types = array_values(array_filter($f['types'], fn ($t) => $t['nb'] > 0));
foreach ($types as $i => &$t) { $t['couleur'] = PALETTE[$i % count(PALETTE)]; }
unset($t);
$idsTypes = array_keys(array_filter($f['types'], fn ($t) => $t['nb'] > 0));

// Top 5 clients + « Autres »
$top = [];
$i = 0;
foreach ($f['top'] as $nom => $montant) { $top[] = ['nom' => $nom, 'valeur' => $montant, 'couleur' => PALETTE[$i++ % count(PALETTE)]]; }
$autres = $f['portefeuille'] - array_sum(array_column($top, 'valeur'));
if ($autres > 0.5) { $top[] = ['nom' => 'Autres clients', 'valeur' => $autres, 'couleur' => '#b0bac3']; }
?>
<h2 class="bandeau">Finances</h2>

<div class="tuiles">
  <div class="tuile tuile-pleine tuile-ca">
    <span class="tuile-titre">CA récurrent annuel (HT)</span>
    <span class="tuile-geant"><?= e(number_format($f['portefeuille'], 0, ',', ' ')) ?><small> €</small></span>
    <span class="tuile-sous"><?= $pluriel($f['nb_portefeuille'], 'contrat') ?> en portefeuille</span>
    <?php if ($pct !== null): ?>
      <div class="objectif-ligne">
        <div class="jauge-blanche"><span style="width: <?= min(100, $pct) ?>%"></span></div>
        <span><strong><?= $pct ?> %</strong> de l'objectif <?= $m($f['objectif']) ?>
          <?= $pct >= 100 ? '· atteint, bravo !' : '· reste ' . $m(max(0, $f['objectif'] - $f['portefeuille'])) ?>
          · <a href="<?= url('reglages') ?>#objectif">modifier</a></span>
      </div>
    <?php else: ?>
      <form method="post" action="<?= url('reglages') ?>" class="objectif-form">
        <?= champ_csrf() ?>
        <input type="hidden" name="action" value="objectif">
        <input type="hidden" name="retour" value="accueil">
        <label for="objectif_annuel">Votre objectif de CA récurrent par an</label>
        <span class="objectif-saisie"><input id="objectif_annuel" name="objectif_annuel" inputmode="decimal" placeholder="ex. 20 000 €" required><button>Fixer l'objectif</button></span>
      </form>
    <?php endif; ?>
  </div>

  <div class="tuile tuile-pleine">
    <span class="tuile-titre">Clients sous contrat</span>
    <span class="tuile-grand"><?= $nbClients ?></span>
  </div>

  <a class="tuile <?= $f['nb_a_securiser'] ? 'tuile-attention' : '' ?>" href="<?= url('contrats', ['periode' => '30']) ?>">
    <span class="tuile-titre">À sécuriser sous 90 jours</span>
    <span class="tuile-moyen"><?= $m($f['a_securiser']) ?></span>
    <span class="tuile-pied"><span><?= $pluriel($f['nb_a_securiser'], 'contrat') ?> à renouveler</span></span>
  </a>

  <div class="tuile">
    <span class="tuile-titre">Bilan <?= e($f['annee']) ?></span>
    <span class="tuile-moyen"><?= $chiffres['taux'] === null ? '–' : $chiffres['taux'] . ' %' ?></span>
    <span class="tuile-sous-gris">taux de renouvellement</span>
    <span class="tuile-pied">
      <span>Renouvelés<br><b class="positif"><?= $f['renouvele'] > 0 ? '+' : '' ?><?= $m($f['renouvele']) ?></b></span>
      <span>Perdus<br><b class="<?= $f['perdu'] > 0 ? 'negatif' : '' ?>"><?= $f['perdu'] > 0 ? '−' : '' ?><?= $m($f['perdu']) ?></b></span>
    </span>
  </div>

  <a class="tuile <?= $f['nb_retard'] ? 'tuile-alerte' : '' ?>" href="<?= url('contrats', ['statut' => 'expire']) ?>">
    <span class="tuile-titre">En retard</span>
    <span class="tuile-moyen"><?= $m($f['retard']) ?></span>
    <span class="tuile-pied"><span><?= $f['nb_retard'] ? $pluriel($f['nb_retard'], 'contrat') . ' à rattraper' : 'Aucun retard' ?></span></span>
  </a>
</div>
<?php if ($f['sans_montant']): ?><p class="note"><?= $pluriel($f['sans_montant'], 'contrat') ?> sans montant : ajoutez-le pour un total juste.</p><?php endif; ?>

<div class="grille-2">
  <section class="panneau">
    <h3 class="panneau-titre">Répartition par type de contrat</h3>
    <?php if (!$types): ?>
      <p class="note">Aucun contrat en cours.</p>
    <?php else: ?>
    <div class="donut-bloc">
      <?= donut_svg(array_map(fn ($t) => ['valeur' => $t['montant'], 'couleur' => $t['couleur']], $types)) ?>
      <table class="legende-table">
        <thead><tr><th>Type</th><th>Clients</th><th>CA / an</th></tr></thead>
        <tbody>
        <?php foreach ($types as $k => $t): ?>
          <tr>
            <td><a href="<?= url('contrats', ['type' => $idsTypes[$k], 'statut' => 'tous']) ?>"><i style="background: <?= e($t['couleur']) ?>"></i><?= e($t['nom']) ?></a>
              <?php if ($t['nb'] > count($t['clients'])): ?><small>(<?= $t['nb'] ?> contrats)</small><?php endif; ?></td>
            <td><?= count($t['clients']) ?></td>
            <td><?= $m($t['montant']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot><tr><td>Total</td><td><?= $nbClients ?></td><td><?= $m($f['portefeuille']) ?></td></tr></tfoot>
      </table>
    </div>
    <?php endif; ?>
  </section>

  <section class="panneau">
    <h3 class="panneau-titre">Top clients</h3>
    <?php if (!$top || $f['portefeuille'] <= 0): ?>
      <p class="note">Ajoutez des montants à vos contrats pour voir vos principaux clients.</p>
    <?php else: ?>
    <div class="donut-bloc">
      <?= donut_svg($top) ?>
      <table class="legende-table">
        <thead><tr><th>Client</th><th>Part</th><th>CA / an</th></tr></thead>
        <tbody>
        <?php foreach ($top as $c): ?>
          <tr>
            <td><i style="background: <?= e($c['couleur']) ?>"></i><?= e($c['nom']) ?></td>
            <td><?= (int) round(100 * $c['valeur'] / $f['portefeuille']) ?> %</td>
            <td><?= $m($c['valeur']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php if ($top[0]['valeur'] / $f['portefeuille'] > 0.4): ?>
      <p class="avertissement">Un seul client pèse <?= (int) round(100 * $top[0]['valeur'] / $f['portefeuille']) ?> % de votre CA récurrent : attention à la dépendance.</p>
    <?php endif; ?>
    <?php endif; ?>
  </section>
</div>

<section class="panneau">
  <h3 class="panneau-titre">Échéances des 12 prochains mois</h3>
  <div class="histo" role="img" aria-label="Montant arrivant à échéance chaque mois, par statut">
    <?php foreach ($f['mois'] as $cle => $parts):
        $total = array_sum($parts);
        [$a, $mo] = explode('-', $cle); ?>
      <a class="histo-col" href="<?= url('echeancier') ?>" title="<?= e(ucfirst($nomsMois[(int) $mo]) . ' ' . $a . ' : ' . $m($total)) ?>">
        <span class="histo-val"><?= $total > 0 ? e(number_format($total, 0, ',', ' ')) . ' €' : '' ?></span>
        <span class="histo-barre" style="height: <?= round(100 * $total / $maxMois) ?>%">
          <?php foreach (['expire', 'a_envoyer', 'envoye', 'signe'] as $st): if ($parts[$st] > 0): ?>
            <span class="histo-part part-<?= $st ?>" style="flex-grow: <?= $parts[$st] ?>"></span>
          <?php endif; endforeach; ?>
        </span>
        <span class="histo-mois"><?= e($nomsMois[(int) $mo]) ?><?= (int) $mo === 1 ? '<br>' . e($a) : '' ?></span>
      </a>
    <?php endforeach; ?>
  </div>
  <p class="legende">
    <?php foreach (['signe', 'envoye', 'a_envoyer', 'expire'] as $st): ?>
      <span><i class="part-<?= $st ?>"></i><?= e($libellesStatut[$st]) ?></span>
    <?php endforeach; ?>
  </p>
</section>

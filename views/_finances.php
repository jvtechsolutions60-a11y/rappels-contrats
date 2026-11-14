<?php
// Bloc « Finances » de l'accueil. Attend $finances et $chiffres.
$f = $finances;
$m = fn (float $v) => number_format($v, 0, ',', ' ') . ' €';
$pct = $f['objectif'] > 0 ? (int) round(100 * $f['portefeuille'] / $f['objectif']) : null;
$nomsMois = [1 => 'janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];
$maxMois = max(1.0, ...array_map('array_sum', array_values($f['mois'])));
$libellesStatut = ['signe' => 'Signé', 'envoye' => 'Devis envoyé', 'a_envoyer' => 'À faire', 'expire' => 'En retard'];
?>
<section class="finances">
  <h2>Finances</h2>

  <div class="carte objectif">
    <div class="objectif-tete">
      <div>
        <span class="chiffre-valeur"><?= $m($f['portefeuille']) ?></span>
        <span class="chiffre-libelle">de CA récurrent par an (HT), <?= $f['nb_portefeuille'] ?> contrat<?= $f['nb_portefeuille'] > 1 ? 's' : '' ?></span>
      </div>
      <?php if ($pct !== null): ?>
        <div class="objectif-pct <?= $pct >= 100 ? 'atteint' : '' ?>"><?= $pct ?> %<small>de l'objectif <?= $m($f['objectif']) ?></small></div>
      <?php endif; ?>
    </div>
    <?php if ($pct !== null): ?>
      <div class="jauge jauge-objectif"><span style="width: <?= min(100, $pct) ?>%"></span></div>
      <p class="note"><?= $pct >= 100 ? 'Objectif atteint, bravo. Pensez à le relever.' : 'Il manque ' . $m(max(0, $f['objectif'] - $f['portefeuille'])) . ' de contrats récurrents pour l\'atteindre.' ?>
        <a href="<?= url('reglages') ?>#objectif">Modifier l'objectif</a></p>
    <?php else: ?>
      <form method="post" action="<?= url('reglages') ?>" class="objectif-form">
        <?= champ_csrf() ?>
        <input type="hidden" name="action" value="objectif">
        <input type="hidden" name="retour" value="accueil">
        <label>Fixez-vous un objectif de CA récurrent annuel (€ HT)
          <span class="date-ligne"><input name="objectif_annuel" inputmode="decimal" placeholder="ex. 15 000" required><button class="principal">Enregistrer</button></span></label>
      </form>
    <?php endif; ?>
    <?php if ($f['sans_montant']): ?><p class="note"><?= $f['sans_montant'] ?> contrat<?= $f['sans_montant'] > 1 ? 's' : '' ?> sans montant : ajoutez-le pour un total juste.</p><?php endif; ?>
  </div>

  <div class="chiffres">
    <a class="chiffre <?= $f['nb_a_securiser'] ? 'chiffre-attention' : '' ?>" href="<?= url('contrats', ['periode' => '30']) ?>">
      <span class="chiffre-valeur"><?= $m($f['a_securiser']) ?></span>
      <span class="chiffre-libelle">à sécuriser dans les 90 jours (<?= $f['nb_a_securiser'] ?> contrat<?= $f['nb_a_securiser'] > 1 ? 's' : '' ?>)</span>
    </a>
    <a class="chiffre <?= $f['nb_retard'] ? 'chiffre-alerte' : '' ?>" href="<?= url('contrats', ['statut' => 'expire']) ?>">
      <span class="chiffre-valeur"><?= $m($f['retard']) ?></span>
      <span class="chiffre-libelle">en retard (<?= $f['nb_retard'] ?> contrat<?= $f['nb_retard'] > 1 ? 's' : '' ?>)</span>
    </a>
    <div class="chiffre chiffre-bilan">
      <span><span class="chiffre-valeur positif">+<?= $m($f['renouvele']) ?></span> <span class="chiffre-libelle">renouvelés en <?= e($f['annee']) ?></span></span>
      <span><span class="chiffre-valeur negatif"><?= $f['perdu'] > 0 ? '−' : '' ?><?= $m($f['perdu']) ?></span> <span class="chiffre-libelle">perdus en <?= e($f['annee']) ?></span></span>
      <span class="chiffre-libelle">Taux de renouvellement sur 12 mois : <strong><?= $chiffres['taux'] === null ? '–' : $chiffres['taux'] . ' %' ?></strong></span>
    </div>
  </div>

  <div class="finances-bas">
    <div class="carte">
      <h3>Échéances des 12 prochains mois</h3>
      <div class="histo" role="img" aria-label="Montant arrivant à échéance chaque mois, par statut">
        <?php foreach ($f['mois'] as $cle => $parts):
            $total = array_sum($parts);
            [$a, $mo] = explode('-', $cle); ?>
          <a class="histo-col" href="<?= url('echeancier') ?>" title="<?= e(ucfirst($nomsMois[(int) $mo]) . ' ' . $a . ' : ' . $m($total)) ?>">
            <span class="histo-val"><?= $total > 0 ? e(number_format($total / 1000, $total < 10000 ? 1 : 0, ',', ' ')) . ' k' : '' ?></span>
            <span class="histo-barre" style="height: <?= round(100 * $total / $maxMois) ?>%">
              <?php foreach (['expire', 'a_envoyer', 'envoye', 'signe'] as $st): if ($parts[$st] > 0): ?>
                <span class="histo-part part-<?= $st ?>" style="flex-grow: <?= $parts[$st] ?>"></span>
              <?php endif; endforeach; ?>
            </span>
            <span class="histo-mois"><?= e($nomsMois[(int) $mo]) ?></span>
          </a>
        <?php endforeach; ?>
      </div>
      <p class="legende">
        <?php foreach (['signe', 'envoye', 'a_envoyer', 'expire'] as $st): ?>
          <span><i class="part-<?= $st ?>"></i><?= e($libellesStatut[$st]) ?></span>
        <?php endforeach; ?>
      </p>
    </div>

    <div class="carte">
      <h3>Top 5 clients</h3>
      <?php if (!$f['top'] || $f['portefeuille'] <= 0): ?>
        <p class="note">Ajoutez des montants à vos contrats pour voir vos principaux clients.</p>
      <?php else: ?>
        <ol class="top-clients">
          <?php foreach ($f['top'] as $nom => $montant): $part = (int) round(100 * $montant / $f['portefeuille']); ?>
            <li>
              <span class="top-nom"><?= e($nom) ?></span>
              <span class="top-montant"><?= $m($montant) ?> <small><?= $part ?> %</small></span>
              <span class="jauge"><span style="width: <?= $part ?>%"></span></span>
            </li>
          <?php endforeach; ?>
        </ol>
        <?php $premier = reset($f['top']); if ($premier / $f['portefeuille'] > 0.4): ?>
          <p class="note retard">Attention : un seul client pèse plus de 40 % de votre CA récurrent.</p>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</section>

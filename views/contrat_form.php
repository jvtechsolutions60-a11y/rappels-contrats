<?php
$date = $valeurs['date_fin'];
$dateLue = lire_date((string) $date);
$dateChamp = $dateLue ? date_fr($dateLue) : $date;
$montant = $valeurs['montant'] === null ? '' : str_replace('.', ',', (string) $valeurs['montant']);
?>
<p class="fil"><a href="<?= $existant ? url('contrat', ['id' => $existant['id']]) : url('contrats') ?>">← Retour</a></p>
<h1><?= e($titre) ?></h1>

<form method="post" class="carte formulaire">
  <?= champ_csrf() ?>
  <label>Client
    <input name="client" list="liste-clients" required autocomplete="off" autofocus value="<?= e($valeurs['client_nom']) ?>" placeholder="Nom du client (créé s'il n'existe pas)">
    <?php if (isset($erreurs['client'])): ?><span class="erreur"><?= e($erreurs['client']) ?></span><?php endif; ?>
  </label>
  <datalist id="liste-clients">
    <?php foreach ($clients as $nom): ?><option value="<?= e($nom) ?>"><?php endforeach; ?>
  </datalist>

  <label>Type de contrat
    <select name="type_id" id="type">
      <?php foreach ($types as $t): ?>
        <option value="<?= (int) $t['id'] ?>" data-produit="<?= e($t['produit_defaut']) ?>" <?= (int) $valeurs['type_id'] === (int) $t['id'] ? 'selected' : '' ?>><?= e($t['nom']) ?></option>
      <?php endforeach; ?>
    </select>
    <?php if (isset($erreurs['type_id'])): ?><span class="erreur"><?= e($erreurs['type_id']) ?></span><?php endif; ?>
  </label>

  <label>Produit
    <input name="produit" id="produit" required value="<?= e($valeurs['produit']) ?>">
    <?php if (isset($erreurs['produit'])): ?><span class="erreur"><?= e($erreurs['produit']) ?></span><?php endif; ?>
  </label>

  <div class="champ-date">
    <label for="date_fin">Date de fin</label>
    <div class="date-ligne">
      <input type="text" id="date_fin" name="date_fin" required inputmode="numeric" autocomplete="off" placeholder="JJ/MM/AA" value="<?= e($dateChamp) ?>">
      <span class="raccourcis">
        <button type="button" data-ans="1">+1 an</button>
        <button type="button" data-ans="2">+2 ans</button>
        <button type="button" data-ans="3">+3 ans</button>
      </span>
    </div>
    <small id="date-apercu" class="date-apercu" aria-live="polite"></small>
    <?php if (isset($erreurs['date_fin'])): ?><span class="erreur"><?= e($erreurs['date_fin']) ?></span><?php endif; ?>
    <?php if (!empty($demanderConfirmation)): ?>
      <label class="case"><input type="checkbox" name="confirmer_date" value="1"> Oui, cette date est correcte</label>
    <?php endif; ?>
  </div>

  <details <?= isset($erreurs['montant']) || isset($erreurs['statut']) || isset($erreurs['preavis_jours']) || ($existant && ($valeurs['note'] || $montant !== '' || (string) ($valeurs['preavis_jours'] ?? '') !== '')) ? 'open' : '' ?>>
    <summary>Plus de détails</summary>
    <label>Montant (€ HT)
      <input name="montant" inputmode="decimal" value="<?= e($montant) ?>">
      <?php if (isset($erreurs['montant'])): ?><span class="erreur"><?= e($erreurs['montant']) ?></span><?php endif; ?>
    </label>
    <label>Préavis de résiliation (jours avant la fin)
      <input name="preavis_jours" inputmode="numeric" value="<?= e((string) ($valeurs['preavis_jours'] ?? '')) ?>" placeholder="ex. 90, vide si aucun">
      <small>Pour un contrat à reconduction tacite : vous serez prévenu avant la date limite pour résilier.</small>
      <?php if (isset($erreurs['preavis_jours'])): ?><span class="erreur"><?= e($erreurs['preavis_jours']) ?></span><?php endif; ?>
    </label>
    <label>Note
      <textarea name="note" rows="3"><?= e($valeurs['note']) ?></textarea>
    </label>
    <?php if ($existant): ?>
    <label>Statut
      <select name="statut">
        <?php foreach (STATUTS as $cle => $libelle): ?>
          <option value="<?= e($cle) ?>" <?= $valeurs['statut'] === $cle ? 'selected' : '' ?>><?= e($libelle) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <?php endif; ?>
  </details>

  <div class="boutons">
    <button class="principal">Enregistrer</button>
    <?php if (!$existant): ?><button name="encore" value="1">Enregistrer et en saisir un autre</button><?php endif; ?>
  </div>
</form>

<?php
$date = $valeurs['date_fin'];
// Le champ date du navigateur attend AAAA-MM-JJ
$dateChamp = lire_date((string) $date) ?? $date;
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

  <label>Date de fin
    <input type="date" name="date_fin" required value="<?= e($dateChamp) ?>">
    <?php if (isset($erreurs['date_fin'])): ?><span class="erreur"><?= e($erreurs['date_fin']) ?></span><?php endif; ?>
  </label>

  <details <?= isset($erreurs['montant']) || isset($erreurs['statut']) || ($existant && ($valeurs['note'] || $montant !== '')) ? 'open' : '' ?>>
    <summary>Plus de détails</summary>
    <label>Montant (€ HT)
      <input name="montant" inputmode="decimal" value="<?= e($montant) ?>">
      <?php if (isset($erreurs['montant'])): ?><span class="erreur"><?= e($erreurs['montant']) ?></span><?php endif; ?>
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

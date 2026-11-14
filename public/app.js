// Préremplit le produit selon le type choisi, tant que l'utilisateur ne l'a pas modifié lui-même
(function () {
  var type = document.getElementById('type');
  var produit = document.getElementById('produit');
  if (!type || !produit) return;
  var produitDefaut = function () {
    return type.options[type.selectedIndex].getAttribute('data-produit') || '';
  };
  var touche = produit.value !== '' && produit.value !== produitDefaut();
  produit.addEventListener('input', function () { touche = true; });
  type.addEventListener('change', function () {
    if (!touche || produit.value === '') {
      produit.value = produitDefaut();
      touche = false;
    }
  });
})();

// Demande confirmation avant une action irréversible
document.querySelectorAll('form[data-confirmer]').forEach(function (form) {
  form.addEventListener('submit', function (e) {
    if (!confirm(form.getAttribute('data-confirmer'))) e.preventDefault();
  });
});

// Date de fin : raccourcis « +1 an » et aperçu en clair de la date tapée
(function () {
  var champ = document.getElementById('date_fin');
  var apercu = document.getElementById('date-apercu');
  if (!champ || !apercu) return;
  var deux = function (n) { return (n < 10 ? '0' : '') + n; };
  var formater = function (d) { return deux(d.getDate()) + '/' + deux(d.getMonth() + 1) + '/' + d.getFullYear(); };

  var lire = function (texte) {
    texte = texte.trim();
    var m = texte.match(/^(\d{4})-(\d{2})-(\d{2})$/);
    if (m) return valide(+m[3], +m[2], +m[1]);
    m = texte.match(/^(\d{2})(\d{2})(\d{2}|\d{4})$/);
    if (!m) m = texte.match(/^(\d{1,2})[\/.\-\s]+(\d{1,2})[\/.\-\s]+(\d{2}|\d{4})$/);
    if (!m) return null;
    var annee = +m[3];
    if (m[3].length === 2) annee += 2000;
    return valide(+m[1], +m[2], annee);
  };
  var valide = function (j, mois, a) {
    var d = new Date(a, mois - 1, j);
    return d.getFullYear() === a && d.getMonth() === mois - 1 && d.getDate() === j ? d : null;
  };

  var maj = function () {
    apercu.className = 'date-apercu';
    if (champ.value.trim() === '') { apercu.textContent = 'Exemples : 13/06/27, 13062027'; return; }
    var d = lire(champ.value);
    if (!d) { apercu.textContent = 'Date non reconnue'; apercu.classList.add('urgent'); return; }
    var auj = new Date(); auj.setHours(0, 0, 0, 0);
    var jours = Math.round((d - auj) / 86400000);
    var texte = jours === 0 ? "aujourd'hui" : jours < 0 ? 'déjà passée de ' + (-jours) + ' j' : 'dans ' + jours + ' j';
    apercu.textContent = formater(d) + ' : ' + texte;
    apercu.classList.add(jours < 15 ? 'urgent' : jours < 60 ? 'bientot' : 'loin');
    if (jours < 0 || jours > 5 * 366) apercu.textContent += ' ⚠ vérifiez la date';
  };

  document.querySelectorAll('[data-ans]').forEach(function (bouton) {
    bouton.addEventListener('click', function () {
      var d = new Date();
      d.setFullYear(d.getFullYear() + parseInt(bouton.getAttribute('data-ans'), 10));
      champ.value = formater(d);
      maj();
      champ.focus();
    });
  });
  champ.addEventListener('input', maj);
  maj();
})();

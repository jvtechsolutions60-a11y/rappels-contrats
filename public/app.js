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

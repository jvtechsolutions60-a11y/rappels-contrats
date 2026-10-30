# Rappels de contrats

Petite application web qui rappelle au gérant les renouvellements de contrats (licences antivirus, maintenance, garanties) au bon moment. PHP 8 + MySQL, prévue pour un hébergement mutualisé O2switch.

Cahier des charges du MVP : https://claude.ai/artifact/GYrwmAfJ9BWXiGuQsLrKan

## Où en est le développement

| Étape | Contenu | État |
| --- | --- | --- |
| 1 | Connexion, contrats (saisie, fiche, liste, statuts, contrat suivant), clients, types de contrat | Fait |
| 2 | Moteur de rappels et e-mail du matin | À faire |
| 3 | Accueil « À faire » et import CSV | À faire |
| 4 | Installation sur O2switch | À faire |
| 5 | Test réel chez JVTECH puis chez 2 clients | À faire |

## Installation sur O2switch

1. Dans cPanel, créer un sous-domaine (ex. `rappels.mondomaine.fr`) dont la racine est le dossier `public/` de l'application.
2. Créer une base MySQL et un utilisateur, puis leur donner tous les droits.
3. Envoyer les fichiers, copier `config.example.php` en `config.php` et y mettre les accès à la base.
4. Activer le certificat HTTPS (Let's Encrypt) sur le sous-domaine.
5. Ouvrir l'adresse : la première visite crée les tables et demande le compte du gérant.

## Tester sur un poste

```
cp config.example.php config.php   # puis choisir la ligne SQLite
php -S 127.0.0.1:8000 -t public
```

## Organisation du code

- `public/` : la seule partie visible depuis le web (page d'entrée, styles, script).
- `src/` : la logique (base de données, statuts, rappels, pages).
- `views/` : les écrans.

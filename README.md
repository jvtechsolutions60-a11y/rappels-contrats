# Rappels de contrats

Petite application web qui rappelle au gérant les renouvellements de contrats (licences antivirus, maintenance, garanties) au bon moment. PHP 8 + MySQL, prévue pour un hébergement mutualisé O2switch.

Cahier des charges du MVP : https://claude.ai/artifact/GYrwmAfJ9BWXiGuQsLrKan

## Où en est le développement

| Étape | Contenu | État |
| --- | --- | --- |
| 1 | Connexion, contrats (saisie, fiche, liste, statuts, contrat suivant), clients, types de contrat | Fait |
| 2 | Moteur de rappels et e-mail du matin | Fait |
| 3 | Accueil « À faire » et import CSV | Fait |
| 4 | Installation sur O2switch | À faire |
| 5 | Test réel chez JVTECH puis chez 2 clients | À faire |

## Installation sur O2switch

1. Dans cPanel, créer un sous-domaine (ex. `rappels.mondomaine.fr`) dont la racine est le dossier `public/` de l'application.
2. Créer une base MySQL et un utilisateur, puis leur donner tous les droits.
3. Créer une boîte e-mail d'envoi (ex. `rappels@mondomaine.fr`) et vérifier que SPF et DKIM sont actifs (cPanel, « Délivrabilité des e-mails »).
4. Envoyer les fichiers, puis en SSH lancer `composer install --no-dev` dans le dossier de l'application.
5. Copier `config.example.php` en `config.php` et y mettre l'adresse de l'application, les accès à la base et ceux de la boîte d'envoi.
6. Activer le certificat HTTPS (Let's Encrypt) sur le sous-domaine.
7. Ouvrir l'adresse : la première visite crée les tables et demande le compte du gérant.
8. Dans Réglages, cliquer « Envoyer un e-mail de test ».
9. Dans cPanel, « Tâches cron », ajouter une tâche chaque jour à 7 h 00 :
   `php /home/COMPTE/CHEMIN/bin/rappels-du-matin.php`

Pour voir l'e-mail du jour sans l'envoyer : `php bin/rappels-du-matin.php --apercu`.

## Comment les rappels fonctionnent

- Chaque type de contrat a ses jalons (ex. 90, 60, 30, 15, 0 jours avant la fin).
- Un contrat « À envoyer » ou « Envoyé » remonte dans l'e-mail le jour où il atteint un jalon, une seule fois par jalon. S'il en a sauté plusieurs (saisie tardive, week-end), seul le plus récent compte.
- Un contrat dont la date de fin est dépassée sans signature passe « Expiré » et remonte tous les jours.
- « Signé » et « Non renouvelé » arrêtent les rappels. Changer la date de fin relance les jalons.
- Pas d'e-mail le week-end ni les jours sans rien à signaler. Si l'envoi échoue, rien n'est noté comme envoyé et un bandeau rouge s'affiche dans l'application.

## Import CSV

Menu « Import ». Première ligne : `client;type;produit;date_fin;statut;montant` (les deux dernières facultatives). Dates en JJ/MM/AAAA, fichier Excel « CSV point-virgule » accepté tel quel. Un aperçu montre les lignes en erreur et les contrats déjà présents avant d'enregistrer quoi que ce soit.

## Tester sur un poste

```
composer install
cp config.example.php config.php   # puis choisir la ligne SQLite
php -S 127.0.0.1:8000 -t public
```

## Organisation du code

- `public/` : la seule partie visible depuis le web (page d'entrée, styles, script).
- `src/` : la logique (base de données, statuts, rappels, pages).
- `bin/` : le script lancé chaque matin par la tâche cron.
- `views/` : les écrans.

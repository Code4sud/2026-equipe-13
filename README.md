# FindMyWay2Work

**Trouve le métier qui te ressemble, pas celui qu’on t’a collé.**

FindMyWay2Work est une application web d’aide à l’orientation pour les jeunes de 15 à 25 ans. Au lieu d’un test de personnalité interminable, elle fait vivre des situations professionnelles concrètes : le joueur classe des notifications par ordre de priorité, donne son avis, et découvre un top 5 personnalisé.

Projet réalisé lors du **Hackathon Azuréen #3** (Nice, 18 au 20 novembre 2026) — équipe 13.

## Lancer en 5 lignes

```bash
cp config.example.php config.php   # renseigner la BDD et la clé DeepSeek
mysql -u utilisateur -p < database.sql
php -S 127.0.0.1:8000
# ouvrir http://127.0.0.1:8000
```


## Fonctionnalités

- inscription et connexion sécurisées (mot de passe haché, CSRF, requêtes préparées) ;
- profil modifiable : 15 centres d’intérêt, sociabilité, attente vis-à-vis du travail ;
- catalogue de 15 métiers réalistes, recherche et filtre par corps de métier ;
- recommandations automatiques dès qu’un métier partage un centre d’intérêt avec le profil ;
- jeu de mise en situation : 10 à 15 notifications contextuelles (1 à 2 phrases) générées par l’IA à chaque partie, à classer par priorité à la souris ou au doigt ;
- note d’appréciation donnée par le joueur, seule note affichée ;
- top 5 classé uniquement selon la note donnée par le joueur ;
- interface entièrement bilingue FR / EN, y compris en pleine partie ;
- responsive téléphone, tablette et ordinateur (le jeu occupe tout l’écran sur téléphone) ;
- CGU, politique de confidentialité et suppression complète du compte.

## Stack

PHP 8.1+, MySQL / MariaDB, HTML, CSS et JavaScript natif. Aucun framework, aucun outil de compilation.

## Installation

### 1. Base de données

Importer `database.sql` dans phpMyAdmin (ou en ligne de commande) :

```bash
mysql -u utilisateur -p < database.sql
```

La base `findmyway2work` et ses tables sont créées vides, en `utf8mb4`.

### 2. Configuration

```bash
cp config.example.php config.php
```

Renseigner dans `config.php` :

- l’hôte, le nom, l’utilisateur et le mot de passe MySQL ;
- la clé API DeepSeek (`cle_api`).

`config.php` n’est pas versionné : il est ignoré par Git. La clé API reste côté serveur et n’est jamais exposée au navigateur.

### 3. Déploiement

Déposer les fichiers tels quels sur un serveur web compatible PHP (Apache, Nginx + PHP-FPM, etc.). Le site n’a pas besoin de Node.js ni d’un dossier `dist/`.

Extensions PHP nécessaires : `pdo_mysql`, `curl`, `mbstring`.

En local :

```bash
php -S 127.0.0.1:8000
```

Puis ouvrir `http://127.0.0.1:8000`.

### 4. Première consultation

À la première visite, si la table `metiers` est vide, le site demande à l’API DeepSeek de générer les 15 métiers et leurs données bilingues. La génération n’a lieu qu’une fois. Les notifications sont, elles, générées à chaque lancement de partie.

## Utilisation de l’IA

L’API DeepSeek sert uniquement à :

1. générer les 15 métiers (nom, corps de métier, description, intérêts, revenu et contact social cachés) en français et en anglais ;
2. générer les 10 à 15 notifications d’une partie, avec leur ordre de priorité attendu, en français et en anglais. Chaque notification est un mini-scénario de 1 à 2 phrases, avec assez de contexte pour être classé par priorité et importance.

**Aucune donnée utilisateur n’est envoyée à l’API.** Les prompts ne contiennent que le contexte du jeu et les informations du métier. Le calcul des notes, le classement, les recommandations et la traduction de l’interface sont exclusivement gérés par le code.

## Calcul des notes

- écart total `D = Σ |place du joueur − place attendue|` ;
- pire écart `Dmax = ⌊n² / 2⌋` (50 pour 10 notifications, 112 pour 15) ;
- réussite `R = 10 × (1 − D / Dmax)`, arrondie à 0,1.

La note de réussite `R` reste **interne** : elle sert uniquement à mesurer le classement et n’est jamais affichée au joueur. Le top 5 est classé uniquement selon la note d’appréciation `A` que le joueur a lui-même donnée à chaque métier.

## Structure

```text
index.php                 accueil
inscription.php           création de compte
connexion.php             connexion
deconnexion.php           déconnexion (POST + CSRF)
profil.php                profil et suppression du compte
metiers.php               catalogue, recherche, filtre
jeu.php                   mise en situation et notation
resultats.php             top 5 et progression
cgu.php                   conditions générales
confidentialite.php       politique de confidentialité
config.example.php        modèle de configuration
database.sql              schéma MySQL complet
includes/                 bootstrap, PDO, helpers, IA, vues partielles
lang/fr.php, lang/en.php  traductions
assets/                   CSS, JavaScript, favicon
```

## Sécurité

- `password_hash` / `password_verify` et rehachage automatique ;
- requêtes préparées PDO (`ATTR_EMULATE_PREPARES = false`) ;
- jeton CSRF sur tous les formulaires POST ;
- cookies de session `HttpOnly`, `SameSite=Lax`, `Secure` en HTTPS ;
- échappement HTML systématique ;
- suppression du compte en cascade (profil, intérêts, parties, notifications, résultats).

## Licence

MIT — voir `LICENSE`.

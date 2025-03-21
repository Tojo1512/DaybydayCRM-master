# Structure du Projet DaybydayCRM

## Vue d'ensemble
Ce document décrit la structure du projet DaybydayCRM, une application CRM basée sur Laravel.

## Structure des Dossiers

### Dossiers Principaux

- `app/` - Contient le code source principal de l'application
- `config/` - Fichiers de configuration
- `database/` - Migrations et seeds de la base de données
- `public/` - Point d'entrée de l'application et assets publics
- `resources/` - Vues, assets non compilés, et fichiers de traduction
- `routes/` - Définition des routes de l'application
- `storage/` - Fichiers générés par l'application
- `tests/` - Tests unitaires et fonctionnels
- `vendor/` - Dépendances Composer
- `node_modules/` - Dépendances npm

### Dossiers de Configuration

- `.docker/` - Configuration Docker
- `docker-config/` - Fichiers de configuration Docker supplémentaires
- `.github/` - Configuration GitHub (workflows, etc.)
- `bootstrap/` - Fichiers de démarrage de l'application

## Fichiers de Configuration

### Configuration de l'Application
- `.env` - Variables d'environnement (à ne pas commiter)
- `.env.example` - Template des variables d'environnement
- `.env.ci` - Configuration pour l'intégration continue
- `.env.dusk.local` - Configuration pour les tests Dusk

### Configuration Docker
- `docker-compose.yml` - Configuration principale Docker Compose
- `docker-compose.env` - Variables d'environnement Docker
- `Dockerfile` - Instructions de build Docker

### Configuration des Tests
- `phpunit.xml` - Configuration PHPUnit
- `phpunit.dusk.xml` - Configuration PHPUnit pour Dusk

### Configuration du Build
- `buildspec.yml` - Spécification de build AWS
- `appspec.yml` - Spécification de déploiement AWS
- `gulpfile.js` - Configuration Gulp
- `webpack.mix.js` - Configuration Laravel Mix

### Gestion des Dépendances
- `composer.json` - Dépendances PHP
- `composer.lock` - Verrouillage des versions PHP
- `package.json` - Dépendances JavaScript
- `yarn.lock` - Verrouillage des versions JavaScript

### Autres Fichiers
- `artisan` - CLI Laravel
- `server.php` - Serveur de développement PHP
- `.gitignore` - Fichiers ignorés par Git
- `.gitattributes` - Attributs Git
- `readme.md` - Documentation principale du projet

## Notes Importantes

1. Le dossier `storage/` doit être accessible en écriture
2. Le fichier `.env` doit être configuré selon votre environnement
3. Les dépendances doivent être installées via Composer et npm/yarn
4. Les assets doivent être compilés avant le déploiement

## Commandes Utiles

```bash
# Installation des dépendances
composer install
npm install

# Compilation des assets
npm run dev
npm run prod

# Tests
php artisan test
php artisan dusk

# Démarrage du serveur de développement
php artisan serve
``` 
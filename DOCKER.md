# Configuration Docker 🐳

Guide complet pour démarrer le projet Symfony avec Docker Compose.

## 📋 Prérequis

- **Docker** >= 24.0
- **Docker Compose** >= 2.20
- **Git**

## 🚀 Démarrage Rapide

### 1. Cloner le projet et configurer l'environnement

```bash
# Cloner le dépôt
git clone <your-repo-url>
cd assur_quote_symfony

# Copier le fichier d'environnement
cp .env.example .env
```

### 2. Démarrer les conteneurs

```bash
# Construire les images (première fois)
make docker-build

# Démarrer les conteneurs
make docker-up

# Ou utiliser docker compose directement
docker compose up -d
```

### 3. Installer les dépendances et initialiser la BD

```bash
# Installer les dépendances Composer (optionnel, déjà dans le Dockerfile)
make install-deps

# Exécuter les migrations
make db-migrate

# Charger les fixtures (optionnel)
make db-fixtures
```

### 4. Accéder à l'application

- **Application**: http://localhost:8080
- **Mailpit (test email)**: http://localhost:8025
- **Base de données**: localhost:5432

## 📦 Architecture

### Services

| Service      | Image                | Port      | Description                   |
| ------------ | -------------------- | --------- | ----------------------------- |
| **app**      | Custom (PHP 8.4-FPM) | 9000      | Application Symfony           |
| **nginx**    | nginx:1.25-alpine    | 8080      | Reverse proxy / Web server    |
| **database** | postgres:16-alpine   | 5432      | Base de données PostgreSQL    |
| **mailer**   | axllent/mailpit      | 1025/8025 | SMTP local pour développement |

### Structure des fichiers Docker

```
├── Dockerfile              # Multi-stage build pour PHP/Symfony
├── docker-compose.yaml     # Configuration des services
├── compose.override.yaml   # Overrides pour développement
├── docker/
│   ├── nginx/
│   │   └── default.conf   # Configuration Nginx
│   └── php/
│       ├── php.ini        # Configuration PHP
│       └── opcache.ini    # Configuration OPcache
└── .env.example           # Variables d'environnement
```

## 🛠️ Commandes Utiles

### Makefile

```bash
# Démarrage et arrêt
make docker-up              # Démarrer les conteneurs
make docker-down            # Arrêter les conteneurs
make docker-build           # Construire les images

# Gestion de la base de données
make db-migrate             # Exécuter les migrations
make db-fixtures            # Charger les fixtures

# Développement
make docker-shell           # Ouvrir un shell dans le conteneur PHP
make install-deps           # Installer les dépendances
make cache-clear            # Vider le cache Symfony
make docker-logs            # Afficher les logs

# Aliases rapides
make build, make up, make down, make shell, make logs, etc.
```

### Docker Compose directement

```bash
# Afficher les logs
docker compose logs -f

# Afficher les logs d'un service spécifique
docker compose logs -f app
docker compose logs -f nginx
docker compose logs -f database

# Exécuter une commande dans un conteneur
docker compose exec app bash
docker compose exec app php bin/console make:entity

# Arrêter et nettoyer
docker compose down -v    # Supprimer aussi les volumes
```

## 🔒 Variables d'environnement

Modifier le fichier `.env` pour personnaliser la configuration :

```env
APP_ENV=dev                    # dev, prod, test
APP_DEBUG=1                    # 0 ou 1
APP_SECRET=<random-secret>     # Clé secrète de l'app

DB_USER=aksam                  # Utilisateur DB
DB_PASSWORD=aksam_pass         # Mot de passe DB
DB_NAME=assur_quote            # Nom de la BD
DB_ROOT_PASSWORD=root_secret   # Mot de passe root MySQL

MAILER_DSN=smtp://mailer:1025  # Configuration SMTP
DEFAULT_URI=http://localhost:8080
```

## 📧 Webmail (Mailpit)

Mailpit capture les emails envoyés en développement. Accédez à l'interface web :

- **URL**: http://localhost:8025
- **SMTP**: localhost:1025

Tous les emails envoyés par l'application seront visibles dans Mailpit.

## 💾 Gestion des données

### Volumes

- **db_data**: Données persistantes de la base de données
- **app_var**: Dossier var de Symfony (cache, logs, sessions)

### Supprimer les données

```bash
# Arrêter et supprimer tout (y compris les données)
docker compose down -v

# Supprimer un volume spécifique
docker volume rm assur_quote_symfony_db_data
```

### Backup de la base de données

```bash
# Exporter
docker compose exec database mysqldump -u root -proot_secret assur_quote > backup.sql

# Importer
docker compose exec -T database mysql -u root -proot_secret assur_quote < backup.sql
```

## 🐛 Dépannage

### Les conteneurs ne démarrent pas

```bash
# Vérifier les logs
docker compose logs

# Reconstruire les images
docker compose down
docker compose build --no-cache
docker compose up -d
```

### Erreur de connexion à la base de données

```bash
# Vérifier que la BD est prête
docker compose exec database mysql -u root -proot_secret -e "SELECT 1;"

# Réinitialiser la BD
docker compose down -v
docker compose up -d database
sleep 10
docker compose up -d
```

### Problèmes de permissions sur les fichiers

```bash
# Corriger les permissions
docker compose exec app chown -R www-data:www-data var/
```

### Cache ou configuration stale

```bash
# Vider complètement le cache
docker compose exec app rm -rf var/cache/*

# Reconstruire le cache
docker compose exec app php bin/console cache:warmup
```

## 🔧 Développement

### Installer une nouvelle dépendance PHP

```bash
docker compose exec app composer require symfony/asset
```

### Générer une entité Doctrine

```bash
docker compose exec app php bin/console make:entity
```

### Créer une migration

```bash
docker compose exec app php bin/console make:migration
docker compose exec app php bin/console doctrine:migrations:migrate
```

### Debugging

Pour debugger avec Xdebug, modifier `docker/php/php.ini` et redémarrer.

## 📝 Notes d'exploitation

- Les migrations Doctrine sont exécutées automatiquement au démarrage si nécessaire
- Les conteneurs redémarrent automatiquement (`restart: unless-stopped`)
- Les logs Nginx et PHP sont disponibles via `docker compose logs`
- La base de données est persistante même après `docker compose down`

## 🚨 Production

Pour la production :

```bash
# Utiliser compose sans override
docker compose -f docker-compose.yaml build

# Configurer les variables d'environnement
export APP_ENV=prod
export APP_DEBUG=0
export APP_SECRET=<generate-a-secure-random-key>

# Démarrer en production
docker compose up -d
```

Assurez-vous que :

- Les secrets sont configurés correctement
- Les certificats SSL/TLS sont en place
- Nginx est configuré en tant que reverse proxy
- Les logs sont centralisés

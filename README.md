<div align="center">

<img src="public/assets/logo.png" alt="Aksam Assurance" height="80">

# 🛡️ Aksam Assurance — Devis en ligne

**Plateforme de simulation et de gestion de devis d'assurance auto & moto au Maroc**

[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=flat-square&logo=php&logoColor=white)](https://www.php.net)
[![Symfony](https://img.shields.io/badge/Symfony-7.4-000000?style=flat-square&logo=symfony&logoColor=white)](https://symfony.com)
[![EasyAdmin](https://img.shields.io/badge/EasyAdmin-5.0-00B8D9?style=flat-square)](https://symfony.com/bundles/EasyAdminBundle)
[![Doctrine](https://img.shields.io/badge/Doctrine-ORM-orange?style=flat-square)](https://www.doctrine-project.org)
[![MariaDB](https://img.shields.io/badge/MariaDB-10.11-003545?style=flat-square&logo=mariadb&logoColor=white)](https://mariadb.org)
[![License](https://img.shields.io/badge/license-MIT-green?style=flat-square)](LICENSE)

[Fonctionnalités](#-fonctionnalités) · [Démo](#-démo) · [Installation](#-installation) · [Configuration](#%EF%B8%8F-configuration) · [Documentation](#-documentation)

</div>

---

## 📋 Présentation

**Aksam Assurance** est une application web full-stack développée avec **Symfony 7** permettant aux clients de simuler et soumettre des demandes de devis d'assurance auto et moto en ligne. Un back-office EasyAdmin permet aux administrateurs de gérer les devis, compagnies, offres et variations de prix.

---

## ✨ Fonctionnalités

### 👤 Espace Client

| Fonctionnalité | Description |
|---|---|
| 🧙 **Wizard multi-étapes** | Formulaire guidé : infos personnelles → conducteur → véhicule |
| 📊 **Comparateur d'offres** | Affichage et comparaison des offres par compagnie en temps réel |
| 📄 **Récapitulatif devis** | Page de synthèse complète avec statut |
| 📧 **Envoi par email** | Récapitulatif HTML envoyé instantanément par email |
| ⬇️ **Téléchargement PDF** | Export PDF professionnel du devis (Dompdf) |
| 🔒 **URLs sécurisées** | UUID + access token — lien unique non-devinable |

### 🔧 Espace Administrateur

| Fonctionnalité | Description |
|---|---|
| 📋 **Gestion des devis** | Tableau de bord, liste, détail, notes admin, estimation personnalisée |
| 🏢 **Gestion des compagnies** | CRUD complet des compagnies partenaires |
| 🏷️ **Gestion des offres** | Création et configuration des formules d'assurance |
| 📈 **Variations de prix** | Majoration/réduction par compagnie et par offre (% ou MAD fixe) |
| 🏙️ **Gestion des villes** | Référentiel des villes marocaines |
| 🔐 **Authentification** | Login sécurisé avec compte AdminUser dédié |

### 🛡️ Sécurité

- URLs avec **UUID v4** — empêche l'énumération (`/devis/550e8400-...`)
- **Access token 64 caractères** — seul le propriétaire du lien accède à son devis
- Vérification **timing-safe** (`hash_equals`)
- Firewall Symfony séparé pour l'espace admin
- Protection CSRF sur tous les formulaires

---

## 🖥️ Démo

```
Page d'accueil     →  http://localhost:8000/
Formulaire devis   →  http://localhost:8000/devis/nouveau
Espace admin       →  http://localhost:8000/admin
Login admin        →  http://localhost:8000/admin/login
```

---

## 🚀 Installation

### Prérequis

- **PHP** >= 8.2 avec extensions : `ctype`, `iconv`, `pdo_mysql`
- **Composer** >= 2.0
- **MariaDB** >= 10.11 ou **MySQL** >= 8.0
- **Node.js** >= 18 (pour les assets)
- **Symfony CLI** (optionnel, recommandé)

### Cloner le projet

```bash
git clone https://github.com/ton-user/aksam-assurance.git
cd aksam-assurance
```

### Installer les dépendances

```bash
composer install
```

### Configurer l'environnement

```bash
cp .env .env.local
```

Édite `.env.local` :

```env
# Base de données
DATABASE_URL="mysql://user:password@127.0.0.1:3306/aksam_assurance?serverVersion=10.11.2-MariaDB&charset=utf8mb4"

# SMTP (voir section Configuration)
MAILER_DSN=smtp://user:password@smtp.gmail.com:587?encryption=tls

# URL de l'application
DEFAULT_URI=http://localhost:8000
```

### Créer la base de données et exécuter les migrations

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

### Créer le premier administrateur

```bash
php bin/console app:create-admin admin@aksam.ma "Votre Nom" votre_mot_de_passe
```

### Lancer le serveur de développement

```bash
symfony server:start
# ou
php -S localhost:8000 -t public/
```

---

## ⚙️ Configuration

### SMTP — Envoi d'emails

```env
# Gmail (recommandé)
MAILER_DSN=smtp://votre.email@gmail.com:app_password@smtp.gmail.com:587?encryption=tls

# Mailtrap (tests locaux)
MAILER_DSN=smtp://user:pass@sandbox.smtp.mailtrap.io:587

# Désactiver l'envoi (dev)
MAILER_DSN=null://null
```

> ⚠️ Pour Gmail, utilisez un **mot de passe d'application** (Compte Google → Sécurité → Mots de passe des applications).

### Services.yaml — Injection du chemin projet (PDF)

Ajoutez dans `config/services.yaml` :

```yaml
services:
    App\Controller\Web\QuoteWizardController:
        bind:
            string $projectDir: '%kernel.project_dir%'
```

### Doctrine — Naming Strategy

Dans `config/packages/doctrine.yaml` :

```yaml
doctrine:
    orm:
        naming_strategy: doctrine.orm.naming_strategy.underscore_number_aware
```

---

## 📁 Structure du projet

```
aksam-assurance/
├── config/
│   ├── packages/
│   │   ├── doctrine.yaml        # Naming strategy
│   │   ├── mailer.yaml          # Config SMTP
│   │   └── security.yaml        # Firewalls + access control
│   └── services.yaml
│
├── migrations/                  # Historique des migrations Doctrine
│
├── public/
│   └── assets/
│       └── logo.png             # Logo Aksam Assurance
│
├── src/
│   ├── Command/
│   │   ├── CreateAdminCommand.php          # app:create-admin
│   │   └── FixMissingEstimationsCommand.php # app:fix-missing-estimations
│   │
│   ├── Controller/
│   │   ├── Admin/
│   │   │   ├── DashboardController.php
│   │   │   ├── CompanyCrudController.php
│   │   │   ├── OfferCrudController.php
│   │   │   ├── CityCrudController.php
│   │   │   ├── QuoteCrudController.php
│   │   │   ├── CompanyOfferVariationCrudController.php
│   │   │   └── SecurityController.php      # Login/Logout admin
│   │   └── Web/
│   │       ├── HomeController.php
│   │       └── QuoteWizardController.php   # Wizard + PDF + Email
│   │
│   ├── DTO/
│   │   └── QuoteRequest.php                # Validation formulaire
│   │
│   ├── Entity/
│   │   ├── Quote.php                       # UUID + AccessToken
│   │   ├── Company.php
│   │   ├── Offer.php
│   │   ├── City.php
│   │   ├── CompanyOfferVariation.php       # Variations prix
│   │   └── AdminUser.php                   # Auth admin
│   │
│   ├── Enum/
│   │   ├── QuoteStatus.php
│   │   ├── InsuranceType.php
│   │   ├── FuelType.php
│   │   ├── VehiculeBrand.php
│   │   ├── City.php
│   │   └── Company.php
│   │
│   ├── Repository/
│   │   ├── QuoteRepository.php             # findByUuid()
│   │   ├── CompanyOfferVariationRepository.php
│   │   └── AdminUserRepository.php
│   │
│   └── Service/
│       ├── QuoteEstimatorService.php       # Calcul des prix
│       ├── QuoteMapper.php                 # DTO → Entity
│       └── QuoteMailerService.php          # Envoi email
│
└── templates/
    ├── admin/
    │   ├── dashboard.html.twig             # Dashboard redesigné
    │   └── login.html.twig                 # Page login admin
    ├── bundles/TwigBundle/Exception/
    │   ├── error404.html.twig              # Pages d'erreur custom
    │   ├── error403.html.twig
    │   ├── error500.html.twig
    │   └── error.html.twig
    ├── emails/
    │   └── quote_recap.html.twig           # Email HTML récapitulatif
    ├── home/
    │   └── index.html.twig                 # Page d'accueil
    ├── pdf/
    │   └── quote_recap.html.twig           # Template PDF (Dompdf)
    └── quote/
        ├── new.html.twig                   # Formulaire wizard
        ├── offers.html.twig                # Sélection offres
        └── show.html.twig                  # Récapitulatif + modal email
```

---

## 🔧 Commandes utiles

```bash
# Migrations
php bin/console doctrine:migrations:migrate
php bin/console doctrine:migrations:status

# Cache
php bin/console cache:clear
php bin/console cache:warmup

# Créer un administrateur
php bin/console app:create-admin email@domain.com "Nom Prénom" mot_de_passe

# Corriger les estimations manquantes (devis existants sans prix)
php bin/console app:fix-missing-estimations --dry-run   # Simulation
php bin/console app:fix-missing-estimations             # Appliquer

# Tester les pages d'erreur (en dev)
curl http://localhost:8000/_error/404
curl http://localhost:8000/_error/403
curl http://localhost:8000/_error/500

# Vérifier le routage
php bin/console debug:router | grep quote
php bin/console debug:router | grep admin
```

---

## 🗄️ Modèle de données

```
quotes
  ├── id, uuid, access_token
  ├── last_name, first_name, email, phone_number
  ├── city (enum), city_entity_id → cities.id
  ├── company (enum), company_entity_id → companies.id
  ├── birth_date, license_date
  ├── insurance_type, vehicule_brand, fuel_type
  ├── first_registration_date, seat_count
  ├── new_value, market_value, registration_number
  ├── fiscal_power, engine_capacity
  ├── status (draft|submitted|confirmed|accepted|rejected)
  ├── selected_offer, custom_estimation, admin_note
  └── created_at, updated_at

companies          → id, name, is_active, ...
offers             → id, code, title, description, annual_price, is_active
cities             → id, name, is_active
admin_users        → id, email, name, roles, password, is_active
company_offer_variations
  ├── company_id → companies.id
  ├── offer_id   → offers.id
  ├── variation_type (percent|fixed)
  ├── value (ex: 10.00 = +10%, -300.00 = -300 MAD)
  └── is_active
```

---

## 🔄 Flux utilisateur

```
/ (accueil)
  └── /devis/nouveau (formulaire wizard)
        └── /devis/{uuid}/offres?token=... (sélection offre + compagnie)
              └── /devis/{uuid}?token=... (récapitulatif)
                    ├── /devis/{uuid}/envoyer-email (POST → email)
                    └── /devis/{uuid}/pdf?token=... (téléchargement PDF)
```

---

## 🔐 Sécurité des URLs

Chaque devis est accessible via une URL unique et sécurisée :

```
/devis/550e8400-e29b-41d4-a716-446655440000?token=a3f8c2d1e4b5...
        └── UUID v4 (non devinable)         └── 64 chars hex (access token)
```

- **Sans token** → redirection vers 403
- **Token invalide** → 403 "Lien invalide ou expiré"
- **Vérification timing-safe** → protection contre les timing attacks

---

## 📦 Dépendances principales

| Package | Version | Rôle |
|---|---|---|
| `symfony/framework-bundle` | ^7.4 | Framework principal |
| `easycorp/easyadmin-bundle` | ^5.0 | Back-office admin |
| `doctrine/orm` | * | ORM base de données |
| `doctrine/doctrine-migrations-bundle` | * | Migrations BDD |
| `symfony/mailer` | * | Envoi d'emails |
| `symfony/security-bundle` | * | Authentification |
| `symfony/uid` | * | Génération UUID v4 |
| `dompdf/dompdf` | * | Génération PDF |
| `twig/twig` | ^3.0 | Moteur de templates |

---

## 🤝 Contribution

1. Fork le projet
2. Crée ta branche (`git checkout -b feature/ma-fonctionnalite`)
3. Commit tes changements (`git commit -m 'feat: ajouter ma fonctionnalité'`)
4. Push sur la branche (`git push origin feature/ma-fonctionnalite`)
5. Ouvre une Pull Request

---

## 📄 Licence

Ce projet est sous licence **MIT**. Voir le fichier [LICENSE](LICENSE) pour plus de détails.

---

<div align="center">

Développé avec ❤️ par l'équipe **Aksam Assurance**

[aksam.ma](https://aksam.ma) · [contact@aksam-assurance.ma](mailto:contact@aksam-assurance.ma)

</div>

# AssurQuote - Devis d'assurance en ligne

Projet exemple **Symfony 7 + Twig + MySQL** pour la création de devis d'assurance auto/moto avec :

- formulaire multi-étapes côté web,
- validation frontend + backend,
- API REST CRUD,
- persistance MySQL via Doctrine,
- architecture simple, propre et évolutive.

## Fonctionnalités

- Étape 1 : informations personnelles
- Étape 2 : informations conducteur
- Étape 3 : choix du type d'assurance
- Étape 4 : informations véhicule dynamiques selon le type (auto/moto)
- Étape 5 : récapitulatif avant confirmation
- Enregistrement en base après validation complète
- API REST : créer, lire, modifier, lister les devis

## Choix d'architecture

### Backend
- **Entité principale** : `App\Entity\Quote`
- **Enums métier** : type d'assurance, carburant, ville, statut
- **DTO** : `App\DTO\QuoteRequest`
- **Services** : mapping DTO -> entité et gestion du wizard web
- **Contrôleurs** :
  - `App\Controller\Web\QuoteWizardController`
  - `App\Controller\Api\QuoteApiController`

### Frontend
- Twig + CSS + JS vanilla
- Stepper multi-étapes dynamique
- Récapitulatif généré en JS avant soumission finale

## Installation

### 1. Créer un projet Symfony

```bash
composer create-project symfony/skeleton .
composer require webapp
composer require orm validator serializer annotations twig
```

### 2. Copier les fichiers du dossier généré dans votre projet Symfony

Copiez le contenu de ce scaffold dans votre projet Symfony.

### 3. Configurer la base MySQL

Dans `.env.local` :

```env
DATABASE_URL="mysql://root:password@127.0.0.1:3306/assur_quote?serverVersion=8.0.32&charset=utf8mb4"
```

### 4. Créer la base et exécuter la migration

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

### 5. Lancer le serveur

```bash
symfony server:start
```

## Routes principales

### Web
- `GET /devis/nouveau` : affiche le formulaire multi-étapes
- `POST /devis/nouveau` : soumet le devis
- `GET /devis/{id}` : affiche un devis enregistré

### API REST
- `GET /api/quotes`
- `POST /api/quotes`
- `GET /api/quotes/{id}`
- `PUT /api/quotes/{id}`

## Validation métier

Exemples :
- téléphone marocain ou international simplifié
- immatriculation format contrôlé par regex
- date de naissance dans le passé
- date du permis dans le passé
- date du permis après la naissance
- mise en circulation dans le passé
- valeurs numériques strictement positives
- `puissanceFiscale` obligatoire pour `AUTO`
- `cylindree` obligatoire pour `MOTO`

## Sécurité et maintenabilité

- validation backend centralisée via le composant Validator
- réponses JSON uniformisées pour l'API
- aucun champ sensible exposé inutilement
- enums PHP pour limiter les valeurs invalides
- séparation DTO / entité / contrôleur / service

## Améliorations possibles

- authentification back-office
- pagination et filtres API
- CSRF plus avancé si API consommée par autre frontend
- upload de pièces justificatives
- moteur de tarification réel
- tests PHPUnit et tests fonctionnels

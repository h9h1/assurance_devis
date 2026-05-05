# Admin Panel Setup Guide

## Overview
J'ai configuré un système d'administration complet pour gérer:
- **Offres (Offers)**: Modifier les prix annuels et mensuels
- **Compagnies (Companies)**: Ajouter, modifier, supprimer des compagnies d'assurance
- **Villes (Cities)**: Ajouter, modifier, supprimer des villes

## Structure Créée

### 1. Entités (Entities)
- `src/Entity/Offer.php`: Gère les offres d'assurance avec prix configurables
- `src/Entity/Company.php`: Gère les compagnies d'assurance
- `src/Entity/City.php`: Gère les villes disponibles

### 2. Repositories
- `src/Repository/OfferRepository.php`
- `src/Repository/CompanyRepository.php`
- `src/Repository/CityRepository.php`

### 3. Admin Controllers (EasyAdmin)
- `src/Controller/Admin/OfferCrudController.php`
- `src/Controller/Admin/CompanyCrudController.php`
- `src/Controller/Admin/CityCrudController.php`

### 4. Dashboard Mise à Jour
- `src/Controller/Admin/DashboardController.php` avec menus pour gérer les entités

## Étapes à Suivre

### 1. Exécuter les Migrations
```bash
php bin/console doctrine:migrations:migrate
```
Cela va créer les tables et insérer les données par défaut (3 compagnies, 39 villes, 3 offres).

### 2. Mettre à Jour l'Entité Quote (optionnel mais recommandé)
Pour utiliser les entités Company et City au lieu des enums:

```php
// Dans Quote.php, remplacer:
#[ORM\Column(enumType: Company::class)]
private Company $company;

#[ORM\Column(enumType: City::class)]
private City $city;

// Par:
#[ORM\ManyToOne(targetEntity: Company::class)]
private Company $company;

#[ORM\ManyToOne(targetEntity: City::class)]
private City $city;
```

### 3. Utilisation dans Admin Panel
Accédez à `/admin` et vous verrez:
- **Configuration**: Manage Companies, Cities, and Offers
- **Gestion des devis**: Manage existing quotes

## Fonctionnalités

### Gérer les Offres
- Accédez à Admin → Configuration → Offres
- Modifiez les prix annuels et mensuels
- Modifiez titre et description

### Ajouter/Supprimer une Compagnie
- Accédez à Admin → Configuration → Compagnies
- Cliquez sur "Ajouter une nouvelle compagnie"
- Remplissez le nom et la description
- Cochez "Actif" pour la rendre visible

### Ajouter une Ville
- Accédez à Admin → Configuration → Villes
- Cliquez sur "Ajouter une nouvelle ville"
- Remplissez le nom et la description
- Cochez "Actif" pour la rendre visible

## Notes
- Toutes les entités ont des timestamps (createdAt, updatedAt)
- Company et City ont un drapeau `isActive` pour contrôler la visibilité
- Les prix des offres sont en MAD (Moroccan Dirham)

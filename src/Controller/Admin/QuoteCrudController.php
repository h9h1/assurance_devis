<?php

namespace App\Controller\Admin;

use App\Entity\Quote;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;

class QuoteCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Quote::class;
    }

    public function configureFields(string $pageName): iterable
    {
        $fields = [
            IdField::new('id')->hideOnForm(),
            TextField::new('lastName')->setLabel('Nom'),
            TextField::new('firstName')->setLabel('Prénom'),
            TextField::new('phoneNumber')->setLabel('Téléphone'),
            ChoiceField::new('city')->setLabel('Ville')->hideOnIndex(),
            ChoiceField::new('company')->setLabel('Compagnie'),
            DateField::new('birthDate')->setLabel('Date de naissance')->hideOnIndex(),
            DateField::new('licenseDate')->setLabel('Date permis')->hideOnIndex(),
            ChoiceField::new('insuranceType')->setLabel('Type d\'assurance')->hideOnIndex(),
            ChoiceField::new('vehiculeBrand')->setLabel('Marque véhicule')->hideOnIndex(),
            ChoiceField::new('fuelType')->setLabel('Carburant')->hideOnIndex(),
            DateField::new('firstRegistrationDate')->setLabel('Date 1ère immatriculation')->hideOnIndex(),
            NumberField::new('seatCount')->setLabel('Nombre de places')->hideOnIndex(),
            NumberField::new('newValue')->setLabel('Valeur neuve')->hideOnIndex(),
            NumberField::new('marketValue')->setLabel('Valeur marchande')->hideOnIndex(),
            TextField::new('registrationNumber')->setLabel('N° immatriculation')->hideOnIndex(),
            NumberField::new('fiscalPower')->setLabel('Puissance fiscale')->hideOnIndex(),
            NumberField::new('engineCapacity')->setLabel('Cylindrée')->hideOnIndex(),
            ChoiceField::new('status')->setLabel('Statut'),
            NumberField::new('customEstimation')->setLabel('Estimation personnalisée (€)')->setFormTypeOption('html5', false),
            TextEditorField::new('adminNote')->setLabel('Notes admin'),
            DateField::new('createdAt')->setLabel('Créé le')->hideOnForm(),
            DateField::new('updatedAt')->setLabel('Modifié le')->hideOnForm(),
        ];

        return $fields;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Devis')
            ->setEntityLabelInPlural('Devis')
            ->setPageTitle('index', 'Gestion des devis')
            ->setPageTitle('new', 'Nouveau devis')
            ->setPageTitle('edit', 'Éditer le devis')
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions;
    }
}

<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\CompanyOfferVariation;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;

class CompanyOfferVariationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return CompanyOfferVariation::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Variation de prix')
            ->setEntityLabelInPlural('Variations de prix par compagnie')
            ->setPageTitle(Crud::PAGE_INDEX, 'Variations de prix — Compagnies / Offres')
            ->setPageTitle(Crud::PAGE_NEW, 'Nouvelle variation de prix')
            ->setPageTitle(Crud::PAGE_EDIT, 'Modifier la variation')
            ->setHelp(Crud::PAGE_INDEX,
                'Définissez ici des majorations (+) ou réductions (−) par compagnie et par offre. '
                . '<strong>Pourcentage</strong> : applique un % sur le prix de base. '
                . '<strong>Montant fixe</strong> : ajoute ou soustrait un montant en MAD.'
            )
            ->setHelp(Crud::PAGE_NEW,
                'Valeur <strong>positive</strong> = majoration | <strong>négative</strong> = réduction.'
            )
            ->setDefaultSort(['company' => 'ASC', 'offer' => 'ASC'])
            ->setSearchFields(['company.name', 'offer.title', 'offer.code']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();

        yield AssociationField::new('company')
            ->setLabel('Compagnie')
            ->setRequired(true)
            ->autocomplete();

        yield AssociationField::new('offer')
            ->setLabel('Offre')
            ->setRequired(true)
            ->autocomplete();

        yield ChoiceField::new('variationType')
            ->setLabel('Type de variation')
            ->setChoices([
                'Pourcentage (%)' => CompanyOfferVariation::TYPE_PERCENT,
                'Montant fixe (MAD)' => CompanyOfferVariation::TYPE_FIXED,
            ])
            ->renderAsBadges([
                CompanyOfferVariation::TYPE_PERCENT => 'primary',
                CompanyOfferVariation::TYPE_FIXED   => 'warning',
            ]);

        yield NumberField::new('value')
            ->setLabel('Valeur')
            ->setHelp('Ex : <code>10</code> → +10 %  |  <code>-300</code> → −300 MAD')
            ->setNumDecimals(2);

        yield BooleanField::new('isActive')->setLabel('Active');

        yield DateTimeField::new('createdAt')->setLabel('Créé le')->hideOnForm();
        yield DateTimeField::new('updatedAt')->setLabel('Modifié le')->hideOnForm();
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->update(Crud::PAGE_INDEX, Action::NEW, fn (Action $a) => $a->setLabel('Nouvelle variation'));
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('company')->setLabel('Compagnie'))
            ->add(EntityFilter::new('offer')->setLabel('Offre'))
            ->add(BooleanFilter::new('isActive')->setLabel('Active'));
    }
}

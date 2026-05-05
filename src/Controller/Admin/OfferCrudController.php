<?php

namespace App\Controller\Admin;

use App\Entity\Offer;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;

class OfferCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Offer::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('code')->setLabel('Code'),
            TextField::new('title')->setLabel('Titre'),
            TextEditorField::new('description')->setLabel('Description'),
            MoneyField::new('annualPrice')->setLabel('Prix annuel')->setCurrency('MAD'),
            MoneyField::new('monthlyPrice')->setLabel('Prix mensuel')->setCurrency('MAD'),
            DateTimeField::new('createdAt')->setLabel('Créé le')->hideOnForm(),
            DateTimeField::new('updatedAt')->setLabel('Modifié le')->hideOnForm(),
        ];
    }
}

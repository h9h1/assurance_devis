<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Quote;
use App\Enum\InsuranceType;
use App\Enum\QuoteStatus;
use App\Repository\QuoteRepository;
use App\Service\QuoteMailerService;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;

class QuoteCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly AdminUrlGenerator    $adminUrlGenerator,
        private readonly EntityManagerInterface $em,
        private readonly QuoteMailerService   $mailerService,
    ) {}

    public static function getEntityFqcn(): string
    {
        return Quote::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Devis')
            ->setEntityLabelInPlural('Devis')
            ->setPageTitle(Crud::PAGE_INDEX, 'Gestion des devis')
            ->setPageTitle(Crud::PAGE_DETAIL, fn (Quote $q) => 'Devis #' . $q->getId() . ' — ' . $q->getFirstName() . ' ' . $q->getLastName())
            ->setPageTitle(Crud::PAGE_EDIT, fn (Quote $q) => 'Modifier le devis #' . $q->getId())
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setSearchFields(['lastName', 'firstName', 'email', 'phoneNumber', 'registrationNumber'])
            ->showEntityActionsInlined();
    }

    public function configureFields(string $pageName): iterable
    {
        // ── Liste & Détail ─────────────────────────────────────────────────
        yield IdField::new('id', '#')->hideOnForm();

        yield TextField::new('firstName', 'Prénom')->hideOnIndex();
        yield TextField::new('lastName', 'Nom');

        yield TextField::new('email', 'Email')->hideOnIndex();
        yield TextField::new('phoneNumber', 'Téléphone')->hideOnIndex();

        yield AssociationField::new('companyEntity', 'Compagnie')->setRequired(false);
        yield AssociationField::new('cityEntity', 'Ville')->setRequired(false)->hideOnIndex();

        yield ChoiceField::new('status', 'Statut')
            ->setChoices([
                'Soumis'     => QuoteStatus::SUBMITTED,
                'Confirmé'   => QuoteStatus::CONFIRMED,
                'Accepté'    => QuoteStatus::ACCEPTED,
                'Rejeté'     => QuoteStatus::REJECTED,
            ])
            ->renderAsBadges([
                QuoteStatus::SUBMITTED->value  => 'primary',
                QuoteStatus::CONFIRMED->value  => 'info',
                QuoteStatus::ACCEPTED->value   => 'success',
                QuoteStatus::REJECTED->value   => 'danger',
            ]);

        yield TextField::new('selectedOffer', 'Offre choisie')->hideOnIndex();

        yield NumberField::new('customEstimation', 'Estimation (MAD)')
            ->setNumDecimals(2)
            ->hideOnIndex();

        yield TextareaField::new('adminNote', 'Note admin')
            ->setRequired(false)
            ->hideOnIndex()
            ->setHelp('Note visible par le client dans son récapitulatif et dans l\'email.');

        yield ChoiceField::new('insuranceType', 'Type')
            ->setChoices([
                'Automobile' => InsuranceType::AUTO,
                'Moto' => InsuranceType::MOTO,
                'Inconnu' => InsuranceType::UNKNOWN,
            ]);

        yield TextField::new('registrationNumber', 'Immatriculation')->hideOnIndex();

        yield DateTimeField::new('createdAt', 'Créé le')->hideOnForm()->setSortable(true);
        yield DateTimeField::new('updatedAt', 'Modifié le')->hideOnForm()->hideOnIndex();
    }

    public function configureActions(Actions $actions): Actions
    {
        // ── Action : Confirmer ─────────────────────────────────────────────
        $confirm = Action::new('confirmQuote', 'Confirmer', 'fa fa-check-circle')
            ->linkToCrudAction('confirmQuote')
            ->setCssClass('btn btn-sm btn-success')
            ->displayIf(fn (Quote $q) => $q->getStatus() === QuoteStatus::SUBMITTED);

        // ── Action : Accepter ──────────────────────────────────────────────
        $accept = Action::new('acceptQuote', 'Accepter', 'fa fa-thumbs-up')
            ->linkToCrudAction('acceptQuote')
            ->setCssClass('btn btn-sm btn-primary')
            ->displayIf(fn (Quote $q) => in_array($q->getStatus(), [QuoteStatus::SUBMITTED, QuoteStatus::CONFIRMED]));

        // ── Action : Rejeter ───────────────────────────────────────────────
        $reject = Action::new('rejectQuote', 'Rejeter', 'fa fa-times-circle')
            ->linkToCrudAction('rejectQuote')
            ->setCssClass('btn btn-sm btn-danger')
            ->displayIf(fn (Quote $q) => in_array($q->getStatus(), [QuoteStatus::SUBMITTED, QuoteStatus::CONFIRMED]));

        // ── Action : Envoyer email ─────────────────────────────────────────
        $sendEmail = Action::new('sendEmailAdmin', 'Envoyer email', 'fa fa-envelope')
            ->linkToCrudAction('sendEmailAdmin')
            ->setCssClass('btn btn-sm btn-info')
            ->displayIf(fn (Quote $q) => $q->getEmail() !== null);

        return $actions
            ->add(Crud::PAGE_INDEX,  Action::DETAIL)
            ->add(Crud::PAGE_DETAIL, $confirm)
            ->add(Crud::PAGE_DETAIL, $accept)
            ->add(Crud::PAGE_DETAIL, $reject)
            ->add(Crud::PAGE_DETAIL, $sendEmail)
            ->add(Crud::PAGE_INDEX,  $confirm)
            ->add(Crud::PAGE_INDEX,  $accept)
            ->add(Crud::PAGE_INDEX,  $reject)
            ->update(Crud::PAGE_INDEX, Action::EDIT, fn (Action $a) => $a->setIcon('fa fa-pen')->setLabel(''))
            ->update(Crud::PAGE_INDEX, Action::DETAIL, fn (Action $a) => $a->setIcon('fa fa-eye')->setLabel(''))
            ->update(Crud::PAGE_INDEX, Action::DELETE, fn (Action $a) => $a->setIcon('fa fa-trash')->setLabel(''))
            ->reorder(Crud::PAGE_INDEX, [Action::DETAIL, Action::EDIT, 'confirmQuote', 'acceptQuote', 'rejectQuote', Action::DELETE]);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('status', 'Statut')->setChoices([
                'Soumis'    => QuoteStatus::SUBMITTED->value,
                'Confirmé'  => QuoteStatus::CONFIRMED->value,
                'Accepté'   => QuoteStatus::ACCEPTED->value,
                'Rejeté'    => QuoteStatus::REJECTED->value,
            ]))
            ->add(EntityFilter::new('companyEntity', 'Compagnie'));
    }

    // ══════════════════════════════════════════════════════════════════════════
    // Actions personnalisées
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Confirmer un devis soumis — envoie un email au client si email disponible.
     */
    #[AdminRoute]
    public function confirmQuote(AdminContext $context): Response
    {
        /** @var Quote $quote */
        $quote = $context->getEntity()->getInstance();

        $quote->setStatus(QuoteStatus::CONFIRMED);
        $quote->touch();
        $this->em->flush();

        $this->addFlash('success', sprintf(
            '✅ Devis #%d de %s %s confirmé.',
            $quote->getId(),
            $quote->getFirstName(),
            $quote->getLastName()
        ));

        // Envoyer email si disponible
        if ($quote->getEmail()) {
            try {
                $this->mailerService->sendRecap($quote);
                $this->addFlash('success', '📧 Email de confirmation envoyé à ' . $quote->getEmail());
            } catch (\Throwable $e) {
                $this->addFlash('warning', '⚠️ Email non envoyé : ' . $e->getMessage());
            }
        }

        return $this->redirect($this->getDetailUrl($quote));
    }

    /**
     * Accepter un devis — statut final positif.
     */
    #[AdminRoute]
    public function acceptQuote(AdminContext $context): Response
    {
        /** @var Quote $quote */
        $quote = $context->getEntity()->getInstance();

        $quote->setStatus(QuoteStatus::ACCEPTED);
        $quote->touch();
        $this->em->flush();

        $this->addFlash('success', sprintf(
            '✅ Devis #%d accepté.',
            $quote->getId()
        ));

        if ($quote->getEmail()) {
            try {
                $this->mailerService->sendRecap($quote);
                $this->addFlash('success', '📧 Email d\'acceptation envoyé à ' . $quote->getEmail());
            } catch (\Throwable $e) {
                $this->addFlash('warning', '⚠️ Email non envoyé : ' . $e->getMessage());
            }
        }

        return $this->redirect($this->getDetailUrl($quote));
    }

    /**
     * Rejeter un devis.
     */
    #[AdminRoute]
    public function rejectQuote(AdminContext $context): Response
    {
        /** @var Quote $quote */
        $quote = $context->getEntity()->getInstance();

        $quote->setStatus(QuoteStatus::REJECTED);
        $quote->touch();
        $this->em->flush();

        $this->addFlash('warning', sprintf(
            '❌ Devis #%d rejeté.',
            $quote->getId()
        ));

        return $this->redirect($this->getDetailUrl($quote));
    }

    /**
     * Envoyer manuellement l'email récapitulatif depuis l'admin.
     */
    #[AdminRoute]
    public function sendEmailAdmin(AdminContext $context): Response
    {
        /** @var Quote $quote */
        $quote = $context->getEntity()->getInstance();

        if (!$quote->getEmail()) {
            $this->addFlash('danger', '❌ Aucun email renseigné pour ce devis.');
            return $this->redirect($this->getDetailUrl($quote));
        }

        try {
            $this->mailerService->sendRecap($quote);
            $this->addFlash('success', '📧 Email envoyé à ' . $quote->getEmail());
        } catch (\Throwable $e) {
            $this->addFlash('danger', '❌ Erreur : ' . $e->getMessage());
        }

        return $this->redirect($this->getDetailUrl($quote));
    }

    // ── Helper ────────────────────────────────────────────────────────────────

    private function getDetailUrl(Quote $quote): string
    {
        return $this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::DETAIL)
            ->setEntityId($quote->getId())
            ->generateUrl();
    }
}
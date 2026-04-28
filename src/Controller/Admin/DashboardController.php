<?php

namespace App\Controller\Admin;

use App\Enum\QuoteStatus;
use App\Repository\QuoteRepository;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Dto\LocaleDto;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private AdminUrlGenerator $adminUrlGenerator,
        private QuoteRepository $quoteRepository,
    ) {}

    public function index(): Response
    {
        // Statistiques des devis
        $stats = $this->getQuoteStats();

        // Ajouter l'URL du CRUD des devis
        $stats['quotesUrl'] = $this->adminUrlGenerator
            ->setController(QuoteCrudController::class)
            ->generateUrl();

        return $this->render('admin/dashboard.html.twig', $stats);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Assurance Aksam')
            ->setFaviconPath('favicon.png')
            ->disableDarkMode();
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');

        yield MenuItem::section('Gestion des devis');

        $quotesUrl = $this->adminUrlGenerator
            ->setController(QuoteCrudController::class)
            ->generateUrl();
        yield MenuItem::linkToUrl('Devis', 'fa fa-file-pdf', $quotesUrl);

        yield MenuItem::section('Configuration');

        $companiesUrl = $this->adminUrlGenerator
            ->setController(CompanyCrudController::class)
            ->generateUrl();
        yield MenuItem::linkToUrl('Compagnies', 'fa fa-building', $companiesUrl);

        $citiesUrl = $this->adminUrlGenerator
            ->setController(CityCrudController::class)
            ->generateUrl();
        yield MenuItem::linkToUrl('Villes', 'fa fa-map-marker', $citiesUrl);

        $offersUrl = $this->adminUrlGenerator
            ->setController(OfferCrudController::class)
            ->generateUrl();
        yield MenuItem::linkToUrl('Offres', 'fa fa-tag', $offersUrl);

        $variationsUrl = $this->adminUrlGenerator
            ->setController(CompanyOfferVariationCrudController::class)
            ->generateUrl();
        yield MenuItem::linkToUrl('Variations de prix', 'fa fa-percent', $variationsUrl);
    }

    private function getQuoteStats(): array
    {
        $total = $this->quoteRepository->count([]);
        $submitted = $this->quoteRepository->count(['status' => QuoteStatus::SUBMITTED]);
        $accepted = $this->quoteRepository->count(['status' => QuoteStatus::ACCEPTED]);
        $rejected = $this->quoteRepository->count(['status' => QuoteStatus::REJECTED]);

        // Derniers devis
        $latestQuotes = $this->quoteRepository->findBy(
            [],
            ['createdAt' => 'DESC'],
            5
        );

        return [
            'total' => $total,
            'submitted' => $submitted,
            'accepted' => $accepted,
            'rejected' => $rejected,
            'latestQuotes' => $latestQuotes,
        ];
    }
}

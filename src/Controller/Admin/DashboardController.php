<?php

namespace App\Controller\Admin;

use App\Entity\Quote;
use App\Enum\QuoteStatus;
use App\Repository\QuoteRepository;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
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
            ->setTitle('Assur Quote - Admin Dashboard')
            ->setFaviconPath('favicon.ico');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');

        $quotesUrl = $this->adminUrlGenerator
            ->setController(QuoteCrudController::class)
            ->generateUrl();

        yield MenuItem::linkToUrl('Devis', 'fa fa-file-pdf', $quotesUrl)
            ->setBadge($this->quoteRepository->count([]) ?? 0);
    }

    private function getQuoteStats(): array
    {
        $total = $this->quoteRepository->count([]);
        $draft = $this->quoteRepository->count(['status' => QuoteStatus::DRAFT]);
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
            'draft' => $draft,
            'submitted' => $submitted,
            'accepted' => $accepted,
            'rejected' => $rejected,
            'latestQuotes' => $latestQuotes,
        ];
    }
}

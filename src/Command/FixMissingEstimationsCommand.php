<?php


namespace App\Command;

use App\Repository\QuoteRepository;
use App\Service\QuoteEstimatorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:fix-missing-estimations',
    description: 'Recalcule et sauvegarde le prix pour les devis qui ont un offer sélectionné mais pas d\'estimation.',
)]
class FixMissingEstimationsCommand extends Command
{
    public function __construct(
        private readonly QuoteRepository        $quoteRepository,
        private readonly QuoteEstimatorService  $estimator,
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Affiche les changements sans les enregistrer');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io     = new SymfonyStyle($input, $output);
        $dryRun = $input->getOption('dry-run');

        $io->title('Recalcul des estimations manquantes');

        if ($dryRun) {
            $io->warning('Mode dry-run : aucune modification ne sera enregistrée.');
        }

        // Récupérer les devis avec offre sélectionnée mais sans estimation
        $quotes = $this->quoteRepository->createQueryBuilder('q')
            ->where('q.selectedOffer IS NOT NULL')
            ->andWhere('q.customEstimation IS NULL OR q.customEstimation = 0')
            ->getQuery()
            ->getResult();

        if (empty($quotes)) {
            $io->success('Aucun devis à corriger. Tout est à jour !');
            return Command::SUCCESS;
        }

        $io->info(sprintf('%d devis à corriger.', count($quotes)));

        $rows    = [];
        $updated = 0;
        $failed  = 0;

        foreach ($quotes as $quote) {
            $offerCode = $quote->getSelectedOffer();
            $company   = $quote->getCompanyEntity();

            try {
                $offers = $company
                    ? $this->estimator->getOffersByCompany($quote, $company)
                    : $this->estimator->getOffers($quote);

                $price = null;
                foreach ($offers as $offer) {
                    if ($offer['code'] === $offerCode) {
                        $price = $offer['annual_price'];
                        break;
                    }
                }

                if ($price !== null) {
                    $rows[] = [
                        '#' . $quote->getId(),
                        $quote->getFirstName() . ' ' . $quote->getLastName(),
                        $offerCode,
                        $price . ' MAD',
                        $dryRun ? '(simulation)' : '✓ mis à jour',
                    ];

                    if (!$dryRun) {
                        $quote->setCustomEstimation((string) $price);
                        $updated++;
                    }
                } else {
                    $rows[] = [
                        '#' . $quote->getId(),
                        $quote->getFirstName() . ' ' . $quote->getLastName(),
                        $offerCode,
                        '—',
                        '⚠ offre introuvable',
                    ];
                    $failed++;
                }
            } catch (\Throwable $e) {
                $rows[] = [
                    '#' . $quote->getId(),
                    $quote->getFirstName() . ' ' . $quote->getLastName(),
                    $offerCode,
                    '—',
                    '✗ erreur : ' . $e->getMessage(),
                ];
                $failed++;
            }
        }

        $io->table(['ID', 'Client', 'Offre', 'Prix calculé', 'Statut'], $rows);

        if (!$dryRun) {
            $this->em->flush();
            $io->success(sprintf('%d devis mis à jour. %d échec(s).', $updated, $failed));
        } else {
            $io->note('Relancez sans --dry-run pour appliquer les changements.');
        }

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}

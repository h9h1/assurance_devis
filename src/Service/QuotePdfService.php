<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Quote;
use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;

class QuotePdfService
{
    public function __construct(
        private readonly Environment $twig,
    ) {}

    /**
     * Génère le PDF récapitulatif d'un devis et retourne le contenu binaire.
     */
    public function generateRecap(Quote $quote, array $quoteArray): string
    {
        $html = $this->twig->render('quote/recap_pdf.html.twig', [
            'quote'     => $quoteArray,
            'generatedAt' => new \DateTimeImmutable(),
        ]);

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }
}
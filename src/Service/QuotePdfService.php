<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Quote;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\HttpKernel\KernelInterface;
use Twig\Environment;

class QuotePdfService
{
    public function __construct(
        private readonly Environment $twig,
        private readonly KernelInterface $kernel,
    ) {}

    /**
     * Génère le PDF récapitulatif d'un devis et retourne le contenu binaire.
     */
    public function generateRecap(Quote $quote, array $quoteArray): string
    {
        $html = $this->twig->render('quote/recap_pdf.html.twig', [
            'quote'       => $quoteArray,
            'generatedAt' => new \DateTimeImmutable(),
            'logoBase64'  => $this->getLogoBase64(),
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

    /**
     * Lit le logo depuis le filesystem et retourne un data URI base64.
     * Retourne null si le fichier est absent.
     */
    private function getLogoBase64(): ?string
    {
        $logoPath = $this->kernel->getProjectDir() . '/public/assets/logo.png';

        if (!file_exists($logoPath)) {
            return null;
        }

        $data = base64_encode(file_get_contents($logoPath));
        $mime = mime_content_type($logoPath) ?: 'image/png';

        return 'data:' . $mime . ';base64,' . $data;
    }
}
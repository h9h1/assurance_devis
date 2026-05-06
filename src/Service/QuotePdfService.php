<?php



namespace App\Service;

use App\Entity\Quote;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Twig\Environment;


class QuotePdfService
{
    public function __construct(
        private readonly Environment    $twig,
        private readonly KernelInterface $kernel,
    ) {}

    // ─────────────────────────────────────────────────────────────────────
    // API publique
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Génère le PDF et retourne le contenu binaire.
     */
    public function generateRecap(Quote $quote): string
    {
        $html = $this->twig->render('quote/recap_pdf.html.twig', [
            'quote'       => $this->serializeQuote($quote),
            'logoBase64'  => $this->getLogoBase64(),
            'generatedAt' => new \DateTimeImmutable(),
        ]);

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isFontSubsettingEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    /**
     * Retourne une réponse HTTP de téléchargement PDF.
     */
    public function streamResponse(Quote $quote): Response
    {
        $pdf      = $this->generateRecap($quote);
        $filename = sprintf('devis-%d-aksam.pdf', $quote->getId());

        return new Response($pdf, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Content-Length'      => strlen($pdf),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Sérialisation
    // ─────────────────────────────────────────────────────────────────────

    private function serializeQuote(Quote $quote): array
    {
        return [
            'id'                    => $quote->getId(),
            'uuid'                  => method_exists($quote, 'getUuid')        ? $quote->getUuid()        : null,
            'accessToken'           => method_exists($quote, 'getAccessToken') ? $quote->getAccessToken() : null,
            'lastName'              => $quote->getLastName(),
            'firstName'             => $quote->getFirstName(),
            'phoneNumber'           => $quote->getPhoneNumber(),
            'email'                 => method_exists($quote, 'getEmail')       ? $quote->getEmail()       : null,
            'city'                  => $quote->getCity()?->value    ?? '',
            'company'               => $quote->getCompany()?->value ?? '',
            'birthDate'             => $quote->getBirthDate()->format('d/m/Y'),
            'licenseDate'           => $quote->getLicenseDate()->format('d/m/Y'),
            'insuranceType'         => $quote->getInsuranceType()->value,
            'vehicleBrand'          => $quote->getVehicleBrand()->value,
            'fuelType'              => $quote->getFuelType()->value,
            'firstRegistrationDate' => $quote->getFirstRegistrationDate()->format('d/m/Y'),
            'seatCount'             => $quote->getSeatCount(),
            'newValue'              => number_format((float)$quote->getNewValue(),    2, ',', ' '),
            'marketValue'           => number_format((float)$quote->getMarketValue(), 2, ',', ' '),
            'registrationNumber'    => $quote->getRegistrationNumber(),
            'fiscalPower'           => $quote->getFiscalPower(),
            'engineCapacity'        => $quote->getEngineCapacity(),
            'selectedOffer'         => $quote->getSelectedOffer(),
            'customEstimation'      => $quote->getCustomEstimation()
                                        ? number_format((float)$quote->getCustomEstimation(), 2, ',', ' ')
                                        : null,
            'adminNote'             => $quote->getAdminNote(),
            'status'                => $quote->getStatus()->value,
            'createdAt'             => $quote->getCreatedAt()->format('d/m/Y'),
            'updatedAt'             => $quote->getUpdatedAt()->format('d/m/Y H:i'),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────
    // Logo en base64 lu depuis le filesystem
    // ─────────────────────────────────────────────────────────────────────

    private function getLogoBase64(): ?string
    {
        $path = $this->kernel->getProjectDir() . '/public/assets/logo.png';

        if (!file_exists($path)) {
            return null;
        }

        $mime = mime_content_type($path) ?: 'image/png';
        return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
    }
}
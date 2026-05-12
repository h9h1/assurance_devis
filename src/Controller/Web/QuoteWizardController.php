<?php



namespace App\Controller\Web;

use App\DTO\QuoteRequest;
use App\Entity\Quote;
use App\Enum\FuelType;
use App\Enum\QuoteStatus;
use App\Enum\VehiculeBrand;
use App\Repository\CityRepository;
use App\Repository\CompanyRepository;
use App\Repository\QuoteRepository;
use App\Service\QuoteEstimatorService;
use App\Service\QuoteMailerService;
use App\Service\QuoteMapper;
use App\Service\QuotePdfService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class QuoteWizardController extends AbstractController
{
    // ── Création ──────────────────────────────────────────────────────────────

    #[Route('/devis/nouveau', name: 'quote_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        ValidatorInterface $validator,
        QuoteMapper $mapper,
        EntityManagerInterface $entityManager,
        CityRepository $cityRepository,
        CompanyRepository $companyRepository,
    ): Response {
        $formData = $request->isMethod('POST') ? $request->request->all() : [];
        $dto      = QuoteRequest::fromArray($formData);
        $errors   = [];

        if ($request->isMethod('POST')) {
            $violations = $validator->validate($dto);

            if (count($violations) === 0) {
                $quote = $mapper->mapToEntity($dto, null, $cityRepository, $companyRepository);
                $entityManager->persist($quote);
                $entityManager->flush();

                $this->addFlash('success', 'Votre demande de devis a bien été enregistrée.');

                return $this->redirectToRoute('quote_offers', [
                    'uuid'  => $quote->getUuid(),
                    'token' => $quote->getAccessToken(),
                ]);
            }

            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()][] = $violation->getMessage();
            }
        }

        return $this->render('quote/new.html.twig', [
            'cities'         => $cityRepository->findActive(),
            'fuelTypes'      => FuelType::cases(),
            'vehiculeBrands' => VehiculeBrand::cases(),
            'data'           => $dto->toArray(),
            'errors'         => $errors,
        ]);
    }

    // ── Récapitulatif ─────────────────────────────────────────────────────────

    #[Route('/devis/{uuid}', name: 'quote_show', requirements: ['uuid' => '[0-9a-f-]{36}'], methods: ['GET'])]
    public function show(
        string $uuid,
        Request $request,
        QuoteRepository $quoteRepository,
        QuoteMapper $mapper,
    ): Response {
        $quote = $this->resolveQuote($uuid, $request, $quoteRepository);

        return $this->render('quote/show.html.twig', [
            'quote' => $mapper->toArray($quote),
        ]);
    }
      #[Route('/devis/{uuid}/pdf', name: 'quote_recap_pdf', requirements: ['uuid' => '[0-9a-f-]{36}'], methods: ['GET'])]
    public function downloadPdf(
        string $uuid,
        Request $request,
        QuoteRepository $quoteRepository,
        ParameterBagInterface $params
    ): Response {
        $quote = $this->resolveQuote($uuid, $request, $quoteRepository);

         $projectDir = $params->get('kernel.project_dir');

        // Encoder le logo en base64 pour Dompdf (pas d'accès HTTP en interne)
        $logoPath = $projectDir . '/public/assets/logo.png';
        $logoBase64 = null;
        if (file_exists($logoPath)) {
            $ext = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
            $mime = match($ext) {
                'png'  => 'image/png',
                'jpg', 'jpeg' => 'image/jpeg',
                'svg'  => 'image/svg+xml',
                'webp' => 'image/webp',
                default => 'image/png',
            };
            $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoPath));
        }

        $options = new \Dompdf\Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);
        $options->set('isPhpEnabled', false);

        $dompdf = new \Dompdf\Dompdf($options);

        $html = $this->renderView('quote/quote_recap_pdf.html.twig', [
            'quote'       => $quote,
            'logoBase64'  => $logoBase64,
        ]);

        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'devis-' . $quote->getId() . '-aksam.pdf';

        return new Response(
            $dompdf->output(),
            200,
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]
        );
    }


    // ── Offres ────────────────────────────────────────────────────────────────

    #[Route('/devis/{uuid}/offres', name: 'quote_offers', requirements: ['uuid' => '[0-9a-f-]{36}'], methods: ['GET'])]
    public function offers(
        string $uuid,
        Request $request,
        QuoteRepository $quoteRepository,
        QuoteEstimatorService $estimator,
        QuoteMapper $mapper,
        CompanyRepository $companyRepository,
    ): Response {
        $quote = $this->resolveQuote($uuid, $request, $quoteRepository);

        // Utiliser les prix avec variation si une compagnie est déjà associée
        $companyEntity = $quote->getCompanyEntity();
        $offers = $companyEntity
            ? $estimator->getOffersByCompany($quote, $companyEntity)
            : $estimator->getOffers($quote);

        return $this->render('quote/offers.html.twig', [
            'quote'           => $mapper->toArray($quote),
            'offers'          => $offers,
            'companies'       => $companyRepository->findActive(),
            'selectedCompany' => $companyEntity?->getName() ?? '',
        ]);
    }

    // ── Sélection offre ───────────────────────────────────────────────────────

    #[Route('/devis/{uuid}/choisir-offre', name: 'quote_select_offer', requirements: ['uuid' => '[0-9a-f-]{36}'], methods: ['POST'])]
    public function selectOffer(
        string $uuid,
        Request $request,
        QuoteRepository $quoteRepository,
        EntityManagerInterface $entityManager,
        QuoteEstimatorService $estimator,
        CompanyRepository $companyRepository,
    ): Response {
        $quote     = $this->resolveQuote($uuid, $request, $quoteRepository);
        $offerCode = $request->request->get('offer_code');

        // ── Sauvegarder la compagnie sélectionnée ──────────────────────────
        $companyName = trim($request->request->get('company', ''));
        if ($companyName) {
            $companyEntity = $companyRepository->findOneBy(['name' => $companyName, 'isActive' => true]);
            if ($companyEntity) {
                $quote->setCompanyEntity($companyEntity);
                // Mettre à jour l'Enum si la valeur correspond
                $companyEnum = \App\Enum\Company::tryFrom($companyName);
                if ($companyEnum) {
                    $quote->setCompany($companyEnum);
                }
            }
        }

        if ($offerCode) {
            $company = $quote->getCompanyEntity();
            $offers  = $company
                ? $estimator->getOffersByCompany($quote, $company)
                : $estimator->getOffers($quote);

            $price = null;
            foreach ($offers as $offer) {
                if ($offer['code'] === $offerCode) {
                    $price = $offer['annual_price'];
                    break;
                }
            }

            $quote->setSelectedOffer($offerCode);
            $quote->setStatus(QuoteStatus::SUBMITTED);
            if ($price !== null) {
                $quote->setCustomEstimation((string) $price);
            }
            $quote->touch();
            $entityManager->flush();

            $this->addFlash('success', 'Offre "' . ucfirst($offerCode) . '" sélectionnée avec succès !');
        }

        return $this->redirectToRoute('quote_show', [
            'uuid'  => $quote->getUuid(),
            'token' => $request->request->get('_token', $quote->getAccessToken()),
        ]);
    }

    // ── Offres par compagnie (AJAX) ───────────────────────────────────────────

    #[Route('/devis/{uuid}/offres-by-company', name: 'quote_offers_by_company', requirements: ['uuid' => '[0-9a-f-]{36}'], methods: ['GET', 'POST'])]
    public function offersByCompany(
        string $uuid,
        Request $request,
        QuoteRepository $quoteRepository,
        QuoteEstimatorService $estimator,
        CompanyRepository $companyRepository,
    ): JsonResponse {
        $quote       = $this->resolveQuote($uuid, $request, $quoteRepository);
        $companyName = $request->query->get('company');

        if ($companyName) {
            $company = $companyRepository->findOneBy(['name' => $companyName, 'isActive' => true]);

            if (!$company) {
                return new JsonResponse(['success' => false, 'message' => 'Compagnie introuvable.'], 404);
            }

            return new JsonResponse([
                'success' => true,
                'offers'  => $estimator->getOffersByCompany($quote, $company),
            ]);
        }

        return new JsonResponse(['success' => false, 'message' => 'Company not provided'], 400);
    }

    // ── Envoi email ───────────────────────────────────────────────────────────

    #[Route('/devis/{uuid}/envoyer-email', name: 'quote_send_email', requirements: ['uuid' => '[0-9a-f-]{36}'], methods: ['POST'])]
    public function sendEmail(
        string $uuid,
        Request $request,
        QuoteRepository $quoteRepository,
        EntityManagerInterface $em,
        QuoteMailerService $mailer,
    ): Response {
        $quote = $this->resolveQuote($uuid, $request, $quoteRepository);
        $email = trim($request->request->get('email', ''));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->addFlash('error', 'Adresse email invalide.');
            return $this->redirectToRoute('quote_show', [
                'uuid'  => $quote->getUuid(),
                'token' => $quote->getAccessToken(),
            ]);
        }

        if ($quote->getEmail() !== $email) {
            $quote->setEmail($email);
            $em->flush();
        }

        try {
            $mailer->sendRecap($quote);
            $this->addFlash('success', '✅ Récapitulatif envoyé à ' . $email . ' avec succès !');
        } catch (\Throwable $e) {
            $this->addFlash('error', '❌ Erreur lors de l\'envoi : ' . $e->getMessage());
        }

        return $this->redirectToRoute('quote_show', [
            'uuid'  => $quote->getUuid(),
            'token' => $quote->getAccessToken(),
        ]);
    }

    // ── Homepage ──────────────────────────────────────────────────────────────

    #[Route('/', name: 'homepage', methods: ['GET'])]
    public function home(): Response
    {
        return $this->render('home/index.html.twig');
    }

   
    private function resolveQuote(string $uuid, Request $request, QuoteRepository $repo): Quote
    {
        $quote = $repo->findByUuid($uuid);

        if (!$quote) {
            throw $this->createNotFoundException('Devis introuvable.');
        }

        
        $token = $request->query->get('token')
            ?? $request->request->get('token')
            ?? '';

        if (!$quote->isValidToken($token)) {
            throw $this->createAccessDeniedException('Lien d\'accès invalide ou expiré.');
        }

        return $quote;
    }
}
<?php

namespace App\Controller\Api;

use App\DTO\QuoteRequest;
use App\Entity\Quote;
use App\Repository\CityRepository;
use App\Repository\CompanyRepository;
use App\Repository\QuoteRepository;
use App\Service\ApiValidationResponder;
use App\Service\QuoteEstimatorService;
use App\Service\QuoteMapper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/quotes', name: 'api_quotes_')]
class QuoteApiController extends AbstractController
{
    // ── existing routes (unchanged) ───────────────────────────────────────────

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(QuoteRepository $quoteRepository, QuoteMapper $mapper): JsonResponse
    {
        $quotes = array_map(
            static fn(Quote $quote) => $mapper->toArray($quote),
            $quoteRepository->findLatest()
        );

        return $this->json(['data' => $quotes]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(
        Request $request,
        ValidatorInterface $validator,
        QuoteMapper $mapper,
        ApiValidationResponder $validationResponder,
        EntityManagerInterface $entityManager,
        CityRepository $cityRepository,
        CompanyRepository $companyRepository,
    ): JsonResponse {
        $payload    = json_decode($request->getContent(), true) ?? [];
        $dto        = QuoteRequest::fromArray($payload);
        $violations = $validator->validate($dto);

        if (count($violations) > 0) {
            return $this->json([
                'message' => 'Validation échouée.',
                'errors'  => $validationResponder->violationsToArray($violations),
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $quote = $mapper->mapToEntity($dto, null, $cityRepository, $companyRepository);
        $entityManager->persist($quote);
        $entityManager->flush();

        return $this->json([
            'message' => 'Devis créé avec succès.',
            'data'    => $mapper->toArray($quote),
        ], JsonResponse::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Quote $quote, QuoteMapper $mapper): JsonResponse
    {
        return $this->json(['data' => $mapper->toArray($quote)]);
    }

    #[Route('/{id}', name: 'update', requirements: ['id' => '\d+'], methods: ['PUT'])]
    public function update(
        Request $request,
        Quote $quote,
        ValidatorInterface $validator,
        QuoteMapper $mapper,
        ApiValidationResponder $validationResponder,
        EntityManagerInterface $entityManager,
        CityRepository $cityRepository,
        CompanyRepository $companyRepository,
    ): JsonResponse {
        $payload    = json_decode($request->getContent(), true) ?? [];
        $dto        = QuoteRequest::fromArray($payload);
        $violations = $validator->validate($dto);

        if (count($violations) > 0) {
            return $this->json([
                'message' => 'Validation échouée.',
                'errors'  => $validationResponder->violationsToArray($violations),
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $mapper->mapToEntity($dto, $quote, $cityRepository, $companyRepository);
        $entityManager->flush();

        return $this->json([
            'message' => 'Devis mis à jour avec succès.',
            'data'    => $mapper->toArray($quote),
        ]);
    }

    // ── NEW: get by UUID + token (for React QuoteShowPage) ────────────────────

    #[Route('/{uuid}', name: 'show_by_uuid', requirements: ['uuid' => '[0-9a-f\-]{36}'], methods: ['GET'])]
    public function showByUuid(
        string $uuid,
        Request $request,
        QuoteRepository $quoteRepository,
        QuoteMapper $mapper,
    ): JsonResponse {
        $token = $request->query->get('token', '');
        $quote = $quoteRepository->findByUuid($uuid);

        if (!$quote || !$quote->isValidToken($token)) {
            return $this->json(['message' => 'Accès refusé.'], JsonResponse::HTTP_FORBIDDEN);
        }

        return $this->json(['data' => $mapper->toArray($quote)]);
    }

    // ── NEW: list offers for a quote ──────────────────────────────────────────

    #[Route('/{uuid}/offers', name: 'offers', requirements: ['uuid' => '[0-9a-f\-]{36}'], methods: ['GET'])]
    public function offers(
        string $uuid,
        Request $request,
        QuoteRepository $quoteRepository,
        QuoteEstimatorService $estimator,
        CompanyRepository $companyRepository,
        QuoteMapper $mapper,
    ): JsonResponse {
        $token = $request->query->get('token', '');
        $quote = $quoteRepository->findByUuid($uuid);

        if (!$quote || !$quote->isValidToken($token)) {
            return $this->json(['message' => 'Accès refusé.'], JsonResponse::HTTP_FORBIDDEN);
        }

        $companyName = $request->query->get('company', '');
        $company     = $companyName
            ? $companyRepository->findOneBy(['name' => $companyName, 'isActive' => true])
            : null;

        $offers    = $company
            ? $estimator->getOffersByCompany($quote, $company)
            : $estimator->getOffers($quote);

        $companies = $companyRepository->findActive();

        return $this->json([
            'quote'     => $mapper->toArray($quote),
            'offers'    => $offers,
            'companies' => array_map(fn($c) => ['name' => $c->getName()], $companies),
        ]);
    }

    // ── NEW: select an offer ──────────────────────────────────────────────────

    #[Route('/{uuid}/select-offer', name: 'select_offer', requirements: ['uuid' => '[0-9a-f\-]{36}'], methods: ['POST'])]
    public function selectOffer(
        string $uuid,
        Request $request,
        QuoteRepository $quoteRepository,
        QuoteEstimatorService $estimator,
        CompanyRepository $companyRepository,
        QuoteMapper $mapper,
        EntityManagerInterface $em,
    ): JsonResponse {
        $payload     = json_decode($request->getContent(), true) ?? [];
        $token       = $payload['token']      ?? '';
        $offerCode   = $payload['offer_code'] ?? '';
        $companyName = $payload['company']    ?? '';

        $quote = $quoteRepository->findByUuid($uuid);

        if (!$quote || !$quote->isValidToken($token)) {
            return $this->json(['message' => 'Accès refusé.'], JsonResponse::HTTP_FORBIDDEN);
        }

        if ($offerCode === '') {
            return $this->json(['message' => 'offer_code est requis.'], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Attach company if provided
        $company = null;
        if ($companyName !== '') {
            $company = $companyRepository->findOneBy(['name' => $companyName, 'isActive' => true]);
            if ($company) {
                $quote->setCompanyEntity($company);
                // Sync the enum if it exists
                $companyEnum = \App\Enum\Company::tryFrom($companyName);
                if ($companyEnum) {
                    $quote->setCompany($companyEnum);
                }
            }
        }

        // Find the matching offer price and store it
        $offers = $company
            ? $estimator->getOffersByCompany($quote, $company)
            : $estimator->getOffers($quote);

        foreach ($offers as $offer) {
            if ($offer['code'] === $offerCode) {
                $quote->setCustomEstimation((string) $offer['annual_price']);
                break;
            }
        }

        $quote->setSelectedOffer($offerCode);
        $quote->setStatus(\App\Enum\QuoteStatus::SUBMITTED);
        $quote->touch();
        $em->flush();

        return $this->json([
            'message' => 'Offre sélectionnée avec succès.',
            'data'    => $mapper->toArray($quote),
        ]);
    }

    // ── NEW: send recap email ─────────────────────────────────────────────────

    #[Route('/{uuid}/send-email', name: 'send_email', requirements: ['uuid' => '[0-9a-f\-]{36}'], methods: ['POST'])]
    public function sendEmail(
        string $uuid,
        Request $request,
        QuoteRepository $quoteRepository,
        QuoteMapper $mapper,
        EntityManagerInterface $em,
        MailerInterface $mailer,
    ): JsonResponse {
        $payload = json_decode($request->getContent(), true) ?? [];
        $token   = $payload['token'] ?? '';
        $email   = trim($payload['email'] ?? '');

        $quote = $quoteRepository->findByUuid($uuid);

        if (!$quote || !$quote->isValidToken($token)) {
            return $this->json(['message' => 'Accès refusé.'], JsonResponse::HTTP_FORBIDDEN);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json(['message' => 'Adresse email invalide.'], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Update email on quote if changed
        if ($quote->getEmail() !== $email) {
            $quote->setEmail($email);
            $em->flush();
        }

        try {
            $data = $mapper->toArray($quote);

            $body = sprintf(
                "Bonjour %s %s,\n\nVoici le récapitulatif de votre devis #%d.\n\n" .
                "Véhicule : %s — %s\nType d'assurance : %s\nImmatriculation : %s\n" .
                "%s\n\nCordialement,\nAksam Assurance",
                $data['firstName'],
                $data['lastName'],
                $data['id'],
                $data['vehicleBrand'],
                $data['fuelType'],
                $data['insuranceType'],
                $data['registrationNumber'],
                $data['selectedOffer']
                    ? sprintf("Offre choisie : %s — %s MAD/an", $data['selectedOffer'], number_format((float) $data['customEstimation'], 2, '.', ' '))
                    : 'Aucune offre sélectionnée.'
            );

            $message = (new Email())
                ->from('noreply@aksam-assurance.ma')
                ->to($email)
                ->subject(sprintf('Votre devis Aksam Assurance #%d', $data['id']))
                ->text($body);

            $mailer->send($message);

            return $this->json(['message' => sprintf('Email envoyé à %s.', $email)]);
        } catch (\Throwable $e) {
            return $this->json(
                ['message' => 'Erreur lors de l\'envoi : ' . $e->getMessage()],
                JsonResponse::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
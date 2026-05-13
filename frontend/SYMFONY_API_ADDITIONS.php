<?php

/**
 * Add these routes to your existing QuoteApiController.php
 * (or create a new ConfigApiController.php for the /api/config endpoint)
 *
 * These additions expose the endpoints the React frontend consumes.
 */

namespace App\Controller\Api;

use App\Repository\CityRepository;
use App\Repository\CompanyRepository;
use App\Repository\QuoteRepository;
use App\Service\QuoteEstimatorService;
use App\Service\QuoteMailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

// ── 1. Config endpoint ────────────────────────────────────────────────────────
// Add this to a new ConfigApiController or append to existing ApiController.

#[Route('/api/config', name: 'api_config', methods: ['GET'])]
class ConfigApiController extends AbstractController
{
    public function __invoke(CityRepository $cityRepo, CompanyRepository $companyRepo): JsonResponse
    {
        return $this->json([
            'cities'    => array_map(
                fn($c) => ['name' => $c->getName()],
                $cityRepo->findActive()
            ),
            'companies' => array_map(
                fn($c) => ['name' => $c->getName()],
                $companyRepo->findActive()
            ),
            'fuelTypes' => ['essence', 'diesel', 'hybride', 'electrique', 'gpl'],
            'vehicleBrands' => [
                'Toyota','Honda','Ford','BMW','Mercedes','Audi','Volkswagen',
                'Nissan','Hyundai','Kia','Peugeot','Citroën','Renault','Fiat',
                'Seat','Skoda','Opel','Volvo','Jeep','Subaru','Mazda','Lexus','Dacia',
            ],
        ]);
    }
}

// ── 2. Add these methods inside QuoteApiController ────────────────────────────

// #[Route('/{uuid}/offers', name: 'offers', requirements: ['uuid' => '[0-9a-f-]{36}'], methods: ['GET'])]
// public function offers(
//     string $uuid,
//     Request $request,
//     QuoteRepository $quoteRepository,
//     QuoteEstimatorService $estimator,
//     CompanyRepository $companyRepository,
// ): JsonResponse {
//     $token = $request->query->get('token', '');
//     $quote = $quoteRepository->findByUuid($uuid);
//
//     if (!$quote || !$quote->isValidToken($token)) {
//         return $this->json(['message' => 'Accès refusé.'], 403);
//     }
//
//     $companyName = $request->query->get('company', '');
//     $offers      = [];
//
//     if ($companyName) {
//         $company = $companyRepository->findOneBy(['name' => $companyName, 'isActive' => true]);
//         if ($company) {
//             $offers = $estimator->getOffersByCompany($quote, $company);
//         }
//     } else {
//         $offers = $estimator->getOffers($quote);
//     }
//
//     $companies = $companyRepository->findActive();
//
//     return $this->json([
//         'quote'     => $this->quoteMapper->toArray($quote),
//         'offers'    => $offers,
//         'companies' => array_map(fn($c) => ['name' => $c->getName()], $companies),
//     ]);
// }

// #[Route('/{uuid}/select-offer', name: 'select_offer', requirements: ['uuid' => '[0-9a-f-]{36}'], methods: ['POST'])]
// public function selectOffer(
//     string $uuid,
//     Request $request,
//     QuoteRepository $quoteRepository,
//     QuoteEstimatorService $estimator,
//     CompanyRepository $companyRepository,
//     EntityManagerInterface $em,
// ): JsonResponse {
//     $payload   = json_decode($request->getContent(), true) ?? [];
//     $token     = $payload['token'] ?? '';
//     $offerCode = $payload['offer_code'] ?? '';
//     $companyName = $payload['company'] ?? '';
//
//     $quote = $quoteRepository->findByUuid($uuid);
//     if (!$quote || !$quote->isValidToken($token)) {
//         return $this->json(['message' => 'Accès refusé.'], 403);
//     }
//
//     if ($offerCode) {
//         $company = $companyName
//             ? $companyRepository->findOneBy(['name' => $companyName, 'isActive' => true])
//             : $quote->getCompanyEntity();
//
//         if ($company) {
//             $quote->setCompanyEntity($company);
//             $companyEnum = \App\Enum\Company::tryFrom($companyName);
//             if ($companyEnum) $quote->setCompany($companyEnum);
//         }
//
//         $offers = $company
//             ? $estimator->getOffersByCompany($quote, $company)
//             : $estimator->getOffers($quote);
//
//         foreach ($offers as $offer) {
//             if ($offer['code'] === $offerCode) {
//                 $quote->setCustomEstimation((string) $offer['annual_price']);
//                 break;
//             }
//         }
//
//         $quote->setSelectedOffer($offerCode);
//         $quote->setStatus(\App\Enum\QuoteStatus::SUBMITTED);
//         $quote->touch();
//         $em->flush();
//     }
//
//     return $this->json(['message' => 'Offre sélectionnée.', 'data' => $this->quoteMapper->toArray($quote)]);
// }

// #[Route('/{uuid}/send-email', name: 'send_email', requirements: ['uuid' => '[0-9a-f-]{36}'], methods: ['POST'])]
// public function sendEmail(
//     string $uuid,
//     Request $request,
//     QuoteRepository $quoteRepository,
//     EntityManagerInterface $em,
//     QuoteMailerService $mailer,
// ): JsonResponse {
//     $payload = json_decode($request->getContent(), true) ?? [];
//     $token   = $payload['token'] ?? '';
//     $email   = trim($payload['email'] ?? '');
//
//     $quote = $quoteRepository->findByUuid($uuid);
//     if (!$quote || !$quote->isValidToken($token)) {
//         return $this->json(['message' => 'Accès refusé.'], 403);
//     }
//
//     if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
//         return $this->json(['message' => 'Email invalide.'], 422);
//     }
//
//     if ($quote->getEmail() !== $email) {
//         $quote->setEmail($email);
//         $em->flush();
//     }
//
//     try {
//         $mailer->sendRecap($quote);
//         return $this->json(['message' => 'Email envoyé à ' . $email]);
//     } catch (\Throwable $e) {
//         return $this->json(['message' => 'Erreur: ' . $e->getMessage()], 500);
//     }
// }

// ── 3. Also add token support to the existing GET /api/quotes/{id} ─────────────
// Replace the current show() method or add a UUID-based lookup:

// #[Route('/{uuid}', name: 'show_by_uuid', requirements: ['uuid' => '[0-9a-f-]{36}'], methods: ['GET'])]
// public function showByUuid(string $uuid, Request $request, QuoteRepository $quoteRepository): JsonResponse
// {
//     $token = $request->query->get('token', '');
//     $quote = $quoteRepository->findByUuid($uuid);
//     if (!$quote || !$quote->isValidToken($token)) {
//         return $this->json(['message' => 'Accès refusé.'], 403);
//     }
//     return $this->json(['data' => $this->quoteMapper->toArray($quote)]);
// }

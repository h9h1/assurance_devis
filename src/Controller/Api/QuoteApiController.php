<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\DTO\QuoteRequest;
use App\Entity\Quote;
use App\Repository\QuoteRepository;
use App\Service\ApiValidationResponder;
use App\Service\QuoteMapper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/quotes', name: 'api_quotes_')]
class QuoteApiController extends AbstractController
{
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
    ): JsonResponse {
        $payload = json_decode($request->getContent(), true) ?? [];
        $dto = QuoteRequest::fromArray($payload);
        $violations = $validator->validate($dto);

        if (count($violations) > 0) {
            return $this->json([
                'message' => 'Validation échouée.',
                'errors' => $validationResponder->violationsToArray($violations),
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $quote = $mapper->mapToEntity($dto);
        $entityManager->persist($quote);
        $entityManager->flush();

        return $this->json([
            'message' => 'Devis créé avec succès.',
            'data' => $mapper->toArray($quote),
        ], JsonResponse::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\\d+'], methods: ['GET'])]
    public function show(Quote $quote, QuoteMapper $mapper): JsonResponse
    {
        return $this->json(['data' => $mapper->toArray($quote)]);
    }

    #[Route('/{id}', name: 'update', requirements: ['id' => '\\d+'], methods: ['PUT'])]
    public function update(
        Request $request,
        Quote $quote,
        ValidatorInterface $validator,
        QuoteMapper $mapper,
        ApiValidationResponder $validationResponder,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $payload = json_decode($request->getContent(), true) ?? [];
        $dto = QuoteRequest::fromArray($payload);
        $violations = $validator->validate($dto);

        if (count($violations) > 0) {
            return $this->json([
                'message' => 'Validation échouée.',
                'errors' => $validationResponder->violationsToArray($violations),
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $mapper->mapToEntity($dto, $quote);
        $entityManager->flush();

        return $this->json([
            'message' => 'Devis mis à jour avec succès.',
            'data' => $mapper->toArray($quote),
        ]);
    }
    
}

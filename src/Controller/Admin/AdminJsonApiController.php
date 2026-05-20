<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\City;
use App\Entity\Company;
use App\Entity\CompanyOfferVariation;
use App\Entity\Offer;
use App\Entity\Quote;
use App\Enum\QuoteStatus;
use App\Repository\CityRepository;
use App\Repository\CompanyOfferVariationRepository;
use App\Repository\CompanyRepository;
use App\Repository\OfferRepository;
use App\Repository\QuoteRepository;
use App\Service\QuoteMapper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Fichier à créer : src/Controller/Admin/AdminJsonApiController.php
 *
 * Expose toutes les données admin en JSON sous /admin/api/*.
 * EasyAdmin reste intact sur /admin — ces routes s'ajoutent juste à côté.
 *
 * Après ajout : php bin/console cache:clear
 */
#[Route('/admin/api', name: 'admin_api_')]
#[IsGranted('ROLE_ADMIN')]
class AdminJsonApiController extends AbstractController
{
    // ══════════════════════════════════════════════════════════════
    // STATS  GET /admin/api/stats
    // ══════════════════════════════════════════════════════════════

    #[Route('/stats', name: 'stats', methods: ['GET'])]
    public function stats(QuoteRepository $qr, CompanyRepository $cr): JsonResponse
    {
        $total     = $qr->count([]);
        $confirmed = $qr->count(['status' => QuoteStatus::CONFIRMED]);
        $submitted = $qr->count(['status' => QuoteStatus::SUBMITTED]);
        $accepted  = $qr->count(['status' => QuoteStatus::ACCEPTED]);
        $rejected  = $qr->count(['status' => QuoteStatus::REJECTED]);

        $revenue = $qr->createQueryBuilder('q')
            ->select('SUM(q.customEstimation)')
            ->where('q.status = :s')->setParameter('s', QuoteStatus::ACCEPTED)
            ->getQuery()->getSingleScalarResult() ?? 0;

        $perDay = $qr->createQueryBuilder('q')
            ->select("DATE(q.createdAt) AS day, COUNT(q.id) AS total")
            ->where('q.createdAt >= :since')
            ->setParameter('since', new \DateTimeImmutable('-30 days'))
            ->groupBy('day')->orderBy('day', 'ASC')
            ->getQuery()->getArrayResult();

        $byType = $qr->createQueryBuilder('q')
            ->select('q.insuranceType, COUNT(q.id) AS total')
            ->groupBy('q.insuranceType')
            ->getQuery()->getArrayResult();

        $byStatus = $qr->createQueryBuilder('q')
            ->select('q.status, COUNT(q.id) AS total')
            ->groupBy('q.status')
            ->getQuery()->getArrayResult();

        $byCompanyRaw = $qr->createQueryBuilder('q')
            ->select('IDENTITY(q.companyEntity) AS cid, COUNT(q.id) AS total')
            ->where('q.companyEntity IS NOT NULL')
            ->groupBy('q.companyEntity')->orderBy('total', 'DESC')->setMaxResults(5)
            ->getQuery()->getArrayResult();

        $names = [];
        foreach ($cr->findAll() as $c) { $names[$c->getId()] = $c->getName(); }

        return $this->json([
            'summary' => [
                'total'      => $total,
                'confirmed'  => $confirmed,
                'submitted'  => $submitted,
                'accepted'   => $accepted,
                'rejected'   => $rejected,
                'revenue'    => round((float) $revenue, 2),
                'acceptRate' => $total > 0 ? round($accepted / $total * 100, 1) : 0,
            ],
            'perDay'    => $perDay,
            'byType'    => $byType,
            'byStatus'  => $byStatus,
            'byCompany' => array_map(fn($r) => [
                'name'  => $names[$r['cid']] ?? '—',
                'total' => (int) $r['total'],
            ], $byCompanyRaw),
        ]);
    }

    // ══════════════════════════════════════════════════════════════
    // QUOTES  /admin/api/quotes
    // ══════════════════════════════════════════════════════════════

    #[Route('/quotes', name: 'quotes', methods: ['GET'])]
    public function quotes(Request $r, QuoteRepository $qr, QuoteMapper $m): JsonResponse
    {
        $page   = max(1, (int) $r->query->get('page', 1));
        $limit  = min(50, max(1, (int) $r->query->get('limit', 20)));
        $status = $r->query->get('status');
        $search = trim($r->query->get('search', ''));

        $qb = $qr->createQueryBuilder('q')->orderBy('q.createdAt', 'DESC');

        if ($status && QuoteStatus::tryFrom($status)) {
            $qb->andWhere('q.status = :st')->setParameter('st', QuoteStatus::from($status));
        }
        if ($search !== '') {
            $qb->andWhere($qb->expr()->orX(
                'LOWER(q.lastName) LIKE :s',
                'LOWER(q.firstName) LIKE :s',
                'LOWER(q.phoneNumber) LIKE :s',
                'LOWER(q.registrationNumber) LIKE :s',
            ))->setParameter('s', '%' . strtolower($search) . '%');
        }

        $total   = (clone $qb)->select('COUNT(q.id)')->getQuery()->getSingleScalarResult();
        $results = $qb->setFirstResult(($page - 1) * $limit)->setMaxResults($limit)->getQuery()->getResult();

        return $this->json([
            'data' => array_map(fn($q) => $m->toArray($q), $results),
            'pagination' => [
                'page'       => $page,
                'limit'      => $limit,
                'total'      => (int) $total,
                'totalPages' => (int) ceil($total / $limit),
            ],
        ]);
    }

    #[Route('/quotes/{id}', name: 'quote_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function quoteShow(Quote $quote, QuoteMapper $m): JsonResponse
    {
        return $this->json(['data' => $m->toArray($quote)]);
    }

    // Correspond à admin_quote_confirm_quote
    #[Route('/quotes/{id}/confirm', name: 'quote_confirm', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function quoteConfirm(Quote $quote, EntityManagerInterface $em): JsonResponse
    {
        $quote->setStatus(QuoteStatus::CONFIRMED);
        $quote->touch();
        $em->flush();
        return $this->json(['message' => 'Devis confirmé.', 'status' => QuoteStatus::CONFIRMED->value]);
    }

    // Correspond à admin_quote_accept_quote
    #[Route('/quotes/{id}/accept', name: 'quote_accept', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function quoteAccept(Quote $quote, EntityManagerInterface $em): JsonResponse
    {
        $quote->setStatus(QuoteStatus::ACCEPTED);
        $quote->touch();
        $em->flush();
        return $this->json(['message' => 'Devis accepté.', 'status' => QuoteStatus::ACCEPTED->value]);
    }

    // Correspond à admin_quote_reject_quote
    #[Route('/quotes/{id}/reject', name: 'quote_reject', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function quoteReject(Quote $quote, EntityManagerInterface $em): JsonResponse
    {
        $quote->setStatus(QuoteStatus::REJECTED);
        $quote->touch();
        $em->flush();
        return $this->json(['message' => 'Devis rejeté.', 'status' => QuoteStatus::REJECTED->value]);
    }

    // Correspond à admin_quote_send_email_admin
    #[Route('/quotes/{id}/send-email', name: 'quote_send_email', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function quoteSendEmail(
        Quote $quote,
        QuoteMapper $m,
        MailerInterface $mailer,
    ): JsonResponse {
        $data = $m->toArray($quote);
        $to   = $quote->getEmail();

        if (!$to || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return $this->json(['message' => 'Email client invalide.'], 422);
        }

        $mailer->send(
            (new Email())
                ->from('noreply@aksam-assurance.ma')
                ->to($to)
                ->subject(sprintf('Votre devis Aksam Assurance #%d', $data['id']))
                ->text(sprintf(
                    "Bonjour %s %s,\n\nVoici le récapitulatif de votre devis #%d.\n\n"
                    . "Véhicule : %s — %s\nType d'assurance : %s\nImmatriculation : %s\nStatut : %s\n\n"
                    . "%s\n\nCordialement,\nAksam Assurance",
                    $data['firstName'], $data['lastName'], $data['id'],
                    $data['vehicleBrand'], $data['fuelType'],
                    $data['insuranceType'], $data['registrationNumber'],
                    $data['status'],
                    $data['customEstimation']
                        ? sprintf('Prime annuelle : %s MAD', number_format((float) $data['customEstimation'], 2, '.', ' '))
                        : 'Aucune offre sélectionnée.'
                ))
        );

        return $this->json(['message' => sprintf('Email envoyé à %s.', $to)]);
    }

    #[Route('/quotes/{id}', name: 'quote_delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function quoteDelete(Quote $quote, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($quote);
        $em->flush();
        return $this->json(['message' => 'Devis supprimé.']);
    }

    // ══════════════════════════════════════════════════════════════
    // CITIES  /admin/api/cities
    // ══════════════════════════════════════════════════════════════

    #[Route('/cities', name: 'cities', methods: ['GET'])]
    public function cities(CityRepository $cr): JsonResponse
    {
        return $this->json(['data' => array_map(
            fn($c) => $this->cityArr($c),
            $cr->findBy([], ['name' => 'ASC'])
        )]);
    }

    #[Route('/cities', name: 'city_create', methods: ['POST'])]
    public function cityCreate(Request $r, EntityManagerInterface $em): JsonResponse
    {
        $b = json_decode($r->getContent(), true) ?? [];
        if (empty($b['name'])) return $this->json(['message' => 'Le nom est requis.'], 422);
        $city = (new City())->setName(trim($b['name']))->setDescription($b['description'] ?? null)->setIsActive($b['isActive'] ?? true);
        $em->persist($city); $em->flush();
        return $this->json(['data' => $this->cityArr($city)], 201);
    }

    #[Route('/cities/{id}', name: 'city_update', requirements: ['id' => '\d+'], methods: ['PUT'])]
    public function cityUpdate(City $city, Request $r, EntityManagerInterface $em): JsonResponse
    {
        $b = json_decode($r->getContent(), true) ?? [];
        if (isset($b['name']))        $city->setName(trim($b['name']));
        if (isset($b['description'])) $city->setDescription($b['description']);
        if (isset($b['isActive']))    $city->setIsActive((bool) $b['isActive']);
        $city->touch(); $em->flush();
        return $this->json(['data' => $this->cityArr($city)]);
    }

    #[Route('/cities/{id}', name: 'city_delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function cityDelete(City $city, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($city); $em->flush();
        return $this->json(['message' => 'Ville supprimée.']);
    }

    private function cityArr(City $c): array
    {
        return ['id' => $c->getId(), 'name' => $c->getName(), 'description' => $c->getDescription(),
            'isActive' => $c->isActive(), 'createdAt' => $c->getCreatedAt()->format('Y-m-d H:i'),
            'updatedAt' => $c->getUpdatedAt()->format('Y-m-d H:i')];
    }

    // ══════════════════════════════════════════════════════════════
    // COMPANIES  /admin/api/companies
    // ══════════════════════════════════════════════════════════════

    #[Route('/companies', name: 'companies', methods: ['GET'])]
    public function companies(CompanyRepository $cr): JsonResponse
    {
        return $this->json(['data' => array_map(fn($c) => $this->companyArr($c), $cr->findBy([], ['name' => 'ASC']))]);
    }

    #[Route('/companies', name: 'company_create', methods: ['POST'])]
    public function companyCreate(Request $r, EntityManagerInterface $em): JsonResponse
    {
        $b = json_decode($r->getContent(), true) ?? [];
        if (empty($b['name'])) return $this->json(['message' => 'Le nom est requis.'], 422);
        $c = (new Company())->setName(trim($b['name']))->setIsActive($b['isActive'] ?? true);
        if (method_exists($c, 'setDescription') && isset($b['description'])) $c->setDescription($b['description']);
        $em->persist($c); $em->flush();
        return $this->json(['data' => $this->companyArr($c)], 201);
    }

    #[Route('/companies/{id}', name: 'company_update', requirements: ['id' => '\d+'], methods: ['PUT'])]
    public function companyUpdate(Company $company, Request $r, EntityManagerInterface $em): JsonResponse
    {
        $b = json_decode($r->getContent(), true) ?? [];
        if (isset($b['name']))     $company->setName(trim($b['name']));
        if (isset($b['isActive'])) $company->setIsActive((bool) $b['isActive']);
        if (method_exists($company, 'setDescription') && isset($b['description'])) $company->setDescription($b['description']);
        $company->touch(); $em->flush();
        return $this->json(['data' => $this->companyArr($company)]);
    }

    #[Route('/companies/{id}', name: 'company_delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function companyDelete(Company $company, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($company); $em->flush();
        return $this->json(['message' => 'Compagnie supprimée.']);
    }

    private function companyArr(Company $c): array
    {
        return ['id' => $c->getId(), 'name' => $c->getName(), 'isActive' => $c->isActive(),
            'createdAt' => $c->getCreatedAt()->format('Y-m-d H:i'),
            'updatedAt' => $c->getUpdatedAt()->format('Y-m-d H:i')];
    }

    // ══════════════════════════════════════════════════════════════
    // OFFERS  /admin/api/offers
    // ══════════════════════════════════════════════════════════════

    #[Route('/offers', name: 'offers', methods: ['GET'])]
    public function offers(OfferRepository $or): JsonResponse
    {
        return $this->json(['data' => array_map(fn($o) => $this->offerArr($o), $or->findBy([], ['annualPrice' => 'ASC']))]);
    }

    #[Route('/offers', name: 'offer_create', methods: ['POST'])]
    public function offerCreate(Request $r, EntityManagerInterface $em): JsonResponse
    {
        $b = json_decode($r->getContent(), true) ?? [];
        foreach (['code', 'title', 'description', 'annualPrice', 'monthlyPrice'] as $f) {
            if (empty($b[$f])) return $this->json(['message' => "$f est requis."], 422);
        }
        $o = (new Offer())->setCode($b['code'])->setTitle($b['title'])->setDescription($b['description'])
            ->setAnnualPrice((string) $b['annualPrice'])->setMonthlyPrice((string) $b['monthlyPrice'])
            ->setIsActive($b['isActive'] ?? true);
        $em->persist($o); $em->flush();
        return $this->json(['data' => $this->offerArr($o)], 201);
    }

    #[Route('/offers/{id}', name: 'offer_update', requirements: ['id' => '\d+'], methods: ['PUT'])]
    public function offerUpdate(Offer $offer, Request $r, EntityManagerInterface $em): JsonResponse
    {
        $b = json_decode($r->getContent(), true) ?? [];
        if (isset($b['code']))         $offer->setCode($b['code']);
        if (isset($b['title']))        $offer->setTitle($b['title']);
        if (isset($b['description']))  $offer->setDescription($b['description']);
        if (isset($b['annualPrice']))  $offer->setAnnualPrice((string) $b['annualPrice']);
        if (isset($b['monthlyPrice'])) $offer->setMonthlyPrice((string) $b['monthlyPrice']);
        if (isset($b['isActive']))     $offer->setIsActive((bool) $b['isActive']);
        $offer->touch(); $em->flush();
        return $this->json(['data' => $this->offerArr($offer)]);
    }

    #[Route('/offers/{id}', name: 'offer_delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function offerDelete(Offer $offer, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($offer); $em->flush();
        return $this->json(['message' => 'Offre supprimée.']);
    }

    private function offerArr(Offer $o): array
    {
        return ['id' => $o->getId(), 'code' => $o->getCode(), 'title' => $o->getTitle(),
            'description' => $o->getDescription(), 'annualPrice' => $o->getAnnualPrice(),
            'monthlyPrice' => $o->getMonthlyPrice(), 'isActive' => $o->isActive(),
            'createdAt' => $o->getCreatedAt()->format('Y-m-d H:i'),
            'updatedAt' => $o->getUpdatedAt()->format('Y-m-d H:i')];
    }

    // ══════════════════════════════════════════════════════════════
    // VARIATIONS  /admin/api/variations
    // ══════════════════════════════════════════════════════════════

    #[Route('/variations', name: 'variations', methods: ['GET'])]
    public function variations(
        Request $r,
        CompanyOfferVariationRepository $vr,
    ): JsonResponse {
        $qb = $vr->createQueryBuilder('v')
            ->join('v.company', 'c')->join('v.offer', 'o')
            ->orderBy('c.name', 'ASC')->addOrderBy('o.title', 'ASC');

        if ($cid = $r->query->get('company')) {
            $qb->andWhere('c.id = :cid')->setParameter('cid', $cid);
        }

        return $this->json(['data' => array_map(
            fn($v) => $this->varArr($v),
            $qb->getQuery()->getResult()
        )]);
    }

    #[Route('/variations', name: 'variation_create', methods: ['POST'])]
    public function variationCreate(
        Request $r, EntityManagerInterface $em,
        CompanyRepository $cr, OfferRepository $or,
    ): JsonResponse {
        $b = json_decode($r->getContent(), true) ?? [];
        $company = $cr->find($b['companyId'] ?? 0);
        $offer   = $or->find($b['offerId']   ?? 0);
        if (!$company || !$offer) return $this->json(['message' => 'Compagnie ou offre introuvable.'], 422);

        $v = (new CompanyOfferVariation())
            ->setCompany($company)->setOffer($offer)
            ->setVariationType($b['variationType'] ?? CompanyOfferVariation::TYPE_PERCENT)
            ->setValue((string) ($b['value'] ?? 0))
            ->setIsActive($b['isActive'] ?? true);
        $em->persist($v); $em->flush();
        return $this->json(['data' => $this->varArr($v)], 201);
    }

    #[Route('/variations/{id}', name: 'variation_update', requirements: ['id' => '\d+'], methods: ['PUT'])]
    public function variationUpdate(
        CompanyOfferVariation $v, Request $r, EntityManagerInterface $em,
        CompanyRepository $cr, OfferRepository $or,
    ): JsonResponse {
        $b = json_decode($r->getContent(), true) ?? [];
        if (!empty($b['companyId'])) { $c = $cr->find($b['companyId']); if ($c) $v->setCompany($c); }
        if (!empty($b['offerId']))   { $o = $or->find($b['offerId']);   if ($o) $v->setOffer($o); }
        if (isset($b['variationType'])) $v->setVariationType($b['variationType']);
        if (isset($b['value']))         $v->setValue((string) $b['value']);
        if (isset($b['isActive']))      $v->setIsActive((bool) $b['isActive']);
        $v->touch(); $em->flush();
        return $this->json(['data' => $this->varArr($v)]);
    }

    #[Route('/variations/{id}', name: 'variation_delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function variationDelete(CompanyOfferVariation $v, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($v); $em->flush();
        return $this->json(['message' => 'Variation supprimée.']);
    }

    private function varArr(CompanyOfferVariation $v): array
    {
        return ['id' => $v->getId(),
            'companyId' => $v->getCompany()->getId(), 'companyName' => $v->getCompany()->getName(),
            'offerId' => $v->getOffer()->getId(), 'offerTitle' => $v->getOffer()->getTitle(),
            'offerCode' => $v->getOffer()->getCode(),
            'variationType' => $v->getVariationType(), 'value' => $v->getValue(),
            'isActive' => $v->isActive(),
            'createdAt' => $v->getCreatedAt()->format('Y-m-d H:i'),
            'updatedAt' => $v->getUpdatedAt()->format('Y-m-d H:i')];
    }
}

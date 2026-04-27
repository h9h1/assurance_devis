<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Company;
use App\Entity\CompanyOfferVariation;
use App\Entity\Offer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CompanyOfferVariation>
 */
class CompanyOfferVariationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CompanyOfferVariation::class);
    }

    /**
     * @return array<int, CompanyOfferVariation>  clé = offer.id
     */
    public function findActiveByCompanyIndexedByOffer(Company $company): array
    {
        $results = $this->createQueryBuilder('v')
            ->join('v.offer', 'o')
            ->where('v.company = :company')
            ->andWhere('v.isActive = true')
            ->setParameter('company', $company)
            ->getQuery()
            ->getResult();

        $indexed = [];
        foreach ($results as $variation) {
            $indexed[$variation->getOffer()->getId()] = $variation;
        }
        return $indexed;
    }

    public function findOneByCompanyAndOffer(Company $company, Offer $offer): ?CompanyOfferVariation
    {
        return $this->findOneBy(['company' => $company, 'offer' => $offer]);
    }

    public function findByCompany(Company $company): array
    {
        return $this->createQueryBuilder('v')
            ->join('v.offer', 'o')
            ->where('v.company = :company')
            ->setParameter('company', $company)
            ->orderBy('o.title', 'ASC')
            ->getQuery()
            ->getResult();
    }
}

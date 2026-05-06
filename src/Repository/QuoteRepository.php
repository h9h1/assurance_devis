<?php



namespace App\Repository;

use App\Entity\Quote;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Quote>
 */
class QuoteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Quote::class);
    }

    /** @return Quote[] */
    public function findLatest(int $limit = 20): array
    {
        return $this->createQueryBuilder('q')
            ->orderBy('q.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByUuid(string $uuid): ?\App\Entity\Quote
    {
        return $this->findOneBy(['uuid' => $uuid]);
    }

    public function findByUuidAndToken(string $uuid, string $token): ?\App\Entity\Quote
    {
        return $this->findOneBy(['uuid' => $uuid, 'accessToken' => $token]);
    }

}
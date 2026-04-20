<?php

namespace App\Repository;

use App\Entity\RefreshToken;
use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Gesdinet\JWTRefreshTokenBundle\Doctrine\RefreshTokenRepositoryInterface;

/**
 * @extends ServiceEntityRepository<RefreshToken>
 *
 * @implements RefreshTokenRepositoryInterface<RefreshToken>
 */
class RefreshTokenRepository extends ServiceEntityRepository implements RefreshTokenRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RefreshToken::class);
    }

    public function findInvalid(?DateTimeInterface $datetime = null): iterable
    {
        $qb = $this->createQueryBuilder('r');

        if (null !== $datetime) {
            $qb->where('r.valid < :datetime')
                ->setParameter('datetime', $datetime);
        }

        return $qb->getQuery()->getResult();
    }

    public function findInvalidBatch(?DateTimeInterface $datetime = null, ?int $batchSize = null, int $offset = 0): iterable
    {
        $qb = $this->createQueryBuilder('r');

        if (null !== $datetime) {
            $qb->where('r.valid < :datetime')
                ->setParameter('datetime', $datetime);
        }

        if (null !== $batchSize) {
            $qb->setMaxResults($batchSize);
        }

        $qb->setFirstResult($offset);

        return $qb->getQuery()->getResult();
    }
}

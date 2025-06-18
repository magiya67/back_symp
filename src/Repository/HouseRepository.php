<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\House;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<House>
 *
 * @method null|House find($id, $lockMode = null, $lockVersion = null)
 * @method null|House findOneBy(array $criteria, array $orderBy = null)
 * @method House[]    findAll()
 * @method House[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
final class HouseRepository extends ServiceEntityRepository
{
    /**
     * @psalm-suppress UnusedParam, PossiblyUnusedMethod
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, House::class);
    }

    /**
     * @psalm-suppress UnusedParam
     */
    public function save(House $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @psalm-suppress UnusedParam
     */
    public function remove(House $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}

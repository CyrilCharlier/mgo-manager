<?php

namespace App\Repository;

use App\Entity\Historique;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Historique>
 */
class HistoriqueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Historique::class);
    }

    public function findLast100ByUser(User $user): array
    {
        return $this->createQueryBuilder('h')
            ->where('h.user = :user')
            ->setParameter('user', $user)
            ->orderBy('h.horodatage', 'DESC')
            ->setMaxResults(100)
            ->getQuery()
            ->getResult();
    }
}

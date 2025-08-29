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

    /**
     * @return array<int, Historique>
     */
    public function findLastXByUser(User $user, int $nb = 100): array
    {
        return $this->createQueryBuilder('h')
            ->join('h.compte', 'c')
            ->where('h.user = :user')
            ->orWhere('c.isGroupe = true')
            ->setParameter('user', $user)
            ->orderBy('h.horodatage', 'DESC')
            ->setMaxResults($nb)
            ->getQuery()
            ->getResult();
    }
}

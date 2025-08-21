<?php

namespace App\Repository;

use App\Entity\Compte;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Compte>
 */
class CompteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Compte::class);
    }

    public function findByUserWithCartesAndAlbum(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.carteObtenues', 'co')->addSelect('co')
            ->leftJoin('co.carte', 'ca')->addSelect('ca')
            ->leftJoin('ca.s', 's')->addSelect('s')
            ->leftJoin('s.album', 'a')->addSelect('a')
            ->andWhere('c.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

    public function findByWithoutUserByCartesAndAlbum(): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.carteObtenues', 'co')->addSelect('co')
            ->leftJoin('co.carte', 'ca')->addSelect('ca')
            ->leftJoin('ca.s', 's')->addSelect('s')
            ->leftJoin('s.album', 'a')->addSelect('a')
            ->andWhere('c.isGroupe = :isGroupe')
            ->setParameter('isGroupe', true)
            ->getQuery()
            ->getResult();
    }

    public function findGroupes(): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.isGroupe = :val')
            ->setParameter('val', true)
            ->getQuery()
            ->getResult();
    }
}

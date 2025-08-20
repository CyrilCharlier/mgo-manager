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

    //    /**
    //     * @return Compte[] Returns an array of Compte objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Compte
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}

<?php

namespace App\Repository;

use App\Entity\Carte;
use App\Entity\CarteObtenue;
use App\Entity\Compte;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CarteObtenue>
 */
class CarteObtenueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CarteObtenue::class);
    }

    public function findOneByCarteCompte(Carte $carte, Compte $compte): ?CarteObtenue
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.carte = :carte')
            ->setParameter('carte', $carte)
            ->andWhere('c.compte = :compte')
            ->setParameter('compte', $compte)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    //    /**
    //     * @return CarteObtenue[] Returns an array of CarteObtenue objects
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

    //    public function findOneBySomeField($value): ?CarteObtenue
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}

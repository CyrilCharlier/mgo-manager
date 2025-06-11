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

    /**
     * @return array<int, array{nomCarte: string, total: string}>
     */
    public function getStatistiquesGlobales(): array
    {
        return $this->createQueryBuilder('co')
            ->select('c.name AS nomCarte', 'SUM(co.nombre) AS total') // adapte les champs si besoin
            ->join('co.carte', 'c')
            ->groupBy('co.carte')
            ->orderBy('total', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<array{etoiles: int, total: numeric-string}>
     */
    public function getStatistiquesParEtoiles(): array
    {
        return $this->createQueryBuilder('co')
            ->select('c.nbetoile AS etoiles', 'SUM(co.nombre) AS total')
            ->join('co.carte', 'c')
            ->groupBy('c.nbetoile')
            ->orderBy('etoiles', 'ASC')
            ->getQuery()
            ->getResult();
    }
}

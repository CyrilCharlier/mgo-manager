<?php

namespace App\Controller;

use App\Repository\CarteObtenueRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class StatistiquesController extends AbstractController
{
    #[Route('/statistiques/cartes', name: 'app_statistiques_cartes')]
    public function cartes(
        CarteObtenueRepository $carteObtenueRepository
    ): Response {
        $stats = $carteObtenueRepository->getStatistiquesGlobales();
        $stats = array_slice($stats, 0, 50);

        $statsEtoiles = $carteObtenueRepository->getStatistiquesParEtoiles();

        return $this->render('statistiques/cartes.html.twig', [
            'stats' => $stats,
            'statsEtoiles' => $statsEtoiles,
        ]);
    }
}

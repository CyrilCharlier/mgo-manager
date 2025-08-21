<?php

// src/Controller/PublicationController.php

namespace App\Controller;

use App\Entity\Compte;
use App\Entity\Publication;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class PublicationController extends AbstractController
{
    #[Route('/publication/jour/{id}', name: 'app_publication_du_jour', methods: ['POST'])]
    public function publicationDuJour(Compte $compte, EntityManagerInterface $em, Request $request): JsonResponse
    {
        $today = (new \DateTime())->format('Y-m-d');

        // Vérifier si déjà une publication aujourd'hui
        foreach ($compte->getPublications() as $pub) {
            if ($pub->getDate()->format('Y-m-d') === $today) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Publication déjà enregistrée pour aujourd’hui.',
                ]);
            }
        }

        // Créer une nouvelle publication
        $publication = new Publication();
        $publication->setCompte($compte);
        $publication->setDate(new \DateTimeImmutable());

        $em->persist($publication);
        $em->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Publication du jour enregistrée avec succès.',
        ]);
    }

    #[Route('/publication/jour/{id}/{date}', name: 'app_publication_date', methods: ['POST'])]
    public function publicationAvecDate(Compte $compte, string $date, EntityManagerInterface $em): JsonResponse {
        try {
            $dateObj = new \DateTimeImmutable($date);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Date invalide.',
            ], 400);
        }

        // Vérifier si une publication existe déjà à cette date
        foreach ($compte->getPublications() as $pub) {
            if ($pub->getDate()->format('Y-m-d') === $dateObj->format('Y-m-d')) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Publication déjà enregistrée pour cette date.',
                ]);
            }
        }

        // Créer la publication
        $publication = new Publication();
        $publication->setCompte($compte);
        $publication->setDate($dateObj);

        $em->persist($publication);
        $em->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Publication enregistrée avec succès.',
        ]);
    }
}
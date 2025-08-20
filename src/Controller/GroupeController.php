<?php

namespace App\Controller;

use App\Entity\Carte;
use App\Entity\CarteObtenue;
use App\Entity\Compte;
use App\Entity\Historique;
use App\Entity\Notification;
use App\Entity\Transfert;
use App\Form\CompteForm;
use App\Form\TransfertForm;
use App\Repository\AlbumRepository;
use App\Repository\CarteRepository;
use App\Repository\CompteRepository;
use App\Repository\HistoriqueRepository;
use App\Service\Calcul;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Uid\Uuid;

#[IsGranted('ROLE_ADMIN_GROUPE')]
final class GroupeController extends AbstractController
{
    /**
     * Get the currently authenticated user.
     *
     * @return \App\Entity\User
     */
    protected function getCurrentUser(): \App\Entity\User
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        return $user;
    }

    #[Route('/groupe/user/{id}', name: 'app_groupe_user', requirements: ['id' => '\d+'])]
    public function index(Compte $compte, AlbumRepository $albumRepository, Security $security): Response
    {
        if(!$compte->isGroupe()) {
            return $this->redirectToRoute('app_groupe_list');
        }
        $album = $albumRepository->findAlbumWithSetsAndCartesActive();

        return $this->render('compte/detail.html.twig', [
            'album' => $album,
            'compte' => $compte,
        ]);
    }

    #[Route('/groupe/list', name: 'app_groupe_list')]
    public function list(Calcul $calcul, AlbumRepository $albumRepository, CompteRepository $compteRepository): Response
    {
        $album = $albumRepository->findAlbumWithSetsAndCartesActive();
        $comptes = $compteRepository->findByWithoutUserByCartesAndAlbum();
        $etoilesDoublesParCompte = [];
        $nombreCartesObtenuesParCompte = [];

        foreach ($comptes as $compte)
        {
            $etoilesDoublesParCompte[$compte->getId()] = $calcul->calculerEtoilesDoublees($compte, $album);
            $nombreCartesObtenuesParCompte[$compte->getId()] = $calcul->calculerNombreCartesObtenuesSurAlbum($compte, $album);
        }

        return $this->render('groupe/groupe.list.html.twig', [
            'album' => $album,
            'userComptes' => $comptes,
            'etoilesDoublesParCompte' => $etoilesDoublesParCompte,
            'nombreCartesObtenuesParCompte' => $nombreCartesObtenuesParCompte,
        ]);
    }

}
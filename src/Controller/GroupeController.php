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
    private MailerInterface $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

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

    #[Route('/groupe/compte/add', name: 'app_groupe_compte_add')]
    public function compteAdd(Request $request, EntityManagerInterface $em): Response
    {
        $compte = new Compte();
        $form = $this->createForm(CompteForm::class, $compte, [
            'action' => $this->generateUrl('app_groupe_compte_add'),
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $compte = $form->getData();
            $compte->setIsGroupe(true);
            $em->persist($compte);
            $em->flush();

            $email = (new Email())
                ->from('mgo@charlier.cloud')
                ->to('cyril.charlier@gmail.com')
                ->subject('[MGO] Création compte MGO')
                ->text('Compte '.$compte->getName().' de Groupe créé avec succés par ' .$this->getCurrentUser()->getUsername());
            $this->mailer->send($email);

            return $this->redirectToRoute('app_groupe_list');
        }
        return $this->render('compte/form.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/groupe/recherche/carteobtenue/{id}', name: 'app_groupe_recherche_carte_otenue')]
    public function rechercheCarteObtenue(Carte $carte, CompteRepository $compteRepository): Response
    {
        $comptesRetour = [];

        foreach($compteRepository->findGroupes() as $c) {
            $carteObtenues = $c->getCarteObtenue($carte);
            if(!is_null($carteObtenues))
            {
                $comptesRetour[] = [
                    'id' => $c->getId(), 
                    'name' => $c->getName(), 
                    'nombre' => $carteObtenues->getNombre()
                ];
            } else {
                $comptesRetour[] = [
                    'id' => $c->getId(), 
                    'name' => $c->getName(), 
                    'nombre' => 0
                ];
            }
        }
        return $this->json([
            'success' => true,
            'message' => ['comptes' => $comptesRetour, 'nombre' => count($comptesRetour)],
        ]);
    }

    #[Route('/groupe/suivi', name: 'app_groupe_suivi')]
    public function stat(CompteRepository $compteRepository, AlbumRepository $albumRepository): Response
    {
        $userComptes = $compteRepository->findByWithoutUserByCartesAndAlbum();
        $album = $albumRepository->findAlbumWithSetsAndCartesActive();

        // Générer les 7 derniers jours (aujourd'hui inclus)
        $last7Days = [];
        $today = new \DateTimeImmutable('today');
        for ($i = 0; $i < 7; $i++) {
            $last7Days[] = $today->sub(new \DateInterval("P{$i}D"));
        }
        $last7Days = array_reverse($last7Days); // du plus ancien au plus récent

        return $this->render('groupe/groupe.stat.html.twig', [
            'album'       => $album,
            'userComptes' => $userComptes,
            'last7Days'   => $last7Days,
        ]);
    }
}
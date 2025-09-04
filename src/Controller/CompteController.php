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
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Uid\Uuid;

final class CompteController extends AbstractController
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
        return $this->getUser();
    }

    #[Route('/', name: 'app_dashboard')]
    public function accueil(Calcul $calcul, AlbumRepository $albumRepository, CompteRepository $compteRepository): Response
    {
        $album = $albumRepository->findAlbumWithSetsAndCartesActive();
        $comptes = $compteRepository->findByUserWithCartesAndAlbum($this->getCurrentUser());
        $etoilesDoublesParCompte = [];
        $nombreCartesObtenuesParCompte = [];

        foreach ($comptes as $compte)
        {
            $etoilesDoublesParCompte[$compte->getId()] = $calcul->calculerEtoilesDoublees($compte, $album);
            $nombreCartesObtenuesParCompte[$compte->getId()] = $calcul->calculerNombreCartesObtenuesSurAlbum($compte, $album);
        }

        return $this->render('compte/index.html.twig', [
            'album' => $album,
            'userComptes' => $comptes,
            'etoilesDoublesParCompte' => $etoilesDoublesParCompte,
            'nombreCartesObtenuesParCompte' => $nombreCartesObtenuesParCompte,
        ]);
    }

    #[Route('/compte/add', name: 'app_compte_add')]
    public function compteAdd(Request $request, EntityManagerInterface $em): Response
    {
        $compte = new Compte();
        $form = $this->createForm(CompteForm::class, $compte, [
            'action' => $this->generateUrl('app_compte_add'),
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $compte = $form->getData();
            $compte->setUser($this->getCurrentUser());
            $em->persist($compte);
            $h = new Historique();
            $h->setTitre('Création compte');
            $h->setDescription('Compte ['.$compte->getName().'] créé.');
            $h->setCompte($compte);
            $h->setUser($compte->getUser());
            $h->setIcone('person_add');
            $em->persist($h);
            $em->flush();

            $email = (new Email())
                ->from('mgo@charlier.cloud')
                ->to('cyril.charlier@gmail.com')
                ->subject('[MGO] Création compte MGO')
                ->text('Compte '.$compte->getName().' créé avec succés par ' .$this->getCurrentUser()->getUsername());
            $this->mailer->send($email);

            return $this->redirectToRoute('app_dashboard');
        }
        return $this->render('compte/form.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/compte/{id}/delete', name: 'app_compte_delete')]
    public function compteDelete(Compte $c, EntityManagerInterface $em): Response
    {
        if($this->getCurrentUser() != $c->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }
        $h = new Historique();
        $h->setTitre('Compte Supprimé');
        $h->setDescription('Compte ['.$c->getUser()->getUsername().']['.$c->getName().']['.$c->getMGO().'] supprimé.');
        $h->setCompte($c);
        $h->setUser($c->getUser());
        $h->setIcone('person_remove');
        $em->persist($h);
        $em->remove($c);
        $em->flush();
        return $this->redirectToRoute('app_dashboard');
    }

    #[Route('/compte/{id}/edit', name: 'app_compte_edit')]
    public function compteEdit(Request $request, Compte $c, EntityManagerInterface $em): Response
    {
        if($this->getCurrentUser() != $c->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        $form = $this->createForm(CompteForm::class, $c, [
            'is_admin_groupe' => $this->isGranted('ROLE_ADMIN_GROUPE'),
            'action' => $this->generateUrl('app_compte_edit', ['id' => $c->getId()]),
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $c = $form->getData();
            $c->setUser($this->getCurrentUser());
            $em->persist($c);
            $h = new Historique();
            $h->setTitre('Compte modifié');
            $h->setDescription('Compte ['.$c->getUser()->getUsername().']['.$c->getName().']['.$c->getMGO().'] modifié.');
            $h->setCompte($c);
            $h->setUser($c->getUser());
            $h->setIcone('manage_accounts');
            $em->persist($h);
            $em->flush();
            return $this->redirectToRoute('app_dashboard');
        }
        return $this->render('compte/form.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/notification/delete', name: 'app_notification_delete_all')]
    public function notificationDelete(EntityManagerInterface $em): Response
    {
        $notifications = $this->getCurrentUser()->getNotifications();
        foreach ($notifications as $notification) {
            $em->remove($notification);
        }
        $em->flush();        
        return $this->redirectToRoute('app_dashboard');
    }

    #[Route('/notifications/{id}', name: 'app_notification_delete', methods: ['DELETE'])]
    public function delete(Notification $notification, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getCurrentUser();
        if ($notification->getUser() !== $user) {
            return new JsonResponse(['error' => 'Forbidden'], 403);
        }

        $em->remove($notification);
        $em->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/{id}', name: 'app_compte_accueil', requirements: ['id' => '\d+'])]
    public function index(Compte $compte, AlbumRepository $albumRepository, Security $security): Response
    {
        $u = $this->getCurrentUser();
        if($u != $compte->getUser()) {
            return $this->redirectToRoute('app_login');
        }
        $album = $albumRepository->findOneBy(['active' => true]);

        return $this->render('compte/detail.html.twig', [
            'album' => $album,
            'compte' => $compte,
        ]);
    }

    #[Route('/compte/donne', name: 'app_compte_donne')]
    public function donne(AlbumRepository $albumRepository): Response
    {
        $album = $albumRepository->findOneBy(['active' => true]);
        return $this->render('compte/transfert.html.twig', [
            'album' => $album,
        ]);
    }

    #[Route('/timeline', name: 'app_timeline')]
    public function timeline(HistoriqueRepository $historiqueRepository): Response
    {
        $historiques = $historiqueRepository->findLastXByUser($this->getCurrentUser(),500);
        $grouped = [];

        foreach ($historiques as $histo) {
            $dateKey = $histo->getHorodatage()->format('d.m.Y');
            $grouped[$dateKey][] = $histo;
        }
        
        return $this->render('compte/timeline.html.twig', [
            'groupedHistoriques' => $grouped,
        ]);
    }

    #[Route('/historique/{id}', name: 'app_historique_get', methods: ['GET'])]
    public function getHistorique(int $id, HistoriqueRepository $historiqueRepository, Security $security): Response
    {
        $historique = $historiqueRepository->find($id);

        if (!$historique) {
            return $this->json([
                'success' => false,
                'message' => 'Historique non trouvé',
            ]);
        }

        if ($security->getUser() !== $historique->getUser()) {
            return $this->json([
                'success' => false,
                'message' => 'Accès refusé',
            ], 403); // Optionnel : code HTTP 403 interdit
        }

        return $this->json([
            'success' => true,
            'historique' => [
                'id' => $historique->getId(),
                'titre' => $historique->getTitre(),
                'description' => $historique->getDescription(),
                'horodatage' => $historique->getHorodatage()->format('d.m.Y H:i:s'),
                'compte' => $historique->getCompte()->getName(),
                'user' => $historique->getUser()->getUsername(),
                'icone' => $historique->getIcone(),
                'commentaires' => $historique->getCommentaire(),
            ]
        ]);
    }

    #[Route('/historique/{id}/commentaire', name: 'app_historique_add_commentaire', methods: ['POST'])]
    public function ajouterCommentaire(Historique $historique, Request $request, EntityManagerInterface $em, Security $security): JsonResponse
    {
        if ($security->getUser() !== $historique->getUser()) {
            return $this->json([
                'success' => false,
                'message' => 'Accès refusé',
            ], 403); // Optionnel : code HTTP 403 interdit
        }

        $data = json_decode($request->getContent(), true);
        $contenu = $data['contenu'] ?? '';

        if (!$contenu) {
            return new JsonResponse(['success' => false, 'message' => 'Aucun contenu fourni'], 400);
        }

        $historique->setCommentaire($contenu);
        $em->persist($historique);
        $em->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/compte/toggle-principal/{id}', name: 'compte_toggle_principal', methods: ['POST'])]
    public function togglePrincipal(
        Compte $compte,                 // Injection automatique de l'entité via ParamConverter
        EntityManagerInterface $em      // Injection automatique de l'EntityManager
    ): JsonResponse {
        $user = $this->getCurrentUser();

        // Vérifie que le compte appartient bien à l’utilisateur connecté
        if ($compte->getUser() !== $user) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }

        // Bascule le booléen principal
        $nouvelEtat = !$compte->isPrincipal();
        $compte->setPrincipal($nouvelEtat);

        $em->persist($compte);
        $em->flush();

        return $this->json([
            'success' => true,
            'compteId' => $compte->getId(),
            'principal' => $nouvelEtat,
        ]);
    }

    #[Route('/compte/{id}/pagepublique', name: 'app_compte_acces_publique')]
    public function activePagePublique(Compte $compte, AuthorizationCheckerInterface $authChecker, EntityManagerInterface $em): Response
    {
        $u = $this->getCurrentUser();
        if($u != $compte->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        $compte->setPublicToken( Uuid::v4()->toRfc4122());
        $em->persist($compte);
        $em->flush();

        return $this->redirectToRoute('app_dashboard');
    }

    #[Route('/public/compte/{token}', name: 'app_compte_public')]
    public function viewPagePublique(string $token, CompteRepository $cr, Calcul $calcul, AlbumRepository $ar): Response
    {
        $compte = $cr->findOneBy(['public_token' => $token]);
        $album = $ar->findOneBy(['active' => true]);

        if (!$compte) {
            return $this->redirectToRoute('app_dashboard');
        }

        $etoilesDoublesParCompte[$compte->getId()] = $calcul->calculerEtoilesDoublees($compte, $album);
        $nombreCartesObtenuesParCompte[$compte->getId()] = $calcul->calculerNombreCartesObtenuesSurAlbum($compte, $album);

        return $this->render('compte/public.html.twig', [
            'compte' => $compte,
            'album' => $album,
            'etoilesDoublesParCompte' => $etoilesDoublesParCompte,
            'nombreCartesObtenuesParCompte' => $nombreCartesObtenuesParCompte,
        ]);
    }
}

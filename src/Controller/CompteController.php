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
        $user = $this->getUser();
        return $user;
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

    #[Route('/compte/transfert', name: 'app_compte_transfert')]
    public function compteTransfert(Request $request, EntityManagerInterface $em, Security $security,): JsonResponse
    {
        $u = $security->getUser();
        $t = new Transfert();
        $formT = $this->createForm(TransfertForm::class, $t);
        $formT->handleRequest($request);

        if ($formT->isSubmitted() && $formT->isValid()) {
            $t = $formT->getData();
            $cFrom = $t->getCFrom();
            $cTo = $t->getcTo();
            $carte = $t->getCarte();
            
            if($cFrom->getUser() != $u || $cTo->getUser() != $u) {
                return $this->json([
                    'success' => false,
                    'message' => 'Vous ne pouvez pas transférer une carte d\'un compte à un autre qui ne vous appartient pas.'
                ]);
            }
            if($carte->isGolden()) {
                if(!$carte->isTransferable()) {
                    return $this->json([
                        'success' => false,
                        'message' => 'Vous ne pouvez pas transférer cette carte Gold.'
                    ]);
                }
            }
            if($cFrom == $cTo) {
                return $this->json([
                    'success' => false,
                    'message' => 'Vous ne pouvez pas transférer depuis et vers le même compte.'
                ]);
            }

            $coFrom = $cFrom->getCarteObtenue($carte);
            if(is_null($coFrom)) {
                return $this->json([
                    'success' => false,
                    'message' => 'Vous ne possédez pas '.$carte->getName().' pour '.$cFrom->getName()
                ]);
            }
            if($coFrom->getNombre() == 1) {
                return $this->json([
                    'success' => false,
                    'message' => 'Vous ne possédez pas '.$carte->getName().' en double dans '.$cFrom->getName()
                ]);
            }
            $coFrom->setNombre($coFrom->getNombre() - 1);
            $coTo = $cTo->getCarteObtenue($carte);
            if(is_null($coTo)) {
                $coTo = new CarteObtenue();
                $coTo->setCarte($carte);
                $coTo->setCompte($cTo);
                $coTo->setNombre(1);
                $cTo->addCarteObtenue($coTo);
                
            } else {
                $coTo->setNombre($coTo->getNombre() + 1);
            }
            $em->persist($coFrom);
            $em->persist($coTo);

            $h = new Historique();
            $h->setTitre('Transfert de carte');
            $h->setDescription('Carte ['.$carte->getS()->getAlbum()->getName().']['.$carte->getS()->getPage().' - '.$carte->getS()->getName().']['.$carte->getNum().' - '.$carte->getName().'] transférée de ['.$cFrom->getUser()->getUsername().']['.$cFrom->getName().'] à ['.$cTo->getUser()->getUsername().']['.$cTo->getName().']');
            $h->setCompte($cTo);
            $h->setUser($cTo->getUser());
            $h->setIcone('swap_horiz');
            $em->persist($h);

            $em->flush();

            return $this->json([
                'success' => true,
                'message' => $carte->getName().' a été transférée de '.$cFrom->getName().' à '.$cTo->getName()
            ]);
        }
        return $this->json([
            'success' => false,
            'message' => 'Vous devez soumettre le transfert via le formulaire.'
        ]);
    }

    #[Route('/compte/transfert/{fromid}/{toid}/{idcarte}', name: 'app_compte_transfert_api')]
    public function compteTransfertApi(CompteRepository $compteRepository, CarteRepository $carteRepository, EntityManagerInterface $em, Security $security, $fromid, $toid, $idcarte): JsonResponse
    {
        $u = $security->getUser();
        $cFrom = $compteRepository->find($fromid);
        $cTo = $compteRepository->find($toid);
        $carte = $carteRepository->find($idcarte);
        
        if (true) {
            if($cFrom->getUser() != $u || $cTo->getUser() != $u) {
                return $this->json([
                    'success' => false,
                    'message' => 'Vous ne pouvez pas transférer une carte d\'un compte à un autre qui ne vous appartient pas.'
                ]);
            }
            if($carte->isGolden()) {
                if(!$carte->isTransferable()) {
                    return $this->json([
                        'success' => false,
                        'message' => 'Vous ne pouvez pas transférer cette carte Gold.'
                    ]);
                }
            }
            if($cFrom == $cTo) {
                return $this->json([
                    'success' => false,
                    'message' => 'Vous ne pouvez pas transférer depuis et vers le même compte.'
                ]);
            }

            $coFrom = $cFrom->getCarteObtenue($carte);
            if(is_null($coFrom)) {
                return $this->json([
                    'success' => false,
                    'message' => 'Vous ne possédez pas '.$carte->getName().' pour '.$cFrom->getName()
                ]);
            }
            if($coFrom->getNombre() == 1) {
                return $this->json([
                    'success' => false,
                    'message' => 'Vous ne possédez pas '.$carte->getName().' en double dans '.$cFrom->getName()
                ]);
            }
            $coFrom->setNombre($coFrom->getNombre() - 1);
            $coTo = $cTo->getCarteObtenue($carte);
            if(is_null($coTo)) {
                $coTo = new CarteObtenue();
                $coTo->setCarte($carte);
                $coTo->setCompte($cTo);
                $coTo->setNombre(1);
                $cTo->addCarteObtenue($coTo);
                
            } else {
                $coTo->setNombre($coTo->getNombre() + 1);
            }
            $em->persist($coFrom);
            $em->persist($coTo);

            $h = new Historique();
            $h->setTitre('Transfert de carte');
            $h->setDescription('Carte ['.$carte->getS()->getAlbum()->getName().']['.$carte->getS()->getPage().' - '.$carte->getS()->getName().']['.$carte->getNum().' - '.$carte->getName().'] transférée de ['.$cFrom->getUser()->getUsername().']['.$cFrom->getName().'] à ['.$cTo->getUser()->getUsername().']['.$cTo->getName().']');
            $h->setCompte($cTo);
            $h->setUser($cTo->getUser());
            $h->setIcone('swap_horiz');
            $em->persist($h);

            $em->flush();

            return $this->json([
                'success' => true,
                'message' => $carte->getName().' a été transférée de '.$cFrom->getName().' à '.$cTo->getName()
            ]);
        }
        return $this->json([
            'success' => false,
            'message' => 'Vous devez soumettre le transfert via le formulaire.'
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

    #[Route('/{idcompte}/carte/obtenuemoins/{idcarte}', name: 'app_add_carte_obtenue_moins', methods: ['GET'])]
    public function addCarteObtenueMoins(
        #[MapEntity(mapping: ['idcarte' => 'id'])]
        Carte $c,
        #[MapEntity(mapping: ['idcompte' => 'id'])]
        Compte $compte,
        Security $security,
        EntityManagerInterface $em
    ): Response
    {
        $u = $this->getCurrentUser();
        if(!$compte->isGroupe())
        {
            if($u != $compte->getUser())
            {
                return $this->json([
                    'success' => false,
                    'message' => 'Vous ne pouvez pas enlever une carte pour un autre compte que le vôtre.'
                ]);
            }
        }
        if(is_null($compte->getCarteObtenue($c))){
            return $this->json([
                'success' => false,
                'message' => 'La carte doit déjà être marquée comme obtenue sur '.$compte->getName()
            ]);
        }
        $co = $compte->getCarteObtenue($c);
        $coDelete = false;
        if($co->getNombre() == 1){
            $em->remove($co);
            $coDelete = true;
        } else {
            $co->setNombre($co->getNombre()-1);
            $em->persist($co);
        }
        $h = new Historique();
        $h->setTitre('Carte Enlevée');
        $h->setDescription('Carte ['.$c->getS()->getAlbum()->getName().']['.$c->getS()->getPage().' - '.$c->getS()->getName().']['.$c->getNum().' - '.$c->getName().'] enlevée du compte ['.$compte->getName().']');
        $h->setCompte($compte);
        $h->setUser($u);
        $h->setIcone('do_not_disturb_on');
        $em->persist($h);
        $em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Carte enlevée avec succès sur '.$compte->getName(),
            'carteobtenue' => ($coDelete ? -1 : $co->getNombre()-1),
            'etoilecarteobtenue' => ($co->getNombre()-1) * $c->getNbetoile()
        ]);
    }

    #[Route('/rapide', name: 'app_rapide')]
    public function rapide(AlbumRepository $albumRepository): Response
    {
        $t = new Transfert();
        $form = $this->createForm(TransfertForm::class, $t, [
            'action' => $this->generateUrl('app_compte_transfert'),
        ]);
        $album = $albumRepository->findOneBy(['active' => true]);
        return $this->render('compte/rapide.html.twig', [
            'album' => $album,
            'formT' => $form->createView(),
        ]);
    }

    #[Route('/rapide/{idcompte}/{idcarte}', name: 'app_rapide_ajout')]
    public function rapideAjout(
        #[MapEntity(mapping: ['idcarte' => 'id'])]
        Carte $c,
        #[MapEntity(mapping: ['idcompte' => 'id'])]
        Compte $compte,
        EntityManagerInterface $em,
    ): Response
    {
        $u = $this->getCurrentUser();
        if(!$compte->isGroupe()) {
            if($u != $compte->getUser()) {
                return $this->json([
                    'success' => false,
                    'message' => 'Vous ne pouvez pas marquer une carte obtenue pour un autre compte que le vôtre'
                ]);
            }
        }
        $co = $compte->getCarteObtenue($c);
        if(is_null($co)){
            $co = new CarteObtenue();
            $co->setCarte($c);
            $co->setCompte($compte);
            $co->setNombre(1);
            $compte->addCarteObtenue($co);
            $em->persist($compte);
        } else {
            $co->setNombre($co->getNombre() + 1);
            $em->persist($co);
        }
        $h = new Historique();
        $h->setTitre('Carte Ajoutée');
        $h->setDescription('Carte ['.$c->getS()->getAlbum()->getName().']['.$c->getS()->getPage().' - '.$c->getS()->getName().']['.$c->getNum().' - '.$c->getName().'] ajoutée sur le compte ['.$compte->getName().']');
        $h->setCompte($compte);
        $h->setUser($u);
        $h->setIcone('add_card');
        $em->persist($h);
        $em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Carte ajoutée avec succès sur '.$compte->getName(),
            'carteobtenue' => $co->getNombre()-1,
            'etoilecarteobtenue' => ($co->getNombre()-1) * $c->getNbetoile(),
            'carte' => [
                'id' => $c->getId(),
                'name' => $c->getName(),
                'nbetoile' => $c->getNbetoile(),
                'nombre' => $co->getNombre(),
            ]
        ]);
    }

    #[Route('/recherche/carteobtenue/{id}', name: 'app_recherche_care_otenue')]
    public function rechercheCarteObtenue(Carte $carte, AuthorizationCheckerInterface $authChecker): Response
    {
        $comptesRetour = [];

        foreach($this->getCurrentUser()->getComptes() as $c) {
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

    #[Route('/compte/album/raz/{id}', name: 'app_compte_raz_compte')]
    public function razCompte(#[MapEntity(id: 'id')] Compte $compte, AlbumRepository $albumRepository, EntityManagerInterface $em): Response
    {
        $u = $this->getCurrentUser();
        if($u != $compte->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }
        $album = $albumRepository->findAlbumWithSetsAndCartesActive();

        foreach ($compte->getCarteObtenues() as $carteObtenue) {
            if($carteObtenue->getNombre()>1) {
                if ($carteObtenue->getCarte()->getS()->getAlbum() === $album) {
                    $carteObtenue->setNombre(1);
                    $em->persist($carteObtenue);
                }
            }
        }
        $em->flush();

        return $this->redirectToRoute('app_compte_accueil', ['id' => $compte->getId()]);
    }
}

<?php

namespace App\Controller;

use App\Entity\Carte;
use App\Entity\CarteObtenue;
use App\Entity\Compte;
use App\Entity\Historique;
use App\Entity\Notification;
use App\Entity\Transfert;
use App\Entity\User;
use App\Form\TransfertForm;
use App\Repository\AlbumRepository;
use App\Repository\CarteRepository;
use App\Repository\CompteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class CarteController extends AbstractController
{
    /**
     * Get the currently authenticated user.
     *
     * @return \App\Entity\User
     */
    protected function getCurrentUser(): \App\Entity\User
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw new \LogicException('Current user is not an instance of App\Entity\User');
        }

        return $user;
    }

    #[Route('/compte/transfert', name: 'app_compte_transfert')]
    public function compteTransfert(
        Request $request,
        EntityManagerInterface $em,
        Security $security,
    ): JsonResponse {
        $u = $this->getCurrentUser();
        $t = new Transfert();
        $formT = $this->createForm(TransfertForm::class, $t);
        $formT->handleRequest($request);

        if (!$formT->isSubmitted() || !$formT->isValid()) {
            return $this->json([
                'success' => false,
                'message' => 'Vous devez soumettre le transfert via le formulaire.'
            ]);
        }

        $t = $formT->getData();
        $validationError = $this->validateTransfert($t, $u);
        if ($validationError !== null) {
            return $this->json([
                'success' => false,
                'message' => $validationError
            ]);
        }

        // --- Traitement du transfert ---
        $cFrom = $t->getCFrom();
        $cTo   = $t->getCTo();
        $carte = $t->getCarte();

        $coFrom = $cFrom->getCarteObtenue($carte);
        $coFrom->setNombre($coFrom->getNombre() - 1);

        $coTo = $cTo->getCarteObtenue($carte);
        if (is_null($coTo)) {
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
        $h->setDescription(sprintf(
            'Carte [%s][%s - %s][%s - %s] transférée de [%s][%s] à [%s][%s]',
            $carte->getS()->getAlbum()->getName(),
            $carte->getS()->getPage(),
            $carte->getS()->getName(),
            $carte->getNum(),
            $carte->getName(),
            $cFrom->getUser()->getUsername(),
            $cFrom->getName(),
            $cTo->getUser()->getUsername(),
            $cTo->getName()
        ));
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

    /**
     * Vérifie les conditions métiers avant transfert.
     * Retourne un message d’erreur si invalide, sinon null.
     */
    private function validateTransfert(Transfert $t, User $u): ?string
    {
        $cFrom = $t->getCFrom();
        $cTo   = $t->getCTo();
        $carte = $t->getCarte();

        $error = null;

        if ($cFrom->getUser() !== $u || $cTo->getUser() !== $u) {
            $error = 'Vous ne pouvez pas transférer une carte d\'un compte à un autre qui ne vous appartient pas.';
        } elseif ($carte->isGolden() && !$carte->isTransferable()) {
            $error = 'Vous ne pouvez pas transférer cette carte Gold.';
        } elseif ($cFrom === $cTo) {
            $error = 'Vous ne pouvez pas transférer depuis et vers le même compte.';
        } else {
            $coFrom = $cFrom->getCarteObtenue($carte);
            if (is_null($coFrom)) {
                $error = 'Vous ne possédez pas '.$carte->getName().' pour '.$cFrom->getName();
            } elseif ($coFrom->getNombre() === 1) {
                $error = 'Vous ne possédez pas '.$carte->getName().' en double dans '.$cFrom->getName();
            }
        }

        return $error;
    }


    #[Route('/compte/transfert/{fromid}/{toid}/{idcarte}', name: 'app_compte_transfert_api')]
    public function compteTransfertApi(CompteRepository $compteRepository, CarteRepository $carteRepository, EntityManagerInterface $em, Security $security, int $fromid, int $toid, int $idcarte): JsonResponse
    {
        $u = $security->getUser();
        $cFrom = $compteRepository->find($fromid);
        $cTo = $compteRepository->find($toid);
        $carte = $carteRepository->find($idcarte);

        if (!$cFrom || !$cTo || !$carte) {
            return $this->json([
                'success' => false,
                'message' => 'Compte ou carte introuvable.'
            ]);
        }
        
        if($cFrom->getUser() != $u || $cTo->getUser() != $u) {
            return $this->json([
                'success' => false,
                'message' => 'Vous ne pouvez pas transférer une carte d\'un compte à un autre qui ne vous appartient pas.'
            ]);
        }
        if($carte->isGolden() && !$carte->isTransferable()) {
            return $this->json([
                'success' => false,
                'message' => 'Vous ne pouvez pas transférer cette carte Gold.'
            ]);
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
            'message' => $carte->getName().' a été transférée de '.$cFrom->getName().' à '.$cTo->getName(),
            'remaining' => $coFrom->getNombre() // <- nombre de cartes restantes après transfert
        ]);
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
        // 🔹 Cas où le compte est un groupe
        if ($compte->isGroupe()) {
            if (!$this->isGranted('ROLE_ADMIN_GROUPE')) {
                return $this->json([
                    'success' => false,
                    'message' => "Vous n'avez pas le droit de modifier un compte de groupe."
                ], 403);
            }
        }
        // 🔹 Cas où le compte n'est pas un groupe
        else {
            if ($u !== $compte->getUser()) {
                return $this->json([
                    'success' => false,
                    'message' => 'Vous ne pouvez pas enlever une carte pour un autre compte que le vôtre.'
                ], 403);
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
        $h->setDescription(
            'Carte ['.$c->getS()->getAlbum()->getName().']['.$c->getS()->getPage().' - '.$c->getS()->getName().']['.$c->getNum().' - '.$c->getName().'] '
            .'enlevée du compte ['.$compte->getName().'] '
            .'par l’utilisateur ['.$u->getUserIdentifier().']'
        );
        $h->setCompte($compte);
        $h->setUser($compte->getUser());
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
        // 🔹 Cas où le compte est un groupe
        if ($compte->isGroupe()) {
            if (!$this->isGranted('ROLE_ADMIN_GROUPE')) {
                return $this->json([
                    'success' => false,
                    'message' => "Vous n'avez pas le droit de modifier un compte de groupe."
                ], 403);
            }
        }
        // 🔹 Cas où le compte n'est pas un groupe
        else {
            if ($u !== $compte->getUser()) {
                return $this->json([
                    'success' => false,
                    'message' => 'Vous ne pouvez pas ajouter une carte pour un autre compte que le vôtre.'
                ], 403);
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
        $h->setDescription(
            'Carte ['.$c->getS()->getAlbum()->getName().']['.$c->getS()->getPage().' - '.$c->getS()->getName().']['.$c->getNum().' - '.$c->getName().'] '
            .'ajoutée au compte ['.$compte->getName().'] '
            .'par l’utilisateur ['.$u->getUserIdentifier().']'
        );
        $h->setCompte($compte);
        $h->setUser($compte->getUser());
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

    #[Route('/compte/album/raz/{id}', name: 'app_compte_raz_compte')]
    public function razCompte(#[MapEntity(id: 'id')] Compte $compte, AlbumRepository $albumRepository, EntityManagerInterface $em): Response
    {
        $u = $this->getCurrentUser();
        if($u != $compte->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }
        $album = $albumRepository->findAlbumWithSetsAndCartesActive();

        foreach ($compte->getCarteObtenues() as $carteObtenue) {
            if($carteObtenue->getNombre()>1 && $carteObtenue->getCarte()->getS()->getAlbum() === $album) {
                $carteObtenue->setNombre(1);
                $em->persist($carteObtenue);
            }
        }
        $em->flush();

        return $this->redirectToRoute('app_compte_accueil', ['id' => $compte->getId()]);
    }
}

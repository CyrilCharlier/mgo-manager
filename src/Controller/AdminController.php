<?php

namespace App\Controller;

use App\Entity\Album;
use App\Entity\Carte;
use App\Entity\Set;
use App\Entity\User;
use App\Form\CarteForm;
use App\Form\SetForm;
use App\Repository\AlbumRepository;
use App\Repository\CarteRepository;
use App\Repository\SetRepository;
use App\Repository\UserRepository;
use App\Service\AlbumCleanupService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AdminController extends AbstractController
{
    #[Route('/cgu', name: 'app_cgu')]
    public function cgu(): Response
    {
        return $this->render('admin/cgu.html.twig');
    }
    
    #[Route('/admin', name: 'app_admin')]
    public function index(AlbumRepository $albumRepository, UserRepository $userRepository): Response
    {
        $albums = $albumRepository->findAll();
        $users = $userRepository->findAll();
        $gCartes = $albumRepository->findOneBy(['active' => true])->getGoldenCartes();

        return $this->render('admin/index.html.twig', [
            'albums' => $albums,
            'gCartes' => $gCartes,
            'users' => $users,
        ]);
    }

    #[Route('/admin/pages', name: 'app_admin_pages')]
    public function pages(SetRepository $setRepository): Response
    {
        $sets = $setRepository->findAll();

        return $this->render('admin/page.html.twig', [
            'sets' => $sets,
        ]);
    }

    #[Route('/admin/cartes', name: 'app_admin_cartes')]
    public function cartes(CarteRepository $carteRepository): Response
    {
        $cartes = $carteRepository->findAll();

        return $this->render('admin/carte.html.twig', [
            'cartes' => $cartes,
        ]);
    }

    #[Route('/admin/user/{id}/add-role-admin', name: 'app_admin_add_admin_role')]
    public function addAdminRole(User $user, EntityManagerInterface $em): Response
    {
        if (!in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            $user->setRoles(array_merge($user->getRoles(), ['ROLE_ADMIN']));
            $em->flush();
        }

        return $this->redirectToRoute('app_admin');
    }

    #[Route('/admin/user/{id}/demote', name: 'app_admin_del_admin_role')]
    public function demoteUser(User $user, EntityManagerInterface $em): Response
    {
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            $user->setRoles(array_filter($user->getRoles(), fn($r) => $r !== 'ROLE_ADMIN'));
            $em->flush();
        }

        return $this->redirectToRoute('app_admin');
    }

    #[Route('/admin/user/{id}/add-role-admin-groupe', name: 'app_admin_add_admin_groupe_role')]
    public function addAdminGroupeRole(User $user, EntityManagerInterface $em): Response
    {
        if (!in_array('ROLE_ADMIN_GROUPE', $user->getRoles(), true)) {
            $user->setRoles(array_merge($user->getRoles(), ['ROLE_ADMIN_GROUPE']));
            $em->flush();
        }

        return $this->redirectToRoute('app_admin');
    }

    #[Route('/admin/user/{id}/demote-admin-groupe', name: 'app_admin_del_admin_groupe_role')]
    public function demoteUserAdminGroupe(User $user, EntityManagerInterface $em): Response
    {
        if (in_array('ROLE_ADMIN_GROUPE', $user->getRoles())) {
            $user->setRoles(array_filter($user->getRoles(), fn($r) => $r !== 'ROLE_ADMIN_GROUPE'));
            $em->flush();
        }

        return $this->redirectToRoute('app_admin');
    }

    #[Route('/admin/album/{id}/active', name: 'app_admin_active_album', methods: ['GET'])]
    public function albumActif(Album $album, AlbumRepository $albumRepository, EntityManagerInterface $em): Response
    {
        $a = $albumRepository->findOneBy(['active' => true]);
        if($a != null) {
            $a->setActive(false);
            $em->persist($a);
        }
        $album->setActive(true);
        $em->persist($album);
        $em->flush();
        
        return $this->redirectToRoute('app_admin');
    }

    #[Route('/admin/album/init', name: 'admin_album_init', methods: ['POST'])]
    public function initAlbum(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $albumId = $request->request->get('albumId');
        $nbSets = (int) $request->request->get('nbSets');

        $album = $em->getRepository(Album::class)->find($albumId);
        if (!$album) {
            return new JsonResponse(['success' => false, 'message' => 'Album introuvable']);
        }

        for ($i = 1; $i <= $nbSets; $i++) {
            $set = new Set();
            $set->setAlbum($album);
            $set->setName("Set $i");
            $set->setPage($i);
            $em->persist($set);

            for ($j = 1; $j <= 9; $j++) {
                $carte = new Carte();
                $carte->setS($set);
                $carte->setNum($j);
                $carte->setNbetoile(1);
                $carte->setGolden(false);
                $carte->setName('.');
                $carte->setTransferable(true);
                $em->persist($carte);
            }
        }

        $em->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/admin/album/create', name: 'admin_album_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $name = $request->request->get('name');

        if (!$name) {
            return new JsonResponse(['success' => false, 'message' => 'Nom obligatoire'], 400);
        }

        $album = new Album();
        $album->setName($name);
        $album->setActive(false);

        $em->persist($album);
        $em->flush();

        return new JsonResponse(['success' => true, 'id' => $album->getId()]);
    }

    #[Route('/admin/carte/{id}/toggletransfert', name: 'app_admin_carte_toggle_transfert', methods: ['GET'])]
    public function carteToggleTransfert(Carte $c, EntityManagerInterface $em): Response
    {
        $c->setTransferable(!$c->isTransferable());
        $em->persist($c);
        $em->flush();
        
        return $this->redirectToRoute('app_admin');
    }
 
    #[Route('/admin/page/{id}/edit', name: 'app_admin_set_edit')]
    public function pageEdit(Set $s, Request $request, EntityManagerInterface $em): Response
    {
        $a = $s->getAlbum();
        $form = $this->createForm(SetForm::class, $s, [
            'action' => $this->generateUrl('app_admin_set_edit', ['id' => $s->getId()]),
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $s->setAlbum($a);
            $em->persist($s);
            $em->flush();
            return $this->json([
                    'success' => true,
                    'data' => [
                        'id' => $s->getId(),
                        'page' => $s->getPage(),
                        'name' => $s->getName(),
                    ] 
            ]);
        }
        return $this->render('admin/form.page.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    
    #[Route('/admin/carte/{id}/edit', name: 'app_admin_carte_edit')]
    public function carteEdit(Carte $c, Request $request, EntityManagerInterface $em): Response
    {
        $s = $c->getS();
        $form = $this->createForm(CarteForm::class, $c, [
            'action' => $this->generateUrl('app_admin_carte_edit', ['id' => $c->getId()]),
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $c->setS($s);
            $em->persist($c);
            $em->flush();
            return $this->json([
                'success' => true,
                'data' => [
                    'id' => $c->getId(),
                    'num' => $c->getNum(),
                    'golden' => $c->isGolden(),
                    'name' => $c->getNameStyle(),
                    'nbetoile' => $c->getNbetoile(),                    
                ] 
            ]);
        }
        return $this->render('admin/form.carte.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/set/{id}/inline-edit', name: 'app_admin_set_inline_edit', methods: ['POST'])]
    public function inlineEditSet(Request $request, Set $set, EntityManagerInterface $em): JsonResponse
    {
        $field = $request->request->get('field');
        $value = $request->request->get('value');

        if ($field === 'name') {
            $set->setName($value);
        } else if ($field === 'page') {
            if (!ctype_digit($value)) {
                return new JsonResponse(['success' => false, 'error' => 'Numéro invalide']);
            }
            $set->setPage($value);
        }

        $em->flush();

        return new JsonResponse(['success' => true, 'value' => $value]);
    }

    #[Route('/admin/carte/{id}/inline-edit', name: 'app_admin_carte_inline_edit', methods: ['POST'])]
    public function inlineEditCarte(Request $request, Carte $carte, EntityManagerInterface $em): JsonResponse
    {
        $field = $request->request->get('field');
        $value = $request->request->get('value');

        if ($field === 'name') {
            $carte->setName($value);
        } else if ($field === 'num') {
            if (!ctype_digit($value)) {
                return new JsonResponse(['success' => false, 'error' => 'Numéro invalide']);
            }
            $carte->setNum($value);
        } else if ($field === 'nbetoile') {
            if (!ctype_digit($value)) {
                return new JsonResponse(['success' => false, 'error' => 'Numéro invalide']);
            }
            $etoiles = (int) $value;
            if ($etoiles < 1 || $etoiles > 6) {
                return new JsonResponse(['success' => false, 'error' => 'Nombre d’étoiles invalide']);
            }
            $carte->setNbetoile($etoiles);
        } else if ($field === 'golden') {
            $carte->setGolden($value);
        }

        $em->flush();

        return new JsonResponse(['success' => true, 'value' => $value]);
    }

    #[Route('/admin/cleanup', name: 'app_admin_cleanup')]
    public function cleanupHistorique(EntityManagerInterface $em): Response
    {
        $conn = $em->getConnection();
        $deletedCount = 0;

        $userIds = $conn->fetchFirstColumn('SELECT DISTINCT user_id FROM historique');

        foreach ($userIds as $userId) {
            $idsToDelete = $conn->fetchFirstColumn(
                'SELECT id FROM historique WHERE user_id = :userId ORDER BY horodatage DESC LIMIT 100000 OFFSET 1000',
                ['userId' => $userId]
            );

            if (!empty($idsToDelete)) {
                $placeholders = implode(',', array_fill(0, count($idsToDelete), '?'));
                $conn->executeStatement("DELETE FROM historique WHERE id IN ($placeholders)", $idsToDelete);
                $deletedCount += count($idsToDelete);
            }
        }

        $userIds = $conn->fetchFirstColumn('SELECT DISTINCT user_id FROM notification');

        foreach ($userIds as $userId) {
            $idsToDelete = $conn->fetchFirstColumn(
                'SELECT id FROM notification WHERE user_id = :userId ORDER BY id DESC LIMIT 100000 OFFSET 1000',
                ['userId' => $userId]
            );

            if (!empty($idsToDelete)) {
                $placeholders = implode(',', array_fill(0, count($idsToDelete), '?'));
                $conn->executeStatement("DELETE FROM notification WHERE id IN ($placeholders)", $idsToDelete);
                $deletedCount += count($idsToDelete);
            }
        }

        return $this->redirectToRoute('app_admin');
    }

    #[Route('/admin/cleanup-albums', name: 'admin_cleanup_albums', methods: ['GET'])]
    public function cleanup(AlbumCleanupService $cleanupService): Response
    {
        $cleanupService->cleanupInactiveAlbums();

        return $this->redirectToRoute('app_admin');
    }
}

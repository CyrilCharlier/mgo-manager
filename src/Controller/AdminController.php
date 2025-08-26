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

    #[Route('/admin/charge/starwars', name: 'app_admin_charge_starwars', methods: ['GET'])]
    public function chargeStarwars(EntityManagerInterface $em): Response
    {
        return $this->redirectToRoute('app_admin');
        /*
        $a = new Album();
        $a->setName('Star Wars');
        $a->setActive(false);
        $em->persist($a);
        
        $sets = [
            ['name' => 'Très lointaine', 'page' => 1, 'cartes' => [
                ['name' => 'Sortie ciné', 'nbetoile' => 1, 'num' => 1, 'golden' => false],
                ['name' => 'Fan galactique', 'nbetoile' => 1, 'num' => 2, 'golden' => false],
                ['name' => 'Trèsors sur cassette', 'nbetoile' => 1, 'num' => 3, 'golden' => false],
                ['name' => 'Un film épique', 'nbetoile' => 1, 'num' => 4, 'golden' => false],
                ['name' => 'Sidération', 'nbetoile' => 1, 'num' => 5, 'golden' => false],
                ['name' => 'Nouveau message', 'nbetoile' => 1, 'num' => 6, 'golden' => false],
                ['name' => 'Mon seul espoir', 'nbetoile' => 1, 'num' => 7, 'golden' => false],
                ['name' => 'Premier sabre', 'nbetoile' => 1, 'num' => 8, 'golden' => false],
                ['name' => 'Rêves galactiques', 'nbetoile' => 1, 'num' => 9, 'golden' => false],
            ]],
            ['name' => 'Entraînement Jedi', 'page' => 2, 'cartes' => [
                ['name' => 'Temple Jedi', 'nbetoile' => 1, 'num' => 1, 'golden' => false],
                ['name' => 'Pouvoir inné', 'nbetoile' => 1, 'num' => 2, 'golden' => false],
                ['name' => 'Corridors sacrés', 'nbetoile' => 1, 'num' => 3, 'golden' => false],
                ['name' => 'Cristaux brillants', 'nbetoile' => 1, 'num' => 4, 'golden' => false],
                ['name' => 'Force méditative', 'nbetoile' => 1, 'num' => 5, 'golden' => false],
                ['name' => 'Entraintement martial', 'nbetoile' => 1, 'num' => 6, 'golden' => false],
                ['name' => 'Savoirs inaccessibles', 'nbetoile' => 1, 'num' => 7, 'golden' => false],
                ['name' => 'Force & détermination', 'nbetoile' => 1, 'num' => 8, 'golden' => false],
                ['name' => 'Enfin Padawans', 'nbetoile' => 2, 'num' => 9, 'golden' => false],
            ]],
            ['name' => 'Pistes de Tatooine', 'page' => 3, 'cartes' => [
                ['name' => 'Doux foyer', 'nbetoile' => 1, 'num' => 1, 'golden' => false],
                ['name' => 'Escapade ensablée', 'nbetoile' => 1, 'num' => 2, 'golden' => false],
                ['name' => 'En repérage', 'nbetoile' => 1, 'num' => 3, 'golden' => false],
                ['name' => 'Ecrou défaillant', 'nbetoile' => 1, 'num' => 4, 'golden' => false],
                ['name' => 'Bain d\'huile', 'nbetoile' => 1, 'num' => 5, 'golden' => false],
                ['name' => 'Le Mos est à la fête', 'nbetoile' => 1, 'num' => 6, 'golden' => false],
                ['name' => 'Soirée à la cantina', 'nbetoile' => 1, 'num' => 7, 'golden' => false],
                ['name' => 'Lait de bantha', 'nbetoile' => 2, 'num' => 8, 'golden' => false],
                ['name' => 'Coucher des soleils', 'nbetoile' => 2, 'num' => 9, 'golden' => false],
            ]],
            ['name' => 'Course de modules', 'page' => 4, 'cartes' => [
                ['name' => 'Grande Arène', 'nbetoile' => 1, 'num' => 1, 'golden' => false],
                ['name' => 'Casse-croûte de Hutt', 'nbetoile' => 1, 'num' => 2, 'golden' => false],
                ['name' => 'Bien calibré', 'nbetoile' => 1, 'num' => 3, 'golden' => false],
                ['name' => 'En concurrence', 'nbetoile' => 1, 'num' => 4, 'golden' => false],
                ['name' => 'Sabotage', 'nbetoile' => 1, 'num' => 5, 'golden' => false],
                ['name' => 'On s\'équipe', 'nbetoile' => 1, 'num' => 6, 'golden' => false],
                ['name' => 'Prêts, foncer!', 'nbetoile' => 2, 'num' => 7, 'golden' => false],
                ['name' => 'Victoire serrée', 'nbetoile' => 2, 'num' => 8, 'golden' => false],
                ['name' => 'Magnats des modules', 'nbetoile' => 2, 'num' => 9, 'golden' => false],
            ]],
            ['name' => 'Elite de la galaxie', 'page' => 5, 'cartes' => [
                ['name' => 'Soir de première', 'nbetoile' => 1, 'num' => 1, 'golden' => false],
                ['name' => 'Entrée remarquée', 'nbetoile' => 1, 'num' => 2, 'golden' => false],
                ['name' => 'Symphonie spatiale', 'nbetoile' => 1, 'num' => 3, 'golden' => false],
                ['name' => 'Sous les néons', 'nbetoile' => 1, 'num' => 4, 'golden' => false],
                ['name' => 'Cité des nuages', 'nbetoile' => 1, 'num' => 5, 'golden' => false],
                ['name' => 'La grande vie sur Bespin', 'nbetoile' => 2, 'num' => 6, 'golden' => false],
                ['name' => 'Abondance céleste', 'nbetoile' => 2, 'num' => 7, 'golden' => false],
                ['name' => 'A vos dés', 'nbetoile' => 2, 'num' => 8, 'golden' => false],
                ['name' => 'Lancer chanceux', 'nbetoile' => 3, 'num' => 9, 'golden' => false],
            ]],
            ['name' => 'Base Echo', 'page' => 6, 'cartes' => [
                ['name' => 'Schémas', 'nbetoile' => 1, 'num' => 1, 'golden' => false],
                ['name' => 'Sous la neige', 'nbetoile' => 1, 'num' => 2, 'golden' => false],
                ['name' => 'Projection gelée', 'nbetoile' => 1, 'num' => 3, 'golden' => false],
                ['name' => 'Hangar de Hoth', 'nbetoile' => 2, 'num' => 4, 'golden' => false],
                ['name' => 'Encore perdus', 'nbetoile' => 2, 'num' => 5, 'golden' => false],
                ['name' => 'Signal localisé', 'nbetoile' => 2, 'num' => 6, 'golden' => false],
                ['name' => 'Système rétabli', 'nbetoile' => 2, 'num' => 7, 'golden' => false],
                ['name' => 'Rebelles honoraires', 'nbetoile' => 2, 'num' => 8, 'golden' => false],
                ['name' => 'Copain à fourrure', 'nbetoile' => 3, 'num' => 9, 'golden' => false],
            ]],
            ['name' => 'Princelle Rebelle', 'page' => 7, 'cartes' => [
                ['name' => 'Accueil en or!', 'nbetoile' => 1, 'num' => 1, 'golden' => false],
                ['name' => 'Prête à servir', 'nbetoile' => 1, 'num' => 2, 'golden' => false],
                ['name' => 'Révérence royale', 'nbetoile' => 1, 'num' => 3, 'golden' => false],
                ['name' => 'Pour l\'Alliance', 'nbetoile' => 2, 'num' => 4, 'golden' => false],
                ['name' => 'Chignons cultes', 'nbetoile' => 2, 'num' => 5, 'golden' => false],
                ['name' => 'Parée à décoller', 'nbetoile' => 2, 'num' => 6, 'golden' => false],
                ['name' => 'Poste de commande', 'nbetoile' => 2, 'num' => 7, 'golden' => false],
                ['name' => 'As des pilotes', 'nbetoile' => 3, 'num' => 8, 'golden' => false],
                ['name' => 'Le triomphe!', 'nbetoile' => 3, 'num' => 9, 'golden' => false],
            ]],
            ['name' => 'Explorer Endor', 'page' => 8, 'cartes' => [
                ['name' => 'Village perché', 'nbetoile' => 1, 'num' => 1, 'golden' => false],
                ['name' => 'Culte du droïde', 'nbetoile' => 1, 'num' => 2, 'golden' => false],
                ['name' => 'Marcheur vaincu', 'nbetoile' => 2, 'num' => 3, 'golden' => false],
                ['name' => 'Le cuir, c\'est in', 'nbetoile' => 2, 'num' => 4, 'golden' => false],
                ['name' => 'Coup de filet', 'nbetoile' => 2, 'num' => 5, 'golden' => false],
                ['name' => 'Vite en haut!', 'nbetoile' => 2, 'num' => 6, 'golden' => false],
                ['name' => 'Peg-E le Grand', 'nbetoile' => 2, 'num' => 7, 'golden' => false],
                ['name' => 'Deltaplanant!', 'nbetoile' => 3, 'num' => 8, 'golden' => false],
                ['name' => 'Fête au feu de camp', 'nbetoile' => 3, 'num' => 9, 'golden' => false],
            ]],
            ['name' => 'Leçons Jedi', 'page' => 9, 'cartes' => [
                ['name' => 'Sanctuaire du marais', 'nbetoile' => 1, 'num' => 1, 'golden' => false],
                ['name' => 'Présentation du sage', 'nbetoile' => 2, 'num' => 2, 'golden' => false],
                ['name' => 'Leçons pesantes', 'nbetoile' => 2, 'num' => 3, 'golden' => false],
                ['name' => 'Concentration', 'nbetoile' => 2, 'num' => 4, 'golden' => false],
                ['name' => 'Epreuves du magnat', 'nbetoile' => 2, 'num' => 5, 'golden' => false],
                ['name' => 'Dark Ronrons', 'nbetoile' => 3, 'num' => 6, 'golden' => false],
                ['name' => 'Chatpristi', 'nbetoile' => 3, 'num' => 7, 'golden' => false],
                ['name' => 'Maître de la Force', 'nbetoile' => 3, 'num' => 8, 'golden' => false],
                ['name' => 'Chevalier Jedi', 'nbetoile' => 3, 'num' => 9, 'golden' => false],
            ]],
            ['name' => 'Le Palais de Jabba', 'page' => 10, 'cartes' => [
                ['name' => 'QG de Hutt', 'nbetoile' => 2, 'num' => 1, 'golden' => false],
                ['name' => 'L\'offre de Jabba', 'nbetoile' => 2, 'num' => 2, 'golden' => false],
                ['name' => 'Sacré coquin', 'nbetoile' => 2, 'num' => 3, 'golden' => false],
                ['name' => 'Mode esclave', 'nbetoile' => 2, 'num' => 4, 'golden' => false],
                ['name' => 'Mets délicats ?', 'nbetoile' => 2, 'num' => 5, 'golden' => false],
                ['name' => 'Barge à voiles', 'nbetoile' => 3, 'num' => 6, 'golden' => false],
                ['name' => 'Spectable de l\'esquif', 'nbetoile' => 3, 'num' => 7, 'golden' => false],
                ['name' => 'Charmeur de rancor', 'nbetoile' => 3, 'num' => 8, 'golden' => false],
                ['name' => 'Groupe de Max Rebo', 'nbetoile' => 3, 'num' => 9, 'golden' => false],
            ]],
            ['name' => 'La Résistance', 'page' => 11, 'cartes' => [
                ['name' => 'Les mécanos', 'nbetoile' => 2, 'num' => 1, 'golden' => false],
                ['name' => 'Bon p\'tit gars', 'nbetoile' => 2, 'num' => 2, 'golden' => false],
                ['name' => 'Briefing de mission', 'nbetoile' => 2, 'num' => 3, 'golden' => false],
                ['name' => 'Alerte rouge!', 'nbetoile' => 2, 'num' => 4, 'golden' => false],
                ['name' => 'Emberlificoté', 'nbetoile' => 3, 'num' => 5, 'golden' => false],
                ['name' => 'Vol X-trême', 'nbetoile' => 3, 'num' => 6, 'golden' => false],
                ['name' => 'Bricoleuse astucieuse', 'nbetoile' => 3, 'num' => 7, 'golden' => false],
                ['name' => 'L\'esprit de la Résistance', 'nbetoile' => 3, 'num' => 8, 'golden' => false],
                ['name' => 'Décorées', 'nbetoile' => 4, 'num' => 9, 'golden' => false],
            ]],
            ['name' => 'L\'Empereur', 'page' => 12, 'cartes' => [
                ['name' => 'Arrivée impériale', 'nbetoile' => 2, 'num' => 1, 'golden' => false],
                ['name' => 'Quel choc!', 'nbetoile' => 2, 'num' => 2, 'golden' => false],
                ['name' => 'Frais galactiques', 'nbetoile' => 2, 'num' => 3, 'golden' => false],
                ['name' => 'Minet impérial', 'nbetoile' => 3, 'num' => 4, 'golden' => false],
                ['name' => 'Empire de magnat', 'nbetoile' => 2, 'num' => 5, 'golden' => false],
                ['name' => 'Vador jaloux', 'nbetoile' => 3, 'num' => 6, 'golden' => false],
                ['name' => 'Edition Sith', 'nbetoile' => 3, 'num' => 7, 'golden' => false],
                ['name' => 'Rivalité féroce', 'nbetoile' => 3, 'num' => 8, 'golden' => false],
                ['name' => 'Le dernier adieu', 'nbetoile' => 4, 'num' => 9, 'golden' => false],
            ]],
            ['name' => 'Galaxie Sauvage', 'page' => 13, 'cartes' => [
                ['name' => 'Facéties kowkiennes', 'nbetoile' => 2, 'num' => 1, 'golden' => false],
                ['name' => 'Cadeant de bantha', 'nbetoile' => 2, 'num' => 2, 'golden' => false],
                ['name' => 'Sieste de tooka', 'nbetoile' => 3, 'num' => 3, 'golden' => false],
                ['name' => 'Balade de blurrg', 'nbetoile' => 3, 'num' => 4, 'golden' => false],
                ['name' => 'Nuéee de mynocks', 'nbetoile' => 3, 'num' => 5, 'golden' => false],
                ['name' => 'Copains porgs', 'nbetoile' => 3, 'num' => 6, 'golden' => false],
                ['name' => 'Anges de wampa', 'nbetoile' => 3, 'num' => 7, 'golden' => false],
                ['name' => 'Tauntaun tout câlin', 'nbetoile' => 4, 'num' => 8, 'golden' => false],
                ['name' => 'Purggils en chasse', 'nbetoile' => 4, 'num' => 9, 'golden' => false],
            ]],
            ['name' => 'Trésors Jawa', 'page' => 14, 'cartes' => [
                ['name' => 'Visite sableuse', 'nbetoile' => 2, 'num' => 1, 'golden' => false],
                ['name' => 'Objets trouvés', 'nbetoile' => 2, 'num' => 2, 'golden' => false],
                ['name' => 'La réparatrice', 'nbetoile' => 3, 'num' => 3, 'golden' => false],
                ['name' => 'Mets raffinés', 'nbetoile' => 3, 'num' => 4, 'golden' => false],
                ['name' => 'Jour de marché', 'nbetoile' => 3, 'num' => 5, 'golden' => false],
                ['name' => 'Client du désert', 'nbetoile' => 3, 'num' => 6, 'golden' => false],
                ['name' => 'Jackpot Jawa', 'nbetoile' => 4, 'num' => 7, 'golden' => false],
                ['name' => 'Une des nôtres', 'nbetoile' => 4, 'num' => 8, 'golden' => false],
                ['name' => 'Utinni et au revoir!', 'nbetoile' => 4, 'num' => 9, 'golden' => false],
            ]],
            ['name' => 'Virée entre Vauriens', 'page' => 15, 'cartes' => [
                ['name' => 'Quai 94', 'nbetoile' => 2, 'num' => 1, 'golden' => false],
                ['name' => 'Han le héros', 'nbetoile' => 3, 'num' => 2, 'golden' => false],
                ['name' => 'Prêts pour le saut', 'nbetoile' => 3, 'num' => 3, 'golden' => false],
                ['name' => 'Cargaison secrète', 'nbetoile' => 3, 'num' => 4, 'golden' => false],
                ['name' => 'Holo-échecs', 'nbetoile' => 2, 'num' => 5, 'golden' => false],
                ['name' => 'Erreur du blaster', 'nbetoile' => 4, 'num' => 6, 'golden' => false],
                ['name' => 'Assaut de chatouilles', 'nbetoile' => 4, 'num' => 7, 'golden' => false],
                ['name' => 'Chaude fourrure', 'nbetoile' => 4, 'num' => 8, 'golden' => true],
                ['name' => 'Direction l\'hyperespace', 'nbetoile' => 5, 'num' => 9, 'golden' => false],
            ]],
            ['name' => 'Journal d\'un Droïde', 'page' => 16, 'cartes' => [
                ['name' => 'Base de Starkiller', 'nbetoile' => 3, 'num' => 1, 'golden' => false],
                ['name' => 'Mission de sauvetage', 'nbetoile' => 3, 'num' => 2, 'golden' => false],
                ['name' => 'La voie est libre', 'nbetoile' => 3, 'num' => 3, 'golden' => false],
                ['name' => 'Faire diversion', 'nbetoile' => 3, 'num' => 4, 'golden' => false],
                ['name' => 'Piratage du système', 'nbetoile' => 4, 'num' => 5, 'golden' => false],
                ['name' => 'Divine bonté du Ciel!', 'nbetoile' => 4, 'num' => 6, 'golden' => false],
                ['name' => 'En mode furtif', 'nbetoile' => 4, 'num' => 7, 'golden' => true],
                ['name' => 'Déguis-E', 'nbetoile' => 5, 'num' => 8, 'golden' => false],
                ['name' => 'Fuite réussie', 'nbetoile' => 5, 'num' => 9, 'golden' => false],
            ]],
            ['name' => 'Le Périple de Mando', 'page' => 17, 'cartes' => [
                ['name' => 'Nouvelle amie', 'nbetoile' => 3, 'num' => 1, 'golden' => false],
                ['name' => 'Amateurs de baballes', 'nbetoile' => 3, 'num' => 2, 'golden' => false],
                ['name' => 'Pas touche!', 'nbetoile' => 3, 'num' => 3, 'golden' => false],
                ['name' => 'Magnat miniature', 'nbetoile' => 4, 'num' => 4, 'golden' => false],
                ['name' => 'Curiosité compacte', 'nbetoile' => 4, 'num' => 5, 'golden' => false],
                ['name' => 'Côte à côte', 'nbetoile' => 4, 'num' => 6, 'golden' => true],
                ['name' => 'Force grandissante', 'nbetoile' => 5, 'num' => 7, 'golden' => false],
                ['name' => 'Baskar de pointe', 'nbetoile' => 5, 'num' => 8, 'golden' => false],
                ['name' => 'Mandalorienne', 'nbetoile' => 5, 'num' => 9, 'golden' => true],
            ]],
            ['name' => 'Retraite Impériale', 'page' => 18, 'cartes' => [
                ['name' => 'Accueil de Vador', 'nbetoile' => 3, 'num' => 1, 'golden' => false],
                ['name' => 'Avec style', 'nbetoile' => 4, 'num' => 2, 'golden' => false],
                ['name' => 'Pique-nique Sith', 'nbetoile' => 4, 'num' => 3, 'golden' => false],
                ['name' => 'Force et baballe', 'nbetoile' => 4, 'num' => 4, 'golden' => false],
                ['name' => 'Dark volley', 'nbetoile' => 4, 'num' => 5, 'golden' => true],
                ['name' => 'Allergie au soleil', 'nbetoile' => 5, 'num' => 6, 'golden' => false],
                ['name' => 'Barbecue obscur', 'nbetoile' => 5, 'num' => 7, 'golden' => false],
                ['name' => 'Adieux fumants', 'nbetoile' => 5, 'num' => 8, 'golden' => false],
                ['name' => 'Lien galactique', 'nbetoile' => 5, 'num' => 9, 'golden' => true],
            ]],
            ['name' => 'Prodiges Wookies', 'page' => 19, 'cartes' => [
                ['name' => 'Kashyyk', 'nbetoile' => 4, 'num' => 1, 'golden' => false],
                ['name' => 'Accueil chaleureux', 'nbetoile' => 4, 'num' => 2, 'golden' => false],
                ['name' => 'Mini Wookiee', 'nbetoile' => 4, 'num' => 3, 'golden' => false],
                ['name' => 'Holo-vision', 'nbetoile' => 4, 'num' => 4, 'golden' => false],
                ['name' => 'Brushing de Chewie', 'nbetoile' => 4, 'num' => 5, 'golden' => true],
                ['name' => 'Tout p\'tit Wookie', 'nbetoile' => 5, 'num' => 6, 'golden' => false],
                ['name' => 'Dans le mille', 'nbetoile' => 5, 'num' => 7, 'golden' => false],
                ['name' => 'Sous les galaxies', 'nbetoile' => 5, 'num' => 8, 'golden' => true],
                ['name' => 'Salutations poilues', 'nbetoile' => 5, 'num' => 9, 'golden' => true],
            ]],
            ['name' => 'Véhicules de l\'Espace', 'page' => 20, 'cartes' => [
                ['name' => 'X-Wing', 'nbetoile' => 4, 'num' => 1, 'golden' => false],
                ['name' => 'Destroyer stellaire', 'nbetoile' => 4, 'num' => 2, 'golden' => false],
                ['name' => 'Chasseur TIE de Dark Vador', 'nbetoile' => 4, 'num' => 3, 'golden' => false],
                ['name' => 'Char des sables', 'nbetoile' => 4, 'num' => 4, 'golden' => true],
                ['name' => 'Etoire de la Mort', 'nbetoile' => 5, 'num' => 5, 'golden' => false],
                ['name' => 'Landspeeder X-34', 'nbetoile' => 5, 'num' => 6, 'golden' => false],
                ['name' => 'Chasseur TIE', 'nbetoile' => 5, 'num' => 7, 'golden' => true],
                ['name' => 'TB-TT', 'nbetoile' => 5, 'num' => 8, 'golden' => true],
                ['name' => 'Faucon Millenium', 'nbetoile' => 5, 'num' => 9, 'golden' => true],
            ]],
            ['name' => 'Retour à la Réalité', 'page' => 21, 'cartes' => [
                ['name' => 'Bisous d\'eopie', 'nbetoile' => 4, 'num' => 1, 'golden' => false],
                ['name' => 'Retour sur Terre', 'nbetoile' => 4, 'num' => 2, 'golden' => false],
                ['name' => 'Fans absolus', 'nbetoile' => 4, 'num' => 3, 'golden' => false],
                ['name' => 'Force-corn', 'nbetoile' => 5, 'num' => 4, 'golden' => false],
                ['name' => 'Maître des goodies', 'nbetoile' => 5, 'num' => 5, 'golden' => false],
                ['name' => 'Concours de répliques', 'nbetoile' => 5, 'num' => 6, 'golden' => true],
                ['name' => 'Crédits de fin', 'nbetoile' => 5, 'num' => 7, 'golden' => true],
                ['name' => 'Buffet spatial', 'nbetoile' => 5, 'num' => 8, 'golden' => true],
                ['name' => 'Selfie stellaire', 'nbetoile' => 5, 'num' => 9, 'golden' => true],
            ]],
            ['name' => 'Héro de la Galaxie', 'page' => 22, 'cartes' => [
                ['name' => 'Luke Skywalker', 'nbetoile' => 4, 'num' => 1, 'golden' => true],
                ['name' => 'Princesse Leïla', 'nbetoile' => 4, 'num' => 2, 'golden' => true],
                ['name' => 'Han Solo', 'nbetoile' => 4, 'num' => 3, 'golden' => true],
                ['name' => 'Chewbacca', 'nbetoile' => 4, 'num' => 4, 'golden' => true],
                ['name' => 'Yoda', 'nbetoile' => 4, 'num' => 5, 'golden' => true],
                ['name' => 'R2-D2', 'nbetoile' => 4, 'num' => 6, 'golden' => true],
                ['name' => 'BB-8', 'nbetoile' => 4, 'num' => 7, 'golden' => true],
                ['name' => 'Le Mandalorien', 'nbetoile' => 4, 'num' => 8, 'golden' => true],
                ['name' => 'Grogu', 'nbetoile' => 4, 'num' => 9, 'golden' => true],
            ]],
        ];

        foreach ($sets as $s) {
            $set = new Set();
            $set->setName($s['name']);
            $set->setPage($s['page']);
            $set->setAlbum($a);
            foreach ($s['cartes'] as $c) {
                $carte = new Carte();
                $carte->setName($c['name']);
                $carte->setNum($c['num']);
                $carte->setNbetoile($c['nbetoile']);
                $carte->setGolden($c['golden']);
                $carte->setS($set);
                $em->persist($carte);
            }
            $em->persist($set);
        }

        $em->flush();
        
        return $this->redirectToRoute('app_admin');*/
    }

    #[Route('/admin/cleanup-albums', name: 'admin_cleanup_albums', methods: ['GET'])]
    public function cleanup(AlbumCleanupService $cleanupService): Response
    {
        $cleanupService->cleanupInactiveAlbums();

        return $this->redirectToRoute('app_admin');
    }
}

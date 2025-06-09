<?php

namespace App\EventListener;

use App\Entity\CarteObtenue;
use App\Entity\Notification;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;
use Psr\Log\LoggerInterface;

#[AsDoctrineListener(event: Events::postPersist, priority: 500, connection: 'default')]
#[AsDoctrineListener(event: Events::postUpdate, priority: 500, connection: 'default')]
#[AsDoctrineListener(event: Events::postPersist, priority: 500, connection: 'default')]
final class CarteObtenueListener
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        // Se déclenche à l'INSERT d'un objet
        $entity = $args->getObject();
        $entityManager = $args->getObjectManager();

        $this->logger->debug('>>>>>  Objet persisté : ' . get_class($entity));
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        // Se déclenche à l'UPDATE d'un objet
        $this->logger->debug('>>>>> Objet UPDATE : ' . get_class($args->getObject()));

        $entity = $args->getObject();

        if (!$entity instanceof CarteObtenue) {
            return;
        }

        $em = $args->getObjectManager();
        $user = $entity->getCompte()->getUser();

        $notificationsToPersist = [];

        foreach ($user->getComptes() as $compte) {
            if ($compte->getId() !== $entity->getCompte()->getId()) {
                if ($compte->getCarteObtenue($entity->getCarte()) === null && $entity->getNombre() > 1) {
                    $notification = new Notification();
                    $notification->setIcone('fa-solid fa-gift');
                    $notification->setTexte(sprintf(
                        '%s peut donner la carte "%s" à %s',
                        $entity->getCompte()->getName(),
                        $entity->getCarte()->getName(),
                        $compte->getName()
                    ));
                    $notification->setUser($user);

                    $notificationsToPersist[] = $notification;
                    $this->logger->debug(sprintf(
                        '>>>>>> %s peut donner la carte "%s" à %s',
                        $entity->getCompte()->getName(),
                        $entity->getCarte()->getName(),
                        $compte->getName()
                    ));
                }
            }
        }

        // Persiste toutes les notifications en une fois
        if (!empty($notificationsToPersist)) {
            foreach ($notificationsToPersist as $notif) {
                $em->persist($notif);
            }
            $em->flush();
        }
    }
}

<?php

namespace App\Form;

use App\Entity\Carte;
use App\Entity\Transfert;
use App\Entity\Compte;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @extends AbstractType<Transfert>
 */
class TransfertForm extends AbstractType
{
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $form = $event->getForm();
            $user = $this->security->getUser();

            $form->add('cFrom', EntityType::class, [
                'class' => Compte::class,
                'query_builder' => function (EntityRepository $er) use ($user) {
                    return $er->createQueryBuilder('c')
                        ->where('c.user = :user')
                        ->setParameter('user', $user);
                },
                'choice_label' => 'name',
            ]);

            $form->add('cTo', EntityType::class, [
                'class' => Compte::class,
                'query_builder' => function (EntityRepository $er) use ($user) {
                    return $er->createQueryBuilder('c')
                        ->where('c.user = :user')
                        ->setParameter('user', $user);
                },
                'choice_label' => 'name',
            ]);

            $form->add('carte', EntityType::class, [
                'class' => Carte::class,
                'query_builder' => function (EntityRepository $er) {
                    $qb = $er->createQueryBuilder('c')
                        ->innerJoin('c.set', 's') 
                        ->innerJoin('s.album', 'a')
                        ->where('a.active = :active')
                        ->orderBy('a.name', 'ASC')
                        ->setParameter('active', true);
                },
                'group_by' => function(Carte $c) {
                    return $c->getS()->getName(); // Remplacez 'getNom' par la méthode qui retourne le nom de l'album
                },
                'choice_label' => 'getNameStyle',
            ]);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Transfert::class,
        ]);
    }
}
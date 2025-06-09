<?php

namespace App\Form;

use App\Entity\Compte;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;


class CompteForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('mgo')
            ->add('image', ChoiceType::class, [
                'choices' => [
                    'Avatar 1' => 'FestiveFeasts.webp',
                    'Avatar 2' => 'Hol.webp',
                    'Avatar 3' => 'NutcrackerDreams.webp',
                    'Avatar 4' => 'SantasWorkshop.webp',
                    'Avatar 5' => 'SweaterParty.webp',
                    'Avatar 6' => 'WinterTraditions.webp',
                    'Avator 7' => 'SweetHome.webp',
                    'Avatar 8' => 'ThePerfectGift.webp',
                    'Avatar 9' => 'SnowedIn.webp',
                    'Avatar 10' => 'HolidayBakes.webp',
                    'Avatar 11' => 'OutdoorFun.webp',
                    'Avatar 12' => 'MonopolyWorld.webp',
                    'Avatar 13' => 'MerryKrampus.webp',
                    'Avatar 14' => 'AussieXmas.webp',
                    'Avatar 15' => 'ChristmasParade.webp',
                ],
                'placeholder' => 'Choisir un avatar',
                'required' => false,
                'label' => 'Image de profil',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Compte::class,
            'csrf_protection' => true,
        ]);
    }
}

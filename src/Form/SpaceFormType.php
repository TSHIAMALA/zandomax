<?php

namespace App\Form;

use App\Entity\Space;
use App\Entity\SpaceCategory;
use App\Entity\SpaceType;
use App\Enum\SpaceStatus;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SpaceFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code', TextType::class, [
                'label' => 'Code de l\'espace',
                'attr' => ['placeholder' => 'ex: A-001', 'class' => 'block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm']
            ])
            ->add('zone', ChoiceType::class, [
                'label' => 'Zone',
                'choices' => [
                    'Zone A' => 'Zone A',
                    'Zone B' => 'Zone B',
                    'Zone C' => 'Zone C',
                    'Zone D' => 'Zone D',
                ],
                'attr' => ['class' => 'block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm']
            ])
            ->add('spaceCategory', EntityType::class, [
                'class' => SpaceCategory::class,
                'choice_label' => 'name',
                'label' => 'Catégorie',
                'attr' => ['class' => 'block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm']
            ])
            ->add('spaceType', EntityType::class, [
                'class' => SpaceType::class,
                'choice_label' => 'label',
                'label' => 'Type',
                'attr' => ['class' => 'block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm']
            ])
            ->add('status', EnumType::class, [
                'class' => SpaceStatus::class,
                'label' => 'Statut',
                'choice_label' => function (SpaceStatus $status): string {
                    return match($status) {
                        SpaceStatus::AVAILABLE => 'Disponible',
                        SpaceStatus::OCCUPIED => 'Occupé',
                        SpaceStatus::MAINTENANCE => 'Maintenance',
                        SpaceStatus::RESERVED => 'Réservé',
                    };
                },
                'attr' => ['class' => 'block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Space::class,
        ]);
    }
}

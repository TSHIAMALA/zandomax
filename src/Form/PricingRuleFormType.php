<?php

namespace App\Form;

use App\Entity\Currency;
use App\Entity\PricingRule;
use App\Entity\Space;
use App\Enum\Periodicity;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PricingRuleFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('space', EntityType::class, [
                'class' => Space::class,
                'choice_label' => function (Space $space) {
                    return $space->getCode() . ' (' . $space->getSpaceType()->getLabel() . ')';
                },
                'label' => 'Espace',
                'attr' => ['class' => 'block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm']
            ])
            ->add('periodicity', EnumType::class, [
                'class' => Periodicity::class,
                'choice_label' => fn(Periodicity $periodicity) => $periodicity->label(),
                'label' => 'Périodicité',
                'attr' => ['class' => 'block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm']
            ])
            ->add('price', NumberType::class, [
                'label' => 'Prix',
                'scale' => 2,
                'attr' => ['class' => 'block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm']
            ])
            ->add('currency', EntityType::class, [
                'class' => Currency::class,
                'choice_label' => 'code',
                'label' => 'Devise',
                'attr' => ['class' => 'block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm']
            ])
            ->add('minDuration', IntegerType::class, [
                'label' => 'Durée Minimum (unités)',
                'attr' => ['class' => 'block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm']
            ])
            ->add('maxDuration', IntegerType::class, [
                'label' => 'Durée Maximum (unités)',
                'required' => false,
                'attr' => ['class' => 'block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm']
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Actif',
                'required' => false,
                'attr' => ['class' => 'h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PricingRule::class,
        ]);
    }
}

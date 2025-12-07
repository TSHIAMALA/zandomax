<?php

namespace App\Form;

use App\Entity\Merchant;
use App\Entity\MerchantCategory;
use App\Enum\MerchantStatus;
use App\Enum\PersonType;
use App\Enum\KycLevel;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MerchantFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstname', TextType::class, [
                'label' => 'Prénom',
                'attr' => ['class' => 'block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm']
            ])
            ->add('lastname', TextType::class, [
                'label' => 'Nom',
                'attr' => ['class' => 'block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm']
            ])
            ->add('phone', TextType::class, [
                'label' => 'Téléphone',
                'attr' => ['class' => 'block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm']
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'required' => false,
                'attr' => ['class' => 'block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm']
            ])
            ->add('merchantCategory', EntityType::class, [
                'class' => MerchantCategory::class,
                'choice_label' => 'name',
                'label' => 'Catégorie',
                'attr' => ['class' => 'block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm']
            ])
            ->add('personType', EnumType::class, [
                'class' => PersonType::class,
                'label' => 'Type de Personne',
                'choice_label' => function (PersonType $type): string {
                    return match($type) {
                        PersonType::PHYSICAL => 'Personne Physique',
                        PersonType::MORAL => 'Personne Morale',
                    };
                },
                'attr' => ['class' => 'block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm']
            ])
            ->add('status', EnumType::class, [
                'class' => MerchantStatus::class,
                'label' => 'Statut',
                'choice_label' => function (MerchantStatus $status): string {
                    return match($status) {
                        MerchantStatus::ACTIVE => 'Actif',
                        MerchantStatus::INACTIVE => 'Inactif',
                        MerchantStatus::PENDING_VALIDATION => 'En attente de validation',
                        MerchantStatus::SUSPENDED => 'Suspendu',
                        MerchantStatus::BLACKLISTED => 'Liste noire',
                    };
                },
                'attr' => ['class' => 'block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm']
            ])
            ->add('kycLevel', EnumType::class, [
                'class' => KycLevel::class,
                'label' => 'Niveau KYC',
                'choice_label' => function (KycLevel $level): string {
                    return match($level) {
                        KycLevel::BASIC => 'Basique',
                        KycLevel::FULL => 'Complet',
                        KycLevel::VERIFIED => 'Vérifié',
                    };
                },
                'attr' => ['class' => 'block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm']
            ])
            ->add('accountNumber', TextType::class, [
                'label' => 'Numéro de Compte',
                'required' => false,
                'attr' => ['class' => 'block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm']
            ])
            ->add('biometricHash', TextType::class, [
                'label' => 'Hash Biométrique (Simulation)',
                'required' => false,
                'attr' => ['class' => 'block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Merchant::class,
        ]);
    }
}

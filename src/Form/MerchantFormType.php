<?php

namespace App\Form;

use App\Entity\Merchant;
use App\Entity\MerchantCategory;
use App\Enum\MerchantStatus;
use App\Enum\PersonType;
use App\Enum\KycLevel;
use App\Form\DataTransformer\BinaryUuidToEntityTransformer;
use App\Repository\MerchantCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MerchantFormType extends AbstractType
{
    public function __construct(
        private MerchantCategoryRepository $categoryRepository,
        private EntityManagerInterface $em
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Récupérer les catégories
        $categories = $this->categoryRepository->findAll();
        $categoryChoices = [];
        foreach ($categories as $category) {
            $categoryChoices[$category->getName()] = bin2hex($category->getId());
        }

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
            ->add('merchantCategory', ChoiceType::class, [
                'label' => 'Catégorie',
                'choices' => $categoryChoices,
                'attr' => ['class' => 'block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm'],
                'placeholder' => 'Sélectionnez une catégorie'
            ])
            ->add('personType', EnumType::class, [
                'class' => PersonType::class,
                'label' => 'Type de Personne',
                'choice_label' => fn(PersonType $type) => $type->label(),
                'attr' => ['class' => 'block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm']
            ])
            ->add('status', EnumType::class, [
                'class' => MerchantStatus::class,
                'label' => 'Statut',
                'choice_label' => fn(MerchantStatus $status) => $status->label(),
                'attr' => ['class' => 'block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm']
            ])
            ->add('kycLevel', EnumType::class, [
                'class' => KycLevel::class,
                'label' => 'Niveau KYC',
                'choice_label' => fn(KycLevel $level) => $level->label(),
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

        // Ajouter le transformer pour la catégorie
        $builder->get('merchantCategory')
            ->addModelTransformer(new BinaryUuidToEntityTransformer($this->em, MerchantCategory::class));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Merchant::class,
        ]);
    }
}

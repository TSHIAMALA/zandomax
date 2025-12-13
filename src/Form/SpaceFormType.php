<?php

namespace App\Form;

use App\Entity\Space;
use App\Entity\SpaceCategory;
use App\Entity\SpaceType;
use App\Enum\SpaceStatus;
use App\Form\DataTransformer\BinaryUuidToEntityTransformer;
use App\Repository\SpaceCategoryRepository;
use App\Repository\SpaceTypeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SpaceFormType extends AbstractType
{
    public function __construct(
        private SpaceCategoryRepository $categoryRepository,
        private SpaceTypeRepository $typeRepository,
        private EntityManagerInterface $em
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Récupérer les catégories et types
        $categories = $this->categoryRepository->findAll();
        $types = $this->typeRepository->findAll();

        // Créer les choix avec bin2hex pour les IDs
        $categoryChoices = [];
        foreach ($categories as $category) {
            $categoryChoices[$category->getName()] = bin2hex($category->getId());
        }

        $typeChoices = [];
        foreach ($types as $type) {
            $typeChoices[$type->getLabel()] = bin2hex($type->getId());
        }

        $builder
            ->add('code', TextType::class, [
                'label' => 'Code de l\'espace',
                'attr' => [
                    'placeholder' => 'ex: A-001', 
                    'class' => 'block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm'
                ]
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
            ->add('spaceCategory', ChoiceType::class, [
                'label' => 'Catégorie',
                'choices' => $categoryChoices,
                'attr' => ['class' => 'block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm'],
                'placeholder' => 'Sélectionnez une catégorie'
            ])
            ->add('spaceType', ChoiceType::class, [
                'label' => 'Type',
                'choices' => $typeChoices,
                'attr' => ['class' => 'block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm'],
                'placeholder' => 'Sélectionnez un type'
            ])
            ->add('status', EnumType::class, [
                'class' => SpaceStatus::class,
                'label' => 'Statut',
                'choice_label' => fn(SpaceStatus $status) => $status->label(),
                'attr' => ['class' => 'block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm']
            ])
        ;

        // Ajouter les transformers pour convertir les IDs hex en entités
        $builder->get('spaceCategory')
            ->addModelTransformer(new BinaryUuidToEntityTransformer($this->em, SpaceCategory::class));
        
        $builder->get('spaceType')
            ->addModelTransformer(new BinaryUuidToEntityTransformer($this->em, SpaceType::class));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Space::class,
        ]);
    }
}

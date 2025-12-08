<?php

namespace App\Controller\MarketAdmin;

use App\Entity\Space;
use App\Form\SpaceFormType;
use App\Repository\SpaceRepository;
use App\Repository\SpaceCategoryRepository;
use App\Repository\SpaceTypeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/spaces', name: 'market_admin_spaces_')]
#[IsGranted('ROLE_MARKET_ADMIN')]
class SpaceController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(SpaceRepository $spaceRepository): Response
    {
        return $this->render('market_admin/spaces/index.html.twig', [
            'spaces' => $spaceRepository->findBy(['isDeleted' => false], ['code' => 'ASC']),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(
        Request $request, 
        EntityManagerInterface $entityManager,
        SpaceCategoryRepository $categoryRepository,
        SpaceTypeRepository $typeRepository,
        \Symfony\Component\Form\FormFactoryInterface $formFactory
    ): Response {
        $space = new Space();
        $form = $formFactory->create(SpaceFormType::class, $space);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Récupérer les IDs hexadécimaux et les convertir
            $categoryId = $form->get('spaceCategory')->getData();
            $typeId = $form->get('spaceType')->getData();

            // Convertir hex en binaire et récupérer les entités
            $category = $categoryRepository->find(hex2bin($categoryId));
            $type = $typeRepository->find(hex2bin($typeId));

            if (!$category || !$type) {
                $this->addFlash('error', 'Catégorie ou type invalide.');
                return $this->render('market_admin/spaces/new.html.twig', [
                    'space' => $space,
                    'form' => $form,
                ]);
            }

            $space->setSpaceCategory($category);
            $space->setSpaceType($type);

            $entityManager->persist($space);
            $entityManager->flush();

            $this->addFlash('success', 'Espace créé avec succès.');

            return $this->redirectToRoute('market_admin_spaces_index');
        }

        return $this->render('market_admin/spaces/new.html.twig', [
            'space' => $space,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(
        string $id, 
        Request $request, 
        SpaceRepository $spaceRepository,
        SpaceCategoryRepository $categoryRepository,
        SpaceTypeRepository $typeRepository,
        EntityManagerInterface $entityManager, 
        \Symfony\Component\Form\FormFactoryInterface $formFactory
    ): Response {
        $space = $spaceRepository->find(hex2bin($id));
        
        if (!$space) {
            throw $this->createNotFoundException('Espace non trouvé');
        }

        $form = $formFactory->create(SpaceFormType::class, $space);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Récupérer les IDs hexadécimaux et les convertir
            $categoryId = $form->get('spaceCategory')->getData();
            $typeId = $form->get('spaceType')->getData();

            // Convertir hex en binaire et récupérer les entités
            $category = $categoryRepository->find(hex2bin($categoryId));
            $type = $typeRepository->find(hex2bin($typeId));

            if (!$category || !$type) {
                $this->addFlash('error', 'Catégorie ou type invalide.');
                return $this->render('market_admin/spaces/edit.html.twig', [
                    'space' => $space,
                    'form' => $form,
                ]);
            }

            $space->setSpaceCategory($category);
            $space->setSpaceType($type);

            $entityManager->flush();

            $this->addFlash('success', 'Espace modifié avec succès.');

            return $this->redirectToRoute('market_admin_spaces_index');
        }

        return $this->render('market_admin/spaces/edit.html.twig', [
            'space' => $space,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(
        string $id, 
        Request $request, 
        SpaceRepository $spaceRepository, 
        EntityManagerInterface $entityManager
    ): Response {
        $space = $spaceRepository->find(hex2bin($id));
        
        if (!$space) {
            throw $this->createNotFoundException('Espace non trouvé');
        }

        if ($this->isCsrfTokenValid('delete'.$space->getId(), $request->request->get('_token'))) {
            // Soft delete
            $space->setIsDeleted(true);
            $entityManager->flush();
            
            $this->addFlash('success', 'Espace supprimé avec succès.');
        }

        return $this->redirectToRoute('market_admin_spaces_index');
    }
}

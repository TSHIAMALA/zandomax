<?php

namespace App\Controller\MarketAdmin;

use App\Entity\Merchant;
use App\Form\MerchantFormType;
use App\Repository\MerchantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/merchants', name: 'market_admin_merchants_')]
#[IsGranted('ROLE_MARKET_ADMIN')]
class MerchantController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(Request $request, MerchantRepository $merchantRepository): Response
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;
        $search = $request->query->get('search');

        $qb = $merchantRepository->createQueryBuilder('m')
            ->leftJoin('m.merchantCategory', 'mc')
            ->addSelect('mc')
            ->where('m.isDeleted = :deleted')
            ->setParameter('deleted', false);

        if ($search) {
            $qb->andWhere('m.firstname LIKE :search OR m.lastname LIKE :search OR m.phone LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        $totalCount = (clone $qb)->select('COUNT(m.id)')->getQuery()->getSingleScalarResult();

        $merchants = $qb
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->orderBy('m.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('market_admin/merchants/index.html.twig', [
            'merchants' => $merchants,
            'currentPage' => $page,
            'totalPages' => ceil($totalCount / $limit),
            'search' => $search,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, \Symfony\Component\Form\FormFactoryInterface $formFactory): Response
    {
        $merchant = new Merchant();
        $form = $formFactory->create(MerchantFormType::class, $merchant);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Generate a dummy biometric hash if not provided (for testing)
            if (!$merchant->getBiometricHash()) {
                $merchant->setBiometricHash(uniqid('bio_'));
            }

            $entityManager->persist($merchant);
            $entityManager->flush();

            $this->addFlash('success', 'Marchand créé avec succès.');

            return $this->redirectToRoute('market_admin_merchants_index');
        }

        return $this->render('market_admin/merchants/new.html.twig', [
            'merchant' => $merchant,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Merchant $merchant, EntityManagerInterface $entityManager, \Symfony\Component\Form\FormFactoryInterface $formFactory): Response
    {
        $form = $formFactory->create(MerchantFormType::class, $merchant);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Marchand modifié avec succès.');

            return $this->redirectToRoute('market_admin_merchants_index');
        }

        return $this->render('market_admin/merchants/edit.html.twig', [
            'merchant' => $merchant,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Merchant $merchant, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$merchant->getId(), $request->request->get('_token'))) {
            // Soft delete
            $merchant->setIsDeleted(true);
            $entityManager->flush();
            
            $this->addFlash('success', 'Marchand supprimé avec succès.');
        }

        return $this->redirectToRoute('market_admin_merchants_index');
    }
    #[Route('/{id}/validate', name: 'validate', methods: ['POST'])]
    public function validate(string $id, MerchantRepository $merchantRepository, EntityManagerInterface $em): Response
    {
        $merchant = $merchantRepository->find(hex2bin($id));
        if (!$merchant) {
            throw $this->createNotFoundException('Marchand non trouvé');
        }
        $merchant->setStatus(\App\Enum\MerchantStatus::ACTIVE);
        $em->flush();
        $this->addFlash('success', 'Marchand validé avec succès');
        return $this->redirectToRoute('market_admin_merchants_show', ['id' => $id]);
    }

    #[Route('/{id}/reject', name: 'reject', methods: ['POST'])]
    public function reject(string $id, Request $request, MerchantRepository $merchantRepository, EntityManagerInterface $em): Response
    {
        $merchant = $merchantRepository->find(hex2bin($id));
        if (!$merchant) {
            throw $this->createNotFoundException('Marchand non trouvé');
        }
        $merchant->setStatus(\App\Enum\MerchantStatus::REJECTED);
        $em->flush();
        $this->addFlash('success', 'Marchand rejeté');
        return $this->redirectToRoute('market_admin_merchants_index');
    }
}

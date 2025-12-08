<?php

namespace App\Controller\MarketAdmin;

use App\Entity\Merchant;
use App\Form\MerchantFormType;
use App\Repository\MerchantRepository;
use App\Repository\ReservationRepository;
use App\Repository\ContractRepository;
use App\Repository\InvoiceRepository;
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
    #[Route('', name: 'index')]
    public function index(MerchantRepository $merchantRepository): Response
    {
        $merchants = $merchantRepository->findBy(['isDeleted' => false]);

        return $this->render('market_admin/merchants/index.html.twig', [
            'merchants' => $merchants,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, \Symfony\Component\Form\FormFactoryInterface $formFactory): Response
    {
        $merchant = new Merchant();
        $form = $formFactory->create(MerchantFormType::class, $merchant);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
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

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(
        string $id, 
        MerchantRepository $merchantRepository,
        ReservationRepository $reservationRepository,
        ContractRepository $contractRepository,
        InvoiceRepository $invoiceRepository
    ): Response {
        $merchant = $merchantRepository->find(hex2bin($id));
        
        if (!$merchant) {
            throw $this->createNotFoundException('Marchand non trouvé');
        }

        // Récupérer toutes les données liées au marchand
        $reservations = $reservationRepository->findBy(['merchant' => $merchant], ['createdAt' => 'DESC']);
        $contracts = $contractRepository->findBy(['merchant' => $merchant], ['createdAt' => 'DESC']);
        $invoices = $invoiceRepository->findBy(['merchant' => $merchant], ['createdAt' => 'DESC']);

        return $this->render('market_admin/merchants/show.html.twig', [
            'merchant' => $merchant,
            'reservations' => $reservations,
            'contracts' => $contracts,
            'invoices' => $invoices,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(
        string $id, 
        Request $request, 
        MerchantRepository $merchantRepository, 
        EntityManagerInterface $entityManager, 
        \Symfony\Component\Form\FormFactoryInterface $formFactory
    ): Response {
        $merchant = $merchantRepository->find(hex2bin($id));
        
        if (!$merchant) {
            throw $this->createNotFoundException('Marchand non trouvé');
        }
        
        $form = $formFactory->create(MerchantFormType::class, $merchant);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Marchand modifié avec succès.');

            return $this->redirectToRoute('market_admin_merchants_show', ['id' => $id]);
        }

        return $this->render('market_admin/merchants/edit.html.twig', [
            'merchant' => $merchant,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(
        string $id, 
        Request $request, 
        MerchantRepository $merchantRepository, 
        EntityManagerInterface $entityManager
    ): Response {
        $merchant = $merchantRepository->find(hex2bin($id));
        
        if (!$merchant) {
            throw $this->createNotFoundException('Marchand non trouvé');
        }
        
        if ($this->isCsrfTokenValid('delete'.$merchant->getId(), $request->request->get('_token'))) {
            // Soft delete
            $merchant->setIsDeleted(true);
            $entityManager->flush();
            
            $this->addFlash('success', 'Marchand supprimé avec succès.');
        }

        return $this->redirectToRoute('market_admin_merchants_index');
    }
    
    #[Route('/{id}/validate', name: 'validate', methods: ['POST'])]
    public function validate(
        string $id, 
        MerchantRepository $merchantRepository, 
        EntityManagerInterface $em
    ): Response {
        $merchant = $merchantRepository->find(hex2bin($id));
        
        if (!$merchant) {
            throw $this->createNotFoundException('Marchand non trouvé');
        }
        
        $merchant->setStatus(\App\Enum\MerchantStatus::ACTIVE);
        $em->flush();
        
        $this->addFlash('success', 'Marchand validé avec succès');
        
        return $this->redirectToRoute('market_admin_merchants_show', ['id' => $id]);
    }
}

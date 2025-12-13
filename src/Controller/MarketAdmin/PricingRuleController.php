<?php

namespace App\Controller\MarketAdmin;

use App\Entity\PricingRule;
use App\Form\PricingRuleFormType;
use App\Repository\PricingRuleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Form\FormFactoryInterface;

#[Route('/admin/pricing-rules', name: 'market_admin_pricing_rules_')]
#[IsGranted('ROLE_MARKET_ADMIN')]
class PricingRuleController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(PricingRuleRepository $pricingRuleRepository): Response
    {
        return $this->render('market_admin/pricing_rules/index.html.twig', [
            'pricing_rules' => $pricingRuleRepository->findBy(['isDeleted' => false], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, FormFactoryInterface $formFactory): Response
    {
        $pricingRule = new PricingRule();
        $form = $formFactory->create(PricingRuleFormType::class, $pricingRule);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($pricingRule);
            $entityManager->flush();

            $this->addFlash('success', 'Règle de prix créée avec succès.');

            return $this->redirectToRoute('market_admin_pricing_rules_index');
        }

        return $this->render('market_admin/pricing_rules/new.html.twig', [
            'pricing_rule' => $pricingRule,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(string $id, Request $request, PricingRuleRepository $pricingRuleRepository, EntityManagerInterface $entityManager, FormFactoryInterface $formFactory): Response
    {
        // Convertir l'ID hexadécimal en binaire
        $binaryId = hex2bin($id);
        $pricingRule = $pricingRuleRepository->find($binaryId);

        if (!$pricingRule) {
            throw $this->createNotFoundException('Règle de prix non trouvée');
        }

        $form = $formFactory->create(PricingRuleFormType::class, $pricingRule);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Règle de prix modifiée avec succès.');

            return $this->redirectToRoute('market_admin_pricing_rules_index');
        }

        return $this->render('market_admin/pricing_rules/edit.html.twig', [
            'pricing_rule' => $pricingRule,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(string $id, Request $request, PricingRuleRepository $pricingRuleRepository, EntityManagerInterface $entityManager): Response
    {
        // Convertir l'ID hexadécimal en binaire
        $binaryId = hex2bin($id);
        $pricingRule = $pricingRuleRepository->find($binaryId);

        if (!$pricingRule) {
            throw $this->createNotFoundException('Règle de prix non trouvée');
        }

        if ($this->isCsrfTokenValid('delete'.$pricingRule->getId(), $request->request->get('_token'))) {
            $pricingRule->setIsDeleted(true);
            $entityManager->flush();
            $this->addFlash('success', 'Règle de prix supprimée avec succès.');
        }

        return $this->redirectToRoute('market_admin_pricing_rules_index');
    }
}

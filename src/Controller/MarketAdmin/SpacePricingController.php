<?php

namespace App\Controller\MarketAdmin;

use App\Entity\Space;
use App\Entity\SpacePricing;
use App\Enum\Periodicity;
use App\Repository\CurrencyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/spaces/{spaceId}/pricing', name: 'market_admin_space_pricing_')]
#[IsGranted('ROLE_MARKET_ADMIN')]
class SpacePricingController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(string $spaceId, EntityManagerInterface $em): Response
    {
        $space = $em->getRepository(Space::class)->find(hex2bin($spaceId));
        
        if (!$space) {
            throw $this->createNotFoundException('Espace non trouvé');
        }

        $pricings = $em->getRepository(SpacePricing::class)->findBy(
            ['space' => $space, 'isDeleted' => false],
            ['periodicity' => 'ASC']
        );

        return $this->render('market_admin/spaces/pricing/index.html.twig', [
            'space' => $space,
            'pricings' => $pricings,
        ]);
    }

    #[Route('/add', name: 'add', methods: ['POST'])]
    public function add(
        string $spaceId,
        Request $request,
        EntityManagerInterface $em,
        CurrencyRepository $currencyRepository
    ): Response {
        $space = $em->getRepository(Space::class)->find(hex2bin($spaceId));
        
        if (!$space) {
            throw $this->createNotFoundException('Espace non trouvé');
        }

        $pricing = new SpacePricing();
        $pricing->setSpace($space);
        $pricing->setPeriodicity(Periodicity::from($request->request->get('periodicity')));
        $pricing->setAmount($request->request->get('amount'));
        $pricing->setMinDuration((int)$request->request->get('min_duration'));
        $pricing->setMaxDuration((int)$request->request->get('max_duration'));
        
        $currency = $currencyRepository->find(hex2bin($request->request->get('currency_id')));
        $pricing->setCurrency($currency);

        $em->persist($pricing);
        $em->flush();

        $this->addFlash('success', 'Tarif ajouté avec succès');

        return $this->redirectToRoute('market_admin_space_pricing_index', ['spaceId' => $spaceId]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(string $spaceId, string $id, EntityManagerInterface $em): Response
    {
        $pricing = $em->getRepository(SpacePricing::class)->find(hex2bin($id));
        
        if ($pricing) {
            $pricing->setIsDeleted(true);
            $em->flush();
            $this->addFlash('success', 'Tarif supprimé avec succès');
        }

        return $this->redirectToRoute('market_admin_space_pricing_index', ['spaceId' => $spaceId]);
    }
}

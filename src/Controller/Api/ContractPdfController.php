<?php

namespace App\Controller\Api;

use App\Entity\Contract;
use App\Service\ContractGenerationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/custom')]
class ContractPdfController extends AbstractController
{
    public function __construct(
        private ContractGenerationService $pdfService
    ) {
    }

    #[Route('/contracts/{id}/pdf', name: 'api_contract_pdf', methods: ['GET'])]
    public function generatePdf(Contract $contract): Response
    {
        $pdfContent = $this->pdfService->generateContractPdf($contract);

        return new Response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="contract-' . $contract->getContractCode() . '.pdf"'
        ]);
    }
}

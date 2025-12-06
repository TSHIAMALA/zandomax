<?php

namespace App\Service;

use App\Entity\Contract;
use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;

class ContractGenerationService
{
    public function __construct(
        private Environment $twig
    ) {
    }

    public function generateContractPdf(Contract $contract): string
    {
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');
        
        $dompdf = new Dompdf($pdfOptions);
        
        $html = $this->twig->render('pdf/contract.html.twig', [
            'contract' => $contract
        ]);
        
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        return $dompdf->output();
    }
}

<?php

namespace App\Command;

use App\Repository\ContractRepository;
use App\Service\NotificationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:check-expiring-contracts',
    description: 'Vérifie les contrats qui expirent bientôt et envoie des notifications',
)]
class CheckExpiringContractsCommand extends Command
{
    public function __construct(
        private ContractRepository $contractRepository,
        private NotificationService $notificationService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Vérification des contrats expirant bientôt');

        $now = new \DateTime();
        $in30Days = (clone $now)->modify('+30 days');

        // Trouver les contrats actifs qui expirent dans les 30 prochains jours
        $expiringContracts = $this->contractRepository->createQueryBuilder('c')
            ->where('c.status = :status')
            ->andWhere('c.endDate BETWEEN :now AND :in30days')
            ->setParameter('status', 'active')
            ->setParameter('now', $now)
            ->setParameter('in30days', $in30Days)
            ->getQuery()
            ->getResult();

        $sent = 0;
        foreach ($expiringContracts as $contract) {
            $daysUntilExpiry = $now->diff($contract->getEndDate())->days;
            
            // Envoyer notification à 30, 15, 7 et 3 jours
            if (in_array($daysUntilExpiry, [30, 15, 7, 3])) {
                $notification = $this->notificationService->notifyContractExpiring($contract, $daysUntilExpiry);
                if ($this->notificationService->sendNotification($notification)) {
                    $sent++;
                }
            }
        }

        $io->success(sprintf('%d notification(s) d\'expiration envoyée(s)', $sent));

        return Command::SUCCESS;
    }
}

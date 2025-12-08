<?php

namespace App\Command;

use App\Repository\PaymentRepository;
use App\Service\NotificationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:send-payment-reminders',
    description: 'Envoie des rappels pour les paiements en retard',
)]
class SendPaymentRemindersCommand extends Command
{
    public function __construct(
        private PaymentRepository $paymentRepository,
        private NotificationService $notificationService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Envoi des rappels de paiement');

        // Trouver les paiements en retard
        $overduePayments = $this->paymentRepository->createQueryBuilder('p')
            ->where('p.status = :status')
            ->andWhere('p.dueDate < :now')
            ->setParameter('status', 'pending')
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getResult();

        $sent = 0;
        foreach ($overduePayments as $payment) {
            $notification = $this->notificationService->notifyPaymentReminder($payment);
            if ($this->notificationService->sendNotification($notification)) {
                $sent++;
            }
        }

        $io->success(sprintf('%d rappel(s) de paiement envoyé(s)', $sent));

        return Command::SUCCESS;
    }
}

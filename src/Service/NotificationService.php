<?php

namespace App\Service;

use App\Entity\Merchant;
use App\Entity\SpaceReservation;
use App\Entity\Contract;
use App\Entity\Payment;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Psr\Log\LoggerInterface;

class NotificationService
{
    public function __construct(
        private MailerInterface $mailer,
        private LoggerInterface $logger,
        private string $fromEmail = 'noreply@zandomarche.com'
    ) {
    }

    /**
     * Notification d'approbation de réservation
     */
    public function notifyReservationApproved(SpaceReservation $reservation, Contract $contract): void
    {
        $merchant = $reservation->getMerchant();
        
        if (!$merchant->getEmail()) {
            return;
        }

        $email = (new Email())
            ->from($this->fromEmail)
            ->to($merchant->getEmail())
            ->subject('✅ Votre réservation a été approuvée - ZANDO Market')
            ->html($this->getReservationApprovedTemplate($merchant, $reservation, $contract));

        try {
            $this->mailer->send($email);
            $this->logger->info('Email d\'approbation envoyé', [
                'merchant' => $merchant->getEmail(),
                'reservation_id' => bin2hex($reservation->getId())
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur envoi email approbation', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Notification de refus de réservation
     */
    public function notifyReservationRejected(SpaceReservation $reservation): void
    {
        $merchant = $reservation->getMerchant();
        
        if (!$merchant->getEmail()) {
            return;
        }

        $email = (new Email())
            ->from($this->fromEmail)
            ->to($merchant->getEmail())
            ->subject('❌ Votre réservation a été refusée - ZANDO Market')
            ->html($this->getReservationRejectedTemplate($merchant, $reservation));

        try {
            $this->mailer->send($email);
            $this->logger->info('Email de refus envoyé', [
                'merchant' => $merchant->getEmail(),
                'reservation_id' => bin2hex($reservation->getId())
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur envoi email refus', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Rappel d'échéance de paiement
     */
    public function notifyPaymentDue(Payment $payment): void
    {
        $merchant = $payment->getMerchant();
        
        if (!$merchant->getEmail()) {
            return;
        }

        $email = (new Email())
            ->from($this->fromEmail)
            ->to($merchant->getEmail())
            ->subject('⏰ Rappel : Paiement à effectuer - ZANDO Market')
            ->html($this->getPaymentDueTemplate($merchant, $payment));

        try {
            $this->mailer->send($email);
            $this->logger->info('Email rappel paiement envoyé', [
                'merchant' => $merchant->getEmail(),
                'payment_id' => bin2hex($payment->getId())
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur envoi email rappel', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Notification de contrat expiré
     */
    public function notifyContractExpiring(Contract $contract, int $daysRemaining): void
    {
        $merchant = $contract->getMerchant();
        
        if (!$merchant->getEmail()) {
            return;
        }

        $email = (new Email())
            ->from($this->fromEmail)
            ->to($merchant->getEmail())
            ->subject("⚠️ Votre contrat expire dans {$daysRemaining} jours - ZANDO Market")
            ->html($this->getContractExpiringTemplate($merchant, $contract, $daysRemaining));

        try {
            $this->mailer->send($email);
            $this->logger->info('Email expiration contrat envoyé', [
                'merchant' => $merchant->getEmail(),
                'contract_id' => bin2hex($contract->getId())
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur envoi email expiration', [
                'error' => $e->getMessage()
            ]);
        }
    }

    // Templates HTML

    private function getReservationApprovedTemplate(Merchant $merchant, SpaceReservation $reservation, Contract $contract): string
    {
        return <<<HTML
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center;">
                <h1 style="color: white; margin: 0;">🎉 Réservation Approuvée</h1>
            </div>
            <div style="padding: 30px; background: #f9fafb;">
                <p>Bonjour <strong>{$merchant->getFirstname()} {$merchant->getLastname()}</strong>,</p>
                
                <p>Excellente nouvelle ! Votre réservation pour l'espace <strong>{$reservation->getSpace()->getCode()}</strong> a été approuvée.</p>
                
                <div style="background: white; padding: 20px; border-radius: 8px; margin: 20px 0;">
                    <h3 style="color: #667eea; margin-top: 0;">Détails du Contrat</h3>
                    <p><strong>Code contrat :</strong> {$contract->getContractCode()}</p>
                    <p><strong>Début :</strong> {$contract->getStartDate()->format('d/m/Y')}</p>
                    <p><strong>Fin :</strong> {$contract->getEndDate()->format('d/m/Y')}</p>
                    <p><strong>Loyer mensuel :</strong> {$contract->getRentAmount()} {$contract->getCurrency()->getCode()}</p>
                </div>
                
                <p>Vous pouvez consulter votre contrat dans votre espace marchand.</p>
                
                <p style="margin-top: 30px;">Cordialement,<br><strong>L'équipe ZANDO Market</strong></p>
            </div>
            <div style="background: #1f2937; padding: 20px; text-align: center; color: #9ca3af; font-size: 12px;">
                <p>© 2025 ZANDO Market - Tous droits réservés</p>
            </div>
        </div>
        HTML;
    }

    private function getReservationRejectedTemplate(Merchant $merchant, SpaceReservation $reservation): string
    {
        return <<<HTML
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
            <div style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); padding: 30px; text-align: center;">
                <h1 style="color: white; margin: 0;">❌ Réservation Refusée</h1>
            </div>
            <div style="padding: 30px; background: #f9fafb;">
                <p>Bonjour <strong>{$merchant->getFirstname()} {$merchant->getLastname()}</strong>,</p>
                
                <p>Nous sommes désolés de vous informer que votre réservation pour l'espace <strong>{$reservation->getSpace()->getCode()}</strong> a été refusée.</p>
                
                <div style="background: #fee2e2; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #ef4444;">
                    <h3 style="color: #dc2626; margin-top: 0;">Motif du refus</h3>
                    <p>{$reservation->getRejectionReason()}</p>
                </div>
                
                <p>N'hésitez pas à nous contacter pour plus d'informations ou pour faire une nouvelle demande.</p>
                
                <p style="margin-top: 30px;">Cordialement,<br><strong>L'équipe ZANDO Market</strong></p>
            </div>
            <div style="background: #1f2937; padding: 20px; text-align: center; color: #9ca3af; font-size: 12px;">
                <p>© 2025 ZANDO Market - Tous droits réservés</p>
            </div>
        </div>
        HTML;
    }

    private function getPaymentDueTemplate(Merchant $merchant, Payment $payment): string
    {
        $dueDate = $payment->getDueDate()->format('d/m/Y');
        
        return <<<HTML
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
            <div style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); padding: 30px; text-align: center;">
                <h1 style="color: white; margin: 0;">⏰ Rappel de Paiement</h1>
            </div>
            <div style="padding: 30px; background: #f9fafb;">
                <p>Bonjour <strong>{$merchant->getFirstname()} {$merchant->getLastname()}</strong>,</p>
                
                <p>Ceci est un rappel concernant un paiement à effectuer.</p>
                
                <div style="background: white; padding: 20px; border-radius: 8px; margin: 20px 0;">
                    <h3 style="color: #f59e0b; margin-top: 0;">Détails du Paiement</h3>
                    <p><strong>Type :</strong> {$payment->getType()}</p>
                    <p><strong>Montant :</strong> {$payment->getAmount()} {$payment->getCurrency()->getCode()}</p>
                    <p><strong>Date d'échéance :</strong> {$dueDate}</p>
                </div>
                
                <p>Merci d'effectuer ce paiement dans les meilleurs délais pour éviter toute pénalité.</p>
                
                <p style="margin-top: 30px;">Cordialement,<br><strong>L'équipe ZANDO Market</strong></p>
            </div>
            <div style="background: #1f2937; padding: 20px; text-align: center; color: #9ca3af; font-size: 12px;">
                <p>© 2025 ZANDO Market - Tous droits réservés</p>
            </div>
        </div>
        HTML;
    }

    private function getContractExpiringTemplate(Merchant $merchant, Contract $contract, int $daysRemaining): string
    {
        return <<<HTML
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
            <div style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); padding: 30px; text-align: center;">
                <h1 style="color: white; margin: 0;">⚠️ Contrat Bientôt Expiré</h1>
            </div>
            <div style="padding: 30px; background: #f9fafb;">
                <p>Bonjour <strong>{$merchant->getFirstname()} {$merchant->getLastname()}</strong>,</p>
                
                <p>Votre contrat pour l'espace <strong>{$contract->getSpace()->getCode()}</strong> expire dans <strong>{$daysRemaining} jours</strong>.</p>
                
                <div style="background: white; padding: 20px; border-radius: 8px; margin: 20px 0;">
                    <h3 style="color: #8b5cf6; margin-top: 0;">Informations du Contrat</h3>
                    <p><strong>Code :</strong> {$contract->getContractCode()}</p>
                    <p><strong>Date de fin :</strong> {$contract->getEndDate()->format('d/m/Y')}</p>
                </div>
                
                <p>Si vous souhaitez renouveler votre contrat, veuillez nous contacter rapidement.</p>
                
                <p style="margin-top: 30px;">Cordialement,<br><strong>L'équipe ZANDO Market</strong></p>
            </div>
            <div style="background: #1f2937; padding: 20px; text-align: center; color: #9ca3af; font-size: 12px;">
                <p>© 2025 ZANDO Market - Tous droits réservés</p>
            </div>
        </div>
        HTML;
    }
}

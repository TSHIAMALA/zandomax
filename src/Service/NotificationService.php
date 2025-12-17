<?php

namespace App\Service;

use App\Entity\Notification;
use App\Entity\Merchant;
use Doctrine\ORM\EntityManagerInterface;

class NotificationService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public function createAdminNotification(
        string $type,
        string $title,
        string $message,
        ?Merchant $merchant = null,
        ?array $metadata = null
    ): Notification {
        $notification = new Notification();
        $notification->setType($type);
        $notification->setTitle($title);
        $notification->setMessage($message);
        $notification->setIsForAdmin(true);
        $notification->setChannel('push');
        
        if ($merchant) {
            $notification->setMerchant($merchant);
        }
        
        if ($metadata) {
            $notification->setMetadata($metadata);
        }

        $this->entityManager->persist($notification);
        $this->entityManager->flush();

        return $notification;
    }

    public function createMerchantNotification(
        Merchant $merchant,
        string $type,
        string $title,
        string $message,
        ?array $metadata = null
    ): Notification {
        $notification = new Notification();
        $notification->setMerchant($merchant);
        $notification->setType($type);
        $notification->setTitle($title);
        $notification->setMessage($message);
        $notification->setIsForAdmin(false);
        $notification->setChannel('push');
        
        if ($metadata) {
            $notification->setMetadata($metadata);
        }

        $this->entityManager->persist($notification);
        $this->entityManager->flush();

        return $notification;
    }

    public function getUnreadAdminNotifications(): array
    {
        return $this->entityManager->getRepository(Notification::class)
            ->findBy(
                ['isForAdmin' => true, 'isRead' => false],
                ['createdAt' => 'DESC'],
                20
            );
    }

    public function getAdminNotifications(int $limit = 50): array
    {
        return $this->entityManager->getRepository(Notification::class)
            ->findBy(
                ['isForAdmin' => true],
                ['createdAt' => 'DESC'],
                $limit
            );
    }

    public function markAsRead(Notification $notification): void
    {
        $notification->setIsRead(true);
        $notification->setReadAt(new \DateTime());
        $this->entityManager->flush();
    }

    public function markAllAdminAsRead(): void
    {
        $notifications = $this->entityManager->getRepository(Notification::class)
            ->findBy(['isForAdmin' => true, 'isRead' => false]);
        
        foreach ($notifications as $notification) {
            $notification->setIsRead(true);
            $notification->setReadAt(new \DateTime());
        }
        
        $this->entityManager->flush();
    }

    public function countUnreadAdmin(): int
    {
        return $this->entityManager->getRepository(Notification::class)
            ->count(['isForAdmin' => true, 'isRead' => false]);
    }
}

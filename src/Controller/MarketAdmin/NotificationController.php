<?php

namespace App\Controller\MarketAdmin;

use App\Service\NotificationService;
use App\Repository\NotificationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/notifications', name: 'market_admin_notifications_')]
#[IsGranted('ROLE_MARKET_ADMIN')]
class NotificationController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(NotificationService $notificationService): Response
    {
        $notifications = $notificationService->getAdminNotifications(100);

        return $this->render('market_admin/notifications/index.html.twig', [
            'notifications' => $notifications,
        ]);
    }

    #[Route('/unread', name: 'unread')]
    public function unread(NotificationService $notificationService): JsonResponse
    {
        $notifications = $notificationService->getUnreadAdminNotifications();
        $count = $notificationService->countUnreadAdmin();

        $data = [];
        foreach ($notifications as $notification) {
            $data[] = [
                'id' => $notification->getId(),
                'type' => $notification->getType(),
                'title' => $notification->getTitle(),
                'message' => $notification->getMessage(),
                'createdAt' => $notification->getCreatedAt()->format('d/m/Y H:i'),
                'metadata' => $notification->getMetadata(),
            ];
        }

        return new JsonResponse([
            'count' => $count,
            'notifications' => $data,
        ]);
    }

    #[Route('/{id}/read', name: 'mark_read', methods: ['POST'])]
    public function markRead(
        string $id, 
        NotificationRepository $notificationRepository,
        NotificationService $notificationService
    ): JsonResponse
    {
        $notification = $notificationRepository->find(hex2bin($id));
        
        if ($notification) {
            $notificationService->markAsRead($notification);
        }

        return new JsonResponse(['success' => true]);
    }

    #[Route('/mark-all-read', name: 'mark_all_read', methods: ['POST'])]
    public function markAllRead(NotificationService $notificationService): JsonResponse
    {
        $notificationService->markAllAdminAsRead();

        return new JsonResponse(['success' => true]);
    }
}

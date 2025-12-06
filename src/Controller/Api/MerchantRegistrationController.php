<?php

namespace App\Controller\Api;

use App\Service\MerchantRegistrationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/custom')]
class MerchantRegistrationController extends AbstractController
{
    public function __construct(
        private MerchantRegistrationService $registrationService,
        private SerializerInterface $serializer
    ) {
    }

    #[Route('/register-merchant', name: 'api_register_merchant', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        try {
            $merchant = $this->registrationService->registerMerchant($data);
            
            $json = $this->serializer->serialize($merchant, 'json', ['groups' => 'merchant:read']);
            
            return new JsonResponse($json, Response::HTTP_CREATED, [], true);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
}

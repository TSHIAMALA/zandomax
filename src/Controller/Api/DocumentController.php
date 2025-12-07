<?php

namespace App\Controller\Api;

use App\Entity\Merchant;
use App\Service\DocumentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/merchants/{id}/documents')]
class DocumentController extends AbstractController
{
    public function __construct(
        private DocumentService $documentService
    ) {
    }

    #[Route('', name: 'api_documents_upload', methods: ['POST'])]
    public function upload(Merchant $merchant, Request $request): JsonResponse
    {
        /** @var UploadedFile $file */
        $file = $request->files->get('file');
        $documentType = $request->request->get('type', 'other');

        if (!$file) {
            return new JsonResponse(['error' => 'No file provided'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $document = $this->documentService->uploadDocument($merchant, $file, $documentType);
            
            return new JsonResponse($document, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('', name: 'api_documents_list', methods: ['GET'])]
    public function list(Merchant $merchant): JsonResponse
    {
        $documents = $merchant->getDocuments() ?? [];
        
        return new JsonResponse($documents);
    }

    #[Route('/{filename}', name: 'api_documents_download', methods: ['GET'])]
    public function download(Merchant $merchant, string $filename): Response
    {
        $documents = $merchant->getDocuments() ?? [];
        
        // Verify document belongs to merchant
        $found = false;
        foreach ($documents as $doc) {
            if ($doc['filename'] === $filename) {
                $found = true;
                break;
            }
        }

        if (!$found) {
            return new JsonResponse(['error' => 'Document not found'], Response::HTTP_NOT_FOUND);
        }

        $filepath = $this->documentService->getDocumentPath($filename);
        
        if (!file_exists($filepath)) {
            return new JsonResponse(['error' => 'File not found on disk'], Response::HTTP_NOT_FOUND);
        }

        return new BinaryFileResponse($filepath);
    }

    #[Route('/{filename}', name: 'api_documents_delete', methods: ['DELETE'])]
    public function delete(Merchant $merchant, string $filename): JsonResponse
    {
        try {
            $this->documentService->deleteDocument($merchant, $filename);
            
            return new JsonResponse(['message' => 'Document deleted successfully']);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
}

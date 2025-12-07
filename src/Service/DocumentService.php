<?php

namespace App\Service;

use App\Entity\Merchant;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class DocumentService
{
    private const UPLOAD_DIR = '/var/www/zandomax/var/uploads/documents';
    private const ALLOWED_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png'];
    private const MAX_FILE_SIZE = 5242880; // 5MB

    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        // Create upload directory if it doesn't exist
        if (!is_dir(self::UPLOAD_DIR)) {
            mkdir(self::UPLOAD_DIR, 0775, true);
        }
    }

    public function uploadDocument(Merchant $merchant, UploadedFile $file, string $documentType): array
    {
        // Validate file
        $this->validateFile($file);

        // Generate unique filename
        $extension = $file->guessExtension();
        $filename = sprintf(
            '%s_%s_%s.%s',
            $merchant->getId(),
            $documentType,
            uniqid(),
            $extension
        );

        // Move file to upload directory
        $file->move(self::UPLOAD_DIR, $filename);

        // Get current documents
        $documents = $merchant->getDocuments() ?? [];

        // Add new document
        $documents[] = [
            'type' => $documentType,
            'filename' => $filename,
            'original_name' => $file->getClientOriginalName(),
            'uploaded_at' => (new \DateTime())->format('Y-m-d H:i:s'),
            'size' => $file->getSize(),
        ];

        // Update merchant
        $merchant->setDocuments($documents);
        $this->entityManager->flush();

        return end($documents);
    }

    public function deleteDocument(Merchant $merchant, string $filename): void
    {
        $documents = $merchant->getDocuments() ?? [];
        
        // Find and remove document
        $documents = array_filter($documents, function($doc) use ($filename) {
            if ($doc['filename'] === $filename) {
                // Delete physical file
                $filepath = self::UPLOAD_DIR . '/' . $filename;
                if (file_exists($filepath)) {
                    unlink($filepath);
                }
                return false;
            }
            return true;
        });

        // Update merchant
        $merchant->setDocuments(array_values($documents));
        $this->entityManager->flush();
    }

    public function getDocumentPath(string $filename): string
    {
        return self::UPLOAD_DIR . '/' . $filename;
    }

    private function validateFile(UploadedFile $file): void
    {
        // Check file size
        if ($file->getSize() > self::MAX_FILE_SIZE) {
            throw new \InvalidArgumentException('File size exceeds 5MB limit.');
        }

        // Check extension
        $extension = $file->guessExtension();
        if (!in_array($extension, self::ALLOWED_EXTENSIONS)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid file type. Allowed: %s', implode(', ', self::ALLOWED_EXTENSIONS))
            );
        }

        // Check if file is valid
        if (!$file->isValid()) {
            throw new \RuntimeException('File upload failed.');
        }
    }
}

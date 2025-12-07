<?php

namespace App\Controller\MarketAdmin;

use App\Entity\Space;
use App\Entity\SpaceMedia;
use App\Service\FileUploadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/spaces/{spaceId}/media', name: 'market_admin_space_media_')]
#[IsGranted('ROLE_MARKET_ADMIN')]
class SpaceMediaController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(string $spaceId, EntityManagerInterface $em): Response
    {
        $space = $em->getRepository(Space::class)->find(hex2bin($spaceId));
        
        if (!$space) {
            throw $this->createNotFoundException('Espace non trouvé');
        }

        $medias = $em->getRepository(SpaceMedia::class)->findBy(
            ['space' => $space, 'isDeleted' => false],
            ['displayOrder' => 'ASC']
        );

        return $this->render('market_admin/spaces/media/index.html.twig', [
            'space' => $space,
            'medias' => $medias,
        ]);
    }

    #[Route('/upload', name: 'upload', methods: ['POST'])]
    public function upload(
        string $spaceId,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $space = $em->getRepository(Space::class)->find(hex2bin($spaceId));
        
        if (!$space) {
            throw $this->createNotFoundException('Espace non trouvé');
        }

        /** @var UploadedFile $file */
        $file = $request->files->get('file');
        
        if ($file) {
            $uploadsDirectory = $this->getParameter('kernel.project_dir') . '/public/uploads/spaces';
            $fileName = uniqid() . '.' . $file->guessExtension();
            $file->move($uploadsDirectory, $fileName);

            $media = new SpaceMedia();
            $media->setSpace($space);
            $media->setFilePath('/uploads/spaces/' . $fileName);
            $media->setMediaType($this->getMediaType($file->getMimeType()));
            $media->setDescription($request->request->get('description', ''));
            
            // Si c'est le premier média, le définir comme principal
            $existingMedias = $em->getRepository(SpaceMedia::class)->count(['space' => $space, 'isDeleted' => false]);
            if ($existingMedias === 0) {
                $media->setIsPrimary(true);
            }
            
            $media->setDisplayOrder($existingMedias + 1);

            $em->persist($media);
            $em->flush();

            $this->addFlash('success', 'Média ajouté avec succès');
        }

        return $this->redirectToRoute('market_admin_space_media_index', ['spaceId' => $spaceId]);
    }

    #[Route('/{id}/primary', name: 'set_primary', methods: ['POST'])]
    public function setPrimary(string $spaceId, string $id, EntityManagerInterface $em): Response
    {
        $media = $em->getRepository(SpaceMedia::class)->find(hex2bin($id));
        
        if ($media) {
            // Retirer le statut principal des autres médias
            $allMedias = $em->getRepository(SpaceMedia::class)->findBy(['space' => $media->getSpace(), 'isDeleted' => false]);
            foreach ($allMedias as $m) {
                $m->setIsPrimary(false);
            }
            
            // Définir ce média comme principal
            $media->setIsPrimary(true);
            $em->flush();
            
            $this->addFlash('success', 'Média principal défini');
        }

        return $this->redirectToRoute('market_admin_space_media_index', ['spaceId' => $spaceId]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(string $spaceId, string $id, EntityManagerInterface $em): Response
    {
        $media = $em->getRepository(SpaceMedia::class)->find(hex2bin($id));
        
        if ($media) {
            $media->setIsDeleted(true);
            $em->flush();
            $this->addFlash('success', 'Média supprimé avec succès');
        }

        return $this->redirectToRoute('market_admin_space_media_index', ['spaceId' => $spaceId]);
    }

    private function getMediaType(?string $mimeType): string
    {
        if (str_starts_with($mimeType, 'video/')) {
            return 'VIDEO';
        }
        return 'IMAGE';
    }
}

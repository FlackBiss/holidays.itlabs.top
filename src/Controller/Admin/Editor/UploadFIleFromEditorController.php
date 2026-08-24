<?php

namespace App\Controller\Admin\Editor;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapUploadedFile;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Ulid;

#[AsController]
#[Route(path: '/admin/upload/trix', name: 'upload_file_from_editor', methods: ['POST'])]
class UploadFIleFromEditorController extends AbstractController
{
    public function __invoke(
        #[MapUploadedFile] UploadedFile $file
    ): JsonResponse
    {
        if (!is_dir('trix')) {
            mkdir('trix');
        }

        $filename = Ulid::generate() . '.' . $file->guessExtension();

        file_put_contents('trix/' . $filename, $file->getContent());

        return $this->json([
            'ok' => true,
            'url' => 'trix/' . $filename,
        ]);
    }
}
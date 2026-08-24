<?php

namespace App\Controller\Admin\Editor;

use App\Controller\Admin\Editor\Input\RemoveFileFromEditorRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
#[Route(path: '/admin/remove/trix', name: 'remove_file_from_editor', methods: ['POST'])]
class RemoveFileFromEditorController extends AbstractController
{
    public function __invoke(
        #[MapRequestPayload] RemoveFileFromEditorRequest $request
    ): JsonResponse
    {
        if (!is_file($request->getFilename())) {
            return $this->json([
               'success' => false,
               'details' => 'File not found'
            ]);
        }

        unlink($request->getFilename());

        return $this->json([
            'success' => true,
            'details' => 'File successfully removed'
        ]);
    }
}
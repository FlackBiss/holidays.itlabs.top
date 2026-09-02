<?php

namespace App\Controller;

use App\Entity\SiteSettings;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final readonly class ExitPasswordController
{
    public function __construct(private EntityManagerInterface $em) {}

    #[Route('/api/settings/exit-password', name: 'api_get_exit_password', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        $settings = $this->em->getRepository(SiteSettings::class)->findOneBy(['code' => 'main']);

        return new JsonResponse([
            'exitPassword' => $settings instanceof SiteSettings ? $settings->getExitPassword() : null,
        ]);
    }
}

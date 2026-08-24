<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class NodeTypeCompatibilityController
{
    #[Route('/api/node_types', name: 'api_legacy_node_types', methods: ['GET'], priority: 200)]
    public function __invoke(): JsonResponse
    {
        return new JsonResponse([
            ['id' => 1, 'title' => 'Обычный маршрут', 'name' => 'Обычный маршрут'],
            ['id' => 2, 'title' => 'Доступный маршрут', 'name' => 'Доступный маршрут'],
        ]);
    }
}

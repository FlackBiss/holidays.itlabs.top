<?php

namespace App\Controller;

use App\Enum\MapPlaceCategory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class MapPlaceCategoryController
{
    #[Route('/api/map/categories', name: 'api_map_categories', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        return new JsonResponse(array_map(
            static fn (MapPlaceCategory $category): array => [
                'value' => $category->value,
                'name' => $category->label(),
            ],
            MapPlaceCategory::cases(),
        ));
    }
}

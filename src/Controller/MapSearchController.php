<?php

namespace App\Controller;

use App\Entity\MapPlace;
use App\Service\MapPlaceSearchMatcher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

final readonly class MapSearchController
{
    private const int MAX_RESULTS = 30;

    public function __construct(
        private EntityManagerInterface $em,
        private MapPlaceSearchMatcher $matcher,
    ) {
    }

    #[Route('/api/map/search', name: 'api_map_search', methods: ['GET'])]
    #[Route('/api/objects/search', name: 'api_objects_search', methods: ['GET'], priority: 200)]
    public function __invoke(Request $request): JsonResponse
    {
        $query = trim((string) $request->query->get('q'));
        if (mb_strlen($query) < 2) {
            throw new BadRequestHttpException('Поисковый запрос должен содержать минимум 2 символа.');
        }

        $qb = $this->em->getRepository(MapPlace::class)
            ->createQueryBuilder('p')
            ->andWhere('p.active = true')
            ->orderBy('p.priority', 'ASC')
            ->addOrderBy('p.name', 'ASC');

        if ($plan = $request->query->getInt('plan')) {
            $qb->andWhere('IDENTITY(p.plan) = :plan')->setParameter('plan', $plan);
        }

        $items = [];
        foreach ($qb->getQuery()->getResult() as $place) {
            if (!$place instanceof MapPlace || !$this->matcher->matches($place, $query)) {
                continue;
            }

            $items[] = $this->normalize($place);
            if (count($items) === self::MAX_RESULTS) {
                break;
            }
        }

        return new JsonResponse(['items' => $items, 'total' => count($items)]);
    }

    /** @return array<string, mixed> */
    private function normalize(MapPlace $place): array
    {
        return [
            'id' => $place->getId(),
            'name' => $place->name,
            'title' => $place->name,
            'type' => $place->type->value,
            'category' => $place->category->value,
            'categoryLabel' => $place->category->label(),
            'routeDrawn' => $place->routeDrawn,
            'routeAvailable' => $place->isRouteAvailable(),
            'mapId' => $place->plan?->getId(),
            'floor' => $place->plan?->getId(),
            'node' => $place->node?->getId(),
            'area' => $place->area?->getId(),
            'icon' => $place->getIconUrl(),
        ];
    }
}

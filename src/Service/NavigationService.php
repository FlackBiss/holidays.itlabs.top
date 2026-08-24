<?php

namespace App\Service;

use App\ApiResource\NavigationRequest;
use App\ApiResource\NavigationRouteResult;
use App\Entity\MapEdge;
use App\Entity\MapNode;
use App\Entity\MapPlace;
use App\Entity\SiteSettings;
use App\Entity\KioskTerminal;
use App\Enum\PlaceType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

final readonly class NavigationService
{
    public function __construct(private EntityManagerInterface $em, private string $mobileMapUrl) {}

    public function buildRoute(NavigationRequest $request): NavigationRouteResult
    {
        if ($request->destinationPlaceId === null || $request->destinationPlaceId < 1) {
            throw new BadRequestHttpException('destinationPlaceId обязателен.');
        }
        $place = $this->em->getRepository(MapPlace::class)->find($request->destinationPlaceId);
        if (!$place instanceof MapPlace || !$place->active || !$place->node) throw new NotFoundHttpException('Объект назначения не найден.');
        if ($place->type === PlaceType::INFRASTRUCTURE && !$place->routeDrawn) throw new UnprocessableEntityHttpException('Маршрут к этому объекту ещё не расчерчен.');

        $plan = $place->plan;
        $edges = $this->em->getRepository(MapEdge::class)->findBy(['plan' => $plan, 'active' => true]);
        $edges = array_values(array_filter($edges, fn(MapEdge $e) => !$request->accessible || $e->accessible));
        if (!$edges) throw new UnprocessableEntityHttpException('Для этой карты не задана маршрутная сеть.');

        $source = null;
        $snapped = null;
        $snapDistance = 0.0;
        $seed = [];

        if ($request->latitude !== null || $request->longitude !== null) {
            if ($request->latitude === null || $request->longitude === null) throw new BadRequestHttpException('latitude и longitude передаются вместе.');
            $source = ['latitude' => $request->latitude, 'longitude' => $request->longitude];
            [$edge, $projection, $snapDistance, $fromCost, $toCost] = $this->nearestEdge($edges, $request->latitude, $request->longitude);
            $limit = $this->em->getRepository(SiteSettings::class)->findOneBy(['code' => 'main'])?->maxGeoSnapDistanceMeters ?? 500;
            if ($snapDistance > $limit) throw new UnprocessableEntityHttpException(sprintf('Пользователь находится слишком далеко от маршрутной сети (%.0f м).', $snapDistance));
            $snapped = $projection;
            if ($edge->bidirectional) $seed[$edge->fromNode->getId()] = $fromCost;
            $seed[$edge->toNode->getId()] = $toCost;
        } elseif ($request->fromNodeId !== null || $request->terminalCode !== null) {
            $from = $request->terminalCode !== null
                ? $this->em->getRepository(KioskTerminal::class)->findOneBy(['code' => $request->terminalCode, 'active' => true])?->startNode
                : $this->em->getRepository(MapNode::class)->find($request->fromNodeId);
            if (!$from instanceof MapNode || $from->plan?->getId() !== $plan?->getId()) throw new NotFoundHttpException('Начальный узел не найден на выбранной карте.');
            $seed[$from->getId()] = 0.0;
        } else {
            throw new BadRequestHttpException('Передайте terminalCode, fromNodeId либо координаты latitude и longitude.');
        }

        [$path, $distance] = $this->dijkstra($edges, $seed, $place->node);
        if (!$path) throw new UnprocessableEntityHttpException('Маршрут до объекта не найден.');

        $points = [];
        if ($snapped) $points[] = ['id' => null, 'x' => $snapped['x'], 'y' => $snapped['y'], 'latitude' => $snapped['latitude'], 'longitude' => $snapped['longitude'], 'kind' => 'snap'];
        foreach ($path as $node) $points[] = ['id' => $node->getId(), 'x' => $node->x, 'y' => $node->y, 'latitude' => $node->latitude, 'longitude' => $node->longitude, 'kind' => 'node'];

        $query = http_build_query(['destination' => $place->getId(), 'map' => $plan?->getId()]);
        $result = new NavigationRouteResult();
        $result->mapId = $plan->getId();
        $result->destinationPlaceId = $place->getId();
        $result->points = $points;
        $result->sourcePosition = $source;
        $result->snappedPosition = $snapped;
        $result->snapDistanceMeters = round($snapDistance, 1);
        $result->distanceMeters = round($distance, 1);
        $result->mobileUrl = rtrim($planUrl = ($this->em->getRepository(SiteSettings::class)->findOneBy(['code' => 'main'])?->mobileMapUrl ?: $this->mobileMapUrl), '?&').'?'.$query;
        $result->qrCodeUrl = '/api/navigation/qr/'.$place->getId();
        return $result;
    }

    /** @return list<array{id: int, x: float, y: float, floor: int|null, floorName: string|null, latitude: float|null, longitude: float|null}> */
    public function buildNodePath(int $fromId, int $toId, bool $accessible = false): array
    {
        $from = $this->em->getRepository(MapNode::class)->find($fromId);
        $to = $this->em->getRepository(MapNode::class)->find($toId);
        if (!$from instanceof MapNode || !$to instanceof MapNode || !$from->active || !$to->active) {
            throw new NotFoundHttpException('Начальная или конечная точка не найдена.');
        }
        if ($from->plan?->getId() !== $to->plan?->getId()) {
            throw new BadRequestHttpException('Начальная и конечная точки должны находиться на одной карте.');
        }
        if ($from === $to) return [$this->legacyPoint($from)];
        $edges = $this->em->getRepository(MapEdge::class)->findBy(['plan' => $from->plan, 'active' => true]);
        if ($accessible) $edges = array_values(array_filter($edges, static fn (MapEdge $edge): bool => $edge->accessible));
        [$path] = $this->dijkstra($edges, [$fromId => 0.0], $to);
        if (!$path) throw new UnprocessableEntityHttpException('Маршрут между точками не найден.');
        return array_map($this->legacyPoint(...), $path);
    }

    /** @return array{id: int, x: float, y: float, floor: int|null, floorName: string|null, latitude: float|null, longitude: float|null} */
    private function legacyPoint(MapNode $node): array
    {
        return ['id' => $node->getId(), 'x' => $node->x, 'y' => $node->y, 'floor' => $node->plan?->getId(), 'floorName' => $node->plan?->title, 'latitude' => $node->latitude, 'longitude' => $node->longitude];
    }

    /** @return array{MapEdge,array,float,float,float} */
    private function nearestEdge(array $edges, float $lat, float $lon): array
    {
        $best = null;
        foreach ($edges as $edge) {
            $a = $edge->fromNode; $b = $edge->toNode;
            if ($a->latitude === null || $a->longitude === null || $b->latitude === null || $b->longitude === null) continue;
            $lat0 = deg2rad($lat); $scaleX = 111320.0 * cos($lat0); $scaleY = 110540.0;
            $ax = ($a->longitude - $lon) * $scaleX; $ay = ($a->latitude - $lat) * $scaleY;
            $bx = ($b->longitude - $lon) * $scaleX; $by = ($b->latitude - $lat) * $scaleY;
            $dx = $bx - $ax; $dy = $by - $ay; $length2 = $dx*$dx + $dy*$dy;
            $t = $length2 > 0 ? max(0.0, min(1.0, -($ax*$dx + $ay*$dy)/$length2)) : 0.0;
            $px = $ax + $t*$dx; $py = $ay + $t*$dy; $distance = hypot($px, $py);
            if ($best === null || $distance < $best[2]) {
                $mapX = $a->x + $t*($b->x - $a->x); $mapY = $a->y + $t*($b->y - $a->y);
                $projection = ['latitude' => $lat + $py/$scaleY, 'longitude' => $lon + $px/$scaleX, 'x' => $mapX, 'y' => $mapY];
                $edgeLength = $edge->distanceMeters ?? hypot($dx, $dy);
                $best = [$edge, $projection, $distance, $edgeLength*$t, $edgeLength*(1-$t)];
            }
        }
        if ($best === null) throw new UnprocessableEntityHttpException('У маршрутной сети не заполнены географические координаты.');
        return $best;
    }

    /** @return array{array<MapNode>,float} */
    private function dijkstra(array $edges, array $seed, MapNode $goal): array
    {
        $nodes = []; $graph = [];
        foreach ($edges as $edge) {
            $a=$edge->fromNode; $b=$edge->toNode; $nodes[$a->getId()]=$a; $nodes[$b->getId()]=$b;
            $cost = $edge->distanceMeters ?? $this->nodeDistance($a, $b);
            $graph[$a->getId()][] = [$b->getId(), $cost];
            if ($edge->bidirectional) $graph[$b->getId()][] = [$a->getId(), $cost];
        }
        $queue = new \SplPriorityQueue(); $queue->setExtractFlags(\SplPriorityQueue::EXTR_BOTH); $distance=[]; $previous=[];
        foreach ($seed as $id=>$cost) { $distance[$id]=$cost; $queue->insert((int)$id, -$cost); }
        while (!$queue->isEmpty()) {
            $item=$queue->extract(); $id=$item['data']; $current=-$item['priority'];
            if ($current > ($distance[$id] ?? INF)+0.0001) continue;
            if ($id === $goal->getId()) break;
            foreach ($graph[$id] ?? [] as [$next,$cost]) { $candidate=$current+$cost; if ($candidate < ($distance[$next] ?? INF)) { $distance[$next]=$candidate; $previous[$next]=$id; $queue->insert($next,-$candidate); } }
        }
        $goalId=$goal->getId(); if (!isset($distance[$goalId])) return [[],0.0];
        $ids=[$goalId]; while (isset($previous[end($ids)])) $ids[]=$previous[end($ids)]; $ids=array_reverse($ids);
        return [array_map(fn($id)=>$nodes[$id],$ids),$distance[$goalId]];
    }

    private function nodeDistance(MapNode $a, MapNode $b): float
    {
        if ($a->latitude !== null && $a->longitude !== null && $b->latitude !== null && $b->longitude !== null) {
            $r=6371000.0; $p1=deg2rad($a->latitude); $p2=deg2rad($b->latitude); $dp=deg2rad($b->latitude-$a->latitude); $dl=deg2rad($b->longitude-$a->longitude);
            $h=sin($dp/2)**2+cos($p1)*cos($p2)*sin($dl/2)**2; return 2*$r*asin(min(1.0,sqrt($h)));
        }
        return hypot($b->x-$a->x,$b->y-$a->y);
    }
}

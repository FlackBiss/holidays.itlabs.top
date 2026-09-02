<?php

namespace App\Service;

use App\Entity\MapEdge;
use App\Entity\MapNode;
use App\Entity\MapPlace;
use App\Entity\MapPlan;
use App\Enum\PlaceType;
use Doctrine\ORM\EntityManagerInterface;

final readonly class RouteDrawnSynchronizer
{
    public function __construct(private EntityManagerInterface $em) {}

    /**
     * Recalculates the persisted route flag from the actual active road network.
     *
     * @return int Number of changed places
     */
    public function synchronizePlan(MapPlan $plan): int
    {
        $routedNodes = [];
        foreach ($this->em->getRepository(MapEdge::class)->findBy(['plan' => $plan, 'active' => true]) as $edge) {
            if (!$edge instanceof MapEdge) continue;
            if ($edge->fromNode instanceof MapNode) $routedNodes[$this->nodeKey($edge->fromNode)] = true;
            if ($edge->toNode instanceof MapNode) $routedNodes[$this->nodeKey($edge->toNode)] = true;
        }

        $changed = 0;
        foreach ($this->em->getRepository(MapPlace::class)->findBy(['plan' => $plan, 'type' => PlaceType::INFRASTRUCTURE]) as $place) {
            if (!$place instanceof MapPlace) continue;
            $routeDrawn = $place->node instanceof MapNode && isset($routedNodes[$this->nodeKey($place->node)]);
            if ($place->routeDrawn === $routeDrawn) continue;
            $place->routeDrawn = $routeDrawn;
            ++$changed;
        }

        return $changed;
    }

    private function nodeKey(MapNode $node): string
    {
        return $node->getId() !== null ? 'id:'.$node->getId() : 'object:'.spl_object_id($node);
    }
}

<?php

namespace App\Tests\Unit;

use App\Entity\MapEdge;
use App\Entity\MapNode;
use App\Entity\MapPlace;
use App\Entity\MapPlan;
use App\Enum\PlaceType;
use App\Service\RouteDrawnSynchronizer;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;

final class RouteDrawnSynchronizerTest extends TestCase
{
    public function testItDerivesInfrastructureFlagsFromActiveRoadEndpoints(): void
    {
        $plan = new MapPlan();
        $connectedNode = new MapNode();
        $otherNode = new MapNode();
        $disconnectedNode = new MapNode();
        $connectedNode->plan = $otherNode->plan = $disconnectedNode->plan = $plan;
        $this->setId($connectedNode, 176);
        $this->setId($otherNode, 239);
        $this->setId($disconnectedNode, 240);

        $edge = new MapEdge();
        $edge->plan = $plan;
        $edge->fromNode = $otherNode;
        $edge->toNode = $connectedNode;

        $connectedPlace = new MapPlace();
        $connectedPlace->plan = $plan;
        $connectedPlace->node = $connectedNode;
        $connectedPlace->type = PlaceType::INFRASTRUCTURE;
        $connectedPlace->routeDrawn = false;

        $disconnectedPlace = new MapPlace();
        $disconnectedPlace->plan = $plan;
        $disconnectedPlace->node = $disconnectedNode;
        $disconnectedPlace->type = PlaceType::INFRASTRUCTURE;
        $disconnectedPlace->routeDrawn = true;

        $edgeRepository = $this->createMock(EntityRepository::class);
        $edgeRepository->expects(self::once())->method('findBy')->with(['plan' => $plan, 'active' => true])->willReturn([$edge]);
        $placeRepository = $this->createMock(EntityRepository::class);
        $placeRepository->expects(self::once())->method('findBy')->with(['plan' => $plan, 'type' => PlaceType::INFRASTRUCTURE])->willReturn([$connectedPlace, $disconnectedPlace]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturnCallback(static fn (string $class): EntityRepository => match ($class) {
            MapEdge::class => $edgeRepository,
            MapPlace::class => $placeRepository,
        });

        $changed = (new RouteDrawnSynchronizer($em))->synchronizePlan($plan);

        self::assertSame(2, $changed);
        self::assertTrue($connectedPlace->routeDrawn);
        self::assertFalse($disconnectedPlace->routeDrawn);
    }

    private function setId(object $entity, int $id): void
    {
        $property = new \ReflectionProperty($entity, 'id');
        $property->setValue($entity, $id);
    }
}

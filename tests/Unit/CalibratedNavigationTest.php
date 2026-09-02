<?php

namespace App\Tests\Unit;

use App\ApiResource\NavigationRequest;
use App\Entity\MapEdge;
use App\Entity\MapNode;
use App\Entity\MapPlace;
use App\Entity\MapPlan;
use App\Entity\SiteSettings;
use App\Enum\GeoSource;
use App\Enum\PlaceType;
use App\Service\NavigationService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;

final class CalibratedNavigationTest extends TestCase
{
    public function testNavigationWorksAndDistanceDecreasesTowardsTarget(): void
    {
        $plan = new MapPlan(); $this->setId($plan, 5);
        $from = $this->node($plan, 1, 0, 0, 56.0, 37.0);
        $to = $this->node($plan, 2, 100, 0, 56.0, 37.01);
        $edge = new MapEdge(); $edge->plan = $plan; $edge->fromNode = $from; $edge->toNode = $to; $edge->bidirectional = true;
        $place = new MapPlace(); $this->setId($place, 10); $place->plan = $plan; $place->node = $to;
        $place->type = PlaceType::INFRASTRUCTURE; $place->routeDrawn = true;
        $settings = new SiteSettings(); $settings->maxGeoSnapDistanceMeters = 500;

        $placeRepository = $this->createMock(EntityRepository::class);
        $placeRepository->method('find')->with(10)->willReturn($place);
        $edgeRepository = $this->createMock(EntityRepository::class);
        $edgeRepository->method('findBy')->with(['plan' => $plan, 'active' => true])->willReturn([$edge]);
        $settingsRepository = $this->createMock(EntityRepository::class);
        $settingsRepository->method('findOneBy')->with(['code' => 'main'])->willReturn($settings);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturnCallback(static fn (string $class): EntityRepository => match ($class) {
            MapPlace::class => $placeRepository,
            MapEdge::class => $edgeRepository,
            SiteSettings::class => $settingsRepository,
        });
        $navigation = new NavigationService($em, 'https://example.test/map');

        $far = new NavigationRequest(); $far->destinationPlaceId = 10; $far->latitude = 56.0; $far->longitude = 37.0;
        $near = new NavigationRequest(); $near->destinationPlaceId = 10; $near->latitude = 56.0; $near->longitude = 37.008;
        $farRoute = $navigation->buildRoute($far);
        $nearRoute = $navigation->buildRoute($near);

        self::assertNotEmpty($farRoute->points);
        self::assertNotNull($farRoute->snappedPosition);
        self::assertLessThan($farRoute->distanceMeters, $nearRoute->distanceMeters);
    }

    private function node(MapPlan $plan, int $id, float $x, float $y, float $latitude, float $longitude): MapNode
    {
        $node = new MapNode(); $this->setId($node, $id); $node->plan = $plan; $node->x = $x; $node->y = $y;
        $node->latitude = $latitude; $node->longitude = $longitude; $node->geoSource = GeoSource::CALIBRATED; $node->geoCalibrationVersion = 1;
        return $node;
    }

    private function setId(object $entity, int $id): void
    {
        $property = new \ReflectionProperty($entity, 'id');
        $property->setValue($entity, $id);
    }
}

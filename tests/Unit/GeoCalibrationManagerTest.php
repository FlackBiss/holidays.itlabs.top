<?php

namespace App\Tests\Unit;

use App\Entity\MapGeoCalibration;
use App\Entity\MapNode;
use App\Entity\MapPlan;
use App\Enum\GeoSource;
use App\Service\GeoCalibrationEngine;
use App\Service\GeoCalibrationManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

final class GeoCalibrationManagerTest extends TestCase
{
    public function testSavingChangesIncrementsVersion(): void
    {
        [$manager, $em, $plan, $calibration] = $this->fixture();
        $em->expects(self::once())->method('wrapInTransaction')->willReturnCallback(static fn (callable $callback): mixed => $callback());
        $em->expects(self::once())->method('flush');

        $saved = $manager->save($plan, [
            'method' => 'piecewise_affine',
            'controlPoints' => [
                $this->point(0, 0, 56, 37, 1), $this->point(100, 0, 56, 37.01, 2),
                $this->point(100, 100, 55.99, 37.01, 3), $this->point(0, 100, 55.99, 37, 4),
            ],
        ]);

        self::assertSame($calibration, $saved);
        self::assertSame(3, $saved->version);
        self::assertCount(4, $saved->getControlPoints());
    }

    public function testApplyProtectsManualCoordinatesAndIsIdempotent(): void
    {
        [$manager, $em, $plan, $calibration, $nodes] = $this->fixture();
        $em->expects(self::exactly(2))->method('wrapInTransaction')->willReturnCallback(static fn (callable $callback): mixed => $callback());
        $em->expects(self::exactly(2))->method('flush');

        $first = $manager->apply($plan, $calibration->version);
        $second = $manager->apply($plan, $calibration->version);

        self::assertSame(1, $first['updatedNodeCount']);
        self::assertSame(1, $first['skippedManualCount']);
        self::assertSame(1, $first['unchangedNodeCount']);
        self::assertSame(0, $second['updatedNodeCount']);
        self::assertSame(2, $second['unchangedNodeCount']);
        self::assertSame(GeoSource::MANUAL, $nodes[0]->geoSource);
        self::assertSame(GeoSource::CALIBRATED, $nodes[2]->geoSource);
        self::assertSame(2, $nodes[2]->geoCalibrationVersion);
    }

    public function testRejectsStaleVersion(): void
    {
        [$manager, , $plan] = $this->fixture();
        $this->expectException(ConflictHttpException::class);
        $manager->apply($plan, 1);
    }

    public function testPreflightPreventsPartialUpdatesWhenAnyNodeIsOutside(): void
    {
        [$manager, $em, $plan, $calibration, $nodes] = $this->fixture(true);
        $before = [$nodes[2]->latitude, $nodes[2]->longitude, $nodes[2]->geoSource];
        $em->expects(self::never())->method('wrapInTransaction');
        $em->expects(self::never())->method('flush');

        try {
            $manager->apply($plan, $calibration->version);
            self::fail('Expected unsafe apply to fail.');
        } catch (UnprocessableEntityHttpException) {
            self::assertSame($before, [$nodes[2]->latitude, $nodes[2]->longitude, $nodes[2]->geoSource]);
        }
    }

    private function fixture(bool $outside = false): array
    {
        $plan = new MapPlan(); $plan->width = 100; $plan->height = 100; $this->setId($plan, 5);
        $calibration = new MapGeoCalibration(); $calibration->plan = $plan; $calibration->version = 2;
        $calibration->replaceControlPoints([
            $this->point(0, 0, 56, 37, 1), $this->point(100, 0, 56, 37.01, 2),
            $this->point(100, 100, 55.99, 37.01, 3), $this->point(0, 100, 55.99, 37, 4),
        ]);

        $manual = new MapNode(); $manual->plan = $plan; $manual->x = 25; $manual->y = 25;
        $manual->latitude = 1; $manual->longitude = 2; $manual->geoSource = GeoSource::MANUAL; $this->setId($manual, 1);
        $same = new MapNode(); $same->plan = $plan; $same->x = 0; $same->y = 0;
        $same->latitude = 56; $same->longitude = 37; $same->geoSource = GeoSource::CALIBRATED; $same->geoCalibrationVersion = 2; $this->setId($same, 2);
        $empty = new MapNode(); $empty->plan = $plan; $empty->x = $outside ? 101 : 50; $empty->y = 50; $this->setId($empty, 3);
        $nodes = [$manual, $same, $empty];

        $calibrationRepository = $this->createMock(EntityRepository::class);
        $calibrationRepository->method('findOneBy')->with(['plan' => $plan])->willReturn($calibration);
        $nodeRepository = $this->createMock(EntityRepository::class);
        $nodeRepository->method('findBy')->with(['plan' => $plan], ['id' => 'ASC'])->willReturn($nodes);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturnCallback(static fn (string $class): EntityRepository => match ($class) {
            MapGeoCalibration::class => $calibrationRepository,
            MapNode::class => $nodeRepository,
        });
        return [new GeoCalibrationManager($em, new GeoCalibrationEngine()), $em, $plan, $calibration, $nodes];
    }

    private function point(float $x, float $y, float $latitude, float $longitude, int $position): array
    {
        return compact('x', 'y', 'latitude', 'longitude', 'position');
    }

    private function setId(object $entity, int $id): void
    {
        $property = new \ReflectionProperty($entity, 'id');
        $property->setValue($entity, $id);
    }
}

<?php

namespace App\Tests\Unit;

use App\Entity\MapNode;
use App\Entity\MapPlan;
use App\Exception\GeoCalibrationValidationException;
use App\Service\GeoCalibrationEngine;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class GeoCalibrationEngineTest extends TestCase
{
    private GeoCalibrationEngine $engine;
    private MapPlan $plan;

    protected function setUp(): void
    {
        $this->engine = new GeoCalibrationEngine();
        $this->plan = new MapPlan();
        $this->plan->width = 1000;
        $this->plan->height = 1000;
    }

    #[DataProvider('invalidCalibrationProvider')]
    public function testRejectsInvalidCalibrations(array $points, string $message): void
    {
        $this->expectException(GeoCalibrationValidationException::class);
        $this->expectExceptionMessage($message);
        $this->engine->validateAndNormalize($this->plan, ['method' => 'piecewise_affine', 'controlPoints' => $points]);
    }

    public static function invalidCalibrationProvider(): iterable
    {
        yield 'too few' => [[
            self::point(0, 0, 56, 37, 1), self::point(100, 0, 56, 38, 2), self::point(0, 100, 55, 37, 3),
        ], 'от 4 до 30'];
        yield 'out of plan and GPS ranges' => [[
            self::point(-1, 0, 56, 37, 1), self::point(100, 0, 91, 38, 2),
            self::point(0, 100, 55, 181, 3), self::point(100, 100, 55, 38, 4),
        ], 'внутри схемы'];
        yield 'duplicate xy' => [[
            self::point(0, 0, 56, 37, 1), self::point(100, 0, 56, 38, 2),
            self::point(0, 100, 55, 37, 3), self::point(0, 100, 55, 38, 4),
        ], 'одинаковые x/y'];
        yield 'collinear' => [[
            self::point(0, 0, 56, 37, 1), self::point(100, 100, 56.1, 37.1, 2),
            self::point(200, 200, 56.2, 37.2, 3), self::point(300, 300, 56.3, 37.3, 4),
        ], 'одной линии'];
    }

    public function testInterpolatesKnownCoordinatesAndDoesNotExtrapolate(): void
    {
        $points = $this->normalizedSquare();
        $center = $this->engine->calculate($points, 500, 500);

        self::assertNotNull($center);
        self::assertEqualsWithDelta(55.995, $center['latitude'], 1.0E-9);
        self::assertEqualsWithDelta(37.01, $center['longitude'], 1.0E-9);
        self::assertNull($this->engine->calculate($points, 1001, 500));
    }

    public function testPreviewDoesNotMutateNodesAndMarksOutsideHull(): void
    {
        $inside = new MapNode();
        $inside->x = 500; $inside->y = 500;
        $outside = new MapNode();
        $outside->x = 1200; $outside->y = 500;

        $preview = $this->engine->preview($this->normalizedSquare(), [$inside, $outside]);

        self::assertFalse($preview['canApply']);
        self::assertSame(1, $preview['calculableNodeCount']);
        self::assertSame(1, $preview['uncoveredNodeCount']);
        self::assertSame('calculated', $preview['nodes'][0]['status']);
        self::assertEqualsWithDelta(55.995, $preview['nodes'][0]['calculatedLatitude'], 1.0E-9);
        self::assertEqualsWithDelta(37.01, $preview['nodes'][0]['calculatedLongitude'], 1.0E-9);
        self::assertSame('outside_control_hull', $preview['nodes'][1]['status']);
        self::assertNull($inside->latitude);
        self::assertNull($inside->longitude);
        self::assertArrayHasKey('medianErrorMeters', $preview['metrics']);
    }

    public function testAcceptsUpToThirtyControlPoints(): void
    {
        $points = [];
        for ($index = 0; $index < 30; ++$index) {
            $angle = 2 * M_PI * $index / 30;
            $points[] = self::point(
                500 + 400 * cos($angle),
                500 + 400 * sin($angle),
                56 + 0.01 * sin($angle),
                37 + 0.01 * cos($angle),
                $index + 1,
            );
        }

        $normalized = $this->engine->validateAndNormalize($this->plan, [
            'method' => 'piecewise_affine',
            'controlPoints' => $points,
        ]);

        self::assertCount(30, $normalized['controlPoints']);
    }

    public function testApplicablePreviewIncludesCalculatedCoordinates(): void
    {
        $node = new MapNode();
        $node->x = 500;
        $node->y = 500;

        $preview = $this->engine->preview($this->normalizedSquare(), [$node]);

        self::assertTrue($preview['canApply']);
        self::assertSame('calculated', $preview['nodes'][0]['status']);
        self::assertIsFloat($preview['nodes'][0]['calculatedLatitude']);
        self::assertIsFloat($preview['nodes'][0]['calculatedLongitude']);
    }

    public function testRejectsMoreThanThirtyControlPoints(): void
    {
        $points = [];
        for ($index = 0; $index < 31; ++$index) {
            $points[] = self::point($index * 10, ($index % 2) * 10, 56, 37, $index + 1);
        }

        $this->expectException(GeoCalibrationValidationException::class);
        $this->expectExceptionMessage('от 4 до 30');
        $this->engine->validateAndNormalize($this->plan, [
            'method' => 'piecewise_affine',
            'controlPoints' => $points,
        ]);
    }

    private function normalizedSquare(): array
    {
        return $this->engine->validateAndNormalize($this->plan, [
            'method' => 'piecewise_affine',
            'controlPoints' => [
                self::point(0, 0, 56.0, 37.0, 1),
                self::point(1000, 0, 56.0, 37.02, 2),
                self::point(1000, 1000, 55.99, 37.02, 3),
                self::point(0, 1000, 55.99, 37.0, 4),
            ],
        ])['controlPoints'];
    }

    private static function point(float $x, float $y, float $latitude, float $longitude, int $position): array
    {
        return compact('x', 'y', 'latitude', 'longitude', 'position');
    }
}

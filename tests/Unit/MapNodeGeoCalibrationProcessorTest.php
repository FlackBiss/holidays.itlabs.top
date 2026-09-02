<?php

namespace App\Tests\Unit;

use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\ApiResource\MapNodeInput;
use App\Entity\MapGeoCalibration;
use App\Entity\MapNode;
use App\Entity\MapPlan;
use App\Enum\GeoSource;
use App\Service\GeoCalibrationEngine;
use App\Service\GeoCalibrationManager;
use App\State\MapNodeProcessor;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AttributeLoader;
use Symfony\Component\Serializer\NameConverter\MetadataAwareNameConverter;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

final class MapNodeGeoCalibrationProcessorTest extends TestCase
{
    public function testGeoProvenanceIsSerializedAtTopLevel(): void
    {
        $node = new MapNode();
        $node->geoSource = GeoSource::CALIBRATED;
        $node->geoCalibrationVersion = 4;
        $metadata = new ClassMetadataFactory(new AttributeLoader());
        $serializer = new Serializer([new ObjectNormalizer($metadata, new MetadataAwareNameConverter($metadata))]);

        $data = $serializer->normalize($node, context: ['groups' => ['map:read']]);

        self::assertSame('calibrated', $data['geoSource']);
        self::assertSame(4, $data['geoCalibrationVersion']);
    }

    public function testAutomaticallyCalculatesOnCreateAndMove(): void
    {
        [$processor, $em, $stack, $plan, $node] = $this->fixture();
        $em->expects(self::exactly(2))->method('persist');
        $em->expects(self::exactly(2))->method('flush');

        $stack->push(Request::create('/api/nodes', 'POST', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode(['planId' => 5, 'x' => 50, 'y' => 50])));
        $create = new MapNodeInput(); $create->planId = 5; $create->x = 50; $create->y = 50;
        $created = $processor->process($create, new Post());
        self::assertSame(GeoSource::CALIBRATED, $created->geoSource);
        self::assertSame(3, $created->geoCalibrationVersion);
        self::assertEqualsWithDelta(55.995, $created->latitude, 1.0E-9);

        $stack->pop();
        $stack->push(Request::create('/api/nodes/10', 'PATCH', server: ['CONTENT_TYPE' => 'application/merge-patch+json'], content: json_encode(['x' => 75])));
        $move = new MapNodeInput(); $move->x = 75;
        $moved = $processor->process($move, new Patch(), ['id' => 10]);
        self::assertSame($node, $moved);
        self::assertSame(GeoSource::CALIBRATED, $moved->geoSource);
        self::assertNotNull($moved->longitude);
    }

    public function testExplicitCoordinatesHavePriorityAndAreManual(): void
    {
        [$processor, $em, $stack] = $this->fixture();
        $em->expects(self::once())->method('persist');
        $em->expects(self::once())->method('flush');
        $stack->push(Request::create('/api/nodes', 'POST', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode([
            'planId' => 5, 'x' => 50, 'y' => 50, 'latitude' => 56.1, 'longitude' => 37.1,
        ])));
        $input = new MapNodeInput(); $input->planId = 5; $input->x = 50; $input->y = 50; $input->latitude = 56.1; $input->longitude = 37.1;

        $node = $processor->process($input, new Post());

        self::assertSame(GeoSource::MANUAL, $node->geoSource);
        self::assertNull($node->geoCalibrationVersion);
        self::assertSame(56.1, $node->latitude);
        self::assertSame(37.1, $node->longitude);
    }

    public function testRejectsPartialCoordinates(): void
    {
        [$processor, , $stack] = $this->fixture();
        $stack->push(Request::create('/api/nodes', 'POST', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode([
            'planId' => 5, 'x' => 50, 'y' => 50, 'latitude' => 56.1,
        ])));
        $input = new MapNodeInput(); $input->planId = 5; $input->x = 50; $input->y = 50; $input->latitude = 56.1;

        $this->expectException(BadRequestHttpException::class);
        $processor->process($input, new Post());
    }

    public function testRejectsAutomaticallyCalculatedPointOutsideHull(): void
    {
        [$processor, $em, $stack] = $this->fixture();
        $em->expects(self::never())->method('persist');
        $em->expects(self::never())->method('flush');
        $stack->push(Request::create('/api/nodes', 'POST', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode([
            'planId' => 5, 'x' => 101, 'y' => 50,
        ])));
        $input = new MapNodeInput(); $input->planId = 5; $input->x = 101; $input->y = 50;

        $this->expectException(UnprocessableEntityHttpException::class);
        $processor->process($input, new Post());
    }

    private function fixture(): array
    {
        $plan = new MapPlan(); $plan->width = 100; $plan->height = 100; $this->setId($plan, 5);
        $calibration = new MapGeoCalibration(); $calibration->plan = $plan; $calibration->version = 3;
        $calibration->replaceControlPoints([
            $this->point(0, 0, 56, 37, 1), $this->point(100, 0, 56, 37.01, 2),
            $this->point(100, 100, 55.99, 37.01, 3), $this->point(0, 100, 55.99, 37, 4),
        ]);
        $node = new MapNode(); $node->plan = $plan; $node->x = 50; $node->y = 50; $this->setId($node, 10);

        $planRepository = $this->createMock(EntityRepository::class);
        $planRepository->method('find')->with(5)->willReturn($plan);
        $nodeRepository = $this->createMock(EntityRepository::class);
        $nodeRepository->method('find')->with(10)->willReturn($node);
        $calibrationRepository = $this->createMock(EntityRepository::class);
        $calibrationRepository->method('findOneBy')->with(['plan' => $plan])->willReturn($calibration);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturnCallback(static fn (string $class): EntityRepository => match ($class) {
            MapPlan::class => $planRepository,
            MapNode::class => $nodeRepository,
            MapGeoCalibration::class => $calibrationRepository,
        });
        $manager = new GeoCalibrationManager($em, new GeoCalibrationEngine());
        $stack = new RequestStack();
        return [new MapNodeProcessor($em, $manager, $stack), $em, $stack, $plan, $node];
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

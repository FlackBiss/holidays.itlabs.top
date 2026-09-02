<?php

namespace App\Service;

use App\Entity\MapGeoCalibration;
use App\Entity\MapNode;
use App\Entity\MapPlan;
use App\Enum\GeoSource;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

final readonly class GeoCalibrationManager
{
    public function __construct(
        private EntityManagerInterface $em,
        private GeoCalibrationEngine $engine,
    ) {
    }

    public function find(MapPlan $plan): ?MapGeoCalibration
    {
        return $this->em->getRepository(MapGeoCalibration::class)->findOneBy(['plan' => $plan]);
    }

    /** @param array<string, mixed> $input @return array<string, mixed> */
    public function preview(MapPlan $plan, array $input): array
    {
        $normalized = $this->engine->validateAndNormalize($plan, $input);
        $nodes = $this->em->getRepository(MapNode::class)->findBy(['plan' => $plan], ['id' => 'ASC']);
        return $this->engine->preview($normalized['controlPoints'], $nodes);
    }

    /** @param array<string, mixed> $input */
    public function save(MapPlan $plan, array $input): MapGeoCalibration
    {
        $normalized = $this->engine->validateAndNormalize($plan, $input);
        return $this->em->wrapInTransaction(function () use ($plan, $normalized): MapGeoCalibration {
            $calibration = $this->find($plan);
            if (!$calibration) {
                $calibration = new MapGeoCalibration();
                $calibration->plan = $plan;
                $plan->geoCalibration = $calibration;
                $this->em->persist($calibration);
            } else {
                ++$calibration->version;
            }
            $calibration->method = $normalized['method'];
            $calibration->updatedAt = new \DateTimeImmutable();
            $calibration->replaceControlPoints($normalized['controlPoints']);
            $this->em->flush();
            return $calibration;
        });
    }

    /**
     * @return array{version: int, totalNodeCount: int, updatedNodeCount: int, skippedManualCount: int, unchangedNodeCount: int}
     */
    public function apply(MapPlan $plan, int $version, bool $overwriteCalibrated = true, bool $overwriteManual = false): array
    {
        $calibration = $this->find($plan);
        if (!$calibration) throw new NotFoundHttpException('Геокалибровка карты не найдена.');
        if ($version !== $calibration->version) {
            throw new ConflictHttpException(sprintf('Версия калибровки устарела: передана %d, текущая %d.', $version, $calibration->version));
        }
        $points = $this->engine->pointsFromCalibration($calibration);
        $nodes = $this->em->getRepository(MapNode::class)->findBy(['plan' => $plan], ['id' => 'ASC']);
        $updates = [];
        $skippedManual = 0;
        $unchanged = 0;
        $outsideIds = [];
        foreach ($nodes as $node) {
            if ($node->geoSource === GeoSource::MANUAL && !$overwriteManual) {
                ++$skippedManual;
                continue;
            }
            if ($node->geoSource === GeoSource::CALIBRATED && !$overwriteCalibrated) {
                ++$unchanged;
                continue;
            }
            $calculated = $this->engine->calculate($points, $node->x, $node->y);
            if ($calculated === null) {
                $outsideIds[] = $node->getId();
                continue;
            }
            $same = $node->geoSource === GeoSource::CALIBRATED
                && $node->geoCalibrationVersion === $calibration->version
                && $node->latitude !== null && abs($node->latitude - $calculated['latitude']) < 1.0E-12
                && $node->longitude !== null && abs($node->longitude - $calculated['longitude']) < 1.0E-12;
            if ($same) {
                ++$unchanged;
                continue;
            }
            $updates[] = [$node, $calculated];
        }
        if ($outsideIds !== []) {
            throw new UnprocessableEntityHttpException(sprintf(
                'Нельзя применить калибровку: узлы вне области контрольных точек: %s.',
                implode(', ', array_slice($outsideIds, 0, 20)),
            ));
        }

        $this->em->wrapInTransaction(function () use ($updates, $calibration): void {
            foreach ($updates as [$node, $calculated]) {
                $node->latitude = $calculated['latitude'];
                $node->longitude = $calculated['longitude'];
                $node->geoSource = GeoSource::CALIBRATED;
                $node->geoCalibrationVersion = $calibration->version;
            }
            $this->em->flush();
        });

        return [
            'version' => $calibration->version,
            'totalNodeCount' => count($nodes),
            'updatedNodeCount' => count($updates),
            'skippedManualCount' => $skippedManual,
            'unchangedNodeCount' => $unchanged,
        ];
    }

    public function delete(MapPlan $plan): void
    {
        $calibration = $this->find($plan);
        if (!$calibration) return;
        $this->em->wrapInTransaction(function () use ($calibration, $plan): void {
            $plan->geoCalibration = null;
            $this->em->remove($calibration);
            $this->em->flush();
        });
    }

    /** @return array{latitude: float, longitude: float, version: int}|null */
    public function calculateForPlan(MapPlan $plan, float $x, float $y): ?array
    {
        $calibration = $this->find($plan);
        if (!$calibration) return null;
        $calculated = $this->engine->calculate($this->engine->pointsFromCalibration($calibration), $x, $y);
        if ($calculated === null) {
            throw new UnprocessableEntityHttpException('Точка находится вне области геокалибровки карты. Добавьте контрольные точки по краям территории.');
        }
        return $calculated + ['version' => $calibration->version];
    }

    /** @return array<string, mixed> */
    public function normalize(MapGeoCalibration $calibration): array
    {
        return [
            'id' => $calibration->getId(),
            'planId' => $calibration->plan?->getId(),
            'method' => $calibration->method,
            'version' => $calibration->version,
            'controlPoints' => array_map(static fn ($point): array => [
                'id' => $point->getId(),
                'x' => $point->x,
                'y' => $point->y,
                'latitude' => $point->latitude,
                'longitude' => $point->longitude,
                'position' => $point->position,
            ], $calibration->getControlPoints()->toArray()),
            'createdAt' => $calibration->createdAt->format(DATE_ATOM),
            'updatedAt' => $calibration->updatedAt->format(DATE_ATOM),
        ];
    }
}

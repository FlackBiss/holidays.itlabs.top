<?php

namespace App\Service;

use App\Entity\MapGeoCalibration;
use App\Entity\MapGeoControlPoint;
use App\Entity\MapNode;
use App\Entity\MapPlan;
use App\Enum\GeoSource;
use App\Exception\GeoCalibrationValidationException;

final class GeoCalibrationEngine
{
    private const float EPSILON = 1.0E-9;

    /**
     * @param array<string, mixed> $input
     * @return array{method: string, controlPoints: list<array{x: float, y: float, latitude: float, longitude: float, position: int}>}
     */
    public function validateAndNormalize(MapPlan $plan, array $input): array
    {
        $errors = [];
        $method = $input['method'] ?? MapGeoCalibration::METHOD_PIECEWISE_AFFINE;
        if ($method !== MapGeoCalibration::METHOD_PIECEWISE_AFFINE) {
            $errors[] = 'Поддерживается только метод piecewise_affine.';
        }

        $rawPoints = $input['controlPoints'] ?? null;
        if (!is_array($rawPoints)) {
            throw new GeoCalibrationValidationException(['controlPoints должен быть массивом.']);
        }
        if (count($rawPoints) < 4 || count($rawPoints) > 15) {
            $errors[] = 'Количество контрольных точек должно быть от 4 до 15.';
        }

        $points = [];
        $xyKeys = [];
        $positions = [];
        foreach (array_values($rawPoints) as $index => $rawPoint) {
            if (!is_array($rawPoint)) {
                $errors[] = sprintf('Контрольная точка %d должна быть объектом.', $index + 1);
                continue;
            }
            $values = [];
            foreach (['x', 'y', 'latitude', 'longitude'] as $field) {
                $value = $rawPoint[$field] ?? null;
                if (!is_int($value) && !is_float($value) && !(is_string($value) && is_numeric($value))) {
                    $errors[] = sprintf('Поле %s контрольной точки %d должно быть числом.', $field, $index + 1);
                    $values[$field] = NAN;
                    continue;
                }
                $values[$field] = (float) $value;
                if (!is_finite($values[$field])) {
                    $errors[] = sprintf('Поле %s контрольной точки %d содержит некорректное число.', $field, $index + 1);
                }
            }
            $position = $rawPoint['position'] ?? ($index + 1);
            if (!is_int($position) && !(is_string($position) && ctype_digit($position))) {
                $errors[] = sprintf('position контрольной точки %d должен быть целым числом.', $index + 1);
                $position = $index + 1;
            }
            $position = (int) $position;
            if ($position < 1 || isset($positions[$position])) {
                $errors[] = sprintf('position контрольной точки %d должен быть положительным и уникальным.', $index + 1);
            }
            $positions[$position] = true;

            if (isset($values['x'], $values['y'])) {
                if ($values['x'] < 0 || $values['x'] > $plan->width || $values['y'] < 0 || $values['y'] > $plan->height) {
                    $errors[] = sprintf('x/y контрольной точки %d должны находиться внутри схемы %dx%d.', $index + 1, $plan->width, $plan->height);
                }
                $xyKey = sprintf('%.12F|%.12F', $values['x'], $values['y']);
                if (isset($xyKeys[$xyKey])) {
                    $errors[] = sprintf('Контрольные точки %d и %d имеют одинаковые x/y.', $xyKeys[$xyKey], $index + 1);
                }
                $xyKeys[$xyKey] = $index + 1;
            }
            if (isset($values['latitude']) && ($values['latitude'] < -90 || $values['latitude'] > 90)) {
                $errors[] = sprintf('latitude контрольной точки %d должна находиться в диапазоне -90..90.', $index + 1);
            }
            if (isset($values['longitude']) && ($values['longitude'] < -180 || $values['longitude'] > 180)) {
                $errors[] = sprintf('longitude контрольной точки %d должна находиться в диапазоне -180..180.', $index + 1);
            }
            $points[] = [
                'x' => $values['x'] ?? NAN,
                'y' => $values['y'] ?? NAN,
                'latitude' => $values['latitude'] ?? NAN,
                'longitude' => $values['longitude'] ?? NAN,
                'position' => $position,
            ];
        }

        if (count($points) >= 3 && !$this->hasNonCollinearTriple($points, max(1.0, $plan->width * $plan->height))) {
            $errors[] = 'Контрольные точки не должны лежать на одной линии.';
        }
        if ($errors !== []) {
            throw new GeoCalibrationValidationException(array_values(array_unique($errors)));
        }

        usort($points, static fn (array $a, array $b): int => $a['position'] <=> $b['position']);
        return ['method' => $method, 'controlPoints' => $points];
    }

    /** @return list<array{x: float, y: float, latitude: float, longitude: float, position: int}> */
    public function pointsFromCalibration(MapGeoCalibration $calibration): array
    {
        return array_map(static fn (MapGeoControlPoint $point): array => [
            'x' => $point->x,
            'y' => $point->y,
            'latitude' => $point->latitude,
            'longitude' => $point->longitude,
            'position' => $point->position,
        ], $calibration->getControlPoints()->toArray());
    }

    /**
     * @param list<array{x: float, y: float, latitude: float, longitude: float, position: int}> $points
     * @return array{latitude: float, longitude: float}|null
     */
    public function calculate(array $points, float $x, float $y): ?array
    {
        if (!is_finite($x) || !is_finite($y)) return null;
        foreach ($points as $point) {
            if (abs($point['x'] - $x) <= self::EPSILON && abs($point['y'] - $y) <= self::EPSILON) {
                return ['latitude' => $point['latitude'], 'longitude' => $point['longitude']];
            }
        }
        foreach ($this->triangulate($points) as $triangle) {
            $weights = $this->barycentric($points[$triangle[0]], $points[$triangle[1]], $points[$triangle[2]], $x, $y);
            if ($weights === null) continue;
            [$wa, $wb, $wc] = $weights;
            $latitude = $wa * $points[$triangle[0]]['latitude'] + $wb * $points[$triangle[1]]['latitude'] + $wc * $points[$triangle[2]]['latitude'];
            $longitude = $wa * $points[$triangle[0]]['longitude'] + $wb * $points[$triangle[1]]['longitude'] + $wc * $points[$triangle[2]]['longitude'];
            if (!is_finite($latitude) || !is_finite($longitude) || $latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) return null;
            return ['latitude' => $latitude, 'longitude' => $longitude];
        }
        return null;
    }

    /**
     * @param list<array{x: float, y: float, latitude: float, longitude: float, position: int}> $points
     * @param list<MapNode> $nodes
     * @return array<string, mixed>
     */
    public function preview(array $points, array $nodes): array
    {
        $items = [];
        $calculable = 0;
        $uncovered = 0;
        $invalid = 0;
        foreach ($nodes as $node) {
            $base = [
                'id' => $node->getId(),
                'x' => $node->x,
                'y' => $node->y,
                'currentLatitude' => $node->latitude,
                'currentLongitude' => $node->longitude,
                'calculatedLatitude' => null,
                'calculatedLongitude' => null,
            ];
            if ($node->geoSource === GeoSource::MANUAL) {
                $items[] = $base + ['status' => 'skipped_manual'];
                continue;
            }
            $calculated = $this->calculate($points, $node->x, $node->y);
            if ($calculated === null) {
                ++$uncovered;
                $items[] = $base + ['status' => 'outside_control_hull'];
                continue;
            }
            if (!is_finite($calculated['latitude']) || !is_finite($calculated['longitude'])) {
                ++$invalid;
                $items[] = $base + ['status' => 'invalid_coordinates'];
                continue;
            }
            ++$calculable;
            $items[] = $base + [
                'calculatedLatitude' => $calculated['latitude'],
                'calculatedLongitude' => $calculated['longitude'],
                'status' => 'calculated',
            ];
        }

        [$metrics, $warnings] = $this->leaveOneOutMetrics($points);
        if ($uncovered > 0) $warnings[] = sprintf('%d узлов находятся вне выпуклой области контрольных точек.', $uncovered);
        if ($invalid > 0) $warnings[] = sprintf('%d узлов получили некорректные координаты.', $invalid);

        return [
            'method' => MapGeoCalibration::METHOD_PIECEWISE_AFFINE,
            'controlPointCount' => count($points),
            'totalNodeCount' => count($nodes),
            'calculableNodeCount' => $calculable,
            'uncoveredNodeCount' => $uncovered,
            'canApply' => $uncovered === 0 && $invalid === 0,
            'metrics' => $metrics,
            'warnings' => $warnings,
            'nodes' => $items,
        ];
    }

    /** @param list<array{x: float, y: float, latitude: float, longitude: float, position: int}> $points */
    private function hasNonCollinearTriple(array $points, float $scale): bool
    {
        $count = count($points);
        for ($i = 0; $i < $count - 2; ++$i) {
            for ($j = $i + 1; $j < $count - 1; ++$j) {
                for ($k = $j + 1; $k < $count; ++$k) {
                    $area2 = ($points[$j]['x'] - $points[$i]['x']) * ($points[$k]['y'] - $points[$i]['y'])
                        - ($points[$j]['y'] - $points[$i]['y']) * ($points[$k]['x'] - $points[$i]['x']);
                    if (abs($area2) > self::EPSILON * $scale) return true;
                }
            }
        }
        return false;
    }

    /**
     * Bowyer-Watson Delaunay triangulation.
     * @param list<array{x: float, y: float, latitude: float, longitude: float, position: int}> $source
     * @return list<array{int, int, int}>
     */
    private function triangulate(array $source): array
    {
        $count = count($source);
        if ($count < 3) return [];
        $points = $source;
        $xs = array_column($source, 'x');
        $ys = array_column($source, 'y');
        $minX = min($xs); $maxX = max($xs); $minY = min($ys); $maxY = max($ys);
        $delta = max($maxX - $minX, $maxY - $minY, 1.0);
        $midX = ($minX + $maxX) / 2; $midY = ($minY + $maxY) / 2;
        $points[] = ['x' => $midX - 20 * $delta, 'y' => $midY - $delta, 'latitude' => 0.0, 'longitude' => 0.0, 'position' => 0];
        $points[] = ['x' => $midX, 'y' => $midY + 20 * $delta, 'latitude' => 0.0, 'longitude' => 0.0, 'position' => 0];
        $points[] = ['x' => $midX + 20 * $delta, 'y' => $midY - $delta, 'latitude' => 0.0, 'longitude' => 0.0, 'position' => 0];
        $triangles = [[$count, $count + 1, $count + 2]];

        for ($pointIndex = 0; $pointIndex < $count; ++$pointIndex) {
            $bad = [];
            foreach ($triangles as $triangleIndex => $triangle) {
                if ($this->circumcircleContains($points, $triangle, $points[$pointIndex])) $bad[$triangleIndex] = $triangle;
            }
            $edges = [];
            foreach ($bad as $triangle) {
                foreach ([[$triangle[0], $triangle[1]], [$triangle[1], $triangle[2]], [$triangle[2], $triangle[0]]] as $edge) {
                    sort($edge);
                    $key = $edge[0].':'.$edge[1];
                    $edges[$key] = isset($edges[$key]) ? null : $edge;
                }
            }
            $triangles = array_values(array_diff_key($triangles, $bad));
            foreach ($edges as $edge) {
                if ($edge !== null) $triangles[] = [$edge[0], $edge[1], $pointIndex];
            }
        }

        return array_values(array_filter($triangles, static fn (array $triangle): bool => max($triangle) < $count));
    }

    /** @param list<array{x: float, y: float}> $points @param array{int, int, int} $triangle @param array{x: float, y: float} $point */
    private function circumcircleContains(array $points, array $triangle, array $point): bool
    {
        $a = $points[$triangle[0]]; $b = $points[$triangle[1]]; $c = $points[$triangle[2]];
        $denominator = 2 * ($a['x'] * ($b['y'] - $c['y']) + $b['x'] * ($c['y'] - $a['y']) + $c['x'] * ($a['y'] - $b['y']));
        if (abs($denominator) <= self::EPSILON) return false;
        $a2 = $a['x'] ** 2 + $a['y'] ** 2; $b2 = $b['x'] ** 2 + $b['y'] ** 2; $c2 = $c['x'] ** 2 + $c['y'] ** 2;
        $ux = ($a2 * ($b['y'] - $c['y']) + $b2 * ($c['y'] - $a['y']) + $c2 * ($a['y'] - $b['y'])) / $denominator;
        $uy = ($a2 * ($c['x'] - $b['x']) + $b2 * ($a['x'] - $c['x']) + $c2 * ($b['x'] - $a['x'])) / $denominator;
        $radius2 = ($ux - $a['x']) ** 2 + ($uy - $a['y']) ** 2;
        $distance2 = ($ux - $point['x']) ** 2 + ($uy - $point['y']) ** 2;
        return $distance2 <= $radius2 + self::EPSILON * max(1.0, $radius2);
    }

    /** @return array{float, float, float}|null */
    private function barycentric(array $a, array $b, array $c, float $x, float $y): ?array
    {
        $denominator = ($b['y'] - $c['y']) * ($a['x'] - $c['x']) + ($c['x'] - $b['x']) * ($a['y'] - $c['y']);
        if (abs($denominator) <= self::EPSILON) return null;
        $wa = (($b['y'] - $c['y']) * ($x - $c['x']) + ($c['x'] - $b['x']) * ($y - $c['y'])) / $denominator;
        $wb = (($c['y'] - $a['y']) * ($x - $c['x']) + ($a['x'] - $c['x']) * ($y - $c['y'])) / $denominator;
        $wc = 1.0 - $wa - $wb;
        $epsilon = 1.0E-8;
        return $wa >= -$epsilon && $wb >= -$epsilon && $wc >= -$epsilon ? [$wa, $wb, $wc] : null;
    }

    /**
     * @param list<array{x: float, y: float, latitude: float, longitude: float, position: int}> $points
     * @return array{array{medianErrorMeters: float|null, p95ErrorMeters: float|null, maximumErrorMeters: float|null}, list<string>}
     */
    private function leaveOneOutMetrics(array $points): array
    {
        $errors = [];
        $notCalculable = 0;
        foreach ($points as $index => $point) {
            $remaining = $points;
            array_splice($remaining, $index, 1);
            $calculated = $this->calculate($remaining, $point['x'], $point['y']);
            if ($calculated === null) {
                ++$notCalculable;
                continue;
            }
            $errors[] = $this->haversine($point['latitude'], $point['longitude'], $calculated['latitude'], $calculated['longitude']);
        }
        sort($errors, SORT_NUMERIC);
        $metrics = [
            'medianErrorMeters' => $errors === [] ? null : round($this->percentile($errors, 0.5), 1),
            'p95ErrorMeters' => $errors === [] ? null : round($this->percentile($errors, 0.95), 1),
            'maximumErrorMeters' => $errors === [] ? null : round(max($errors), 1),
        ];
        $warnings = [];
        if ($notCalculable > 0) {
            $warnings[] = sprintf('Для %d граничных контрольных точек leave-one-out невозможен без экстраполяции.', $notCalculable);
        }
        return [$metrics, $warnings];
    }

    /** @param list<float> $sorted */
    private function percentile(array $sorted, float $percentile): float
    {
        if (count($sorted) === 1) return $sorted[0];
        $index = $percentile * (count($sorted) - 1);
        $lower = (int) floor($index); $upper = (int) ceil($index);
        if ($lower === $upper) return $sorted[$lower];
        return $sorted[$lower] + ($sorted[$upper] - $sorted[$lower]) * ($index - $lower);
    }

    private function haversine(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000.0;
        $phi1 = deg2rad($lat1); $phi2 = deg2rad($lat2);
        $deltaPhi = deg2rad($lat2 - $lat1); $deltaLambda = deg2rad($lon2 - $lon1);
        $h = sin($deltaPhi / 2) ** 2 + cos($phi1) * cos($phi2) * sin($deltaLambda / 2) ** 2;
        return 2 * $earthRadius * asin(min(1.0, sqrt($h)));
    }
}

<?php

namespace App\Controller;

use App\Entity\MapPlan;
use App\Exception\GeoCalibrationValidationException;
use App\Service\GeoCalibrationManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/map_plans/{planId}/geo_calibration', requirements: ['planId' => '\\d+'])]
final readonly class MapGeoCalibrationController
{
    public function __construct(
        private EntityManagerInterface $em,
        private GeoCalibrationManager $manager,
    ) {
    }

    #[Route('', name: 'api_map_geo_calibration_get', methods: ['GET'])]
    public function get(int $planId): JsonResponse
    {
        $plan = $this->plan($planId);
        $calibration = $this->manager->find($plan);
        return new JsonResponse(['calibration' => $calibration ? $this->manager->normalize($calibration) : null]);
    }

    #[Route('/preview', name: 'api_map_geo_calibration_preview', methods: ['POST'])]
    public function preview(int $planId, Request $request): JsonResponse
    {
        try {
            return new JsonResponse($this->manager->preview($this->plan($planId), $this->payload($request)));
        } catch (GeoCalibrationValidationException $exception) {
            return $this->validationError($exception);
        }
    }

    #[Route('', name: 'api_map_geo_calibration_put', methods: ['PUT'])]
    public function put(int $planId, Request $request): JsonResponse
    {
        try {
            $calibration = $this->manager->save($this->plan($planId), $this->payload($request));
            return new JsonResponse($this->manager->normalize($calibration));
        } catch (GeoCalibrationValidationException $exception) {
            return $this->validationError($exception);
        }
    }

    #[Route('/apply', name: 'api_map_geo_calibration_apply', methods: ['POST'])]
    public function apply(int $planId, Request $request): JsonResponse
    {
        $payload = $this->payload($request);
        if (!isset($payload['version']) || !is_int($payload['version'])) {
            throw new BadRequestHttpException('version обязателен и должен быть целым числом.');
        }
        try {
            return new JsonResponse($this->manager->apply(
                $this->plan($planId),
                $payload['version'],
                $this->booleanOption($payload, 'overwriteCalibrated', true),
                $this->booleanOption($payload, 'overwriteManual', false),
            ));
        } catch (ConflictHttpException $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], Response::HTTP_CONFLICT);
        } catch (UnprocessableEntityHttpException $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[Route('', name: 'api_map_geo_calibration_delete', methods: ['DELETE'])]
    public function delete(int $planId): Response
    {
        $this->manager->delete($this->plan($planId));
        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    private function plan(int $id): MapPlan
    {
        $plan = $this->em->getRepository(MapPlan::class)->find($id);
        if (!$plan instanceof MapPlan) throw new NotFoundHttpException('Карта не найдена.');
        return $plan;
    }

    /** @return array<string, mixed> */
    private function payload(Request $request): array
    {
        try {
            $payload = $request->toArray();
        } catch (\Throwable $exception) {
            throw new BadRequestHttpException('Тело запроса должно содержать корректный JSON-объект.', $exception);
        }
        return $payload;
    }

    /** @param array<string, mixed> $payload */
    private function booleanOption(array $payload, string $name, bool $default): bool
    {
        if (!array_key_exists($name, $payload)) return $default;
        if (!is_bool($payload[$name])) throw new BadRequestHttpException($name.' должен быть boolean.');
        return $payload[$name];
    }

    private function validationError(GeoCalibrationValidationException $exception): JsonResponse
    {
        return new JsonResponse([
            'error' => 'Некорректная геокалибровка.',
            'violations' => $exception->getErrors(),
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}

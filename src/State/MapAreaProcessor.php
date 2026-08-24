<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\MapAreaInput;
use App\Entity\MapArea;
use App\Entity\MapPlan;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** @implements ProcessorInterface<MapAreaInput, MapArea> */
final readonly class MapAreaProcessor implements ProcessorInterface
{
    public function __construct(private EntityManagerInterface $em) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): MapArea
    {
        if (!$data instanceof MapAreaInput) throw new BadRequestHttpException('Неверные данные области.');
        $id = isset($uriVariables['id']) ? (int) $uriVariables['id'] : null;
        $area = $id ? $this->em->getRepository(MapArea::class)->find($id) : new MapArea();
        if (!$area) throw new NotFoundHttpException('Область не найдена.');
        $planId = $data->planId ?? $data->floor;
        if ($planId !== null) {
            $plan = $this->em->getRepository(MapPlan::class)->find($planId);
            if (!$plan) throw new NotFoundHttpException('Карта не найдена.');
            $area->plan = $plan;
        }
        if (!$id && !$area->plan) throw new BadRequestHttpException('planId обязателен.');
        if ($data->title !== null) $area->title = $data->title;
        if (!$id && $area->title === '') $area->title = 'Новая область';
        if ($data->points !== null) {
            foreach ($data->points as $point) {
                if (isset($point['floor']) && (int) $point['floor'] !== $area->plan?->getId()) {
                    throw new BadRequestHttpException('Все точки области должны принадлежать её карте.');
                }
            }
            $area->replacePoints($data->points);
        }
        if (!$id && count($area->points) < 3) throw new BadRequestHttpException('Область должна содержать не менее трёх точек.');
        if ($data->active !== null) $area->active = $data->active;
        $this->em->persist($area);
        $this->em->flush();
        return $area;
    }
}

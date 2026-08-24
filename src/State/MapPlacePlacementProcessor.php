<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\MapPlacementInput;
use App\Entity\MapArea;
use App\Entity\MapNode;
use App\Entity\MapPlace;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** @implements ProcessorInterface<MapPlacementInput, MapPlace> */
final readonly class MapPlacePlacementProcessor implements ProcessorInterface
{
    public function __construct(private EntityManagerInterface $em) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): MapPlace
    {
        if (!$data instanceof MapPlacementInput) throw new BadRequestHttpException('Неверная привязка объекта.');
        $place = $this->em->getRepository(MapPlace::class)->find((int) ($uriVariables['id'] ?? 0));
        if (!$place) throw new NotFoundHttpException('Объект карты не найден.');
        $nodeId = $data->nodeId ?? $data->node;
        $areaId = $data->areaId ?? $data->area;
        $place->node = $nodeId ? $this->em->getRepository(MapNode::class)->find($nodeId) : null;
        $place->area = $areaId ? $this->em->getRepository(MapArea::class)->find($areaId) : null;
        if ($nodeId && !$place->node) throw new NotFoundHttpException('Точка не найдена.');
        if ($areaId && !$place->area) throw new NotFoundHttpException('Область не найдена.');
        if ($place->node && $place->node->plan?->getId() !== $place->plan?->getId()) throw new BadRequestHttpException('Точка и объект должны быть на одной карте.');
        if ($place->area && $place->area->plan?->getId() !== $place->plan?->getId()) throw new BadRequestHttpException('Область и объект должны быть на одной карте.');
        $this->em->flush();
        return $place;
    }
}

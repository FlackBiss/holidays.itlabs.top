<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\MapRoadInput;
use App\Entity\MapEdge;
use App\Entity\MapNode;
use App\Entity\MapPlan;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** @implements ProcessorInterface<MapRoadInput, MapEdge> */
final readonly class MapRoadProcessor implements ProcessorInterface
{
    public function __construct(private EntityManagerInterface $em) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): MapEdge
    {
        if (!$data instanceof MapRoadInput) throw new BadRequestHttpException('Неверные данные дороги.');
        $id = isset($uriVariables['id']) ? (int) $uriVariables['id'] : null;
        $road = $id ? $this->em->getRepository(MapEdge::class)->find($id) : new MapEdge();
        if (!$road) throw new NotFoundHttpException('Дорога не найдена.');

        if ($data->planId !== null) {
            $plan = $this->em->getRepository(MapPlan::class)->find($data->planId);
            if (!$plan) throw new NotFoundHttpException('Карта не найдена.');
            $road->plan = $plan;
        }
        foreach (['fromNodeId' => 'fromNode', 'toNodeId' => 'toNode'] as $input => $property) {
            if ($data->{$input} === null) continue;
            $node = $this->em->getRepository(MapNode::class)->find($data->{$input});
            if (!$node) throw new NotFoundHttpException('Точка дороги не найдена.');
            $road->{$property} = $node;
        }
        if (!$id && (!$road->plan || !$road->fromNode || !$road->toNode)) throw new BadRequestHttpException('planId, fromNodeId и toNodeId обязательны при создании дороги.');
        if ($road->fromNode === $road->toNode) throw new BadRequestHttpException('Дорога не может соединять точку саму с собой.');
        if ($road->fromNode?->plan?->getId() !== $road->plan?->getId() || $road->toNode?->plan?->getId() !== $road->plan?->getId()) throw new BadRequestHttpException('Обе точки дороги должны принадлежать одной карте.');
        if ($data->bidirectional !== null) $road->bidirectional = $data->bidirectional;
        if ($data->accessible !== null) $road->accessible = $data->accessible;
        if ($data->distanceMeters !== null) $road->distanceMeters = $data->distanceMeters;
        if ($data->active !== null) $road->active = $data->active;
        $this->em->persist($road);
        $this->em->flush();
        return $road;
    }
}

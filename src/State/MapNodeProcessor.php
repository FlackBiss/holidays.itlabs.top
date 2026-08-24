<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\MapNodeInput;
use App\Entity\MapEdge;
use App\Entity\MapNode;
use App\Entity\MapPlan;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** @implements ProcessorInterface<MapNodeInput, MapNode> */
final readonly class MapNodeProcessor implements ProcessorInterface
{
    public function __construct(private EntityManagerInterface $em) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): MapNode
    {
        if (!$data instanceof MapNodeInput) throw new BadRequestHttpException('Неверные данные точки.');
        $id = isset($uriVariables['id']) ? (int) $uriVariables['id'] : null;
        $node = $id ? $this->em->getRepository(MapNode::class)->find($id) : new MapNode();
        if (!$node) throw new NotFoundHttpException('Точка маршрута не найдена.');

        $legacyPoint = is_array($data->point) ? $data->point : [];
        $planId = $data->planId ?? $data->floor ?? ($legacyPoint['floor'] ?? null);
        $x = $data->x ?? ($legacyPoint['x'] ?? null);
        $y = $data->y ?? ($legacyPoint['y'] ?? null);
        $latitude = $data->latitude ?? ($legacyPoint['latitude'] ?? null);
        $longitude = $data->longitude ?? ($legacyPoint['longitude'] ?? null);

        if ($planId !== null) {
            $plan = $this->em->getRepository(MapPlan::class)->find((int) $planId);
            if (!$plan) throw new NotFoundHttpException('Карта не найдена.');
            if ($id && $node->plan?->getId() !== $plan->getId() && $this->hasRoads($node)) {
                throw new BadRequestHttpException('Нельзя перенести точку на другую карту, пока к ней привязаны дороги.');
            }
            $node->plan = $plan;
        }
        if (!$id && !$node->plan) throw new BadRequestHttpException('planId обязателен при создании точки.');
        if (!$id && ($x === null || $y === null)) throw new BadRequestHttpException('x и y обязательны при создании точки.');
        if ($latitude !== null && ((float) $latitude < -90 || (float) $latitude > 90)) throw new BadRequestHttpException('Широта должна находиться в диапазоне от -90 до 90.');
        if ($longitude !== null && ((float) $longitude < -180 || (float) $longitude > 180)) throw new BadRequestHttpException('Долгота должна находиться в диапазоне от -180 до 180.');
        if ($data->name !== null) $node->name = $data->name;
        if ($x !== null) $node->x = (float) $x;
        if ($y !== null) $node->y = (float) $y;
        if ($latitude !== null) $node->latitude = (float) $latitude;
        if ($longitude !== null) $node->longitude = (float) $longitude;
        if ($data->active !== null) $node->active = $data->active;

        $linkedNodes = $this->resolveLinkedNodes($data->nodes, $node);
        $this->em->persist($node);
        $this->em->flush();
        if ($linkedNodes !== null) {
            $this->replaceRoads($node, $linkedNodes);
            $this->em->flush();
        }
        return $node;
    }

    private function hasRoads(MapNode $node): bool
    {
        return (int) $this->em->createQueryBuilder()
            ->select('COUNT(edge.id)')->from(MapEdge::class, 'edge')
            ->where('edge.fromNode = :node OR edge.toNode = :node')
            ->setParameter('node', $node)->getQuery()->getSingleScalarResult() > 0;
    }

    /** @param list<int>|null $ids @return list<MapNode>|null */
    private function resolveLinkedNodes(?array $ids, MapNode $node): ?array
    {
        if ($ids === null) return null;
        $linked = [];
        foreach (array_values(array_unique($ids)) as $linkedId) {
            if (!is_int($linkedId) && !ctype_digit((string) $linkedId)) throw new BadRequestHttpException('nodes должен содержать идентификаторы точек.');
            $candidate = $this->em->getRepository(MapNode::class)->find((int) $linkedId);
            if (!$candidate instanceof MapNode) throw new NotFoundHttpException('Связанная точка не найдена.');
            if ($candidate === $node) throw new BadRequestHttpException('Точка не может быть связана сама с собой.');
            if ($candidate->plan?->getId() !== $node->plan?->getId()) throw new BadRequestHttpException('Связанные точки должны находиться на одной карте.');
            $linked[] = $candidate;
        }
        return $linked;
    }

    /** @param list<MapNode> $linkedNodes */
    private function replaceRoads(MapNode $node, array $linkedNodes): void
    {
        $existing = $this->em->createQueryBuilder()->select('edge')->from(MapEdge::class, 'edge')
            ->where('edge.fromNode = :node')->setParameter('node', $node)->getQuery()->getResult();
        foreach ($existing as $edge) {
            $node->removeOutgoingEdge($edge);
            $this->em->remove($edge);
        }
        foreach ($linkedNodes as $linkedNode) {
            $reverse = $this->em->getRepository(MapEdge::class)->findOneBy(['fromNode' => $linkedNode, 'toNode' => $node, 'bidirectional' => true]);
            if ($reverse instanceof MapEdge) continue;
            $edge = new MapEdge();
            $edge->plan = $node->plan;
            $edge->fromNode = $node;
            $edge->toNode = $linkedNode;
            $edge->bidirectional = true;
            $edge->accessible = true;
            $node->addOutgoingEdge($edge);
            $this->em->persist($edge);
        }
    }
}

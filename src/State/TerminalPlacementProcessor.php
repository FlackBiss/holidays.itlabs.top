<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\MapPlacementInput;
use App\Entity\KioskTerminal;
use App\Entity\MapArea;
use App\Entity\MapNode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** @implements ProcessorInterface<MapPlacementInput, KioskTerminal> */
final readonly class TerminalPlacementProcessor implements ProcessorInterface
{
    public function __construct(private EntityManagerInterface $em) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): KioskTerminal
    {
        if (!$data instanceof MapPlacementInput) throw new BadRequestHttpException('Неверная привязка терминала.');
        $terminal = $this->em->getRepository(KioskTerminal::class)->find((int) ($uriVariables['id'] ?? 0));
        if (!$terminal) throw new NotFoundHttpException('Терминал не найден.');
        $nodeId = $data->nodeId ?? $data->node;
        $areaId = $data->areaId ?? $data->area;
        $terminal->startNode = $nodeId ? $this->em->getRepository(MapNode::class)->find($nodeId) : null;
        $terminal->area = $areaId ? $this->em->getRepository(MapArea::class)->find($areaId) : null;
        if ($nodeId && !$terminal->startNode) throw new NotFoundHttpException('Точка не найдена.');
        if ($areaId && !$terminal->area) throw new NotFoundHttpException('Область не найдена.');
        if ($terminal->startNode && $terminal->area && $terminal->startNode->plan?->getId() !== $terminal->area->plan?->getId()) throw new BadRequestHttpException('Точка и область терминала должны быть на одной карте.');
        $this->em->flush();
        return $terminal;
    }
}

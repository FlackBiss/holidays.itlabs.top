<?php

namespace App\EventListener;

use App\Entity\MapEdge;
use App\Entity\MapNode;
use App\Entity\MapPlace;
use App\Entity\MapPlan;
use App\Service\RouteDrawnSynchronizer;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::onFlush)]
#[AsDoctrineListener(event: Events::postFlush)]
final class RouteDrawnSynchronizerListener
{
    /** @var array<int, MapPlan> */
    private array $affectedPlans = [];
    private bool $synchronizing = false;

    public function __construct(private readonly RouteDrawnSynchronizer $synchronizer) {}

    public function onFlush(OnFlushEventArgs $event): void
    {
        if ($this->synchronizing) return;

        $em = $event->getObjectManager();
        $unitOfWork = $em->getUnitOfWork();
        foreach ([
            ...$unitOfWork->getScheduledEntityInsertions(),
            ...$unitOfWork->getScheduledEntityUpdates(),
            ...$unitOfWork->getScheduledEntityDeletions(),
        ] as $entity) {
            if (!$entity instanceof MapEdge && !$entity instanceof MapNode && !$entity instanceof MapPlace) continue;
            $this->rememberPlan($entity->plan);
            $originalPlan = $unitOfWork->getOriginalEntityData($entity)['plan'] ?? null;
            $this->rememberPlan($originalPlan instanceof MapPlan ? $originalPlan : null);
        }
    }

    public function postFlush(PostFlushEventArgs $event): void
    {
        if ($this->synchronizing || $this->affectedPlans === []) return;

        $plans = $this->affectedPlans;
        $this->affectedPlans = [];
        $this->synchronizing = true;
        try {
            $changed = 0;
            foreach ($plans as $plan) $changed += $this->synchronizer->synchronizePlan($plan);
            if ($changed > 0) {
                /** @var EntityManagerInterface $em */
                $em = $event->getObjectManager();
                $em->flush();
            }
        } finally {
            $this->synchronizing = false;
        }
    }

    private function rememberPlan(?MapPlan $plan): void
    {
        if ($plan !== null) $this->affectedPlans[spl_object_id($plan)] = $plan;
    }
}

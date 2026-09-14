<?php

namespace App\EventListener;

use App\Entity\ContentRevision;
use App\Entity\KioskTerminal;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\UnitOfWork;

#[AsDoctrineListener(event: Events::onFlush, priority: -1024)]
final class ContentRevisionListener
{
    public function onFlush(OnFlushEventArgs $event): void
    {
        $em = $event->getObjectManager();
        $unitOfWork = $em->getUnitOfWork();
        if (!$this->hasContentChanges($unitOfWork)) return;

        $revision = $em->find(ContentRevision::class, 1);
        if (!$revision instanceof ContentRevision) {
            throw new \LogicException('Content revision is missing. Run database migrations before updating content.');
        }

        // Schedule this update in the same transaction as the content itself.
        $revision->touch();
        $unitOfWork->recomputeSingleEntityChangeSet($em->getClassMetadata(ContentRevision::class), $revision);
    }

    private function hasContentChanges(UnitOfWork $unitOfWork): bool
    {
        foreach ([...$unitOfWork->getScheduledEntityInsertions(), ...$unitOfWork->getScheduledEntityDeletions()] as $entity) {
            if ($this->isContent($entity)) return true;
        }
        foreach ($unitOfWork->getScheduledEntityUpdates() as $entity) {
            if (!$this->isContent($entity)) continue;
            $changes = $unitOfWork->getEntityChangeSet($entity);
            if ($entity instanceof KioskTerminal) unset($changes['lastSeenAt']);
            if ($changes !== []) return true;
        }
        foreach ([...$unitOfWork->getScheduledCollectionUpdates(), ...$unitOfWork->getScheduledCollectionDeletions()] as $collection) {
            $owner = $collection->getOwner();
            if ($owner !== null && $this->isContent($owner)) return true;
        }
        return false;
    }

    private function isContent(object $entity): bool
    {
        return !$entity instanceof User && !$entity instanceof ContentRevision;
    }
}

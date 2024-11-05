<?php

declare(strict_types=1);

namespace BinSoul\Symfony\Bundle\Doctrine\EventListener;

use BinSoul\Symfony\Bundle\Doctrine\Behavior\Archivable;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::onFlush)]
final class ArchivableListener
{
    public function onFlush(OnFlushEventArgs $args): void
    {
        $em = $args->getObjectManager();
        $unitOfWork = $em->getUnitOfWork();

        foreach ($unitOfWork->getScheduledEntityInsertions() as $entity) {
            if (! $entity instanceof Archivable) {
                continue;
            }

            if ($entity->getArchivedAt() !== null) {
                $entity->archive();
            }
        }

        foreach ($unitOfWork->getScheduledEntityUpdates() as $entity) {
            if (! $entity instanceof Archivable) {
                continue;
            }

            if ($entity->getArchivedAt() !== null) {
                $entity->archive();
            }
        }
    }
}

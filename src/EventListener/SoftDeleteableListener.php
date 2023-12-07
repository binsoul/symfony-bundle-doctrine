<?php

declare(strict_types=1);

namespace BinSoul\Symfony\Bundle\Doctrine\EventListener;

use BinSoul\Symfony\Bundle\Doctrine\Behavior\SoftDeleteable;
use BinSoul\Symfony\Bundle\Doctrine\Behavior\TimestampableTrait;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::onFlush)]
final class SoftDeleteableListener
{
    use TimestampableTrait;

    public function onFlush(OnFlushEventArgs $eventArgs): void
    {
        $em = $eventArgs->getObjectManager();
        $unitOfWork = $em->getUnitOfWork();

        foreach ($unitOfWork->getScheduledEntityDeletions() as $entity) {
            if (! $entity instanceof SoftDeleteable) {
                continue;
            }

            $entity->setDeletedAt($this->getTimestamp());
            $entity->deleteSoft();

            $metadata = $em->getClassMetadata(get_class($entity));
            $unitOfWork->persist($entity);
            $unitOfWork->recomputeSingleEntityChangeSet($metadata, $entity);
        }
    }
}

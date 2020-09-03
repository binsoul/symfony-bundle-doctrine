<?php

declare(strict_types=1);

namespace BinSoul\Symfony\Bundle\Doctrine\EventListener;

use BinSoul\Symfony\Bundle\Doctrine\Behavior\SoftDeleteable;
use BinSoul\Symfony\Bundle\Doctrine\Behavior\TimestampableTrait;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;

final class SoftDeleteableListener implements EventSubscriber
{
    use TimestampableTrait;

    public function onFlush(OnFlushEventArgs $eventArgs)
    {
        $em = $eventArgs->getEntityManager();
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

    /**
     * @return array<int, string>
     */
    public function getSubscribedEvents(): array
    {
        return [Events::onFlush];
    }
}

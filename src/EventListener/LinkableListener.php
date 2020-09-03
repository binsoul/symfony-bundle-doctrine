<?php

declare(strict_types=1);

namespace BinSoul\Symfony\Bundle\Doctrine\EventListener;

use BinSoul\Symfony\Bundle\Doctrine\Behavior\Linkable;
use Doctrine\ORM\Event\OnFlushEventArgs;

class LinkableListener
{
    public function onFlush(OnFlushEventArgs $args): void
    {
        $em = $args->getEntityManager();
        $unitOfWork = $em->getUnitOfWork();

        foreach ($unitOfWork->getScheduledEntityInsertions() as $entity) {
            if (! $entity instanceof Linkable) {
                continue;
            }

            $linkedObject = $entity->getLinkedObject();
            $linkedObject->linkedObjectCreated($entity);

            $metadata = $em->getClassMetadata(get_class($linkedObject));
            $unitOfWork->persist($linkedObject);
            $unitOfWork->recomputeSingleEntityChangeSet($metadata, $linkedObject);
        }

        foreach ($unitOfWork->getScheduledEntityUpdates() as $entity) {
            if (! $entity instanceof Linkable) {
                continue;
            }

            $linkedObject = $entity->getLinkedObject();
            $linkedObject->linkedObjectUpdated($entity);

            $metadata = $em->getClassMetadata(get_class($linkedObject));
            $unitOfWork->persist($linkedObject);
            $unitOfWork->recomputeSingleEntityChangeSet($metadata, $linkedObject);
        }

        foreach ($unitOfWork->getScheduledEntityDeletions() as $entity) {
            if (! $entity instanceof Linkable) {
                continue;
            }

            $linkedObject = $entity->getLinkedObject();
            $linkedObject->linkedObjectDeleted($entity);

            $metadata = $em->getClassMetadata(get_class($linkedObject));
            $unitOfWork->persist($linkedObject);
            $unitOfWork->recomputeSingleEntityChangeSet($metadata, $linkedObject);
        }
    }
}

<?php

declare(strict_types=1);

namespace BinSoul\Symfony\Bundle\Doctrine\EventListener;

use BinSoul\Symfony\Bundle\Doctrine\Behavior\Timestampable;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::loadClassMetadata)]
final class TimestampableListener
{
    public function loadClassMetadata(LoadClassMetadataEventArgs $loadClassMetadataEventArgs): void
    {
        $classMetadata = $loadClassMetadataEventArgs->getClassMetadata();

        if ($classMetadata->reflClass === null || ! $classMetadata->reflClass->implementsInterface(Timestampable::class)) {
            return;
        }

        $classMetadata->addLifecycleCallback('updateTimestamps', Events::prePersist);
        $classMetadata->addLifecycleCallback('updateTimestamps', Events::preUpdate);
    }
}

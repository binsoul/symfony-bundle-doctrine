<?php

namespace BinSoul\Test\Symfony\Bundle\Doctrine\EventListener;

use BinSoul\Symfony\Bundle\Doctrine\Behavior\Timestampable;
use BinSoul\Symfony\Bundle\Doctrine\EventListener\TimestampableListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\ClassMetadata;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class TimestampableListenerTest extends TestCase
{
    public function test_load_class_metadata_for_timestampable(): void
    {
        $classMetadata = $this->buildClassMetadata(true);
        $eventArgs = new LoadClassMetadataEventArgs($classMetadata, $this->createStub(EntityManagerInterface::class));

        $timestampableListener = new TimestampableListener();
        $timestampableListener->loadClassMetadata($eventArgs);

        $this->assertCount(1, $classMetadata->getLifecycleCallbacks(Events::prePersist));
        $this->assertCount(1, $classMetadata->getLifecycleCallbacks(Events::preUpdate));
    }

    public function test_load_class_metadata_for_non_timestampable(): void
    {
        $classMetadata = $this->buildClassMetadata(false);
        $eventArgs = new LoadClassMetadataEventArgs($classMetadata, $this->createStub(EntityManagerInterface::class));

        $timestampableListener = new TimestampableListener();
        $timestampableListener->loadClassMetadata($eventArgs);

        $this->assertCount(0, $classMetadata->getLifecycleCallbacks(Events::prePersist));
        $this->assertCount(0, $classMetadata->getLifecycleCallbacks(Events::preUpdate));
    }

    /**
     * @return ClassMetadata<object>
     */
    private function buildClassMetadata(bool $implementsInterface): ClassMetadata
    {
        $entityClass = $implementsInterface ? TimestampableEntity::class : NotTimestampableEntity::class;

        $classMetadata = new ClassMetadata($entityClass);
        $reflClass = new ReflectionClass($entityClass);
        $classMetadata->reflClass = $reflClass;

        return $classMetadata;
    }
}

/**
 * Concrete test entity implementing Timestampable.
 */
class TimestampableEntity implements Timestampable
{
    public function updateTimestamps(): void
    {
    }
}

/**
 * Plain test entity without Timestampable behavior.
 */
class NotTimestampableEntity
{
}

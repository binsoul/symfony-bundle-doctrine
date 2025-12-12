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
    private $timestampableListener;

    protected function setUp(): void
    {
        $this->timestampableListener = new TimestampableListener();
    }

    public function test_load_class_metadata_for_non_timestampable(): void
    {
        $classMetadata = $this->buildClassMetadata(Timestampable::class, false);
        $eventArgs = $this->buildLoadClassMetadataEventArgs($classMetadata);

        $this->timestampableListener->loadClassMetadata($eventArgs);

        $this->assertCount(0, $classMetadata->lifecycleCallbacks);
    }

    public function test_load_class_metadata_for_timestampable(): void
    {
        $classMetadata = $this->buildClassMetadata(Timestampable::class, true);
        $eventArgs = $this->buildLoadClassMetadataEventArgs($classMetadata);

        $this->timestampableListener->loadClassMetadata($eventArgs);

        $this->assertCount(2, $classMetadata->lifecycleCallbacks);
        $this->assertArrayHasKey(Events::prePersist, $classMetadata->lifecycleCallbacks);
        $this->assertArrayHasKey(Events::preUpdate, $classMetadata->lifecycleCallbacks);
    }

    private function buildClassMetadata(string $entityClass, bool $hasInterface): ClassMetadata
    {
        $classMetadata = new ClassMetadata($entityClass);
        $classMetadata->reflClass = $this->createStub(ReflectionClass::class);

        $classMetadata->reflClass
            ->method('implementsInterface')
            ->with(Timestampable::class)
            ->willReturn($hasInterface);

        return $classMetadata;
    }

    private function buildLoadClassMetadataEventArgs(ClassMetadata $classMetadata): LoadClassMetadataEventArgs
    {
        $entityManager = $this->createStub(EntityManagerInterface::class);

        return new LoadClassMetadataEventArgs($classMetadata, $entityManager);
    }
}

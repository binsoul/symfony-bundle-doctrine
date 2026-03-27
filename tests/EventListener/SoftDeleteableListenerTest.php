<?php

namespace BinSoul\Test\Symfony\Bundle\Doctrine\EventListener;

use BinSoul\Symfony\Bundle\Doctrine\Behavior\SoftDeleteable;
use BinSoul\Symfony\Bundle\Doctrine\EventListener\SoftDeleteableListener;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\UnitOfWork;
use PHPUnit\Framework\TestCase;

class SoftDeleteableListenerTest extends TestCase
{
    public function test_valid_softdeletable(): void
    {
        $entity = new SoftDeletableEntity();
        $classMetadata = $this->createStub(ClassMetadata::class);

        $unitOfWork = $this->createMock(UnitOfWork::class);
        $unitOfWork
            ->expects($this->once())
            ->method('getScheduledEntityDeletions')
            ->willReturn([$entity]);

        $unitOfWork
            ->expects($this->once())
            ->method('persist')
            ->with($entity);

        $unitOfWork
            ->expects($this->once())
            ->method('recomputeSingleEntityChangeSet')
            ->with($classMetadata, $entity);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);

        $entityManager
            ->expects($this->once())
            ->method('getClassMetadata')
            ->willReturn($classMetadata);

        $eventArgs = new OnFlushEventArgs($entityManager);

        $listener = new SoftDeleteableListener();
        $listener->onFlush($eventArgs);

        self::assertTrue($entity->deleteSoftCalled);
        self::assertNotNull($entity->getDeletedAt());
    }

    public function test_class_without_interface(): void
    {
        $entity = new NotSoftDeletableEntity();

        $unitOfWork = $this->createMock(UnitOfWork::class);
        $unitOfWork
            ->expects($this->once())
            ->method('getScheduledEntityDeletions')
            ->willReturn([$entity]);

        $unitOfWork
            ->expects($this->never())
            ->method('persist')
            ->with($entity);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);

        $eventArgs = new OnFlushEventArgs($entityManager);

        $listener = new SoftDeleteableListener();
        $listener->onFlush($eventArgs);
    }
}

/**
 * Concrete test entity implementing SoftDeleteable.
 */
class SoftDeletableEntity implements SoftDeleteable
{
    public bool $deleteSoftCalled = false;

    private ?DateTimeInterface $deletedAt = null;

    public function getDeletedAt(): ?DateTimeInterface
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?DateTimeInterface $deletedAt): void
    {
        $this->deletedAt = $deletedAt;
    }

    public function deleteSoft(): void
    {
        $this->deleteSoftCalled = true;
    }
}

/**
 * Plain test entity without SoftDeleteable behavior.
 */
class NotSoftDeletableEntity
{
}

<?php

namespace BinSoul\Test\Symfony\Bundle\Doctrine\EventListener;

use BinSoul\Symfony\Bundle\Doctrine\Behavior\SoftDeleteable;
use BinSoul\Symfony\Bundle\Doctrine\Behavior\Timestampable;
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
        $entity = $this->createMock(SoftDeleteable::class);
        $entity->expects($this->once())
            ->method('setDeletedAt')
            ->with($this->isInstanceOf(DateTimeInterface::class));

        $entity->expects($this->once())
            ->method('deleteSoft');

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

        $eventArgs = $this->getMockBuilder(OnFlushEventArgs::class)
            ->disableOriginalConstructor()
            ->getMock();

        $eventArgs->expects($this->once())
            ->method('getObjectManager')
            ->willReturn($entityManager);

        $listener = new SoftDeleteableListener();
        $listener->onFlush($eventArgs);
    }

    public function test_class_without_interface(): void
    {
        $entity = $this->createStub(Timestampable::class);

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

        $eventArgs = $this->getMockBuilder(OnFlushEventArgs::class)
            ->disableOriginalConstructor()
            ->getMock();

        $eventArgs->expects($this->once())
            ->method('getObjectManager')
            ->willReturn($entityManager);

        $listener = new SoftDeleteableListener();
        $listener->onFlush($eventArgs);
    }
}

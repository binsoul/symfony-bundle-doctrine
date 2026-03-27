<?php

declare(strict_types=1);

namespace BinSoul\Test\Symfony\Bundle\Doctrine\EventListener;

use BinSoul\Symfony\Bundle\Doctrine\Behavior\Linkable;
use BinSoul\Symfony\Bundle\Doctrine\Behavior\LinkedObject;
use BinSoul\Symfony\Bundle\Doctrine\EventListener\LinkableListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\UnitOfWork;
use PHPUnit\Framework\TestCase;

class LinkableListenerTest extends TestCase
{
    /**
     * Test that the linked object is updated on insertion.
     */
    public function test_on_flush_updates_linked_object_on_insertion(): void
    {
        $linkedObject = new LinkedObjectEntity();
        $entity = new LinkableEntity($linkedObject);

        $unitOfWork = $this->createMock(UnitOfWork::class);
        $unitOfWork->method('getScheduledEntityInsertions')->willReturn([$entity]);
        $unitOfWork->method('getScheduledEntityUpdates')->willReturn([]);
        $unitOfWork->method('getScheduledEntityDeletions')->willReturn([]);
        $unitOfWork->expects($this->once())->method('persist')->with($linkedObject);
        $unitOfWork->expects($this->once())->method('recomputeSingleEntityChangeSet');

        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('getUnitOfWork')->willReturn($unitOfWork);
        $entityManager->method('getClassMetadata')->willReturn($this->createStub(ClassMetadata::class));

        $args = new OnFlushEventArgs($entityManager);
        $listener = new LinkableListener();
        $listener->onFlush($args);

        $this->assertTrue($linkedObject->isCreated());
    }

    /**
     * Test that the linked object is updated on update.
     */
    public function test_on_flush_updates_linked_object_on_update(): void
    {
        $linkedObject = new LinkedObjectEntity();
        $entity = new LinkableEntity($linkedObject);

        $unitOfWork = $this->createMock(UnitOfWork::class);
        $unitOfWork->method('getScheduledEntityInsertions')->willReturn([]);
        $unitOfWork->method('getScheduledEntityUpdates')->willReturn([$entity]);
        $unitOfWork->method('getScheduledEntityDeletions')->willReturn([]);
        $unitOfWork->expects($this->once())->method('persist')->with($linkedObject);
        $unitOfWork->expects($this->once())->method('recomputeSingleEntityChangeSet');

        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('getUnitOfWork')->willReturn($unitOfWork);
        $entityManager->method('getClassMetadata')->willReturn($this->createStub(ClassMetadata::class));

        $args = new OnFlushEventArgs($entityManager);
        $listener = new LinkableListener();
        $listener->onFlush($args);

        $this->assertTrue($linkedObject->isUpdated());
    }

    /**
     * Test that the linked object is updated on deletion.
     */
    public function test_on_flush_updates_linked_object_on_deletion(): void
    {
        $linkedObject = new LinkedObjectEntity();
        $entity = new LinkableEntity($linkedObject);

        $unitOfWork = $this->createMock(UnitOfWork::class);
        $unitOfWork->method('getScheduledEntityInsertions')->willReturn([]);
        $unitOfWork->method('getScheduledEntityUpdates')->willReturn([]);
        $unitOfWork->method('getScheduledEntityDeletions')->willReturn([$entity]);
        $unitOfWork->expects($this->once())->method('persist')->with($linkedObject);
        $unitOfWork->expects($this->once())->method('recomputeSingleEntityChangeSet');

        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('getUnitOfWork')->willReturn($unitOfWork);
        $entityManager->method('getClassMetadata')->willReturn($this->createStub(ClassMetadata::class));

        $args = new OnFlushEventArgs($entityManager);
        $listener = new LinkableListener();
        $listener->onFlush($args);

        $this->assertTrue($linkedObject->isDeleted());
    }

    /**
     * Test that entities not implementing Linkable are ignored.
     */
    public function test_on_flush_ignores_non_linkable_entities(): void
    {
        $entity = new NotLinkableEntity();

        $unitOfWork = $this->createMock(UnitOfWork::class);
        $unitOfWork->method('getScheduledEntityInsertions')->willReturn([$entity]);
        $unitOfWork->method('getScheduledEntityUpdates')->willReturn([$entity]);
        $unitOfWork->method('getScheduledEntityDeletions')->willReturn([$entity]);
        $unitOfWork->expects($this->never())->method('persist');
        $unitOfWork->expects($this->never())->method('recomputeSingleEntityChangeSet');

        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('getUnitOfWork')->willReturn($unitOfWork);

        $args = new OnFlushEventArgs($entityManager);
        $listener = new LinkableListener();
        $listener->onFlush($args);
    }
}

/**
 * Concrete test entity implementing Linkable.
 */
class LinkableEntity implements Linkable
{
    public function __construct(
        private readonly LinkedObject $linkedObject
    ) {
    }

    public function getLinkedObject(): LinkedObject
    {
        return $this->linkedObject;
    }
}

/**
 * Concrete test entity implementing LinkedObject.
 */
class LinkedObjectEntity implements LinkedObject
{
    private bool $isCreated = false;

    private bool $isUpdated = false;

    private bool $isDeleted = false;

    public function linkedObjectCreated(Linkable $object): void
    {
        $this->isCreated = true;
    }

    public function linkedObjectUpdated(Linkable $object): void
    {
        $this->isUpdated = true;
    }

    public function linkedObjectDeleted(Linkable $object): void
    {
        $this->isDeleted = true;
    }

    public function isCreated(): bool
    {
        return $this->isCreated;
    }

    public function isUpdated(): bool
    {
        return $this->isUpdated;
    }

    public function isDeleted(): bool
    {
        return $this->isDeleted;
    }
}

/**
 * Plain test entity without Linkable behavior.
 */
class NotLinkableEntity
{
}

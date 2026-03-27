<?php

declare(strict_types=1);

namespace BinSoul\Test\Symfony\Bundle\Doctrine\EventListener;

use BinSoul\Symfony\Bundle\Doctrine\Behavior\Archivable;
use BinSoul\Symfony\Bundle\Doctrine\EventListener\ArchivableListener;
use DateTime;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use PHPUnit\Framework\TestCase;

class ArchivableListenerTest extends TestCase
{
    /**
     * Test that scheduled insertions are archived correctly.
     */
    public function test_on_flush_archives_scheduled_insertions(): void
    {
        $entity = new ArchivableEntity();
        $entity->setArchivedAt(new DateTime());

        $args = $this->buildOnFlushEventArgs([$entity], []);
        $listener = new ArchivableListener();
        $listener->onFlush($args);

        $this->assertTrue($entity->isArchived());
    }

    /**
     * Test that scheduled updates are archived correctly.
     */
    public function test_on_flush_archives_scheduled_updates(): void
    {
        $entity = new ArchivableEntity();
        $entity->setArchivedAt(new DateTime());

        $args = $this->buildOnFlushEventArgs([], [$entity]);
        $listener = new ArchivableListener();
        $listener->onFlush($args);

        $this->assertTrue($entity->isArchived());
    }

    /**
     * Test that entities without an archival date are ignored.
     */
    public function test_on_flush_ignores_entities_without_archived_at(): void
    {
        $entity = new ArchivableEntity();
        $entity->setArchivedAt(null);

        $args = $this->buildOnFlushEventArgs([$entity], [$entity]);
        $listener = new ArchivableListener();
        $listener->onFlush($args);

        $this->assertFalse($entity->isArchived());
    }

    /**
     * Test that entities not implementing Archivable are ignored.
     */
    public function test_on_flush_ignores_non_archivable_entities(): void
    {
        $entity = new NotArchivableEntity();

        $args = $this->buildOnFlushEventArgs([$entity], [$entity]);
        $listener = new ArchivableListener();
        $listener->onFlush($args);

        $this->assertFalse($entity->getArchivedAtCalled);
        $this->assertFalse($entity->archiveCalled);
    }

    /**
     * Builds OnFlushEventArgs with the given entities in the UnitOfWork.
     *
     * @param object[] $insertions
     * @param object[] $updates
     */
    private function buildOnFlushEventArgs(array $insertions, array $updates): OnFlushEventArgs
    {
        $unitOfWork = $this->createStub(UnitOfWork::class);
        $unitOfWork->method('getScheduledEntityInsertions')->willReturn($insertions);
        $unitOfWork->method('getScheduledEntityUpdates')->willReturn($updates);

        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('getUnitOfWork')->willReturn($unitOfWork);

        return new OnFlushEventArgs($entityManager);
    }
}

/**
 * Concrete test entity implementing Archivable.
 */
class ArchivableEntity implements Archivable
{
    private ?DateTimeInterface $archivedAt = null;

    private bool $isArchived = false;

    public function getArchivedAt(): ?DateTimeInterface
    {
        return $this->archivedAt;
    }

    public function setArchivedAt(?DateTimeInterface $archivedAt): void
    {
        $this->archivedAt = $archivedAt;
    }

    public function archive(): void
    {
        $this->isArchived = true;
    }

    public function isArchived(): bool
    {
        return $this->isArchived;
    }
}

/**
 * Plain test entity without Archivable behavior.
 */
class NotArchivableEntity
{
    public bool $getArchivedAtCalled = false;

    public bool $archiveCalled = false;

    public function getArchivedAt(): ?DateTimeInterface
    {
        $this->getArchivedAtCalled = true;

        return new DateTime();
    }

    public function archive(): void
    {
        $this->archiveCalled = true;
    }
}

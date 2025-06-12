<?php

declare(strict_types=1);

namespace BinSoul\Symfony\Bundle\Doctrine\Repository;

use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\Persistence\ManagerRegistry;
use RuntimeException;
use Throwable;

/**
 * Provides basic methods for repositories.
 *
 * @template T of object
 */
abstract class AbstractRepository
{
    /**
     * @var class-string<T>
     */
    private string $entityClass;

    private ManagerRegistry $registry;

    private ?EntityManager $manager = null;

    /**
     * @var EntityRepository<T>|null
     */
    private ?EntityRepository $repository = null;

    /**
     * Constructs an instance of this class.
     *
     * @param class-string<T> $entityClass
     */
    public function __construct(string $entityClass, ManagerRegistry $registry)
    {
        $this->entityClass = $entityClass;
        $this->registry = $registry;
    }

    /**
     * Flushes all changes to objects that have been queued up to now to the database.
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function flush(): void
    {
        $this->getManager()->flush();
    }

    /**
     * Returns the primary table's schema name.
     */
    public function getSchemaName(): ?string
    {
        return $this->getManager()->getClassMetadata($this->entityClass)->getSchemaName();
    }

    /**
     * Returns the name of the primary table.
     */
    public function getTableName(): string
    {
        return $this->getManager()->getClassMetadata($this->entityClass)->getTableName();
    }

    /**
     * Tests if the primary table exists.
     */
    public function tableExists(): bool
    {
        try {
            $schemaManager = $this->getManager()->getConnection()->createSchemaManager();
        } catch (Exception) {
            return false;
        }

        try {
            return $schemaManager->tablesExist([$this->getTableName()]);
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Returns a EntityRepository instance.
     *
     * @return EntityRepository<T>
     */
    protected function getRepository(): EntityRepository
    {
        $this->checkManager();

        if ($this->repository === null) {
            $this->repository = $this->getManager()->getRepository($this->entityClass);
        }

        return $this->repository;
    }

    /**
     * Returns a EntityManager instance.
     */
    protected function getManager(): EntityManager
    {
        $this->checkManager();

        if ($this->manager === null) {
            $manager = $this->registry->getManagerForClass($this->entityClass) ?? $this->registry->getManager();

            if (! $manager instanceof EntityManager) {
                throw new RuntimeException(sprintf('Registry returned %s.', get_class($manager)));
            }

            $this->manager = $manager;
        }

        return $this->manager;
    }

    /**
     * Checks if the manager is open and creates a new manager if it is closed.
     */
    private function checkManager(): void
    {
        if ($this->manager === null || $this->manager->isOpen()) {
            return;
        }

        $this->repository = null;
        $manager = $this->registry->getManagerForClass($this->entityClass) ?? $this->registry->getManager();

        if (! $manager instanceof EntityManager) {
            throw new RuntimeException(sprintf('Registry returned %s.', get_class($manager)));
        }

        $this->manager = $manager;

        if ($this->manager->isOpen()) {
            return;
        }

        foreach ($this->registry->getManagers() as $name => $object) {
            if ($object === $this->manager) {
                $this->manager = $this->registry->resetManager($name);

                break;
            }
        }
    }
}

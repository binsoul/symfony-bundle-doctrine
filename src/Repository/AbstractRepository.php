<?php

declare(strict_types=1);

namespace BinSoul\Symfony\Bundle\Doctrine\Repository;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;

/**
 * Provides basic methods for repositories.
 */
abstract class AbstractRepository
{
    /**
     * @var string
     */
    private $entityClass;

    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var EntityManager|null
     */
    private $manager;

    /**
     * @var EntityRepository|null
     */
    private $repository;

    /**
     * Constructs an instance of this class.
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
     *
     * @return string|null
     */
    public function getSchemaName(): ?string
    {
        return $this->getManager()->getClassMetadata($this->entityClass)->getSchemaName();
    }

    /**
     * Returns the name of the primary table.
     *
     * @return string
     */
    public function getTableName(): string
    {
        return $this->getManager()->getClassMetadata($this->entityClass)->getTableName();
    }

    /**
     * Returns a EntityRepository instance.
     */
    protected function getRepository(): EntityRepository
    {
        $this->checkManager();

        if ($this->repository === null) {
            $repository = $this->getManager()->getRepository($this->entityClass);
            if (!$repository instanceof EntityRepository) {
                throw new \RuntimeException(sprintf('Manager returned %s.', get_class($repository)));
            }

            $this->repository = $repository;
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
            if (!$manager instanceof EntityManager) {
                throw new \RuntimeException(sprintf('Registry returned %s.', get_class($manager)));
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
        if (!$manager instanceof EntityManager) {
            throw new \RuntimeException(sprintf('Registry returned %s.', get_class($manager)));
        }

        $this->manager = $manager;
        if ($this->manager->isOpen()) {
            return;
        }

        foreach ($this->registry->getManagers() as $name => $object) {
            if ($object === $this->manager) {
                $manager = $this->registry->resetManager($name);
                if (!$manager instanceof EntityManager) {
                    throw new \RuntimeException(sprintf('Registry returned %s.', get_class($manager)));
                }

                $this->manager = $manager;

                break;
            }
        }
    }
}

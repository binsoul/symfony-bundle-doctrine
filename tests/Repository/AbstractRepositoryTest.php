<?php

declare(strict_types=1);

namespace BinSoul\Test\Symfony\Bundle\Doctrine\Repository;

use BinSoul\Symfony\Bundle\Doctrine\Repository\AbstractRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ConnectionException;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class AbstractRepositoryTest extends TestCase
{
    /**
     * Tests if flush() calls the entity manager's flush().
     */
    public function test_flush_calls_manager_flush(): void
    {
        $manager = $this->createMock(EntityManager::class);
        $manager->expects($this->once())->method('flush');
        $manager->method('isOpen')->willReturn(true);

        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($manager);

        $repository = new Repository(TestEntity::class, $registry);
        $repository->flush();
    }

    /**
     * Tests schema and table name access.
     */
    public function test_get_names(): void
    {
        $metadata = $this->createStub(ClassMetadata::class);
        $metadata->method('getSchemaName')->willReturn('public');
        $metadata->method('getTableName')->willReturn('user');

        $manager = $this->createStub(EntityManager::class);
        $manager->method('getClassMetadata')->willReturn($metadata);
        $manager->method('isOpen')->willReturn(true);

        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($manager);

        $repository = new Repository(TestEntity::class, $registry);

        $this->assertEquals('public', $repository->getSchemaName());
        $this->assertEquals('user', $repository->getTableName());
    }

    /**
     * Tests if tableExists() works correctly.
     */
    public function test_table_exists(): void
    {
        $metadata = new ClassMetadata(TestEntity::class);
        $metadata->setPrimaryTable(['name' => 'user']);

        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $schemaManager->method('tablesExist')->with(['user'])->willReturn(true);

        $connection = $this->createStub(Connection::class);
        $connection->method('createSchemaManager')->willReturn($schemaManager);

        $manager = $this->createStub(EntityManager::class);
        $manager->method('getClassMetadata')->willReturn($metadata);
        $manager->method('getConnection')->willReturn($connection);
        $manager->method('isOpen')->willReturn(true);

        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($manager);

        $repository = new Repository(TestEntity::class, $registry);

        $this->assertTrue($repository->tableExists());
    }

    /**
     * Tests getRepository() method.
     */
    public function test_get_repository(): void
    {
        $manager = $this->createStub(EntityManager::class);
        $manager->method('isOpen')->willReturn(true);
        $entityRepository = $this->createStub(EntityRepository::class);
        $manager->method('getRepository')->willReturn($entityRepository);

        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($manager);

        $repository = new Repository(TestEntity::class, $registry);

        $this->assertSame($entityRepository, $repository->callGetRepository());
        // Second call should use cached repository
        $this->assertSame($entityRepository, $repository->callGetRepository());
    }

    /**
     * Tests getManager() when manager is already set and open (cache path).
     */
    public function test_get_manager_already_set_and_open_uses_cache(): void
    {
        $manager = $this->createStub(EntityManager::class);
        $manager->method('isOpen')->willReturn(true);

        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($manager);

        $repository = new Repository(TestEntity::class, $registry);

        $m1 = $repository->callGetManager();
        $m2 = $repository->callGetManager();
        $this->assertSame($m1, $m2);
        $this->assertSame($manager, $m1);
    }

    /**
     * Tests getManager() fallback to default manager.
     */
    public function test_get_manager_uses_fallback_manager(): void
    {
        $manager = $this->createStub(EntityManager::class);
        $manager->method('isOpen')->willReturn(true);

        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn(null);
        $registry->method('getManager')->willReturn($manager);

        $repository = new Repository(TestEntity::class, $registry);

        $this->assertSame($manager, $repository->callGetManager());
    }

    /**
     * Tests checkManager() with a closed manager.
     */
    public function test_check_manager_handles_closed_manager(): void
    {
        $closedManager = $this->createStub(EntityManager::class);
        $closedManager->method('isOpen')->willReturn(false);

        $openManager = $this->createStub(EntityManager::class);
        $openManager->method('isOpen')->willReturn(true);

        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($closedManager, $openManager);

        $repository = new Repository(TestEntity::class, $registry);

        // Initial call sets closed manager
        $repository->callGetManager();
        // Second call triggers checkManager(), which fetches $openManager and returns
        $this->assertSame($openManager, $repository->callGetManager());
    }

    /**
     * Tests manager reset when it is closed.
     */
    public function test_check_manager_resets_if_closed(): void
    {
        $closedManager = $this->createStub(EntityManager::class);
        $closedManager->method('isOpen')->willReturn(false);

        $openManager = $this->createStub(EntityManager::class);
        $openManager->method('isOpen')->willReturn(true);

        $registry = $this->createMock(ManagerRegistry::class);
        // Always return closed to force resetManager path
        $registry->method('getManagerForClass')->willReturn($closedManager);
        $registry->method('getManagers')->willReturn(['default' => $closedManager]);
        $registry->expects($this->once())->method('resetManager')->with('default')->willReturn($openManager);

        $repository = new Repository(TestEntity::class, $registry);

        // Initial call to set closed manager, second call triggers checkManager and reset
        $m1 = $repository->callGetManager();
        $repository->flush();
        $m2 = $repository->callGetManager();

        $this->assertNotSame($m1, $m2);
        $this->assertSame($openManager, $m2);
    }

    /**
     * Tests exception handling in tableExists().
     */
    public function test_table_exists_handles_exceptions(): void
    {
        $manager = $this->createStub(EntityManager::class);
        $manager->method('isOpen')->willReturn(true);
        $manager->method('getConnection')->willThrowException(new ConnectionException(''));

        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($manager);

        $repository = new Repository(TestEntity::class, $registry);
        $this->assertFalse($repository->tableExists());
    }

    /**
     * Tests RuntimeException when registry does not provide an entity manager.
     */
    public function test_get_manager_throws_exception_on_invalid_manager(): void
    {
        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($this->createStub(\Doctrine\Persistence\ObjectManager::class));

        $repository = new Repository(TestEntity::class, $registry);

        $this->expectException(RuntimeException::class);
        $repository->flush();
    }

    /**
     * Tests getManager() throws exception when it returns an invalid manager.
     */
    public function test_get_manager_throws_exception_on_invalid_manager_initial(): void
    {
        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn(null);
        $registry->method('getManager')->willReturn($this->createStub(\Doctrine\Persistence\ObjectManager::class));

        $repository = new Repository(TestEntity::class, $registry);

        $this->expectException(RuntimeException::class);
        $repository->callGetManager();
    }

    /**
     * Tests getManager() throws exception when getManagerForClass returns an invalid manager.
     */
    public function test_get_manager_throws_exception_on_invalid_manager_for_class(): void
    {
        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($this->createStub(\Doctrine\Persistence\ObjectManager::class));

        $repository = new Repository(TestEntity::class, $registry);

        $this->expectException(RuntimeException::class);
        $repository->callGetManager();
    }

    /**
     * Tests checkManager() throws exception when getManagerForClass returns an invalid manager.
     */
    public function test_check_manager_throws_exception_on_invalid_manager_for_class(): void
    {
        $closedManager = $this->createStub(EntityManager::class);
        $closedManager->method('isOpen')->willReturn(false);

        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($closedManager, $this->createStub(\Doctrine\Persistence\ObjectManager::class));

        $repository = new Repository(TestEntity::class, $registry);

        // Initial call sets closed manager
        $repository->callGetManager();

        $this->expectException(RuntimeException::class);
        // Second call triggers checkManager(), which should fetch the invalid manager from getManagerForClass() and throw
        $repository->callGetManager();
    }

    /**
     * Tests checkManager() with a reset manager that is not an entity manager.
     */
    public function test_check_manager_throws_exception_on_invalid_reset_manager(): void
    {
        $closedManager = $this->createStub(EntityManager::class);
        $closedManager->method('isOpen')->willReturn(false);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($closedManager);
        $registry->method('getManager')->willReturn($closedManager);
        $registry->method('getManagers')->willReturn(['default' => $closedManager]);
        $registry->method('resetManager')->with('default')->willReturn($this->createStub(\Doctrine\Persistence\ObjectManager::class));

        $repository = new Repository(TestEntity::class, $registry);

        // Initial call sets closed manager
        $repository->callGetManager();

        $this->expectException(RuntimeException::class);
        // Second call triggers checkManager and reset, which throws
        $repository->callGetManager();
    }

    /**
     * Tests checkManager() with an invalid manager after first check.
     */
    public function test_check_manager_throws_exception_on_invalid_manager_after_first_check(): void
    {
        $closedManager = $this->createStub(EntityManager::class);
        $closedManager->method('isOpen')->willReturn(false);

        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($closedManager, null);
        // Inside checkManager(), we want getManager() to return something that is not an EntityManager
        $registry->method('getManager')->willReturn($this->createStub(\Doctrine\Persistence\ObjectManager::class));

        $repository = new Repository(TestEntity::class, $registry);

        // Initial call sets closed manager
        $repository->callGetManager();

        $this->expectException(RuntimeException::class);
        // Second call triggers checkManager(), which should fetch the invalid manager from getManager() and throw
        $repository->callGetManager();
    }
}

/**
 * Concrete implementation for testing AbstractRepository.
 *
 * @extends AbstractRepository<TestEntity>
 */
class Repository extends AbstractRepository
{
    /**
     * @return EntityRepository<TestEntity>
     */
    public function callGetRepository(): EntityRepository
    {
        return $this->getRepository();
    }

    public function callGetManager(): EntityManager
    {
        return $this->getManager();
    }
}

class TestEntity
{
}

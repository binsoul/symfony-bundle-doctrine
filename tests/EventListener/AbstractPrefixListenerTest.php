<?php

declare(strict_types=1);

namespace BinSoul\Test\Symfony\Bundle\Doctrine\EventListener;

use BinSoul\Symfony\Bundle\Doctrine\EventListener\AbstractPrefixListener;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Id\SequenceGenerator;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\JoinTableMapping;
use Doctrine\ORM\Mapping\ManyToManyOwningSideMapping;
use Doctrine\ORM\Mapping\ManyToOneAssociationMapping;
use Doctrine\ORM\Mapping\QuoteStrategy;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

class AbstractPrefixListenerTest extends TestCase
{
    /**
     * Test if the listener returns early if the prefix is empty.
     */
    public function test_load_class_metadata_returns_early_if_prefix_is_empty(): void
    {
        $listener = new PrefixListener('', 'Namespace');
        $classMetadata = new ClassMetadata(PlainEntity1::class);
        $classMetadata->setPrimaryTable(['name' => 'entity']);

        $args = new LoadClassMetadataEventArgs($classMetadata, $this->createStub(EntityManagerInterface::class));
        $listener->loadClassMetadata($args);

        $this->assertEquals('entity', $classMetadata->table['name']);
    }

    /**
     * Test if the listener ignores entities outside the configured namespace.
     */
    public function test_load_class_metadata_returns_early_if_namespace_does_not_match(): void
    {
        $listener = new PrefixListener('prefix_', __NAMESPACE__ . '\Other');
        $classMetadata = new ClassMetadata(PlainEntity2::class);
        $classMetadata->setPrimaryTable(['name' => 'entity']);

        $args = new LoadClassMetadataEventArgs($classMetadata, $this->createStub(EntityManagerInterface::class));
        $listener->loadClassMetadata($args);

        $this->assertEquals('entity', $classMetadata->table['name']);
    }

    /**
     * Test that associations are ignored.
     */
    public function test_load_class_metadata_ignores_associations(): void
    {
        $listener = new PrefixListener('prefix_', __NAMESPACE__);
        $classMetadata = new ClassMetadata(PlainEntity1::class);
        $classMetadata->setPrimaryTable(['name' => 'entity']);

        $mapping = new ManyToOneAssociationMapping(
            fieldName: 'no_many_to_many',
            sourceEntity: PlainEntity1::class,
            targetEntity: PlainEntity2::class,
        );

        $classMetadata->associationMappings = [
            'no_many_to_many' => $mapping,
        ];

        $args = new LoadClassMetadataEventArgs($classMetadata, $this->buildEntityManager());
        $listener->loadClassMetadata($args);

        $associationMappings = $classMetadata->getAssociationMappings();
        $mapping = $associationMappings['no_many_to_many'];
        $this->assertInstanceOf(ManyToOneAssociationMapping::class, $mapping);
    }

    /**
     * Test correct prefixing for tables, indexes and many-to-many join tables.
     */
    public function test_load_class_metadata_prefixes_tables_indexes_and_join_tables(): void
    {
        $listener = new PrefixListener('prefix_', __NAMESPACE__);
        $classMetadata = new ClassMetadata(PlainEntity1::class);
        $classMetadata->table = [
            'name' => 'user',
            'indexes' => [
                'idx_user_email' => [
                    'columns' => ['email'],
                ],
            ],
        ];

        $mapping = new ManyToManyOwningSideMapping(
            fieldName: 'groups',
            sourceEntity: PlainEntity1::class,
            targetEntity: PlainEntity2::class,
        );
        $mapping->joinTable = new JoinTableMapping('user_groups');

        $mappingWithPrefix = new ManyToManyOwningSideMapping(
            fieldName: 'roles',
            sourceEntity: PlainEntity1::class,
            targetEntity: PlainEntity2::class,
        );

        $mappingWithPrefix->joinTable = new JoinTableMapping('user_roles');

        $classMetadata->associationMappings = [
            'groups' => $mapping,
            'roles' => $mappingWithPrefix,
        ];

        $args = new LoadClassMetadataEventArgs($classMetadata, $this->buildEntityManager());
        $listener->loadClassMetadata($args);

        $this->assertEquals('prefix_user', $classMetadata->getTableName());
        $this->assertArrayHasKey('prefix_idx_user_email', $classMetadata->table['indexes']);

        $associationMappings = $classMetadata->getAssociationMappings();

        $mapping = $associationMappings['groups'];
        $this->assertInstanceOf(ManyToManyOwningSideMapping::class, $mapping);
        $this->assertEquals('prefix_user_groups', $mapping->joinTable->name);

        $mappingWithPrefix = $associationMappings['roles'];
        $this->assertInstanceOf(ManyToManyOwningSideMapping::class, $mappingWithPrefix);
        $this->assertEquals('prefix_user_roles', $mappingWithPrefix->joinTable->name);
    }

    /**
     * Test that an existing prefix is not added twice.
     */
    public function test_add_prefix_avoids_double_prefixing(): void
    {
        $listener = new PrefixListener('prefix_', __NAMESPACE__);
        $classMetadata = new ClassMetadata(PlainEntity1::class);
        $classMetadata->table = [
            'name' => 'prefix_user',
            'indexes' => [
                'prefix_idx_user' => ['columns' => ['id']],
            ],
        ];

        $args = new LoadClassMetadataEventArgs($classMetadata, $this->buildEntityManager());
        $listener->loadClassMetadata($args);

        $this->assertEquals('prefix_user', $classMetadata->getTableName());
        $this->assertArrayHasKey('prefix_idx_user', $classMetadata->table['indexes']);
    }

    public function test_postgres_sequence(): void
    {
        $listener = new PrefixListener('prefix_', __NAMESPACE__);
        $classMetadata = new ClassMetadata(PlainEntity1::class);
        $classMetadata->table = [
            'name' => 'user',
            'indexes' => [
                'idx_user' => ['columns' => ['id']],
            ],
        ];

        $classMetadata->generatorType = ClassMetadata::GENERATOR_TYPE_SEQUENCE;
        $classMetadata->sequenceGeneratorDefinition = [
            'sequenceName' => 'sequence',
            'allocationSize' => '20',
            'initialValue' => '1',
        ];
        $classMetadata->idGenerator = new SequenceGenerator('sequence', 20);

        $quoteStrategy = $this->createStub(QuoteStrategy::class);
        $quoteStrategy->method('getSequenceName')->willReturnCallback(fn (array $definition) => $definition['sequenceName']);
        $configuration = $this->createStub(Configuration::class);
        $configuration->method('getQuoteStrategy')->willReturn($quoteStrategy);
        $em = $this->buildEntityManager(PostgreSQLPlatform::class);
        $em->method('getConfiguration')->willReturn($configuration);

        $args = new LoadClassMetadataEventArgs($classMetadata, $em);
        $listener->loadClassMetadata($args);

        $this->assertEquals('prefix_user', $classMetadata->getTableName());
        $this->assertArrayHasKey('prefix_idx_user', $classMetadata->table['indexes']);
        $this->assertEquals('prefix_sequence', $classMetadata->sequenceGeneratorDefinition['sequenceName']);
        $this->assertEquals('prefix_sequence', $classMetadata->idGenerator->__serialize()['sequenceName']);
    }

    /**
     * @param class-string $platform
     */
    private function buildEntityManager(string $platform = AbstractPlatform::class): EntityManagerInterface&Stub
    {
        $platform = $this->createStub($platform);
        $connection = $this->createStub(Connection::class);
        $connection->method('getDatabasePlatform')->willReturn($platform);
        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('getConnection')->willReturn($connection);

        return $em;
    }
}

/**
 * Concrete implementation for testing AbstractPrefixListener.
 */
class PrefixListener extends AbstractPrefixListener
{
}

class PlainEntity1
{
}

class PlainEntity2
{
}

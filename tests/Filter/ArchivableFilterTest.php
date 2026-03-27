<?php

declare(strict_types=1);

namespace BinSoul\Test\Symfony\Bundle\Doctrine\Filter;

use BinSoul\Symfony\Bundle\Doctrine\Behavior\Archivable;
use BinSoul\Symfony\Bundle\Doctrine\Filter\ArchivableFilter;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class ArchivableFilterTest extends TestCase
{
    /**
     * Test if it can be disabled globally.
     */
    public function test_can_be_disabled_globally(): void
    {
        $filter = new ArchivableFilter($this->createStub(EntityManagerInterface::class));
        $filter->disable();

        $classMetadata = $this->buildClassMetadata(true, true);
        $result = $filter->addFilterConstraint($classMetadata, 'alias');

        $this->assertEmpty($result);

        $filter->enable();
        $result = $filter->addFilterConstraint($classMetadata, 'alias');

        $this->assertNotEmpty($result);
    }

    /**
     * Test if it can be disabled for an entity.
     */
    public function test_can_be_disabled_for_entity(): void
    {
        $filter = new ArchivableFilter($this->createStub(EntityManagerInterface::class));
        $filter->disableForEntity(ArchivableTestEntity::class);

        $classMetadata = $this->buildClassMetadata(true, true);
        $result = $filter->addFilterConstraint($classMetadata, 'alias');

        $this->assertEmpty($result);

        $filter->enableForEntity(ArchivableTestEntity::class);
        $result = $filter->addFilterConstraint($classMetadata, 'alias');

        $this->assertNotEmpty($result);
    }

    /**
     * Test if it returns empty string if class does not implements Archivable interface.
     */
    public function test_returns_empty_string_if_class_does_not_implement_archivable(): void
    {
        $filter = new ArchivableFilter($this->createStub(EntityManagerInterface::class));
        $classMetadata = $this->buildClassMetadata(true, false);
        $result = $filter->addFilterConstraint($classMetadata, 'test');

        $this->assertEmpty($result);
    }

    /**
     * Test if it returns empty string if class does not have a archivedAt field.
     */
    public function test_returns_empty_string_if_class_does_not_have_archived_field(): void
    {
        $filter = new ArchivableFilter($this->createStub(\Doctrine\ORM\EntityManager::class));
        $classMetadata = $this->buildClassMetadata(false, true);
        $result = $filter->addFilterConstraint($classMetadata, 'test');

        $this->assertEmpty($result);
    }

    /**
     * @return ClassMetadata<object>
     */
    private function buildClassMetadata(bool $hasArchivedAtField, bool $implementsArchivable): ClassMetadata
    {
        $entityClass = $implementsArchivable ? ArchivableTestEntity::class : NotArchivableTestEntity::class;
        $classMetadata = new ClassMetadata($entityClass);

        if ($hasArchivedAtField) {
            $classMetadata->mapField([
                'fieldName' => 'archivedAt',
                'type' => 'datetime',
                'columnName' => 'archived_at',
            ]);
        }

        $reflClass = new ReflectionClass($entityClass);
        $classMetadata->reflClass = $reflClass;

        return $classMetadata;
    }
}

/**
 * Test entity implementing SoftDeleteable for concrete ReflectionClass instances.
 */
class ArchivableTestEntity implements Archivable
{
    public function getArchivedAt(): ?DateTimeInterface
    {
        return null;
    }

    public function setArchivedAt(?DateTimeInterface $archivedAt): void
    {
    }

    public function archive(): void
    {
    }
}

/**
 * Plain test entity without any behavior interfaces.
 */
class NotArchivableTestEntity
{
}

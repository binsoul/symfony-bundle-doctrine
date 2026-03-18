<?php

namespace BinSoul\Test\Symfony\Bundle\Doctrine\Filter;

use BinSoul\Symfony\Bundle\Doctrine\Behavior\SoftDeleteable;
use BinSoul\Symfony\Bundle\Doctrine\Filter\SoftDeleteableFilter;
use Doctrine\ORM\Mapping\ClassMetadata;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class SoftDeleteableFilterTest extends TestCase
{
    private SoftDeleteableFilter $filter;

    protected function setUp(): void
    {
        $this->filter = new SoftDeleteableFilter($this->createStub(\Doctrine\ORM\EntityManager::class));
    }

    /**
     * Test if it returns empty string if class is disabled.
     */
    public function test_returns_empty_string_if_class_is_disabled(): void
    {
        $this->filter->disableForEntity(ClassMetadata::class);
        $result = $this->filter->addFilterConstraint($this->buildClassMetadata(true, true), 'alias');

        $this->assertSame('', $result);

        $this->filter->enableForEntity(ClassMetadata::class);
        $result = $this->filter->addFilterConstraint($this->buildClassMetadata(true, true), 'alias');

        $this->assertSame('alias.deleted_at IS NULL', $result);
    }

    /**
     * Test if it returns empty string if class does not implements SoftDeleteable interface.
     */
    public function test_returns_empty_string_if_class_does_not_implement_softdeleteable(): void
    {
        $classMetadata = $this->buildClassMetadata(true, false);
        $result = $this->filter->addFilterConstraint($classMetadata, 'test');

        $this->assertSame('', $result);
    }

    /**
     * Test if it returns empty string if class does not have a deletedAt field.
     */
    public function test_returns_empty_string_if_class_does_not_have_deletedat_field(): void
    {
        $classMetadata = $this->buildClassMetadata(false, true);
        $result = $this->filter->addFilterConstraint($classMetadata, 'test');

        $this->assertSame('', $result);
    }

    /**
     * Test if it returns correct SQL string for a valid SoftDeleteable entity.
     */
    public function test_returns_correct_sql_if_entity_is_valid(): void
    {
        $classMetadata = $this->buildClassMetadata(true, true);
        $result = $this->filter->addFilterConstraint($classMetadata, 'alias');

        $this->assertSame('alias.deleted_at IS NULL', $result);
    }

    /**
     * @return ClassMetadata<object>
     */
    private function buildClassMetadata(bool $hasDeletedAtField, bool $implementsSoftDeleteable): ClassMetadata
    {
        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->rootEntityName = ClassMetadata::class;
        $classMetadata->reflClass = $implementsSoftDeleteable ? $this->createMock(ReflectionClass::class) : $this->createStub(ReflectionClass::class);
        $classMetadata
            ->method('getName')
            ->willReturn(ClassMetadata::class);

        $classMetadata
            ->method('hasField')
            ->with('deletedAt')
            ->willReturn($hasDeletedAtField);

        $classMetadata
            ->method('getColumnName')
            ->with('deletedAt')
            ->willReturn('deleted_at');

        if ($implementsSoftDeleteable) {
            $classMetadata->reflClass
                ->method('implementsInterface')
                ->with(SoftDeleteable::class)
                ->willReturn(true);
        }

        return $classMetadata;
    }
}

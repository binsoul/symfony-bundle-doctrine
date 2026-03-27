<?php

declare(strict_types=1);

namespace BinSoul\Symfony\Bundle\Doctrine\Filter;

use BinSoul\Symfony\Bundle\Doctrine\Behavior\SoftDeleteable;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;

class SoftDeleteableFilter extends SQLFilter
{
    private bool $disabled = false;

    /**
     * @var array<class-string, bool>
     */
    private array $disabledClasses = [];

    public function addFilterConstraint(ClassMetadata $targetEntity, string $targetTableAlias): string
    {
        if ($this->disabled) {
            return '';
        }

        if ($this->disabledClasses !== [] && array_key_exists($targetEntity->getName(), $this->disabledClasses)) {
            return '';
        }

        if (! $targetEntity->hasField('deletedAt') || ! $targetEntity->getReflectionClass()->implementsInterface(SoftDeleteable::class)) {
            return '';
        }

        return $targetTableAlias . '.' . $targetEntity->getColumnName('deletedAt') . ' IS NULL';
    }

    public function disable(): void
    {
        $this->disabled = true;
    }

    public function enable(): void
    {
        $this->disabled = false;
    }

    /**
     * @param class-string $class
     */
    public function disableForEntity(string $class): void
    {
        $this->disabledClasses[$class] = true;

        $this->setParameter(sprintf('disabled_%s', $class), true);
    }

    /**
     * @param class-string $class
     */
    public function enableForEntity(string $class): void
    {
        unset($this->disabledClasses[$class]);

        $this->setParameter(sprintf('disabled_%s', $class), false);
    }
}

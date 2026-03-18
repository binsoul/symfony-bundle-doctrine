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
     * @var array<string, bool>
     */
    private array $disabledClasses = [];

    public function addFilterConstraint(ClassMetadata $targetEntity, string $targetTableAlias): string
    {
        $class = $targetEntity->getName();

        if ($this->disabled) {
            return '';
        }

        if (array_key_exists($class, $this->disabledClasses) || array_key_exists($targetEntity->rootEntityName, $this->disabledClasses)) {
            return '';
        }

        if ($targetEntity->reflClass === null || ! $targetEntity->hasField('deletedAt') || ! $targetEntity->reflClass->implementsInterface(SoftDeleteable::class)) {
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

    public function disableForEntity(string $class): void
    {
        $this->disabledClasses[$class] = true;

        $this->setParameter(sprintf('disabled_%s', $class), true);
    }

    public function enableForEntity(string $class): void
    {
        unset($this->disabledClasses[$class]);

        $this->setParameter(sprintf('disabled_%s', $class), false);
    }
}

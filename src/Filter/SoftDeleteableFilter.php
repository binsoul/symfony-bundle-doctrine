<?php

declare(strict_types=1);

namespace BinSoul\Symfony\Bundle\Doctrine\Filter;

use BinSoul\Symfony\Bundle\Doctrine\Behavior\SoftDeleteable;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;

class SoftDeleteableFilter extends SQLFilter
{
    /**
     * @var array<string, bool>
     */
    private array $disabled = [];

    public function addFilterConstraint(ClassMetadata $targetEntity, string $targetTableAlias): string
    {
        $class = $targetEntity->getName();

        if (array_key_exists($class, $this->disabled) || array_key_exists($targetEntity->rootEntityName, $this->disabled)) {
            return '';
        }

        if ($targetEntity->reflClass === null || ! $targetEntity->hasField('deletedAt') || ! $targetEntity->reflClass->implementsInterface(SoftDeleteable::class)) {
            return '';
        }

        return $targetTableAlias . '.' . $targetEntity->getColumnName('deletedAt') . ' IS NULL';
    }

    public function disableForEntity(string $class): void
    {
        $this->disabled[$class] = true;

        $this->setParameter(sprintf('disabled_%s', $class), true);
    }

    public function enableForEntity(string $class): void
    {
        unset($this->disabled[$class]);

        $this->setParameter(sprintf('disabled_%s', $class), false);
    }
}

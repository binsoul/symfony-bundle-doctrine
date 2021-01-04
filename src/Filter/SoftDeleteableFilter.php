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
    private $disabled = [];

    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias)
    {
        $class = $targetEntity->getName();

        if (array_key_exists($class, $this->disabled) || array_key_exists($targetEntity->rootEntityName, $this->disabled)) {
            return '';
        }

        if (! $targetEntity->hasField('deletedAt') || ! $targetEntity->reflClass->implementsInterface(SoftDeleteable::class)) {
            return '';
        }

        $connection = $this->getConnection();
        $platform = $connection->getDatabasePlatform();

        return $platform->getIsNullExpression($targetTableAlias . '.' . $targetEntity->getColumnName('deletedAt'));
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

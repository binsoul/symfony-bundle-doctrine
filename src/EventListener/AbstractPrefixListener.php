<?php

declare(strict_types=1);

namespace BinSoul\Symfony\Bundle\Doctrine\EventListener;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\Id\BigIntegerIdentityGenerator;
use Doctrine\ORM\Id\IdentityGenerator;
use Doctrine\ORM\Id\SequenceGenerator;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

/**
 * Adds a prefix to all tables of a namespace.
 */
#[AsDoctrineListener(event: Events::loadClassMetadata)]
abstract class AbstractPrefixListener
{
    private string $prefix;

    private string $namespace;

    /**
     * Constructs an instance of this class.
     */
    public function __construct(string $prefix, string $namespace)
    {
        $this->prefix = trim($prefix);
        $this->namespace = trim($namespace);
    }

    public function loadClassMetadata(LoadClassMetadataEventArgs $args): void
    {
        if ($this->prefix === '') {
            return;
        }

        $classMetadata = $args->getClassMetadata();

        if (! str_starts_with($classMetadata->namespace, $this->namespace)) {
            return;
        }

        // Generate table
        if (isset($classMetadata->table['name'])) {
            $classMetadata->setPrimaryTable(['name' => $this->addPrefix($classMetadata->table['name'])]);
        }

        // Generate indexes
        if (isset($classMetadata->table['indexes'])) {
            foreach ($classMetadata->table['indexes'] as $index => $value) {
                unset($classMetadata->table['indexes'][$index]);
                $classMetadata->table['indexes'][$this->addPrefix((string) $index)] = $value;
            }
        }

        foreach ($classMetadata->getAssociationMappings() as $fieldName => $mapping) {
            if ($mapping['type'] === ClassMetadataInfo::MANY_TO_MANY && isset($classMetadata->associationMappings[$fieldName]['joinTable']['name'])) {
                $mappedTableName = $classMetadata->associationMappings[$fieldName]['joinTable']['name'];
                $classMetadata->associationMappings[$fieldName]['joinTable']['name'] = $this->addPrefix($mappedTableName);
            }
        }

        // Generate sequences
        $em = $args->getEntityManager();
        $platform = $em->getConnection()->getDatabasePlatform();

        if ($platform instanceof PostgreSqlPlatform) {
            if ($classMetadata->isIdGeneratorSequence()) {
                $newDefinition = $classMetadata->sequenceGeneratorDefinition;
                $newDefinition['sequenceName'] = $this->addPrefix($newDefinition['sequenceName']);

                $classMetadata->setSequenceGeneratorDefinition($newDefinition);

                if (isset($classMetadata->idGenerator)) {
                    $sequenceGenerator = new SequenceGenerator(
                        $em->getConfiguration()->getQuoteStrategy()->getSequenceName(
                            $newDefinition,
                            $classMetadata,
                            $platform
                        ),
                        $newDefinition['allocationSize']
                    );

                    $classMetadata->setIdGenerator($sequenceGenerator);
                }
            } elseif ($classMetadata->isIdGeneratorIdentity()) {
                $fieldName = $classMetadata->identifier ? $classMetadata->getSingleIdentifierFieldName() : null;
                $columnName = $classMetadata->getSingleIdentifierColumnName();
                $sequenceName = $classMetadata->getTableName() . '_' . $columnName . '_seq';

                if (method_exists($platform, 'fixSchemaElementName')) {
                    $definition = ['sequenceName' => $platform->fixSchemaElementName($sequenceName)];
                } else {
                    $definition = ['sequenceName' => $sequenceName];
                }

                if (isset($classMetadata->fieldMappings[$fieldName]['quoted']) || isset($classMetadata->table['quoted'])) {
                    $definition['quoted'] = true;
                }

                $sequenceName = $em->getConfiguration()->getQuoteStrategy()->getSequenceName(
                    $definition,
                    $classMetadata,
                    $platform
                );

                $generator = $fieldName && $classMetadata->fieldMappings[$fieldName]['type'] === 'bigint'
                    ? new BigIntegerIdentityGenerator($sequenceName)
                    : new IdentityGenerator($sequenceName);

                $classMetadata->setIdGenerator($generator);
            }
        }
    }

    private function addPrefix(string $name): string
    {
        if (str_starts_with($name, $this->prefix)) {
            return $name;
        }

        return $this->prefix . $name;
    }
}

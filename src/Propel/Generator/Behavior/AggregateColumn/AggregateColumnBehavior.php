<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Behavior\AggregateColumn;

use InvalidArgumentException;
use Propel\Generator\Builder\Om\ObjectBuilder;
use Propel\Generator\Model\Behavior;
use Propel\Generator\Model\Column;
use Propel\Generator\Model\ForeignKey;
use Propel\Generator\Model\Table;

/**
 * Keeps an aggregate column updated with related table
 *
 * @author François Zaninotto
 */
class AggregateColumnBehavior extends Behavior
{
    /**
     * Default parameters value
     *
     * @var array<string, mixed>
     */
    protected $parameters = [
        'name' => null,
        'expression' => null,
        'condition' => null,
        'foreign_table' => null,
        'foreign_schema' => null,
    ];

    /**
     * Multiple aggregates on the same table is OK.
     *
     * @return bool
     */
    public function allowMultiple(): bool
    {
        return true;
    }

    /**
     * Add the aggregate key to the current table
     *
     * @throws \InvalidArgumentException
     *
     * @return void
     */
    public function modifyTable(): void
    {
        $table = $this->getTable();
        $columnName = $this->getParameter('name');
        if (!$columnName) {
            throw new InvalidArgumentException(sprintf('You must define a \'name\' parameter for the \'aggregate_column\' behavior in the \'%s\' table', $table->getName()));
        }

        // add the aggregate column if not present
        if (!$table->hasColumn($columnName)) {
            $table->addColumn([
                'name' => $columnName,
                'type' => 'INTEGER',
            ]);
        }

        // add a behavior in the foreign table to autoupdate the aggregate column
        $foreignTable = $this->getForeignTable();
        if (!$foreignTable->hasBehavior('concrete_inheritance_parent')) {
            $relationBehavior = new AggregateColumnRelationBehavior();
            $relationBehavior->setName('aggregate_column_relation');
            $relationBehavior->setId('aggregate_column_relation_' . $this->getId());
            $relationBehavior->addParameter(['name' => 'foreign_table', 'value' => $table->getName()]);
            $relationBehavior->addParameter(['name' => 'aggregate_name', 'value' => $this->getColumn()->getPhpName()]);
            $relationBehavior->addParameter(['name' => 'update_method', 'value' => 'update' . $this->getColumn()->getPhpName()]);
            $foreignTable->addBehavior($relationBehavior);
        }
    }

    /**
     * @param \Propel\Generator\Builder\Om\ObjectBuilder $builder
     *
     * @throws \InvalidArgumentException
     *
     * @return string
     */
    public function objectMethods(ObjectBuilder $builder): string
    {
        if (!$this->getParameter('foreign_table')) {
            throw new InvalidArgumentException(sprintf('You must define a \'foreign_table\' parameter for the \'aggregate_column\' behavior in the \'%s\' table', $this->getTable()->getName()));
        }
        $script = $this->addObjectCompute($builder);
        $script .= $this->addObjectUpdate();

        return $script;
    }

    /**
     * @param \Propel\Generator\Builder\Om\ObjectBuilder $builder
     *
     * @throws \InvalidArgumentException
     *
     * @return string
     */
    protected function addObjectCompute(ObjectBuilder $builder): string
    {
        $conditions = [];
        if ($this->getParameter('condition')) {
            $conditions[] = $this->getParameter('condition');
        }

        $bindings = [];
        $database = $this->getTable()->getDatabase();

        if ($this->getForeignKey()->isPolymorphic()) {
            throw new InvalidArgumentException('AggregateColumnBehavior does not work with polymorphic relations.');
        }

        foreach ($this->getForeignKey()->getMapping() as $index => $mapping) {
            [$localColumn, $foreignColumn] = $mapping;
            $conditions[] = $localColumn->getFullyQualifiedName() . ' = :p' . ($index + 1);
            $bindings[$index + 1] = $foreignColumn->getPhpName();
        }
        $tableName = $database->getTablePrefix() . $this->getParameter('foreign_table');
        if ($database->getPlatform()->supportsSchemas() && $this->getParameter('foreign_schema')) {
            $tableName = $this->getParameter('foreign_schema')
                . $database->getPlatform()->getSchemaDelimiter()
                . $tableName;
        }

        $sql = sprintf(
            'SELECT %s FROM %s WHERE %s',
            $this->getParameter('expression'),
            $builder->getTable()->quoteIdentifier($tableName),
            implode(' AND ', $conditions),
        );

        return $this->renderTemplate('objectCompute', [
            'column' => $this->getColumn(),
            'sql' => $sql,
            'bindings' => $bindings,
        ]);
    }

    /**
     * @return string
     */
    protected function addObjectUpdate(): string
    {
        return $this->renderTemplate('objectUpdate', [
            'column' => $this->getColumn(),
        ]);
    }

    /**
     * @return \Propel\Generator\Model\Table|null
     */
    protected function getForeignTable(): ?Table
    {
        $database = $this->getTable()->getDatabase();
        $tableName = $database->getTablePrefix() . $this->getParameter('foreign_table');
        if ($database->getPlatform()->supportsSchemas() && $this->getParameter('foreign_schema')) {
            $tableName = $this->getParameter('foreign_schema') . $database->getPlatform()->getSchemaDelimiter() . $tableName;
        }

        return $database->getTable($tableName);
    }

    /**
     * @throws \InvalidArgumentException
     *
     * @return \Propel\Generator\Model\ForeignKey
     */
    protected function getForeignKey(): ForeignKey
    {
        $foreignTable = $this->getForeignTable();
        // let's infer the relation from the foreign table
        $fks = $foreignTable->getForeignKeysReferencingTable($this->getTable()->getName());
        if (!$fks) {
            throw new InvalidArgumentException(sprintf('You must define a foreign key to the \'%s\' table in the \'%s\' table to enable the \'aggregate_column\' behavior', $this->getTable()->getName(), $foreignTable->getName()));
        }

        // FIXME doesn't work when more than one fk to the same table
        return array_shift($fks);
    }

    /**
     * @return \Propel\Generator\Model\Column|null
     */
    protected function getColumn(): ?Column
    {
        return $this->getTable()->getColumn($this->getParameter('name'));
    }
}

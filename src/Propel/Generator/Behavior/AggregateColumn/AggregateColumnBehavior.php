<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\AggregateColumn;

use Propel\Generator\Model\Behavior;

/**
 * Keeps an aggregate column updated with related table
 *
 * @author François Zaninotto
 */
class AggregateColumnBehavior extends Behavior
{
    // default parameters value
    protected $parameters = array(
        'name'           => null,
        'expression'     => null,
        'condition'      => null,
        'foreign_table'  => null,
        'foreign_schema' => null,
    );

    /**
     * Add the aggregate key to the current table
     */
    public function modifyTable()
    {
        $table = $this->getTable();
        if (!$columnName = $this->getParameter('name')) {
            throw new \InvalidArgumentException(sprintf('You must define a \'name\' parameter for the \'aggregate_column\' behavior in the \'%s\' table', $table->getName()));
        }

        // add the aggregate column if not present
        if (!$table->hasColumn($columnName)) {
            $table->addColumn(array(
                'name'    => $columnName,
                'type'    => 'INTEGER',
            ));
        }

        // add a behavior in the foreign table to autoupdate the aggregate column
        $foreignTable = $this->getForeignTable();
        if (!$foreignTable->hasBehavior('concrete_inheritance_parent')) {
            $relationBehavior = new AggregateColumnRelationBehavior();
            $relationBehavior->setName('aggregate_column_relation');
            $relationBehavior->addParameter(array('name' => 'foreign_table', 'value' => $table->getName()));
            $relationBehavior->addParameter(array('name' => 'update_method', 'value' => 'update' . $this->getColumn()->getPhpName()));
            $foreignTable->addBehavior($relationBehavior);
        }
    }

    public function objectMethods($builder)
    {
        if (!$this->getParameter('foreign_table')) {
            throw new \InvalidArgumentException(sprintf('You must define a \'foreign_table\' parameter for the \'aggregate_column\' behavior in the \'%s\' table', $this->getTable()->getName()));
        }
        $script = '';
        $script .= $this->addObjectCompute();
        $script .= $this->addObjectUpdate();

        return $script;
    }

    protected function addObjectCompute()
    {
        $conditions = array();
        if ($this->getParameter('condition')) {
            $conditions[] = $this->getParameter('condition');
        }

        $bindings = array();
        $database = $this->getTable()->getDatabase();
        foreach ($this->getForeignKey()->getColumnObjectsMapping() as $index => $columnReference) {
            $conditions[] = $columnReference['local']->getFullyQualifiedName() . ' = :p' . ($index + 1);
            $bindings[$index + 1]   = $columnReference['foreign']->getPhpName();
        }
        $tableName = $database->getTablePrefix() . $this->getParameter('foreign_table');
        if ($database->getPlatform()->supportsSchemas() && $this->getParameter('foreign_schema')) {
            $tableName = $this->getParameter('foreign_schema')
                .$database->getPlatform()->getSchemaDelimiter()
                .$tableName;
        }
        $sql = sprintf('SELECT %s FROM %s WHERE %s',
            $this->getParameter('expression'),
            $database->getPlatform()->quoteIdentifier($tableName),
            implode(' AND ', $conditions)
        );

        return $this->renderTemplate('objectCompute', array(
            'column'   => $this->getColumn(),
            'sql'      => $sql,
            'bindings' => $bindings,
        ));
    }

    protected function addObjectUpdate()
    {
        return $this->renderTemplate('objectUpdate', array(
            'column'  => $this->getColumn(),
        ));
    }

    protected function getForeignTable()
    {
        $database = $this->getTable()->getDatabase();
        $tableName = $database->getTablePrefix() . $this->getParameter('foreign_table');
        if ($database->getPlatform()->supportsSchemas() && $this->getParameter('foreign_schema')) {
            $tableName = $this->getParameter('foreign_schema'). $database->getPlatform()->getSchemaDelimiter() . $tableName;
        }

        return $database->getTable($tableName);
    }

    protected function getForeignKey()
    {
        $foreignTable = $this->getForeignTable();
        // let's infer the relation from the foreign table
        $fks = $foreignTable->getForeignKeysReferencingTable($this->getTable()->getName());
        if (!$fks) {
            throw new \InvalidArgumentException(sprintf('You must define a foreign key to the \'%s\' table in the \'%s\' table to enable the \'aggregate_column\' behavior', $this->getTable()->getName(), $foreignTable->getName()));
        }
        // FIXME doesn't work when more than one fk to the same table
        return array_shift($fks);
    }

    protected function getColumn()
    {
        return $this->getTable()->getColumn($this->getParameter('name'));
    }
}

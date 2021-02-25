<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Behavior\AggregateMultipleColumns;

use InvalidArgumentException;
use Propel\Generator\Behavior\AggregateColumn\AggregateColumnRelationBehavior;
use Propel\Generator\Builder\Om\ObjectBuilder;
use Propel\Generator\Model\Behavior;

/**
 * Keeps an aggregate column updated with related table
 *
 * @author François Zaninotto
 */
class AggregateMultipleColumnsBehavior extends Behavior
{
    public const PARAMETER_KEY_FOREIGN_TABLE = 'foreign_table';
    public const PARAMETER_KEY_FOREIGN_SCHEMA = 'foreign_schema';
    public const PARAMETER_KEY_CONDITION = 'condition';
    public const PARAMETER_KEY_COLUMNS = 'columns';
    public const PARAMETER_KEY_COLUMN_NAME = 'column_name';
    public const PARAMETER_KEY_COLUMN_EXPRESSION = 'expression';

    /**
     * Keeps track of inserted aggregation functions to avoid naming collisions.
     *
     * @var array
     */
    private static $insertedAggregationNames = [];

    /**
     * Default parameters value.
     *
     * @var array
     */
    protected $parameters = [
        self::PARAMETER_KEY_FOREIGN_TABLE => null,
        self::PARAMETER_KEY_FOREIGN_SCHEMA => null,
        self::PARAMETER_KEY_CONDITION => null,
        self::PARAMETER_KEY_COLUMNS => null,
    ];

    /**
     * @var string|null
     */
    private $aggregationName;

    /**
     * Multiple aggregates on the same table is OK.
     *
     * @return bool
     */
    public function allowMultiple()
    {
        return true;
    }

    /**
     * @return string
     */
    public function getAggregationName()
    {
        if ($this->aggregationName === null) {
            $this->aggregationName = $this->buildAggregationName();
        }

        return $this->aggregationName;
    }

    /**
     * @return string
     */
    private function buildAggregationName()
    {
        $foreignTableName = $this->getForeignTable()->getPhpName();
        $baseAggregationName = 'AggregatedColumnsFrom' . $foreignTableName;
        $tableName = $this->getTable()->getPhpName();
        if (!array_key_exists($tableName, self::$insertedAggregationNames)) {
            self::$insertedAggregationNames[$tableName] = [];
        }

        $existingNames = &self::$insertedAggregationNames[$tableName];
        if (!in_array($baseAggregationName, $existingNames)) {
            $existingNames[] = $baseAggregationName;

            return $baseAggregationName;
        }

        $ix = 1;
        do {
            $aggregationName = $baseAggregationName . $ix;
            $ix++;
        } while (in_array($aggregationName, $existingNames));

        $existingNames[] = $aggregationName;

        return $aggregationName;
    }

    /**
     * Add the aggregate key to the current table
     *
     * @return void
     */
    public function modifyTable()
    {
        $this->validateColumnParameter();
        $this->addMissingColumnsToTable();
        $this->addAutoupdateBehaviorToForeignTable();
    }

    /**
     * @return void
     */
    private function validateColumnParameter()
    {
        $columnParameters = $this->getParameter(self::PARAMETER_KEY_COLUMNS);
        if (empty($columnParameters)) {
            $this->throwInvalidArgumentExceptionWithLocation('At least one column is required');
        }
        foreach ($columnParameters as $columnDefinition) {
            if (empty($columnDefinition[self::PARAMETER_KEY_COLUMN_NAME])) {
                $this->throwInvalidArgumentExceptionWithLocation('Parameter \'name\' is missing on a column');
            }
            if (empty($columnDefinition[self::PARAMETER_KEY_COLUMN_EXPRESSION])) {
                $colName = $columnDefinition[self::PARAMETER_KEY_COLUMN_NAME];
                $this->throwInvalidArgumentExceptionWithLocation('Parameter \'expression\' is missing on column ' . $colName);
            }
        }
    }

    /**
     * Add the aggregate column if not present
     *
     * @return void
     */
    private function addMissingColumnsToTable()
    {
        $table = $this->getTable();
        $columnParameters = $this->getParameter(self::PARAMETER_KEY_COLUMNS);
        foreach ($columnParameters as $columnDefinition) {
            $columnName = $columnDefinition[self::PARAMETER_KEY_COLUMN_NAME];
            if ($table->hasColumn($columnName)) {
                continue;
            }

            $table->addColumn(['name' => $columnName, 'type' => 'INTEGER']);
        }
    }

    /**
     * Add a behavior in the foreign table to autoupdate the aggregate column
     *
     * @return void
     */
    private function addAutoupdateBehaviorToForeignTable()
    {
        if (!$this->getParameter(self::PARAMETER_KEY_FOREIGN_TABLE)) {
            $this->throwInvalidArgumentExceptionWithLocation('You must define a \'foreign_table\' parameter');
        }
        $foreignTable = $this->getForeignTable();
        if ($foreignTable->hasBehavior('concrete_inheritance_parent')) {
            return;
        }

        $relationBehavior = new AggregateColumnRelationBehavior();
        $relationBehavior->setName('aggregate_multiple_columns_relation');
        $relationBehavior->setId('aggregate_multiple_columns_relation_' . $this->getId());
        $relationBehavior->addParameter(['name' => 'foreign_table', 'value' => $this->getTable()->getName()]);
        $relationBehavior->addParameter(['name' => 'aggregate_name', 'value' => $this->getAggregationName()]);
        $relationBehavior->addParameter(['name' => 'update_method', 'value' => 'update' . $this->getAggregationName()]);
        $foreignTable->addBehavior($relationBehavior);
    }

    /**
     * @param \Propel\Generator\Builder\Om\ObjectBuilder $builder
     *
     * @return string
     */
    public function objectMethods(ObjectBuilder $builder)
    {
        $script = '';
        $script .= $this->addObjectCompute($builder);
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
    protected function addObjectCompute(ObjectBuilder $builder)
    {
        if ($this->getForeignKey()->isPolymorphic()) {
            throw new InvalidArgumentException('AggregateColumnBehavior does not work with polymorphic relations.');
        }

        $conditions = [];
        if ($this->getParameter(self::PARAMETER_KEY_CONDITION)) {
            $conditions[] = $this->getParameter(self::PARAMETER_KEY_CONDITION);
        }

        $bindings = [];
        foreach ($this->getForeignKey()->getMapping() as $index => $mapping) {
            [$localColumn, $foreignColumn] = $mapping;
            $conditions[] = $localColumn->getFullyQualifiedName() . ' = :p' . ($index + 1);
            $bindings[$index + 1] = $foreignColumn->getPhpName();
        }

        $foreignTableName = $this->getForeignTableNameFullyQualified();

        $sql = sprintf(
            'SELECT %s FROM %s WHERE %s',
            $this->buildSelectionStatement(),
            $builder->getTable()->quoteIdentifier($foreignTableName),
            implode(' AND ', $conditions)
        );

        return $this->renderTemplate('objectCompute', [
            'aggregationName' => $this->getAggregationName(),
            'sql' => $sql,
            'bindings' => $bindings,
        ]);
    }

    /**
     * @return string
     */
    private function buildSelectionStatement()
    {
        $columnDefinitions = $this->getParameter(self::PARAMETER_KEY_COLUMNS);
        $selects = [];
        $table = $this->getTable();
        foreach ($columnDefinitions as $columnDefinition) {
            $expression = $columnDefinition[self::PARAMETER_KEY_COLUMN_EXPRESSION];
            $columName = $columnDefinition[self::PARAMETER_KEY_COLUMN_NAME];
            $columnPhpName = $table->getColumn($columName)->getPhpName();
            $selects[] = "$expression AS $columnPhpName";
        }

        return implode(', ', $selects);
    }

    /**
     * @return string
     */
    protected function addObjectUpdate()
    {
        $table = $this->getTable();
        $columnPhpNames = array_map(function (array $colDef) use ($table) {
            $columName = $colDef[AggregateMultipleColumnsBehavior::PARAMETER_KEY_COLUMN_NAME];

            return $table->getColumn($columName)->getPhpName();
        },
        $this->getParameter(self::PARAMETER_KEY_COLUMNS));

        return $this->renderTemplate('objectUpdate', [
            'aggregationName' => $this->getAggregationName(),
            'columnPhpNames' => $columnPhpNames,
        ]);
    }

    /**
     * @return string
     */
    private function getForeignTableNameFullyQualified()
    {
        $database = $this->getTable()->getDatabase();
        $foreignTableName = $database->getTablePrefix() . $this->getParameter(self::PARAMETER_KEY_FOREIGN_TABLE);
        $platform = $database->getPlatform();
        $foreignSchema = $this->getParameter(self::PARAMETER_KEY_FOREIGN_SCHEMA);
        if ($platform->supportsSchemas() && $foreignSchema) {
            $foreignTableName = $foreignSchema . $platform->getSchemaDelimiter() . $foreignTableName;
        }

        return $foreignTableName;
    }

    /**
     * @return \Propel\Generator\Model\Table|null
     */
    protected function getForeignTable()
    {
        $database = $this->getTable()->getDatabase();
        $foreignTableName = $this->getForeignTableNameFullyQualified();

        return $database->getTable($foreignTableName);
    }

    /**
     * @return \Propel\Generator\Model\ForeignKey
     */
    protected function getForeignKey()
    {
        $foreignTable = $this->getForeignTable();
        // let's infer the relation from the foreign table
        $fks = $foreignTable->getForeignKeysReferencingTable($this->getTable()->getName());
        if (!$fks) {
            $msg = 'You must define a foreign key from the \'%s\' table to the table witht the aggregated columns';
            $this->throwInvalidArgumentExceptionWithLocation($msg, $foreignTable->getName());
        }

        // FIXME doesn't work when more than one fk to the same table
        return array_shift($fks);
    }

    /**
     * @param string $format
     * @param mixed ...$args
     *
     * @throws \InvalidArgumentException
     *
     * @return void
     */
    private function throwInvalidArgumentExceptionWithLocation($format, ...$args)
    {
        $format .= ' in the \'aggregate_multiple_columns\' behavior definition in the \'%s\' table definition';
        $args[] = $this->getTable()->getName();
        $message = vsprintf($format, $args);

        throw new InvalidArgumentException($message);
    }
}

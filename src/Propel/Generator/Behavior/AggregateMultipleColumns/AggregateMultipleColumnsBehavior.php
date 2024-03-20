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
use Propel\Generator\Model\ForeignKey;
use Propel\Generator\Model\Table;

/**
 * Keeps an aggregate column updated with related table
 *
 * @author François Zaninotto
 */
class AggregateMultipleColumnsBehavior extends Behavior
{
    /**
     * @var string
     */
    public const PARAMETER_KEY_FOREIGN_TABLE = 'foreign_table';

    /**
     * @var string
     */
    public const PARAMETER_KEY_FOREIGN_SCHEMA = 'foreign_schema';

    /**
     * @var string
     */
    public const PARAMETER_KEY_CONDITION = 'condition';

    /**
     * @var string
     */
    public const PARAMETER_KEY_COLUMNS = 'columns';

    /**
     * @var string
     */
    public const PARAMETER_KEY_COLUMN_NAME = 'column_name';

    /**
     * @var string
     */
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
     * @var array<string, mixed>
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
     * Reset the function name memorizer. Needed when running tests.
     *
     * @return void
     */
    public static function resetInsertedAggregationNames(): void
    {
        self::$insertedAggregationNames = [];
    }

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
     * @return string
     */
    public function getAggregationName(): string
    {
        if ($this->aggregationName === null) {
            $this->aggregationName = $this->buildAggregationName();
        }

        return $this->aggregationName;
    }

    /**
     * @return string
     */
    private function buildAggregationName(): string
    {
        $foreignTableName = $this->getForeignTable()->getPhpName();
        $baseAggregationName = 'AggregatedColumnsFrom' . $foreignTableName;
        $tableName = $this->getTable()->getPhpName();
        if (!array_key_exists($tableName, self::$insertedAggregationNames)) {
            self::$insertedAggregationNames[$tableName] = [];
        }

        $existingNames = &self::$insertedAggregationNames[$tableName];
        if (!in_array($baseAggregationName, $existingNames, true)) {
            $existingNames[] = $baseAggregationName;

            return $baseAggregationName;
        }

        $duplicateAvoidanceSuffix = 1;
        do {
            $aggregationName = $baseAggregationName . $duplicateAvoidanceSuffix;
            $duplicateAvoidanceSuffix++;
        } while (in_array($aggregationName, $existingNames, true));

        $existingNames[] = $aggregationName;

        return $aggregationName;
    }

    /**
     * Add the aggregate key to the current table
     *
     * @return void
     */
    public function modifyTable(): void
    {
        $this->validateColumnParameter();
        $this->addMissingColumnsToTable();
        $this->addAutoupdateBehaviorToForeignTable();
    }

    /**
     * @return void
     */
    private function validateColumnParameter(): void
    {
        $columnParameters = $this->getParameter(static::PARAMETER_KEY_COLUMNS);
        if (!$columnParameters) {
            $this->throwInvalidArgumentExceptionWithLocation('At least one column is required');
        }
        foreach ($columnParameters as $columnDefinition) {
            if (empty($columnDefinition[static::PARAMETER_KEY_COLUMN_NAME])) {
                $this->throwInvalidArgumentExceptionWithLocation('Parameter \'name\' is missing on a column');
            }
            if (empty($columnDefinition[static::PARAMETER_KEY_COLUMN_EXPRESSION])) {
                $colName = $columnDefinition[static::PARAMETER_KEY_COLUMN_NAME];
                $this->throwInvalidArgumentExceptionWithLocation('Parameter \'expression\' is missing on column ' . $colName);
            }
        }
    }

    /**
     * Add the aggregate column if not present
     *
     * @return void
     */
    private function addMissingColumnsToTable(): void
    {
        $table = $this->getTable();
        $columnParameters = $this->getParameter(static::PARAMETER_KEY_COLUMNS);
        foreach ($columnParameters as $columnDefinition) {
            $columnName = $columnDefinition[static::PARAMETER_KEY_COLUMN_NAME];
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
    private function addAutoupdateBehaviorToForeignTable(): void
    {
        if (!$this->getParameter(static::PARAMETER_KEY_FOREIGN_TABLE)) {
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
    public function objectMethods(ObjectBuilder $builder): string
    {
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
        if ($this->getForeignKey()->isPolymorphic()) {
            throw new InvalidArgumentException('AggregateColumnBehavior does not work with polymorphic relations.');
        }

        $conditions = [];
        if ($this->getParameter(static::PARAMETER_KEY_CONDITION)) {
            $conditions[] = $this->getParameter(static::PARAMETER_KEY_CONDITION);
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
            implode(' AND ', $conditions),
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
    private function buildSelectionStatement(): string
    {
        $columnDefinitions = $this->getParameter(static::PARAMETER_KEY_COLUMNS);
        $selects = [];
        $table = $this->getTable();
        foreach ($columnDefinitions as $columnDefinition) {
            $expression = $columnDefinition[static::PARAMETER_KEY_COLUMN_EXPRESSION];
            $columName = $columnDefinition[static::PARAMETER_KEY_COLUMN_NAME];
            $columnPhpName = $table->getColumn($columName)->getPhpName();
            $selects[] = "$expression AS $columnPhpName";
        }

        return implode(', ', $selects);
    }

    /**
     * @return string
     */
    protected function addObjectUpdate(): string
    {
        $table = $this->getTable();
        $columnPhpNames = array_map(function (array $columnParameters) use ($table) {
            $columName = $columnParameters[self::PARAMETER_KEY_COLUMN_NAME];

            return $table->getColumn($columName)->getPhpName();
        },
        $this->getParameter(static::PARAMETER_KEY_COLUMNS));

        return $this->renderTemplate('objectUpdate', [
            'aggregationName' => $this->getAggregationName(),
            'columnPhpNames' => $columnPhpNames,
        ]);
    }

    /**
     * @return string
     */
    private function getForeignTableNameFullyQualified(): string
    {
        $database = $this->getTable()->getDatabase();
        $foreignTableName = $database->getTablePrefix() . $this->getParameter(static::PARAMETER_KEY_FOREIGN_TABLE);
        $platform = $database->getPlatform();
        $foreignSchema = $this->getParameter(static::PARAMETER_KEY_FOREIGN_SCHEMA);
        if ($platform->supportsSchemas() && $foreignSchema) {
            $foreignTableName = $foreignSchema . $platform->getSchemaDelimiter() . $foreignTableName;
        }

        return $foreignTableName;
    }

    /**
     * @return \Propel\Generator\Model\Table|null
     */
    protected function getForeignTable(): ?Table
    {
        $database = $this->getTable()->getDatabase();
        $foreignTableName = $this->getForeignTableNameFullyQualified();

        return $database->getTable($foreignTableName);
    }

    /**
     * @return \Propel\Generator\Model\ForeignKey
     */
    protected function getForeignKey(): ForeignKey
    {
        $foreignTable = $this->getForeignTable();
        // let's infer the relation from the foreign table
        $fks = $foreignTable->getForeignKeysReferencingTable($this->getTable()->getName());
        if (!$fks) {
            $msg = 'You must define a foreign key from the \'%s\' table to the table with the aggregated columns';
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
    private function throwInvalidArgumentExceptionWithLocation(string $format, ...$args): void
    {
        $format .= ' in the \'aggregate_multiple_columns\' behavior definition in the \'%s\' table definition';
        $args[] = $this->getTable()->getName();
        $message = vsprintf($format, $args);

        throw new InvalidArgumentException($message);
    }
}

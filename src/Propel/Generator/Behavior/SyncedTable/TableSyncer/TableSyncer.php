<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Behavior\SyncedTable\TableSyncer;

use Propel\Generator\Behavior\SyncedTable\SyncedTableBehavior;
use Propel\Generator\Behavior\SyncedTable\SyncedTableBehaviorDeclaration;
use Propel\Generator\Behavior\Util\InsertCodeBehavior;
use Propel\Generator\Exception\SchemaException;
use Propel\Generator\Model\Column;
use Propel\Generator\Model\ForeignKey;
use Propel\Generator\Model\Index;
use Propel\Generator\Model\Table;
use Propel\Generator\Model\Unique;
use Propel\Generator\Platform\PgsqlPlatform;
use Propel\Generator\Platform\PlatformInterface;
use Propel\Generator\Platform\SqlitePlatform;
use RuntimeException;

/**
 * Creates the the synced table according to the given behavior.
 */
class TableSyncer
{
    /**
     * Attribute key set on built element to identify heritage.
     *
     * @var string
     */
    public const ATTRIBUTE_KEY_SYNCED_THROUGH = 'synced_through';

    /**
     * @var \Propel\Generator\Behavior\SyncedTable\TableSyncer\TableSyncerConfigInterface
     */
    protected $config;

    /**
     * @param \Propel\Generator\Behavior\SyncedTable\TableSyncer\TableSyncerConfigInterface $config
     */
    public function __construct(TableSyncerConfigInterface $config)
    {
        $this->config = $config;
    }

    /**
     * @param \Propel\Generator\Behavior\SyncedTable\TableSyncer\TableSyncerConfigInterface $config
     * @param \Propel\Generator\Model\Table $sourceTable
     *
     * @return \Propel\Generator\Model\Table
     */
    public static function getSyncedTable(TableSyncerConfigInterface $config, Table $sourceTable): Table
    {
        return (new self($config))->buildSyncedTable($sourceTable);
    }

    /**
     * @param \Propel\Generator\Model\Table $sourceTable
     *
     * @return \Propel\Generator\Model\Table
     */
    protected function buildSyncedTable(Table $sourceTable): Table
    {
        $database = $sourceTable->getDatabase();
        $syncedTableName = $this->config->resolveSyncedTableName();

        $tableExistsInSchema = $database->hasTable($syncedTableName);

        $syncedTable = $tableExistsInSchema ?
            $database->getTable($syncedTableName) :
            $this->createSyncedTable($sourceTable);

        $this->resolveInheritance($syncedTable);

        if (!$tableExistsInSchema || $this->config->isSync()) {
            $this->syncTables($sourceTable, $syncedTable);
        } else {
            $this->addCustomElements($syncedTable, true);
        }

        return $syncedTable;
    }

    /**
     * @param \Propel\Generator\Model\Table $sourceTable
     *
     * @return \Propel\Generator\Model\Table
     */
    protected function createSyncedTable(Table $sourceTable): Table
    {
        $database = $sourceTable->getDatabase();

        $tableAttributes = $this->config->getTableAttributes();
        $defaultAttributes = [
            'name' => $this->config->resolveSyncedTableName(),
            'phpName' => $this->config->getSyncedTablePhpName(),
            'package' => $sourceTable->getPackage(),
            'schema' => $sourceTable->getSchema(),
            'namespace' => $sourceTable->getNamespace() ? '\\' . $sourceTable->getNamespace() : null,
            'identifierQuoting' => $sourceTable->isIdentifierQuotingEnabled(),
        ];

        if ($this->config->inheritSkipSql()) {
            $defaultAttributes['skipSql'] = $sourceTable->isSkipSql();
        }

        return $database->addTable(array_merge($defaultAttributes, $tableAttributes));
    }

    /**
     * @param \Propel\Generator\Model\Table $targetTable Table to copy to.
     *
     * @throws \Propel\Generator\Exception\SchemaException
     *
     * @return void
     */
    protected function resolveInheritance(Table $targetTable): void
    {
        $inheritance = $this->config->getTableInheritance();
        if (!$inheritance) {
            return;
        }
        $sourceTableName = $inheritance['source_table'];
        $sourceTable = $targetTable->getDatabase()->getTable($sourceTableName);
        if (!$sourceTable) {
            throw new SchemaException("Cannot find source table '$sourceTableName'");
        }
        $behavior = new SyncedTableBehavior();
        $behavior->setId('sync_to_table_' . $targetTable->getName());
        $behavior->setTable($sourceTable);
        $defaultParameters = [
            SyncedTableBehaviorDeclaration::PARAMETER_KEY_SYNCED_TABLE => $targetTable->getName(),
            SyncedTableBehaviorDeclaration::PARAMETER_KEY_SYNC => 'true',
            SyncedTableBehaviorDeclaration::PARAMETER_KEY_SYNC_INDEXES => 'true',
            SyncedTableBehaviorDeclaration::PARAMETER_KEY_SYNC_UNIQUE_AS => 'unique',
            SyncedTableBehaviorDeclaration::PARAMETER_KEY_INHERIT_FOREIGN_KEY_CONSTRAINTS => 'true',
            SyncedTableBehaviorDeclaration::PARAMETER_KEY_ON_SKIP_SQL => 'ignore',
        ];
        $parameters = array_merge($defaultParameters, $inheritance);
        $behavior->setParameters($parameters);
        $behavior->modifyTable();
        InsertCodeBehavior::addToTable($behavior, $targetTable, ['parentClass' => $sourceTable]);
    }

    /**
     * @param \Propel\Generator\Model\Table $sourceTable
     * @param \Propel\Generator\Model\Table $syncedTable
     *
     * @return \Propel\Generator\Model\Table
     */
    protected function syncTables(Table $sourceTable, Table $syncedTable): Table
    {
        $columns = $sourceTable->getColumns();
        $ignoreColumnNames = $this->resolveIgnoredColumnNames($sourceTable);
        $this->syncColumns($syncedTable, $columns, $ignoreColumnNames);

        $relationAttributes = $this->config->getRelationAttributes();
        if ($relationAttributes !== null) {
            $this->addForeignKeyRelationToSyncedTable($syncedTable, $sourceTable, $relationAttributes);
        }

        $this->addCustomElements($syncedTable, false);

        $inheritFkRelations = $this->config->isInheritForeignKeyRelations();
        $inheritFkConstraints = $this->config->isInheritForeignKeyConstraints();
        if ($inheritFkRelations || $inheritFkConstraints) {
            $foreignKeys = $sourceTable->getForeignKeys();
            $this->syncForeignKeys($syncedTable, $foreignKeys, $inheritFkConstraints, $ignoreColumnNames);
        }

        if ($this->config->isSyncIndexes()) {
            $indexes = $sourceTable->getIndices();
            $platform = $sourceTable->getDatabase()->getPlatform();
            $renameIndexes = $this->isDistinctiveIndexNameRequired($platform);
            $this->syncIndexes($syncedTable, $indexes, $renameIndexes, $ignoreColumnNames);
        }

        $syncUniqueAs = $this->config->getSyncUniqueIndexAs();
        if ($syncUniqueAs) {
            $asIndex = $syncUniqueAs !== 'unique';
            $uniqueIndexes = $sourceTable->getUnices();
            $this->syncUniqueIndexes($asIndex, $syncedTable, $uniqueIndexes, $ignoreColumnNames);
        }

        $this->reapplyTableBehaviors($sourceTable);

        return $syncedTable;
    }

    /**
     * @param \Propel\Generator\Model\Table $sourceTable
     *
     * @return array<string>
     */
    protected function resolveIgnoredColumnNames(Table $sourceTable): array
    {
        $ignoreColumnNames = $this->config->getIgnoredColumnNames();
        if (!$this->config->isSyncPkOnly()) {
            return $ignoreColumnNames;
        }
        $nonPkColumns = array_filter($sourceTable->getColumns(), fn (Column $column) => !$column->isPrimaryKey());
        $nonPkColumnNames = array_map(fn (Column $column) => $column->getName(), $nonPkColumns);

        return array_unique(array_merge($ignoreColumnNames, $nonPkColumnNames));
    }

    /**
     * @param \Propel\Generator\Model\Table $syncedTable
     * @param bool $tableExistsInSchema
     *
     * @return void
     */
    protected function addCustomElements(Table $syncedTable, bool $tableExistsInSchema)
    {
        $this->addPkColumn($syncedTable);
        $this->addColumnsFromParameter($syncedTable);
        $this->addCustomForeignKeysToSyncedTable($syncedTable);
        $this->config->addTableElements($syncedTable, $tableExistsInSchema);
    }

    /**
     * @param \Propel\Generator\Model\Table $table
     *
     * @return void
     */
    protected function addColumnsFromParameter(Table $table): void
    {
        $columnData = $this->config->getColmns();
        if (!$columnData) {
            return;
        }
        array_map([$table, 'addColumn'], $columnData);
    }

    /**
     * Allows inheriting classes to add columns.
     *
     * @param \Propel\Generator\Model\Table $table
     *
     * @return void
     */
    protected function addPkColumn(Table $table)
    {
        $idColumnName = $this->config->addPkAs();
        if (!$idColumnName) {
            return;
        }
        static::addColumnIfNotExists($table, $idColumnName, [
            'type' => 'INTEGER',
            'required' => 'true',
            'primaryKey' => 'true',
            'autoIncrement' => 'true',
        ]);
        foreach ($table->getPrimaryKey() as $pkColumn) {
            if ($pkColumn->getName() === $idColumnName) {
                continue;
            }
            $pkColumn->setPrimaryKey(false);
        }
    }

    /**
     * @param \Propel\Generator\Model\Table $syncedTable
     * @param \Propel\Generator\Model\Table $sourceTable
     * @param array $relationAttributes
     *
     * @throws \Propel\Generator\Exception\SchemaException If columns are not found.
     *
     * @return void
     */
    protected function addForeignKeyRelationToSyncedTable(Table $syncedTable, Table $sourceTable, array $relationAttributes): void
    {
        $fk = new ForeignKey();
        $syncedTable->addForeignKey($fk);
        $defaultAttributes = [
            'foreignTable' => $sourceTable->getOriginCommonName(),
            'foreignSchema' => $sourceTable->getSchema(),
            static::ATTRIBUTE_KEY_SYNCED_THROUGH => $this->config->getId(), // allows to retrieve relation
        ];
        $fullAttributes = array_merge($defaultAttributes, $relationAttributes);

        $fk->loadMapping($fullAttributes);

        foreach ($sourceTable->getPrimaryKey() as $sourceColumn) {
            $syncedColumnName = $this->getPrefixedColumnName($sourceColumn->getName());
            $syncedColumn = $syncedTable->getColumn($syncedColumnName);
            if (!$syncedColumn) {
                throw new SchemaException('Synced table behavior cannot create relation: primary key column of source table is missing on synced table: ' . $syncedColumnName);
            }
            $fk->addReference($syncedColumn, $sourceColumn);
        }
    }

    /**
     * @param \Propel\Generator\Behavior\SyncedTable\TableSyncer\TableSyncerConfigInterface $config
     * @param array<\Propel\Generator\Model\ForeignKey> $foreignKeys
     *
     * @throws \RuntimeException If more than one relation is found.
     *
     * @return \Propel\Generator\Model\ForeignKey|null
     */
    public static function findSyncedRelation(TableSyncerConfigInterface $config, array $foreignKeys): ?ForeignKey
    {
        $filter = fn (ForeignKey $fk) => $fk->getAttribute(static::ATTRIBUTE_KEY_SYNCED_THROUGH) === $config->getId();
        $matches = array_filter($foreignKeys, $filter);
        if (count($matches) > 1) {
            throw new RuntimeException('More than one relation identified through ');
        }

        return reset($matches) ?: null;
    }

    /**
     * @param \Propel\Generator\Model\Table $syncedTable
     *
     * @return void
     */
    protected function addCustomForeignKeysToSyncedTable(Table $syncedTable)
    {
        $foreignKeys = $this->config->getForeignKeys();
        foreach ($foreignKeys as $fkData) {
            $this->createForeignKeyFromParameters($syncedTable, $fkData);
        }
    }

    /**
     * @param \Propel\Generator\Model\Table $syncedTable
     * @param array<\Propel\Generator\Model\Column> $columns
     * @param array<string> $ignoreColumnNames
     *
     * @return void
     */
    protected function syncColumns(Table $syncedTable, array $columns, array $ignoreColumnNames)
    {
        foreach ($columns as $sourceColumn) {
            $syncedColumnName = $this->getPrefixedColumnName($sourceColumn->getName());
            if (in_array($sourceColumn->getName(), $ignoreColumnNames) || $syncedTable->hasColumn($syncedColumnName)) {
                continue;
            }
            $syncedColumn = clone $sourceColumn;
            $syncedColumn->setName($syncedColumnName);
            $syncedColumn->setPhpName(null);
            $syncedColumn->clearReferrers();
            $syncedColumn->clearInheritanceList();
            $syncedColumn->setAutoIncrement(false);
            $syncedTable->addColumn($syncedColumn);
        }
    }

    /**
     * @param \Propel\Generator\Model\Table $syncedTable
     * @param array<\Propel\Generator\Model\ForeignKey> $foreignKeys
     * @param bool $inheritConstraints
     * @param array<string> $ignoreColumnNames
     *
     * @return void
     */
    protected function syncForeignKeys(Table $syncedTable, array $foreignKeys, bool $inheritConstraints, array $ignoreColumnNames)
    {
        foreach ($foreignKeys as $originalForeignKey) {
            if (
                $syncedTable->containsForeignKeyWithSameName($originalForeignKey)
                || array_intersect($originalForeignKey->getLocalColumns(), $ignoreColumnNames)
            ) {
                continue;
            }
            $syncedForeignKey = clone $originalForeignKey;
            $syncedForeignKey->setSkipSql(!$inheritConstraints);
            $this->prefixForeignKeyColumnNames($syncedForeignKey);
            $syncedTable->addForeignKey($syncedForeignKey);
        }
    }

    /**
     * @param \Propel\Generator\Model\ForeignKey $fk
     *
     * @return void
     */
    protected function prefixForeignKeyColumnNames(ForeignKey $fk): void
    {
        if (!$this->config->getColumnPrefix()) {
            return;
        }
        $mapping = $fk->getColumnObjectsMapping();
        $fk->clearReferences();
        foreach ($mapping as $def) {
            $fk->addReference([
                'local' => $this->getPrefixedColumnName($def['local']->getName()),
                'foreign' => $def['foreign'] ? $def['foreign']->getName() : null,
                'value' => $def['value'],
            ]);
        }
    }

    /**
     * @param \Propel\Generator\Model\Table $syncedTable
     * @param array<\Propel\Generator\Model\Index> $indexes
     * @param bool $rename
     * @param array<string> $ignoreColumnNames
     *
     * @return void
     */
    protected function syncIndexes(Table $syncedTable, array $indexes, bool $rename, array $ignoreColumnNames)
    {
        foreach ($indexes as $originalIndex) {
            $index = clone $originalIndex;

            if (!$this->removeColumnsFromIndex($index, $ignoreColumnNames)) {
                continue;
            }

            if ($rename) {
                // by removing the name, Propel will generate a unique name based on table and columns
                $index->setName(null);
            }

            $this->prefixIndexColumnNames($index, $syncedTable);

            if ($syncedTable->hasIndex($index->getName())) {
                continue;
            }
            $syncedTable->addIndex($index);
        }
    }

    /**
     * @param \Propel\Generator\Model\Index $index
     * @param \Propel\Generator\Model\Table $table
     *
     * @return void
     */
    protected function prefixIndexColumnNames(Index $index, Table $table): void
    {
        if (!$this->config->getColumnPrefix()) {
            return;
        }
        $updatedColumnNames = array_map([$this, 'getPrefixedColumnName'], $index->getColumns());
        $columns = array_map([$table, 'getColumn'], $updatedColumnNames);
        $index->setColumns($columns);
    }

    /**
     * @param \Propel\Generator\Model\Index $index
     * @param array $ignoreColumnNames
     *
     * @return \Propel\Generator\Model\Index|null Returns null if the index has no remaining columns.
     */
    protected function removeColumnsFromIndex(Index $index, array $ignoreColumnNames): ?Index
    {
        $ignoredColumnsInIndex = array_intersect($index->getColumns(), $ignoreColumnNames);
        if (!$ignoredColumnsInIndex) {
            return $index;
        }
        if (count($ignoredColumnsInIndex) === count($index->getColumns())) {
            return null;
        }
        $indexColumns = array_filter($index->getColumnObjects(), fn (Column $col) => !in_array($col->getName(), $ignoredColumnsInIndex));
        $index->setColumns($indexColumns);

        return $index;
    }

    /**
     * Create regular indexes from unique indexes on the given synced table.
     *
     * The synced table cannot use unique indexes, as even unique data on the
     * source table can be syncedd several times.
     *
     * @param bool $asIndex
     * @param \Propel\Generator\Model\Table $syncedTable
     * @param array<\Propel\Generator\Model\Unique> $uniqueIndexes
     * @param array<string> $ignoreColumnNames
     *
     * @return void
     */
    protected function syncUniqueIndexes(bool $asIndex, Table $syncedTable, array $uniqueIndexes, array $ignoreColumnNames)
    {
        $indexClass = $asIndex ? Index::class : Unique::class;
        foreach ($uniqueIndexes as $unique) {
            if (array_intersect($unique->getColumns(), $ignoreColumnNames)) {
                continue;
            }
            $index = new $indexClass();
            $index->setTable($syncedTable);
            foreach ($unique->getColumns() as $columnName) {
                $columnDef = [
                    'name' => $this->getPrefixedColumnName($columnName),
                    'size' => $unique->getColumnSize($columnName),
                ];
                $index->addColumn($columnDef);
            }

            $existingIndexes = $asIndex ? $syncedTable->getIndices() : $syncedTable->getUnices();
            $existingIndexNames = array_map(fn ($index) => $index->getName(), $existingIndexes);
            if (in_array($index->getName(), $existingIndexNames)) {
                continue;
            }
            $index instanceof Unique ? $syncedTable->addUnique($index) : $syncedTable->addIndex($index);
        }
    }

    /**
     * @param \Propel\Generator\Model\Table $table
     *
     * @return void
     */
    protected function reapplyTableBehaviors(Table $table)
    {
        $behaviors = $table->getDatabase()->getBehaviors();
        foreach ($behaviors as $behavior) {
            if ($behavior instanceof SyncedTableBehavior) {
                continue;
            }
            $behavior->modifyDatabase();
        }
    }

    /**
     * @psalm-param array{name?: string, localColumn: string, foreignTable: string, foreignColumn: string, relationOnly?: string} $fkData
     *
     * @param \Propel\Generator\Model\Table $syncedTable
     * @param array $fkData
     *
     * @return void
     */
    protected function createForeignKeyFromParameters(Table $syncedTable, array $fkData): void
    {
        $fk = new ForeignKey($fkData['name'] ?? null);
        $fk->addReference($fkData['localColumn'], $fkData['foreignColumn']);
        $syncedTable->addForeignKey($fk);
        $fk->loadMapping($fkData);
    }

    /**
     * @param \Propel\Generator\Model\Table $table
     * @param string $columnName
     * @param array $columnDefinition
     *
     * @return \Propel\Generator\Model\Column
     */
    public static function addColumnIfNotExists(Table $table, string $columnName, array $columnDefinition): Column
    {
        if ($table->hasColumn($columnName)) {
            return $table->getColumn($columnName);
        }
        $columnDefinitionWithName = array_merge(['name' => $columnName], $columnDefinition);

        return $table->addColumn($columnDefinitionWithName);
    }

    /**
     * @param \Propel\Generator\Platform\PlatformInterface|null $platform
     *
     * @return bool
     */
    protected function isDistinctiveIndexNameRequired(?PlatformInterface $platform): bool
    {
        return $platform instanceof PgsqlPlatform || $platform instanceof SqlitePlatform;
    }

    /**
     * @param string $columnName
     *
     * @return string
     */
    protected function getPrefixedColumnName(string $columnName): string
    {
        return $this->config->getColumnPrefix() . $columnName;
    }
}

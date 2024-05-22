<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Behavior\SyncedTable;

use Propel\Generator\Behavior\SyncedTable\TableSyncer\TableSyncer;
use Propel\Generator\Model\ForeignKey;
use Propel\Generator\Model\Table;

/**
 * Syncs another table definition to the one holding this behavior.
 *
 * Inherits parameter declaration from SyncedTableBehaviorDeclaration.
 *
 * Base for Archivable and Versionable behavior.
 */
class SyncedTableBehavior extends SyncedTableBehaviorDeclaration
{
    /**
     * @var \Propel\Generator\Model\Table|null
     */
    protected $syncedTable;

    /**
     * @return \Propel\Generator\Model\Table|null
     */
    public function getSyncedTable(): ?Table
    {
        return $this->syncedTable;
    }

    /**
     * @return string
     */
    public function getDefaultTableSuffix(): string
    {
        return static::DEFAULT_SYNCED_TABLE_SUFFIX;
    }

    /**
     * @return void
     */
    protected function setupObject(): void
    {
        parent::setupObject();
        $this->setParameterDefaults();
    }

    /**
     * @return void
     */
    protected function setParameterDefaults(): void
    {
        $params = $this->getParameters();
        $defaultParams = $this->getDefaultParameters();
        $this->setParameters(array_merge($defaultParams, $params));
    }

    /**
     * @return string
     */
    public function resolveSyncedTableName(): string
    {
        return $this->getSyncedTableName()
            ?: $this->getTable()->getOriginCommonName() . $this->getDefaultSyncedTableSuffix();
    }

    /**
     * @see \Propel\Generator\Model\Behavior::modifyDatabase()
     *
     * @return void
     */
    public function modifyDatabase(): void
    {
        foreach ($this->getDatabase()->getTables() as $table) {
            $this->addBehaviorToTable($table);
        }
    }

    /**
     * Note overridden by inheriting classes.
     *
     * @param \Propel\Generator\Model\Table $table
     *
     * @return void
     */
    protected function addBehaviorToTable(Table $table): void
    {
        if ($table->hasBehavior($this->getId())) {
            // don't add the same behavior twice
            return;
        }
        $b = clone $this;
        $table->addBehavior($b);
    }

    /**
     * @see \Propel\Generator\Model\Behavior::modifyTable()
     *
     * @return void
     */
    public function modifyTable(): void
    {
        if ($this->omitOnSkipSql() && $this->table->isSkipSql()) {
            return;
        }
        $this->validateParameters();
        $this->syncedTable = TableSyncer::getSyncedTable($this, $this->getTable());
        $this->addEmptyAccessorsToTable($this->syncedTable);
    }

    /**
     * @param \Propel\Generator\Model\Table $table
     *
     * @return void
     */
    protected function addEmptyAccessorsToTable(Table $table): void
    {
        $emptyAccessorColumnNames = $this->getEmptyAccessorColumnNames();
        if (!$emptyAccessorColumnNames) {
            return;
        }
        EmptyColumnAccessorsBehavior::addEmptyAccessors($this, $table, $emptyAccessorColumnNames);
    }

    /**
     * @throws \Propel\Generator\Behavior\SyncedTable\SyncedTableException
     *
     * @return void
     */
    public function validateParameters(): void
    {
        foreach ($this->getForeignKeys() as $fkData) {
            if (empty($fkData['localColumn']) || empty($fkData['foreignTable']) || empty($fkData['foreignColumn'])) {
                throw new SyncedTableException($this, 'Missing foreign key parameters - please supply `localColumn`, `foreignTable` and `foreignColumn` for every entry');
            }
        }
    }

    /**
     * Manual add elements to the synced table.
     *
     * Allows extending classes to setup custom element. Happens somewhat
     * between table setup for backward compatibility.
     *
     * @param \Propel\Generator\Model\Table $syncedTable
     * @param bool $tableExistsInSchema
     *
     * @return void
     */
    public function addTableElements(Table $syncedTable, bool $tableExistsInSchema): void
    {
        // base implementation does nothing
    }

    /**
     * @param \Propel\Generator\Model\Table $table
     * @param string $parameterWithColumnName
     * @param array $columnDefinition
     *
     * @return void
     */
    protected function addColumnFromParameterIfNotExists(Table $table, string $parameterWithColumnName, array $columnDefinition): void
    {
        $columnName = $this->getParameter($parameterWithColumnName);
        TableSyncer::addColumnIfNotExists($table, $columnName, $columnDefinition);
    }

    /**
     * @param string $parameterName
     * @param bool $canBeBoolean
     * @param \Propel\Generator\Model\Table|null $table
     *
     * @throws \Propel\Generator\Behavior\SyncedTable\SyncedTableException
     *
     * @return void
     */
    protected function checkColumnsInParameterExistInTable(string $parameterName, bool $canBeBoolean = false, ?Table $table = null): void
    {
        $table ??= $this->getTable();

        if (
            empty($this->parameters[$parameterName]) ||
            ($canBeBoolean && in_array(strtolower($this->parameters[$parameterName]), ['true', 'false', 0, 1]))
        ) {
            return;
        }
        $columnNames = $this->getParameterCsv($parameterName);
        foreach ($columnNames as $columnName) {
            if ($table->hasColumn($columnName)) {
                continue;
            }

            throw new SyncedTableException($this, "Column '$columnName' in parameter '$parameterName' does not exist in table");
        }
    }

    /**
     * @return string
     */
    public function getColumnPrefix(): string
    {
        $val = $this->useColumnPrefix();
        if ($val === true) {
            return $this->table->getName() . '_';
        }

        return is_string($val) ? $val : '';
    }

    /**
     * @param \Propel\Generator\Model\Table $syncedTable
     *
     * @throws \Propel\Generator\Behavior\SyncedTable\SyncedTableException
     *
     * @return array<\Propel\Generator\Model\Column>
     */
    protected function getSyncedPrimaryKeyColumns(Table $syncedTable): array
    {
        $prefix = $this->getColumnPrefix();
        $pkColumns = [];
        foreach ($this->table->getPrimaryKey() as $sourcePkColumn) {
            $syncedPkColumnName = $prefix . $sourcePkColumn->getName();
            $syncedPkColumn = $syncedTable->getColumn($syncedPkColumnName);
            if (!$syncedPkColumn) {
                throw new SyncedTableException($this, "Cannot find synced PK column '{$syncedPkColumnName}' for source column '{$sourcePkColumn->getName()}'");
            }
            $pkColumns[] = $syncedPkColumn;
        }

        return $pkColumns;
    }

    /**
     * @param array<\Propel\Generator\Model\ForeignKey> $foreignKeys
     *
     * @return \Propel\Generator\Model\ForeignKey|null
     */
    public function findSyncedRelation(array $foreignKeys): ?ForeignKey
    {
        return TableSyncer::findSyncedRelation($this, $foreignKeys);
    }
}

<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Behavior\SyncedTable\TableSyncer;

use Propel\Generator\Model\Table;

interface TableSyncerConfigInterface
{
    /**
     * @return string
     */
    public function getId(): string;

    /**
     * @return string
     */
    public function getDefaultSyncedTableSuffix(): string;

    /**
     * @return string
     */
    public function resolveSyncedTableName(): string;

    /**
     * @return string|null
     */
    public function getSyncedTablePhpName(): ?string;

    /**
     * @return array
     */
    public function getTableAttributes(): array;

    /**
     * @return string|null
     */
    public function addPkAs(): ?string;

    /**
     * @return array|null
     */
    public function getColmns(): ?array;

    /**
     * @return array
     */
    public function getForeignKeys(): array;

    /**
     * @return bool
     */
    public function isSync(): bool;

    /**
     * @return bool
     */
    public function isSyncIndexes(): bool;

    /**
     * @return string|null
     */
    public function getSyncUniqueIndexAs(): ?string;

    /**
     * @return array|null
     */
    public function getRelationAttributes(): ?array;

    /**
     * @return bool
     */
    public function isInheritForeignKeyRelations(): bool;

    /**
     * @return bool
     */
    public function isInheritForeignKeyConstraints(): bool;

    /**
     * @return array
     */
    public function getIgnoredColumnNames(): array;

    /**
     * @return bool
     */
    public function isSyncPkOnly(): bool;

    /**
     * Add elements to the synced table manually.
     *
     * Allows extending classes to setup custom element. Happens somewhat
     * between table setup for backward compatibility.
     *
     * @param \Propel\Generator\Model\Table $syncedTable
     * @param bool $tableExistsInSchema
     *
     * @return void
     */
    public function addTableElements(Table $syncedTable, bool $tableExistsInSchema): void;

    /**
     * @return string
     */
    public function getColumnPrefix(): string;

    /**
     * @return bool
     */
    public function inheritSkipSql(): bool;

    /**
     * @return array|null
     */
    public function getTableInheritance();
}

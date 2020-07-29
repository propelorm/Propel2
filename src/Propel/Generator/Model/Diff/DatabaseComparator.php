<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Model\Diff;

use Propel\Generator\Model\Database;
use Propel\Generator\Model\Table;

/**
 * Service class for comparing Database objects
 * Heavily inspired by Doctrine2's Migrations
 * (see http://github.com/doctrine/dbal/tree/master/lib/Doctrine/DBAL/Schema/)
 */
class DatabaseComparator
{
    /**
     * @var \Propel\Generator\Model\Diff\DatabaseDiff
     */
    protected $databaseDiff;

    /**
     * @var \Propel\Generator\Model\Database
     */
    protected $fromDatabase;

    /**
     * @var \Propel\Generator\Model\Database
     */
    protected $toDatabase;

    /**
     * Whether we should detect renamings and track it via `addRenamedTable` at the
     * DatabaseDiff object.
     *
     * @var bool
     */
    protected $withRenaming = false;

    /**
     * @var bool
     */
    protected $removeTable = true;

    /**
     * @var string[] list of excluded tables
     */
    protected $excludedTables = [];

    /**
     * @param \Propel\Generator\Model\Diff\DatabaseDiff|null $databaseDiff
     */
    public function __construct($databaseDiff = null)
    {
        $this->databaseDiff = ($databaseDiff === null) ? new DatabaseDiff() : $databaseDiff;
    }

    /**
     * @return \Propel\Generator\Model\Diff\DatabaseDiff
     */
    public function getDatabaseDiff()
    {
        return $this->databaseDiff;
    }

    /**
     * Sets the fromDatabase property.
     *
     * @param \Propel\Generator\Model\Database $fromDatabase
     *
     * @return void
     */
    public function setFromDatabase(Database $fromDatabase)
    {
        $this->fromDatabase = $fromDatabase;
    }

    /**
     * Returns the fromDatabase property.
     *
     * @return \Propel\Generator\Model\Database
     */
    public function getFromDatabase()
    {
        return $this->fromDatabase;
    }

    /**
     * Sets the toDatabase property.
     *
     * @param \Propel\Generator\Model\Database $toDatabase
     *
     * @return void
     */
    public function setToDatabase(Database $toDatabase)
    {
        $this->toDatabase = $toDatabase;
    }

    /**
     * Returns the toDatabase property.
     *
     * @return \Propel\Generator\Model\Database
     */
    public function getToDatabase()
    {
        return $this->toDatabase;
    }

    /**
     * Set true to handle removed tables or false to ignore them
     *
     * @param bool $removeTable
     *
     * @return void
     */
    public function setRemoveTable($removeTable)
    {
        $this->removeTable = $removeTable;
    }

    /**
     * @return bool
     */
    public function getRemoveTable()
    {
        return $this->removeTable;
    }

    /**
     * Set the list of tables excluded from the comparison
     *
     * @param string[] $excludedTables set the list of table name
     *
     * @return void
     */
    public function setExcludedTables(array $excludedTables)
    {
        $this->excludedTables = $excludedTables;
    }

    /**
     * Returns the list of tables excluded from the comparison
     *
     * @return string[]
     */
    public function getExcludedTables()
    {
        return $this->excludedTables;
    }

    /**
     * Returns the computed difference between two database objects.
     *
     * @param \Propel\Generator\Model\Database $fromDatabase
     * @param \Propel\Generator\Model\Database $toDatabase
     * @param bool $caseInsensitive
     * @param bool $withRenaming
     * @param bool $removeTable
     * @param array $excludedTables
     *
     * @return \Propel\Generator\Model\Diff\DatabaseDiff|bool
     */
    public static function computeDiff(
        Database $fromDatabase,
        Database $toDatabase,
        $caseInsensitive = false,
        $withRenaming = false,
        $removeTable = true,
        $excludedTables = []
    ) {
        $databaseComparator = new self();
        $databaseComparator->setFromDatabase($fromDatabase);
        $databaseComparator->setToDatabase($toDatabase);
        $databaseComparator->setWithRenaming($withRenaming);
        $databaseComparator->setRemoveTable($removeTable);
        $databaseComparator->setExcludedTables($excludedTables);

        $platform = $toDatabase->getPlatform() ?: $fromDatabase->getPlatform();

        if ($platform) {
            foreach ($fromDatabase->getTables() as $table) {
                $platform->normalizeTable($table);
            }
            foreach ($toDatabase->getTables() as $table) {
                $platform->normalizeTable($table);
            }
        }

        $differences = 0;
        $differences += $databaseComparator->compareTables($caseInsensitive);

        return ($differences > 0) ? $databaseComparator->getDatabaseDiff() : false;
    }

    /**
     * @param bool $withRenaming
     *
     * @return void
     */
    public function setWithRenaming($withRenaming)
    {
        $this->withRenaming = $withRenaming;
    }

    /**
     * @return bool
     */
    public function getWithRenaming()
    {
        return $this->withRenaming;
    }

    /**
     * Returns the number of differences.
     *
     * Compares the tables of the fromDatabase and the toDatabase, and modifies
     * the inner databaseDiff if necessary.
     *
     * @param bool $caseInsensitive
     *
     * @return int
     */
    public function compareTables($caseInsensitive = false)
    {
        $fromDatabaseTables = $this->fromDatabase->getTables();
        $toDatabaseTables = $this->toDatabase->getTables();
        $databaseDifferences = 0;

        // check for new tables in $toDatabase
        foreach ($toDatabaseTables as $table) {
            if ($this->isTableExcluded($table)) {
                continue;
            }
            if (!$this->fromDatabase->hasTable($table->getName(), $caseInsensitive) && !$table->isSkipSql()) {
                $this->databaseDiff->addAddedTable($table->getName(), $table);
                $databaseDifferences++;
            }
        }

        // check for removed tables in $toDatabase
        if ($this->getRemoveTable()) {
            foreach ($fromDatabaseTables as $table) {
                if ($this->isTableExcluded($table)) {
                    continue;
                }
                if (!$this->toDatabase->hasTable($table->getName(), $caseInsensitive) && !$table->isSkipSql()) {
                    $this->databaseDiff->addRemovedTable($table->getName(), $table);
                    $databaseDifferences++;
                }
            }
        }

        // check for table differences
        foreach ($fromDatabaseTables as $fromTable) {
            if ($this->isTableExcluded($fromTable)) {
                continue;
            }
            if ($this->toDatabase->hasTable($fromTable->getName(), $caseInsensitive)) {
                $toTable = $this->toDatabase->getTable($fromTable->getName(), $caseInsensitive);
                $databaseDiff = TableComparator::computeDiff($fromTable, $toTable, $caseInsensitive);
                if ($databaseDiff) {
                    $this->databaseDiff->addModifiedTable($fromTable->getName(), $databaseDiff);
                    $databaseDifferences++;
                }
            }
        }

        // check for table renamings
        foreach ($this->databaseDiff->getAddedTables() as $addedTableName => $addedTable) {
            foreach ($this->databaseDiff->getRemovedTables() as $removedTableName => $removedTable) {
                if (!TableComparator::computeDiff($addedTable, $removedTable, $caseInsensitive)) {
                    // no difference except the name, that's probably a renaming
                    if ($this->getWithRenaming()) {
                        $this->databaseDiff->addRenamedTable($removedTableName, $addedTableName);
                        $this->databaseDiff->removeAddedTable($addedTableName);
                        $this->databaseDiff->removeRemovedTable($removedTableName);
                        $databaseDifferences--;
                    } else {
                        $this->databaseDiff->addPossibleRenamedTable($removedTableName, $addedTableName);
                    }

                    // skip to the next added table
                    break;
                }
            }
        }

        return $databaseDifferences;
    }

    /**
     * @param \Propel\Generator\Model\Table $table
     *
     * @return bool
     */
    protected function isTableExcluded(Table $table)
    {
        $tablename = $table->getName();
        if (in_array($tablename, $this->excludedTables)) {
            return true;
        }

        foreach ($this->excludedTables as $exclude_tablename) {
            if (preg_match('/^' . str_replace('*', '.*', $exclude_tablename) . '$/', $tablename)) {
                return true;
            }
        }

        return false;
    }
}

<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Model\Diff;

use Propel\Generator\Model\Database;

/**
 * Service class for comparing Database objects
 * Heavily inspired by Doctrine2's Migrations
 * (see http://github.com/doctrine/dbal/tree/master/lib/Doctrine/DBAL/Schema/)
 */
class DatabaseComparator
{
    /**
     * @var DatabaseDiff
     */
    protected $databaseDiff;

    /**
     * @var Database
     */
    protected $fromDatabase;

    /**
     * @var Database
     */
    protected $toDatabase;

    /**
     * Whether we should detect renamings and track it via `addRenamedTable` at the
     * DatabaseDiff object.
     *
     * @var bool
     */
    protected $withRenaming = false;

    public function __construct($databaseDiff = null)
    {
        $this->databaseDiff = (null === $databaseDiff) ? new DatabaseDiff() : $databaseDiff;
    }

    public function getDatabaseDiff()
    {
        return $this->databaseDiff;
    }

    /**
     * Sets the fromDatabase property.
     *
     * @param Database $fromDatabase
     */
    public function setFromDatabase(Database $fromDatabase)
    {
        $this->fromDatabase = $fromDatabase;
    }

    /**
     * Returns the fromDatabase property.
     *
     * @return Database
     */
    public function getFromDatabase()
    {
        return $this->fromDatabase;
    }

    /**
     * Sets the toDatabase property.
     *
     * @param Database $toDatabase
     */
    public function setToDatabase(Database $toDatabase)
    {
        $this->toDatabase = $toDatabase;
    }

    /**
     * Returns the toDatabase property.
     *
     * @return Database
     */
    public function getToDatabase()
    {
        return $this->toDatabase;
    }

    /**
     * Returns the computed difference between two database objects.
     *
     * @param  Database             $fromDatabase
     * @param  Database             $toDatabase
     * @param  boolean              $caseInsensitive
     * @return DatabaseDiff|Boolean
     */
    public static function computeDiff(Database $fromDatabase, Database $toDatabase, $caseInsensitive = false, $withRenaming = false)
    {
        $dc = new self();
        $dc->setFromDatabase($fromDatabase);
        $dc->setToDatabase($toDatabase);
        $dc->setWithRenaming($withRenaming);

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
        $differences += $dc->compareTables($caseInsensitive);

        return ($differences > 0) ? $dc->getDatabaseDiff() : false;
    }

    /**
     * @param boolean $withRenaming
     */
    public function setWithRenaming($withRenaming)
    {
        $this->withRenaming = $withRenaming;
    }

    /**
     * @return boolean
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
     * @param  boolean $caseInsensitive
     * @return integer
     */
    public function compareTables($caseInsensitive = false)
    {
        $fromDatabaseTables = $this->fromDatabase->getTables();
        $toDatabaseTables = $this->toDatabase->getTables();
        $databaseDifferences = 0;

        // check for new tables in $toDatabase
        foreach ($toDatabaseTables as $table) {
            if (!$this->fromDatabase->hasTable($table->getName(), $caseInsensitive) && !$table->isSkipSql()) {
                $this->databaseDiff->addAddedTable($table->getName(), $table);
                $databaseDifferences++;
            }
        }

        // check for removed tables in $toDatabase
        foreach ($fromDatabaseTables as $table) {
            if (!$this->toDatabase->hasTable($table->getName(), $caseInsensitive) && !$table->isSkipSql()) {
                $this->databaseDiff->addRemovedTable($table->getName(), $table);
                $databaseDifferences++;
            }
        }

        // check for table differences
        foreach ($fromDatabaseTables as $fromTable) {
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
}

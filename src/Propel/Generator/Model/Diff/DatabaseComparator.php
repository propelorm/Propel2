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
use Propel\Generator\Model\Entity;

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
     * Whether we should detect renamings and track it via `addRenamedEntity` at the
     * DatabaseDiff object.
     *
     * @var bool
     */
    protected $withRenaming = false;

    /**
     * @var boolean
     */
    protected $removeEntity = true;

    /**
     * @var array list of excluded tables
     */
    protected $excludedEntities = array();

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
     * Set true to handle removed tables or false to ignore them
     *
     * @param boolean $removeEntity
     */
    public function setRemoveEntity($removeEntity)
    {
        $this->removeEntity = $removeEntity;
    }

    /**
     * @return boolean
     */
    public function getRemoveEntity()
    {
        return $this->removeEntity;
    }

    /**
     * Set the list of tables excluded from the comparison
     *
     * @param array $excludedEntities set the list of table name
     */
    public function setExcludedEntities(array $excludedEntities)
    {
        $this->excludedEntities = $excludedEntities;
    }

    /**
     * Returns the list of tables excluded from the comparison
     *
     * @return array
     */
    public function getExcludedEntities()
    {
        return $this->excludedEntities;
    }

    /**
     * Returns the computed difference between two database objects.
     *
     * @param  Database             $fromDatabase
     * @param  Database             $toDatabase
     * @param  boolean              $caseInsensitive
     * @return DatabaseDiff|Boolean
     */
    public static function computeDiff(Database $fromDatabase, Database $toDatabase, $caseInsensitive = false, $withRenaming = false, $removeEntity = true, $excludedEntities = array())
    {
        $databaseComparator = new self();
        $databaseComparator->setFromDatabase($fromDatabase);
        $databaseComparator->setToDatabase($toDatabase);
        $databaseComparator->setWithRenaming($withRenaming);
        $databaseComparator->setRemoveEntity($removeEntity);
        $databaseComparator->setExcludedEntities($excludedEntities);

        $platform = $toDatabase->getPlatform() ?: $fromDatabase->getPlatform();

        if ($platform) {
            foreach ($fromDatabase->getEntities() as $table) {
                $platform->normalizeEntity($table);
            }
            foreach ($toDatabase->getEntities() as $table) {
                $platform->normalizeEntity($table);
            }
        }

        $differences = 0;
        $differences += $databaseComparator->compareEntities($caseInsensitive);

        return ($differences > 0) ? $databaseComparator->getDatabaseDiff() : false;
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
    public function compareEntities($caseInsensitive = false)
    {
        $fromDatabaseEntities = $this->fromDatabase->getEntities();
        $toDatabaseEntities = $this->toDatabase->getEntities();
        $databaseDifferences = 0;

        // check for new tables in $toDatabase
        foreach ($toDatabaseEntities as $table) {
            if ($this->isEntityExcluded($table)) {
                continue;
            }
            if (!$this->fromDatabase->hasEntity($table->getName(), $caseInsensitive) && !$table->isSkipSql()) {
                $this->databaseDiff->addAddedEntity($table->getName(), $table);
                $databaseDifferences++;
            }
        }

        // check for removed tables in $toDatabase
        if ($this->getRemoveEntity()) {
            foreach ($fromDatabaseEntities as $table) {
                if ($this->isEntityExcluded($table)) {
                    continue;
                }
                if (!$this->toDatabase->hasEntity($table->getName(), $caseInsensitive) && !$table->isSkipSql()) {
                    $this->databaseDiff->addRemovedEntity($table->getName(), $table);
                    $databaseDifferences++;
                }
            }
        }

        // check for table differences
        foreach ($fromDatabaseEntities as $fromEntity) {
            if ($this->isEntityExcluded($fromEntity)) {
                continue;
            }
            if ($this->toDatabase->hasEntity($fromEntity->getName(), $caseInsensitive)) {
                $toEntity = $this->toDatabase->getEntity($fromEntity->getName(), $caseInsensitive);
                $databaseDiff = EntityComparator::computeDiff($fromEntity, $toEntity, $caseInsensitive);
                if ($databaseDiff) {
                    $this->databaseDiff->addModifiedEntity($fromEntity->getName(), $databaseDiff);
                    $databaseDifferences++;
                }
            }
        }

        // check for table renamings
        foreach ($this->databaseDiff->getAddedEntities() as $addedEntityName => $addedEntity) {
            foreach ($this->databaseDiff->getRemovedEntities() as $removedEntityName => $removedEntity) {
                if (!EntityComparator::computeDiff($addedEntity, $removedEntity, $caseInsensitive)) {
                    // no difference except the name, that's probably a renaming
                    if ($this->getWithRenaming()) {
                        $this->databaseDiff->addRenamedEntity($removedEntityName, $addedEntityName);
                        $this->databaseDiff->removeAddedEntity($addedEntityName);
                        $this->databaseDiff->removeRemovedEntity($removedEntityName);
                        $databaseDifferences--;
                    } else {
                        $this->databaseDiff->addPossibleRenamedEntity($removedEntityName, $addedEntityName);
                    }
                    // skip to the next added table
                    break;
                }
            }
        }

        return $databaseDifferences;
    }

    /**
     * @param Entity $table
     * @return bool
     */
    protected function isEntityExcluded(Entity $table)
    {
        return in_array($table->getName(), $this->excludedEntities);
    }
}

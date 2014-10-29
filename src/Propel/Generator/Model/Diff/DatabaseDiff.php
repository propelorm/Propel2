<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Model\Diff;

use Propel\Generator\Model\IdMethod;
use Propel\Generator\Model\Entity;

/**
 * Value object for storing Database object diffs
 * Heavily inspired by Doctrine2's Migrations
 * (see http://github.com/doctrine/dbal/tree/master/lib/Doctrine/DBAL/Schema/)
 */
class DatabaseDiff
{
    protected $addedEntities;
    protected $removedEntities;
    protected $modifiedEntities;
    protected $renamedEntities;
    protected $possibleRenamedEntities;

    public function __construct()
    {
        $this->addedEntities    = [];
        $this->removedEntities  = [];
        $this->modifiedEntities = [];
        $this->renamedEntities  = [];
        $this->possibleRenamedEntities  = [];
    }

    /**
     * Sets the added tables.
     *
     * @param array $tables
     */
    public function setAddedEntities($tables)
    {
        $this->addedEntities = $tables;
    }

    /**
     * Adds an added table.
     *
     * @param string $name
     * @param Entity  $table
     */
    public function addAddedEntity($name, Entity $table)
    {
        $this->addedEntities[$name] = $table;
    }

    /**
     * Removes an added table.
     *
     * @param string $name
     */
    public function removeAddedEntity($name)
    {
        unset($this->addedEntities[$name]);
    }

    /**
     * @return string[]
     */
    public function getPossibleRenamedEntities()
    {
        return $this->possibleRenamedEntities;
    }

    /**
     * Adds a possible renamed table.
     *
     * @param string $fromName
     * @param string $toName
     */
    public function addPossibleRenamedEntity($fromName, $toName)
    {
        $this->possibleRenamedEntities[$fromName] = $toName;
    }

    /**
     * Returns the list of added tables.
     *
     * @return Entity[]
     */
    public function getAddedEntities()
    {
        return $this->addedEntities;
    }

    /**
     * Returns the number of added tables.
     *
     * @return integer
     */
    public function countAddedEntities()
    {
        return count($this->addedEntities);
    }

    /**
     * Returns an added table by its name.
     *
     * @param string $name
     * @param Entity
     */
    public function getAddedEntity($name)
    {
        return $this->addedEntities[$name];
    }

    /**
     * Sets the removes tables.
     *
     * @param array $tables
     */
    public function setRemovedEntities($tables)
    {
        $this->removedEntities = $tables;
    }

    /**
     * Adds a table to remove.
     *
     * @param string $name
     * @param Entity  $table
     */
    public function addRemovedEntity($name, Entity $table)
    {
        $this->removedEntities[$name] = $table;
    }

    /**
     * Removes a removed table.
     *
     * @param string $name
     */
    public function removeRemovedEntity($name)
    {
        unset($this->removedEntities[$name]);
    }

    /**
     * Returns the list of removed tables.
     *
     * @return Entity[]
     */
    public function getRemovedEntities()
    {
        return $this->removedEntities;
    }

    /**
     * Returns the number of removed tables.
     *
     * @return integer
     */
    public function countRemovedEntities()
    {
        return count($this->removedEntities);
    }

    /**
     * Returns a removed table.
     *
     * @param string $name
     * @param Entity
     */
    public function getRemovedEntity($name)
    {
        return $this->removedEntities[$name];
    }

    /**
     * Sets the modified tables
     *
     * @param array $tables
     */
    public function setModifiedEntities($tables)
    {
        $this->modifiedEntities = $tables;
    }

    /**
     * Adds a table difference.
     *
     * @param string    $name
     * @param EntityDiff $difference
     */
    public function addModifiedEntity($name, EntityDiff $difference)
    {
        $this->modifiedEntities[$name] = $difference;
    }

    /**
     * Returns the number of modified tables.
     *
     * @return integer
     */
    public function countModifiedEntities()
    {
        return count($this->modifiedEntities);
    }

    /**
     * Returns the modified tables.
     *
     * @return EntityDiff[]
     */
    public function getModifiedEntities()
    {
        return $this->modifiedEntities;
    }

    /**
     * Sets the renamed tables.
     *
     * @param array $tables
     */
    public function setRenamedEntities($tables)
    {
        $this->renamedEntities = $tables;
    }

    /**
     * Adds a renamed table.
     *
     * @param string $fromName
     * @param string $toName
     */
    public function addRenamedEntity($fromName, $toName)
    {
        $this->renamedEntities[$fromName] = $toName;
    }

    /**
     * Returns the list of renamed tables.
     *
     * @return array
     */
    public function getRenamedEntities()
    {
        return $this->renamedEntities;
    }

    /**
     * Returns the number of renamed tables.
     *
     * @return integer
     */
    public function countRenamedEntities()
    {
        return count($this->renamedEntities);
    }

    /**
     * Returns the reverse diff for this diff.
     *
     * @return DatabaseDiff
     */
    public function getReverseDiff()
    {
        $diff = new self();
        $diff->setAddedEntities($this->getRemovedEntities());
        // idMethod is not set for tables build from reverse engineering
        // FIXME: this should be handled by reverse classes
        foreach ($diff->getAddedEntities() as $table) {
            if ($table->getIdMethod() == IdMethod::NO_ID_METHOD) {
                $table->setIdMethod(IdMethod::NATIVE);
            }
        }
        $diff->setRemovedEntities($this->getAddedEntities());
        $diff->setRenamedEntities(array_flip($this->getRenamedEntities()));
        $tableDiffs = [];
        foreach ($this->getModifiedEntities() as $name => $tableDiff) {
            $tableDiffs[$name] = $tableDiff->getReverseDiff();
        }
        $diff->setModifiedEntities($tableDiffs);

        return $diff;
    }

    /**
     * Returns a description of the database modifications.
     *
     * @return string
     */
    public function getDescription()
    {
        $changes = [];
        if ($count = $this->countAddedEntities()) {
            $changes[] = sprintf('%d added tables', $count);
        }
        if ($count = $this->countRemovedEntities()) {
            $changes[] = sprintf('%d removed tables', $count);
        }
        if ($count = $this->countModifiedEntities()) {
            $changes[] = sprintf('%d modified tables', $count);
        }
        if ($count = $this->countRenamedEntities()) {
            $changes[] = sprintf('%d renamed tables', $count);
        }

        return implode(', ', $changes);
    }

    public function __toString()
    {
        $ret = '';
        if ($addedEntities = $this->getAddedEntities()) {
            $ret .= "addedEntities:\n";
            foreach ($addedEntities as $tableName => $table) {
                $ret .= sprintf("  - %s\n", $tableName);
            }
        }
        if ($removedEntities = $this->getRemovedEntities()) {
            $ret .= "removedEntities:\n";
            foreach ($removedEntities as $tableName => $table) {
                $ret .= sprintf("  - %s\n", $tableName);
            }
        }
        if ($modifiedEntities = $this->getModifiedEntities()) {
            $ret .= "modifiedEntities:\n";
            foreach ($modifiedEntities as $tableDiff) {
                $ret .= $tableDiff->__toString();
            }
        }
        if ($renamedEntities = $this->getRenamedEntities()) {
            $ret .= "renamedEntities:\n";
            foreach ($renamedEntities as $fromName => $toName) {
                $ret .= sprintf("  %s: %s\n", $fromName, $toName);
            }
        }

        return $ret;
    }
}

<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Model\Diff;

use Propel\Generator\Model\IdMethod;
use Propel\Generator\Model\Table;

/**
 * Value object for storing Database object diffs
 * Heavily inspired by Doctrine2's Migrations
 * (see http://github.com/doctrine/dbal/tree/master/lib/Doctrine/DBAL/Schema/)
 */
class DatabaseDiff
{
    /**
     * @var \Propel\Generator\Model\Table[]
     */
    protected $addedTables;

    /**
     * @var \Propel\Generator\Model\Table[]
     */
    protected $removedTables;

    /**
     * @var \Propel\Generator\Model\Diff\TableDiff[]
     */
    protected $modifiedTables;

    /**
     * @var string[]
     */
    protected $renamedTables;

    /**
     * @var string[]
     */
    protected $possibleRenamedTables;

    public function __construct()
    {
        $this->addedTables = [];
        $this->removedTables = [];
        $this->modifiedTables = [];
        $this->renamedTables = [];
        $this->possibleRenamedTables = [];
    }

    /**
     * Sets the added tables.
     *
     * @param array $tables
     *
     * @return void
     */
    public function setAddedTables($tables)
    {
        $this->addedTables = $tables;
    }

    /**
     * Adds an added table.
     *
     * @param string $name
     * @param \Propel\Generator\Model\Table $table
     *
     * @return void
     */
    public function addAddedTable($name, Table $table)
    {
        $this->addedTables[$name] = $table;
    }

    /**
     * Removes an added table.
     *
     * @param string $name
     *
     * @return void
     */
    public function removeAddedTable($name)
    {
        unset($this->addedTables[$name]);
    }

    /**
     * @return string[]
     */
    public function getPossibleRenamedTables()
    {
        return $this->possibleRenamedTables;
    }

    /**
     * Adds a possible renamed table.
     *
     * @param string $fromName
     * @param string $toName
     *
     * @return void
     */
    public function addPossibleRenamedTable($fromName, $toName)
    {
        $this->possibleRenamedTables[$fromName] = $toName;
    }

    /**
     * Returns the list of added tables.
     *
     * @return \Propel\Generator\Model\Table[]
     */
    public function getAddedTables()
    {
        return $this->addedTables;
    }

    /**
     * Returns the number of added tables.
     *
     * @return int
     */
    public function countAddedTables()
    {
        return count($this->addedTables);
    }

    /**
     * Returns an added table by its name.
     *
     * @param string $name
     *
     * @return \Propel\Generator\Model\Table
     */
    public function getAddedTable($name)
    {
        return $this->addedTables[$name];
    }

    /**
     * Sets the removes tables.
     *
     * @param \Propel\Generator\Model\Table[] $tables
     *
     * @return void
     */
    public function setRemovedTables($tables)
    {
        $this->removedTables = $tables;
    }

    /**
     * Adds a table to remove.
     *
     * @param string $name
     * @param \Propel\Generator\Model\Table $table
     *
     * @return void
     */
    public function addRemovedTable($name, Table $table)
    {
        $this->removedTables[$name] = $table;
    }

    /**
     * Removes a removed table.
     *
     * @param string $name
     *
     * @return void
     */
    public function removeRemovedTable($name)
    {
        unset($this->removedTables[$name]);
    }

    /**
     * Returns the list of removed tables.
     *
     * @return \Propel\Generator\Model\Table[]
     */
    public function getRemovedTables()
    {
        return $this->removedTables;
    }

    /**
     * Returns the number of removed tables.
     *
     * @return int
     */
    public function countRemovedTables()
    {
        return count($this->removedTables);
    }

    /**
     * Returns a removed table.
     *
     * @param string $name
     *
     * @return \Propel\Generator\Model\Table
     */
    public function getRemovedTable($name)
    {
        return $this->removedTables[$name];
    }

    /**
     * Sets the modified tables
     *
     * @param \Propel\Generator\Model\Diff\TableDiff[] $tables
     *
     * @return void
     */
    public function setModifiedTables($tables)
    {
        $this->modifiedTables = $tables;
    }

    /**
     * Adds a table difference.
     *
     * @param string $name
     * @param \Propel\Generator\Model\Diff\TableDiff $difference
     *
     * @return void
     */
    public function addModifiedTable($name, TableDiff $difference)
    {
        $this->modifiedTables[$name] = $difference;
    }

    /**
     * Returns the number of modified tables.
     *
     * @return int
     */
    public function countModifiedTables()
    {
        return count($this->modifiedTables);
    }

    /**
     * Returns the modified tables.
     *
     * @return \Propel\Generator\Model\Diff\TableDiff[]
     */
    public function getModifiedTables()
    {
        return $this->modifiedTables;
    }

    /**
     * Sets the renamed tables.
     *
     * @param string[] $tables
     *
     * @return void
     */
    public function setRenamedTables($tables)
    {
        $this->renamedTables = $tables;
    }

    /**
     * Adds a renamed table.
     *
     * @param string $fromName
     * @param string $toName
     *
     * @return void
     */
    public function addRenamedTable($fromName, $toName)
    {
        $this->renamedTables[$fromName] = $toName;
    }

    /**
     * Returns the list of renamed tables.
     *
     * @return string[]
     */
    public function getRenamedTables()
    {
        return $this->renamedTables;
    }

    /**
     * Returns the number of renamed tables.
     *
     * @return int
     */
    public function countRenamedTables()
    {
        return count($this->renamedTables);
    }

    /**
     * Returns the reverse diff for this diff.
     *
     * @return \Propel\Generator\Model\Diff\DatabaseDiff
     */
    public function getReverseDiff()
    {
        $diff = new self();
        $diff->setAddedTables($this->getRemovedTables());
        // idMethod is not set for tables build from reverse engineering
        // FIXME: this should be handled by reverse classes
        foreach ($diff->getAddedTables() as $table) {
            if ($table->getIdMethod() == IdMethod::NO_ID_METHOD) {
                $table->setIdMethod(IdMethod::NATIVE);
            }
        }
        $diff->setRemovedTables($this->getAddedTables());
        $diff->setRenamedTables(array_flip($this->getRenamedTables()));
        $tableDiffs = [];
        foreach ($this->getModifiedTables() as $name => $tableDiff) {
            $tableDiffs[$name] = $tableDiff->getReverseDiff();
        }
        $diff->setModifiedTables($tableDiffs);

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
        if ($count = $this->countAddedTables()) {
            $changes[] = sprintf('%d added tables', $count);
        }
        if ($count = $this->countRemovedTables()) {
            $changes[] = sprintf('%d removed tables', $count);
        }
        if ($count = $this->countModifiedTables()) {
            $changes[] = sprintf('%d modified tables', $count);
        }
        if ($count = $this->countRenamedTables()) {
            $changes[] = sprintf('%d renamed tables', $count);
        }

        return implode(', ', $changes);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $ret = '';
        if ($addedTables = $this->getAddedTables()) {
            $ret .= "addedTables:\n";
            foreach ($addedTables as $tableName => $table) {
                $ret .= sprintf("  - %s\n", $tableName);
            }
        }
        if ($removedTables = $this->getRemovedTables()) {
            $ret .= "removedTables:\n";
            foreach ($removedTables as $tableName => $table) {
                $ret .= sprintf("  - %s\n", $tableName);
            }
        }
        if ($modifiedTables = $this->getModifiedTables()) {
            $ret .= "modifiedTables:\n";
            foreach ($modifiedTables as $tableDiff) {
                $ret .= $tableDiff->__toString();
            }
        }
        if ($renamedTables = $this->getRenamedTables()) {
            $ret .= "renamedTables:\n";
            foreach ($renamedTables as $fromName => $toName) {
                $ret .= sprintf("  %s: %s\n", $fromName, $toName);
            }
        }

        return $ret;
    }
}

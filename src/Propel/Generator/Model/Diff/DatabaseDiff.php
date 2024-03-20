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
     * @var array<string, \Propel\Generator\Model\Table>
     */
    protected $addedTables;

    /**
     * @var array<string, \Propel\Generator\Model\Table>
     */
    protected $removedTables;

    /**
     * @var array<\Propel\Generator\Model\Diff\TableDiff>
     */
    protected $modifiedTables;

    /**
     * @var array<string, string>
     */
    protected $renamedTables;

    /**
     * @var array<string, string>
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
    public function setAddedTables(array $tables): void
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
    public function addAddedTable(string $name, Table $table): void
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
    public function removeAddedTable(string $name): void
    {
        unset($this->addedTables[$name]);
    }

    /**
     * @return array<string>
     */
    public function getPossibleRenamedTables(): array
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
    public function addPossibleRenamedTable(string $fromName, string $toName): void
    {
        $this->possibleRenamedTables[$fromName] = $toName;
    }

    /**
     * Returns the list of added tables.
     *
     * @return array<\Propel\Generator\Model\Table>
     */
    public function getAddedTables(): array
    {
        return $this->addedTables;
    }

    /**
     * Returns the number of added tables.
     *
     * @return int
     */
    public function countAddedTables(): int
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
    public function getAddedTable(string $name): Table
    {
        return $this->addedTables[$name];
    }

    /**
     * Sets the removes tables.
     *
     * @param array<string, \Propel\Generator\Model\Table> $tables
     *
     * @return void
     */
    public function setRemovedTables(array $tables): void
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
    public function addRemovedTable(string $name, Table $table): void
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
    public function removeRemovedTable(string $name): void
    {
        unset($this->removedTables[$name]);
    }

    /**
     * Returns the list of removed tables.
     *
     * @return array<string, \Propel\Generator\Model\Table>
     */
    public function getRemovedTables(): array
    {
        return $this->removedTables;
    }

    /**
     * Returns the number of removed tables.
     *
     * @return int
     */
    public function countRemovedTables(): int
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
    public function getRemovedTable(string $name): Table
    {
        return $this->removedTables[$name];
    }

    /**
     * Sets the modified tables
     *
     * @param array<string, \Propel\Generator\Model\Diff\TableDiff> $tables
     *
     * @return void
     */
    public function setModifiedTables(array $tables): void
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
    public function addModifiedTable(string $name, TableDiff $difference): void
    {
        $this->modifiedTables[$name] = $difference;
    }

    /**
     * Returns the number of modified tables.
     *
     * @return int
     */
    public function countModifiedTables(): int
    {
        return count($this->modifiedTables);
    }

    /**
     * Returns the modified tables.
     *
     * @return array<string, \Propel\Generator\Model\Diff\TableDiff>
     */
    public function getModifiedTables(): array
    {
        return $this->modifiedTables;
    }

    /**
     * Sets the renamed tables.
     *
     * @param array<string, string> $tables
     *
     * @return void
     */
    public function setRenamedTables(array $tables): void
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
    public function addRenamedTable(string $fromName, string $toName): void
    {
        $this->renamedTables[$fromName] = $toName;
    }

    /**
     * Returns the list of renamed tables.
     *
     * @return array<string, string>
     */
    public function getRenamedTables(): array
    {
        return $this->renamedTables;
    }

    /**
     * Returns the number of renamed tables.
     *
     * @return int
     */
    public function countRenamedTables(): int
    {
        return count($this->renamedTables);
    }

    /**
     * Returns the reverse diff for this diff.
     *
     * @return self
     */
    public function getReverseDiff(): self
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
    public function getDescription(): string
    {
        $numberOfAddedTables = $this->countAddedTables();
        $numberOfRemovedTables = $this->countRemovedTables();
        $numberOfModifiedTables = $this->countModifiedTables();
        $numberOfRenamedTables = $this->countRenamedTables();
        $changes = [];

        if ($numberOfAddedTables) {
            $changes[] = sprintf('%d added tables', $numberOfAddedTables);
        }

        if ($numberOfRemovedTables) {
            $changes[] = sprintf('%d removed tables', $numberOfRemovedTables);
        }

        if ($numberOfModifiedTables) {
            $changes[] = sprintf('%d modified tables', $numberOfModifiedTables);
        }

        if ($numberOfRenamedTables) {
            $changes[] = sprintf('%d renamed tables', $numberOfRenamedTables);
        }

        return implode(', ', $changes);
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        $ret = '';
        $ret = $this->appendAddedTablesToString($ret);
        $ret = $this->appendRemovedTablesToString($ret);
        $ret = $this->appendModifiedTablesToString($ret);

        return $this->appendRenamedTablesToString($ret);
    }

    /**
     * @param string $ret
     *
     * @return string
     */
    protected function appendAddedTablesToString(string $ret): string
    {
        $addedTables = $this->getAddedTables();

        if ($addedTables) {
            $ret .= "addedTables:\n";

            foreach ($addedTables as $tableName => $table) {
                $ret .= sprintf("  - %s\n", $tableName);
            }
        }

        return $ret;
    }

    /**
     * @param string $ret
     *
     * @return string
     */
    protected function appendRemovedTablesToString(string $ret): string
    {
        $removedTables = $this->getRemovedTables();

        if ($removedTables) {
            $ret .= "removedTables:\n";

            foreach ($removedTables as $tableName => $table) {
                $ret .= sprintf("  - %s\n", $tableName);
            }
        }

        return $ret;
    }

    /**
     * @param string $ret
     *
     * @return string
     */
    protected function appendModifiedTablesToString(string $ret): string
    {
        $modifiedTables = $this->getModifiedTables();

        if ($modifiedTables) {
            $ret .= "modifiedTables:\n";

            foreach ($modifiedTables as $tableDiff) {
                $ret .= $tableDiff->__toString();
            }
        }

        return $ret;
    }

    /**
     * @param string $ret
     *
     * @return string
     */
    protected function appendRenamedTablesToString(string $ret): string
    {
        $renamedTables = $this->getRenamedTables();

        if ($renamedTables) {
            $ret .= "renamedTables:\n";

            foreach ($renamedTables as $fromName => $toName) {
                $ret .= sprintf("  %s: %s\n", $fromName, $toName);
            }
        }

        return $ret;
    }
}

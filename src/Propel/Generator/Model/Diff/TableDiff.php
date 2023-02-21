<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Model\Diff;

use Propel\Generator\Exception\DiffException;
use Propel\Generator\Model\Column;
use Propel\Generator\Model\ForeignKey;
use Propel\Generator\Model\Index;
use Propel\Generator\Model\Table;

/**
 * Value object for storing Table object diffs
 * Heavily inspired by Doctrine2's Migrations
 * (see http://github.com/doctrine/dbal/tree/master/lib/Doctrine/DBAL/Schema/)
 */
class TableDiff
{
    /**
     * The first Table object.
     *
     * @var \Propel\Generator\Model\Table|null
     */
    protected $fromTable;

    /**
     * The second Table object.
     *
     * @var \Propel\Generator\Model\Table|null
     */
    protected $toTable;

    /**
     * The list of added columns.
     *
     * @var array<\Propel\Generator\Model\Column>
     */
    protected $addedColumns;

    /**
     * The list of removed columns.
     *
     * @var array<\Propel\Generator\Model\Column>
     */
    protected $removedColumns;

    /**
     * The list of modified columns.
     *
     * @var array<\Propel\Generator\Model\Diff\ColumnDiff>
     */
    protected $modifiedColumns;

    /**
     * The list of renamed columns.
     *
     * @var array
     */
    protected $renamedColumns;

    /**
     * The list of added primary key columns.
     *
     * @var array<\Propel\Generator\Model\Column>
     */
    protected $addedPkColumns;

    /**
     * The list of removed primary key columns.
     *
     * @var array<\Propel\Generator\Model\Column>
     */
    protected $removedPkColumns;

    /**
     * The list of renamed primary key columns.
     *
     * @var array
     */
    protected $renamedPkColumns;

    /**
     * The list of added indices.
     *
     * @var array
     */
    protected $addedIndices;

    /**
     * The list of removed indices.
     *
     * @var array
     */
    protected $removedIndices;

    /**
     * The list of modified indices.
     *
     * @var array
     */
    protected $modifiedIndices;

    /**
     * The list of added foreign keys.
     *
     * @var array
     */
    protected $addedFks;

    /**
     * The list of removed foreign keys.
     *
     * @var array<\Propel\Generator\Model\ForeignKey>
     */
    protected $removedFks;

    /**
     * The list of modified columns.
     *
     * @var array
     */
    protected $modifiedFks;

    /**
     * Constructor.
     *
     * @param \Propel\Generator\Model\Table|null $fromTable The first table
     * @param \Propel\Generator\Model\Table|null $toTable The second table
     */
    public function __construct(?Table $fromTable = null, ?Table $toTable = null)
    {
        if ($fromTable !== null) {
            $this->setFromTable($fromTable);
        }

        if ($toTable !== null) {
            $this->setToTable($toTable);
        }

        $this->addedColumns = [];
        $this->removedColumns = [];
        $this->modifiedColumns = [];
        $this->renamedColumns = [];
        $this->addedPkColumns = [];
        $this->removedPkColumns = [];
        $this->renamedPkColumns = [];
        $this->addedIndices = [];
        $this->modifiedIndices = [];
        $this->removedIndices = [];
        $this->addedFks = [];
        $this->modifiedFks = [];
        $this->removedFks = [];
    }

    /**
     * Sets the fromTable property.
     *
     * @param \Propel\Generator\Model\Table $fromTable
     *
     * @return void
     */
    public function setFromTable(Table $fromTable): void
    {
        $this->fromTable = $fromTable;
    }

    /**
     * Returns the fromTable property.
     *
     * @return \Propel\Generator\Model\Table|null
     */
    public function getFromTable(): ?Table
    {
        return $this->fromTable;
    }

    /**
     * Sets the toTable property.
     *
     * @param \Propel\Generator\Model\Table $toTable
     *
     * @return void
     */
    public function setToTable(Table $toTable): void
    {
        $this->toTable = $toTable;
    }

    /**
     * Returns the toTable property.
     *
     * @return \Propel\Generator\Model\Table|null
     */
    public function getToTable(): ?Table
    {
        return $this->toTable;
    }

    /**
     * Sets the added columns.
     *
     * @param array<\Propel\Generator\Model\Column> $columns
     *
     * @return void
     */
    public function setAddedColumns(array $columns): void
    {
        $this->addedColumns = [];
        foreach ($columns as $column) {
            $this->addAddedColumn($column->getName(), $column);
        }
    }

    /**
     * Adds an added column.
     *
     * @param string $name
     * @param \Propel\Generator\Model\Column $column
     *
     * @return void
     */
    public function addAddedColumn(string $name, Column $column): void
    {
        $this->addedColumns[$name] = $column;
    }

    /**
     * Removes an added column.
     *
     * @param string $columnName
     *
     * @return void
     */
    public function removeAddedColumn(string $columnName): void
    {
        unset($this->addedColumns[$columnName]);
    }

    /**
     * Returns the list of added columns
     *
     * @return array<\Propel\Generator\Model\Column>
     */
    public function getAddedColumns(): array
    {
        return $this->addedColumns;
    }

    /**
     * Returns an added column by its name.
     *
     * @param string $columnName
     *
     * @return \Propel\Generator\Model\Column|null
     */
    public function getAddedColumn(string $columnName): ?Column
    {
        if (isset($this->addedColumns[$columnName])) {
            return $this->addedColumns[$columnName];
        }

        return null;
    }

    /**
     * Setter for the removedColumns property
     *
     * @param array<\Propel\Generator\Model\Column> $removedColumns
     *
     * @return void
     */
    public function setRemovedColumns(array $removedColumns): void
    {
        $this->removedColumns = [];
        foreach ($removedColumns as $removedColumn) {
            $this->addRemovedColumn($removedColumn->getName(), $removedColumn);
        }
    }

    /**
     * Adds a removed column.
     *
     * @param string $columnName
     * @param \Propel\Generator\Model\Column $removedColumn
     *
     * @return void
     */
    public function addRemovedColumn(string $columnName, Column $removedColumn): void
    {
        $this->removedColumns[$columnName] = $removedColumn;
    }

    /**
     * Removes a removed column.
     *
     * @param string $columnName
     *
     * @return void
     */
    public function removeRemovedColumn(string $columnName): void
    {
        unset($this->removedColumns[$columnName]);
    }

    /**
     * Getter for the removedColumns property.
     *
     * @return array<\Propel\Generator\Model\Column>
     */
    public function getRemovedColumns(): array
    {
        return $this->removedColumns;
    }

    /**
     * Get a removed column
     *
     * @param string $columnName
     *
     * @return \Propel\Generator\Model\Column|null
     */
    public function getRemovedColumn(string $columnName): ?Column
    {
        if (isset($this->removedColumns[$columnName])) {
            return $this->removedColumns[$columnName];
        }

        return null;
    }

    /**
     * Sets the list of modified columns.
     *
     * @param array<\Propel\Generator\Model\Diff\ColumnDiff> $modifiedColumns An associative array of ColumnDiff objects
     *
     * @return void
     */
    public function setModifiedColumns(array $modifiedColumns): void
    {
        $this->modifiedColumns = [];
        foreach ($modifiedColumns as $columnName => $modifiedColumn) {
            $this->addModifiedColumn($columnName, $modifiedColumn);
        }
    }

    /**
     * Add a column difference
     *
     * @param string $columnName
     * @param \Propel\Generator\Model\Diff\ColumnDiff $modifiedColumn
     *
     * @return void
     */
    public function addModifiedColumn(string $columnName, ColumnDiff $modifiedColumn): void
    {
        $this->modifiedColumns[$columnName] = $modifiedColumn;
    }

    /**
     * Getter for the modifiedColumns property
     *
     * @return array<\Propel\Generator\Model\Diff\ColumnDiff>
     */
    public function getModifiedColumns(): array
    {
        return $this->modifiedColumns;
    }

    /**
     * Sets the list of renamed columns.
     *
     * @param array $renamedColumns
     *
     * @return void
     */
    public function setRenamedColumns(array $renamedColumns): void
    {
        $this->renamedColumns = [];
        foreach ($renamedColumns as $columns) {
            [$fromColumn, $toColumn] = $columns;
            $this->addRenamedColumn($fromColumn, $toColumn);
        }
    }

    /**
     * Add a renamed column
     *
     * @param \Propel\Generator\Model\Column $fromColumn
     * @param \Propel\Generator\Model\Column $toColumn
     *
     * @return void
     */
    public function addRenamedColumn(Column $fromColumn, Column $toColumn): void
    {
        $this->renamedColumns[] = [$fromColumn, $toColumn];
    }

    /**
     * Getter for the renamedColumns property
     *
     * @return array
     */
    public function getRenamedColumns(): array
    {
        return $this->renamedColumns;
    }

    /**
     * Sets the list of added primary key columns.
     *
     * @param array<\Propel\Generator\Model\Column> $addedPkColumns
     *
     * @return void
     */
    public function setAddedPkColumns(array $addedPkColumns): void
    {
        $this->addedPkColumns = [];
        foreach ($addedPkColumns as $addedPkColumn) {
            $this->addAddedPkColumn($addedPkColumn->getName(), $addedPkColumn);
        }
    }

    /**
     * Add an added Pk column
     *
     * @param string $columnName
     * @param \Propel\Generator\Model\Column $addedPkColumn
     *
     * @throws \Propel\Generator\Exception\DiffException
     *
     * @return void
     */
    public function addAddedPkColumn(string $columnName, Column $addedPkColumn): void
    {
        if (!$addedPkColumn->isPrimaryKey()) {
            throw new DiffException(sprintf('Column `%s` is not a valid primary key column.', $columnName));
        }

        $this->addedPkColumns[$columnName] = $addedPkColumn;
    }

    /**
     * Removes an added primary key column.
     *
     * @param string $columnName
     *
     * @return void
     */
    public function removeAddedPkColumn(string $columnName): void
    {
        unset($this->addedPkColumns[$columnName]);
    }

    /**
     * Getter for the addedPkColumns property
     *
     * @return array
     */
    public function getAddedPkColumns(): array
    {
        return $this->addedPkColumns;
    }

    /**
     * Sets the list of removed primary key columns.
     *
     * @param array<\Propel\Generator\Model\Column> $removedPkColumns
     *
     * @return void
     */
    public function setRemovedPkColumns(array $removedPkColumns): void
    {
        $this->removedPkColumns = [];
        foreach ($removedPkColumns as $removedPkColumn) {
            $this->addRemovedPkColumn($removedPkColumn->getName(), $removedPkColumn);
        }
    }

    /**
     * Add a removed Pk column
     *
     * @param string $columnName
     * @param \Propel\Generator\Model\Column $removedPkColumn
     *
     * @return void
     */
    public function addRemovedPkColumn(string $columnName, Column $removedPkColumn): void
    {
        $this->removedPkColumns[$columnName] = $removedPkColumn;
    }

    /**
     * Removes a removed primary key column.
     *
     * @param string $columnName
     *
     * @return void
     */
    public function removeRemovedPkColumn(string $columnName): void
    {
        unset($this->removedPkColumns[$columnName]);
    }

    /**
     * Getter for the removedPkColumns property
     *
     * @return array
     */
    public function getRemovedPkColumns(): array
    {
        return $this->removedPkColumns;
    }

    /**
     * Sets the list of all renamed primary key columns.
     *
     * @param array<array<\Propel\Generator\Model\Column>> $renamedPkColumns
     *
     * @return void
     */
    public function setRenamedPkColumns(array $renamedPkColumns): void
    {
        $this->renamedPkColumns = [];
        foreach ($renamedPkColumns as $columns) {
            [$fromColumn, $toColumn] = $columns;
            $this->addRenamedPkColumn($fromColumn, $toColumn);
        }
    }

    /**
     * Adds a renamed primary key column.
     *
     * @param \Propel\Generator\Model\Column $fromColumn The original column
     * @param \Propel\Generator\Model\Column $toColumn The renamed column
     *
     * @return void
     */
    public function addRenamedPkColumn(Column $fromColumn, Column $toColumn): void
    {
        $this->renamedPkColumns[] = [$fromColumn, $toColumn];
    }

    /**
     * Getter for the renamedPkColumns property
     *
     * @return array
     */
    public function getRenamedPkColumns(): array
    {
        return $this->renamedPkColumns;
    }

    /**
     * Whether the primary key was modified
     *
     * @return bool
     */
    public function hasModifiedPk(): bool
    {
        return $this->renamedPkColumns || $this->removedPkColumns || $this->addedPkColumns;
    }

    /**
     * Sets the list of new added indices.
     *
     * @param array<\Propel\Generator\Model\Index> $addedIndices
     *
     * @return void
     */
    public function setAddedIndices(array $addedIndices): void
    {
        $this->addedIndices = [];
        foreach ($addedIndices as $addedIndex) {
            $this->addAddedIndex($addedIndex->getName(), $addedIndex);
        }
    }

    /**
     * Add an added index.
     *
     * @param string $indexName
     * @param \Propel\Generator\Model\Index $addedIndex
     *
     * @return void
     */
    public function addAddedIndex(string $indexName, Index $addedIndex): void
    {
        $this->addedIndices[$indexName] = $addedIndex;
    }

    /**
     * Getter for the addedIndices property
     *
     * @return array<\Propel\Generator\Model\Index>
     */
    public function getAddedIndices(): array
    {
        return $this->addedIndices;
    }

    /**
     * Sets the list of removed indices.
     *
     * @param array<\Propel\Generator\Model\Index> $removedIndices
     *
     * @return void
     */
    public function setRemovedIndices(array $removedIndices): void
    {
        $this->removedIndices = [];
        foreach ($removedIndices as $removedIndex) {
            $this->addRemovedIndex($removedIndex->getName(), $removedIndex);
        }
    }

    /**
     * Adds a removed index.
     *
     * @param string $indexName
     * @param \Propel\Generator\Model\Index $removedIndex
     *
     * @return void
     */
    public function addRemovedIndex(string $indexName, Index $removedIndex): void
    {
        $this->removedIndices[$indexName] = $removedIndex;
    }

    /**
     * Getter for the removedIndices property
     *
     * @return array<\Propel\Generator\Model\Index>
     */
    public function getRemovedIndices(): array
    {
        return $this->removedIndices;
    }

    /**
     * Sets the list of modified indices.
     *
     * Array must be [ [ Index $fromIndex, Index $toIndex ], [ ... ] ]
     *
     * @param array<array<\Propel\Generator\Model\Index>> $modifiedIndices An array of modified indices
     *
     * @return void
     */
    public function setModifiedIndices(array $modifiedIndices): void
    {
        $this->modifiedIndices = [];
        foreach ($modifiedIndices as $indices) {
            [$fromIndex, $toIndex] = $indices;
            $this->addModifiedIndex($fromIndex->getName(), $fromIndex, $toIndex);
        }
    }

    /**
     * Add a modified index.
     *
     * @param string $indexName
     * @param \Propel\Generator\Model\Index $fromIndex
     * @param \Propel\Generator\Model\Index $toIndex
     *
     * @return void
     */
    public function addModifiedIndex(string $indexName, Index $fromIndex, Index $toIndex): void
    {
        $this->modifiedIndices[$indexName] = [$fromIndex, $toIndex];
    }

    /**
     * Getter for the modifiedIndices property
     *
     * @return array
     */
    public function getModifiedIndices(): array
    {
        return $this->modifiedIndices;
    }

    /**
     * Sets the list of added foreign keys.
     *
     * @param array<\Propel\Generator\Model\ForeignKey> $addedFks
     *
     * @return void
     */
    public function setAddedFks(array $addedFks): void
    {
        $this->addedFks = [];
        foreach ($addedFks as $addedFk) {
            $this->addAddedFk($addedFk->getName(), $addedFk);
        }
    }

    /**
     * Adds an added foreign key.
     *
     * @param string $fkName
     * @param \Propel\Generator\Model\ForeignKey $addedFk
     *
     * @return void
     */
    public function addAddedFk(string $fkName, ForeignKey $addedFk): void
    {
        $this->addedFks[$fkName] = $addedFk;
    }

    /**
     * Remove an added Fk column
     *
     * @param string $fkName
     *
     * @return void
     */
    public function removeAddedFk(string $fkName): void
    {
        unset($this->addedFks[$fkName]);
    }

    /**
     * Getter for the addedFks property
     *
     * @return array<\Propel\Generator\Model\ForeignKey>
     */
    public function getAddedFks(): array
    {
        return $this->addedFks;
    }

    /**
     * Sets the list of removed foreign keys.
     *
     * @param array<\Propel\Generator\Model\ForeignKey> $removedFks
     *
     * @return void
     */
    public function setRemovedFks(array $removedFks): void
    {
        $this->removedFks = [];
        foreach ($removedFks as $removedFk) {
            $this->addRemovedFk($removedFk->getName(), $removedFk);
        }
    }

    /**
     * Adds a removed foreign key column.
     *
     * @param string $fkName
     * @param \Propel\Generator\Model\ForeignKey $removedFk
     *
     * @return void
     */
    public function addRemovedFk(string $fkName, ForeignKey $removedFk): void
    {
        $this->removedFks[$fkName] = $removedFk;
    }

    /**
     * Removes a removed foreign key.
     *
     * @param string $fkName
     *
     * @return void
     */
    public function removeRemovedFk(string $fkName): void
    {
        unset($this->removedFks[$fkName]);
    }

    /**
     * Returns the list of removed foreign keys.
     *
     * @return array<\Propel\Generator\Model\ForeignKey>
     */
    public function getRemovedFks(): array
    {
        return $this->removedFks;
    }

    /**
     * Sets the list of modified foreign keys.
     *
     * Array must be [ [ ForeignKey $fromFk, ForeignKey $toFk ], [ ... ] ]
     *
     * @param array<array<\Propel\Generator\Model\ForeignKey>> $modifiedFks
     *
     * @return void
     */
    public function setModifiedFks(array $modifiedFks): void
    {
        $this->modifiedFks = [];
        foreach ($modifiedFks as $foreignKeys) {
            [$fromForeignKey, $toForeignKey] = $foreignKeys;
            $this->addModifiedFk($fromForeignKey->getName(), $fromForeignKey, $toForeignKey);
        }
    }

    /**
     * Adds a modified foreign key.
     *
     * @param string $fkName
     * @param \Propel\Generator\Model\ForeignKey $fromFk
     * @param \Propel\Generator\Model\ForeignKey $toFk
     *
     * @return void
     */
    public function addModifiedFk(string $fkName, ForeignKey $fromFk, ForeignKey $toFk): void
    {
        $this->modifiedFks[$fkName] = [$fromFk, $toFk];
    }

    /**
     * Returns the list of modified foreign keys.
     *
     * @return array
     */
    public function getModifiedFks(): array
    {
        return $this->modifiedFks;
    }

    /**
     * Returns whether there are
     * some modified foreign keys.
     *
     * @return bool
     */
    public function hasModifiedFks(): bool
    {
        return (bool)$this->modifiedFks;
    }

    /**
     * Returns whether there are
     * some modified indices.
     *
     * @return bool
     */
    public function hasModifiedIndices(): bool
    {
        return (bool)$this->modifiedIndices;
    }

    /**
     * Returns whether there are
     * some modified columns.
     *
     * @return bool
     */
    public function hasModifiedColumns(): bool
    {
        return (bool)$this->modifiedColumns;
    }

    /**
     * Returns whether there are
     * some removed foreign keys.
     *
     * @return bool
     */
    public function hasRemovedFks(): bool
    {
        return (bool)$this->removedFks;
    }

    /**
     * Returns whether there are
     * some removed indices.
     *
     * @return bool
     */
    public function hasRemovedIndices(): bool
    {
        return (bool)$this->removedIndices;
    }

    /**
     * Returns whether there are
     * some renamed columns.
     *
     * @return bool
     */
    public function hasRenamedColumns(): bool
    {
        return (bool)$this->renamedColumns;
    }

    /**
     * Returns whether there are
     * some removed columns.
     *
     * @return bool
     */
    public function hasRemovedColumns(): bool
    {
        return (bool)$this->removedColumns;
    }

    /**
     * Returns whether there are
     * some added columns.
     *
     * @return bool
     */
    public function hasAddedColumns(): bool
    {
        return (bool)$this->addedColumns;
    }

    /**
     * Returns whether there are
     * some added indices.
     *
     * @return bool
     */
    public function hasAddedIndices(): bool
    {
        return (bool)$this->addedIndices;
    }

    /**
     * Returns whether there are
     * some added foreign keys.
     *
     * @return bool
     */
    public function hasAddedFks(): bool
    {
        return (bool)$this->addedFks;
    }

    /**
     * Returns whether there are
     * some added primary key columns.
     *
     * @return bool
     */
    public function hasAddedPkColumns(): bool
    {
        return (bool)$this->addedPkColumns;
    }

    /**
     * Returns whether there are
     * some removed primary key columns.
     *
     * @return bool
     */
    public function hasRemovedPkColumns(): bool
    {
        return (bool)$this->removedPkColumns;
    }

    /**
     * Returns whether there are
     * some renamed primary key columns.
     *
     * @return bool
     */
    public function hasRenamedPkColumns(): bool
    {
        return (bool)$this->renamedPkColumns;
    }

    /**
     * Returns the reverse diff for this diff.
     *
     * @return self
     */
    public function getReverseDiff(): self
    {
        $diff = new self();

        // tables
        $diff->setFromTable($this->toTable);
        $diff->setToTable($this->fromTable);

        // columns
        if ($this->hasAddedColumns()) {
            $diff->setRemovedColumns($this->addedColumns);
        }

        if ($this->hasRemovedColumns()) {
            $diff->setAddedColumns($this->removedColumns);
        }

        if ($this->hasRenamedColumns()) {
            $renamedColumns = [];
            foreach ($this->renamedColumns as $columnRenaming) {
                $renamedColumns[] = array_reverse($columnRenaming);
            }
            $diff->setRenamedColumns($renamedColumns);
        }

        if ($this->hasModifiedColumns()) {
            $columnDiffs = [];
            foreach ($this->modifiedColumns as $name => $columnDiff) {
                $columnDiffs[$name] = $columnDiff->getReverseDiff();
            }
            $diff->setModifiedColumns($columnDiffs);
        }

        // pks
        if ($this->hasRemovedPkColumns()) {
            $diff->setAddedPkColumns($this->removedPkColumns);
        }

        if ($this->hasAddedPkColumns()) {
            $diff->setRemovedPkColumns($this->addedPkColumns);
        }

        if ($this->hasRenamedPkColumns()) {
            $renamedPkColumns = [];
            foreach ($this->renamedPkColumns as $columnRenaming) {
                $renamedPkColumns[] = array_reverse($columnRenaming);
            }
            $diff->setRenamedPkColumns($renamedPkColumns);
        }

        // indices
        if ($this->hasRemovedIndices()) {
            $diff->setAddedIndices($this->removedIndices);
        }

        if ($this->hasAddedIndices()) {
            $diff->setRemovedIndices($this->addedIndices);
        }

        if ($this->hasModifiedIndices()) {
            $indexDiffs = [];
            foreach ($this->modifiedIndices as $name => $indexDiff) {
                $indexDiffs[$name] = array_reverse($indexDiff);
            }
            $diff->setModifiedIndices($indexDiffs);
        }

        // fks
        if ($this->hasAddedFks()) {
            $diff->setRemovedFks($this->addedFks);
        }

        if ($this->hasRemovedFks()) {
            $diff->setAddedFks($this->removedFks);
        }

        if ($this->hasModifiedFks()) {
            $fkDiffs = [];
            foreach ($this->modifiedFks as $name => $fkDiff) {
                $fkDiffs[$name] = array_reverse($fkDiff);
            }
            $diff->setModifiedFks($fkDiffs);
        }

        return $diff;
    }

    /**
     * Clones the current diff object.
     *
     * @return void
     */
    public function __clone()
    {
        if ($this->fromTable) {
            $this->fromTable = clone $this->fromTable;
        }
        if ($this->toTable) {
            $this->toTable = clone $this->toTable;
        }
    }

    /**
     * Returns the string representation of this object.
     *
     * @return string
     */
    public function __toString(): string
    {
        $ret = sprintf("  %s:\n", $this->fromTable->getName());
        $ret = $this->appendAddedColumnsToString($ret);
        $ret = $this->appendRemovedColumnsToString($ret);
        $ret = $this->appendModifiedColumnsToString($ret);
        $ret = $this->appendRenamedColumnsToString($ret);
        $ret = $this->appendAddedIndicesToString($ret);
        $ret = $this->appendRemovedIndicesToString($ret);
        $ret = $this->appendModifiedIndicesToString($ret);
        $ret = $this->appendAddedFksToString($ret);
        $ret = $this->appendRemovedFksToString($ret);

        return $this->appendModifiedFksToString($ret);
    }

    /**
     * @param string $ret
     *
     * @return string
     */
    protected function appendAddedColumnsToString(string $ret): string
    {
        $addedColumns = $this->getAddedColumns();

        if ($addedColumns) {
            $ret .= "    addedColumns:\n";

            foreach ($addedColumns as $colname => $column) {
                $ret .= sprintf("      - %s\n", $colname);
            }
        }

        return $ret;
    }

    /**
     * @param string $ret
     *
     * @return string
     */
    protected function appendRemovedColumnsToString(string $ret): string
    {
        $removedColumns = $this->getRemovedColumns();

        if ($removedColumns) {
            $ret .= "    removedColumns:\n";

            foreach ($removedColumns as $colname => $column) {
                $ret .= sprintf("      - %s\n", $colname);
            }
        }

        return $ret;
    }

    /**
     * @param string $ret
     *
     * @return string
     */
    protected function appendModifiedColumnsToString(string $ret): string
    {
        $modifiedColumns = $this->getModifiedColumns();

        if ($modifiedColumns) {
            $ret .= "    modifiedColumns:\n";

            foreach ($modifiedColumns as $colDiff) {
                $ret .= (string)$colDiff;
            }
        }

        return $ret;
    }

    /**
     * @param string $ret
     *
     * @return string
     */
    protected function appendRenamedColumnsToString(string $ret): string
    {
        $renamedColumns = $this->getRenamedColumns();

        if ($renamedColumns) {
            $ret .= "    renamedColumns:\n";

            foreach ($renamedColumns as $columnRenaming) {
                [$fromColumn, $toColumn] = $columnRenaming;
                $ret .= sprintf("      %s: %s\n", $fromColumn->getName(), $toColumn->getName());
            }
        }

        return $ret;
    }

    /**
     * @param string $ret
     *
     * @return string
     */
    protected function appendAddedIndicesToString(string $ret): string
    {
        $addedIndices = $this->getAddedIndices();

        if ($addedIndices) {
            $ret .= "    addedIndices:\n";

            foreach ($addedIndices as $indexName => $index) {
                $ret .= sprintf("      - %s\n", $indexName);
            }
        }

        return $ret;
    }

    /**
     * @param string $ret
     *
     * @return string
     */
    protected function appendRemovedIndicesToString(string $ret): string
    {
        $removedIndices = $this->getRemovedIndices();

        if ($removedIndices) {
            $ret .= "    removedIndices:\n";

            foreach ($removedIndices as $indexName => $index) {
                $ret .= sprintf("      - %s\n", $indexName);
            }
        }

        return $ret;
    }

    /**
     * @param string $ret
     *
     * @return string
     */
    protected function appendModifiedIndicesToString(string $ret): string
    {
        $modifiedIndices = $this->getModifiedIndices();

        if ($modifiedIndices) {
            $ret .= "    modifiedIndices:\n";

            foreach ($modifiedIndices as $indexName => $indexDiff) {
                $ret .= sprintf("      - %s\n", $indexName);
            }
        }

        return $ret;
    }

    /**
     * @param string $ret
     *
     * @return string
     */
    protected function appendAddedFksToString(string $ret): string
    {
        $addedFks = $this->getAddedFks();

        if ($addedFks) {
            $ret .= "    addedFks:\n";

            foreach ($addedFks as $fkName => $fk) {
                $ret .= sprintf("      - %s\n", $fkName);
            }
        }

        return $ret;
    }

    /**
     * @param string $ret
     *
     * @return string
     */
    protected function appendRemovedFksToString(string $ret): string
    {
        $removedFks = $this->getRemovedFks();

        if ($removedFks) {
            $ret .= "    removedFks:\n";

            foreach ($removedFks as $fkName => $fk) {
                $ret .= sprintf("      - %s\n", $fkName);
            }
        }

        return $ret;
    }

    /**
     * @param string $ret
     *
     * @return string
     */
    protected function appendModifiedFksToString(string $ret): string
    {
        $modifiedFks = $this->getModifiedFks();

        if ($modifiedFks) {
            $ret .= "    modifiedFks:\n";

            foreach ($modifiedFks as $fkName => $fkFromTo) {
                $ret .= sprintf("      %s:\n", $fkName);
                [$fromFk, $toFk] = $fkFromTo;
                $fromLocalColumns = json_encode($fromFk->getLocalColumns());
                $toLocalColumns = json_encode($toFk->getLocalColumns());

                if ($fromLocalColumns != $toLocalColumns) {
                    $ret .= sprintf("          localColumns: from %s to %s\n", $fromLocalColumns, $toLocalColumns);
                }

                $fromForeignColumns = json_encode($fromFk->getForeignColumns());
                $toForeignColumns = json_encode($toFk->getForeignColumns());

                if ($fromForeignColumns != $toForeignColumns) {
                    $ret .= sprintf("          foreignColumns: from %s to %s\n", $fromForeignColumns, $toForeignColumns);
                }

                if ($fromFk->normalizeFKey($fromFk->getOnUpdate()) != $toFk->normalizeFKey($toFk->getOnUpdate())) {
                    $ret .= sprintf("          onUpdate: from %s to %s\n", $fromFk->getOnUpdate(), $toFk->getOnUpdate());
                }

                if ($fromFk->normalizeFKey($fromFk->getOnDelete()) != $toFk->normalizeFKey($toFk->getOnDelete())) {
                    $ret .= sprintf("          onDelete: from %s to %s\n", $fromFk->getOnDelete(), $toFk->getOnDelete());
                }
            }
        }

        return $ret;
    }
}

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
     * @var \Propel\Generator\Model\Column[]
     */
    protected $addedColumns;

    /**
     * The list of removed columns.
     *
     * @var \Propel\Generator\Model\Column[]
     */
    protected $removedColumns;

    /**
     * The list of modified columns.
     *
     * @var \Propel\Generator\Model\Diff\ColumnDiff[]
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
     * @var \Propel\Generator\Model\Column[]
     */
    protected $addedPkColumns;

    /**
     * The list of removed primary key columns.
     *
     * @var \Propel\Generator\Model\Column[]
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
     * @var \Propel\Generator\Model\ForeignKey[]
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
    public function setFromTable(Table $fromTable)
    {
        $this->fromTable = $fromTable;
    }

    /**
     * Returns the fromTable property.
     *
     * @return \Propel\Generator\Model\Table|null
     */
    public function getFromTable()
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
    public function setToTable(Table $toTable)
    {
        $this->toTable = $toTable;
    }

    /**
     * Returns the toTable property.
     *
     * @return \Propel\Generator\Model\Table
     */
    public function getToTable()
    {
        return $this->toTable;
    }

    /**
     * Sets the added columns.
     *
     * @param \Propel\Generator\Model\Column[] $columns
     *
     * @return void
     */
    public function setAddedColumns(array $columns)
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
    public function addAddedColumn($name, Column $column)
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
    public function removeAddedColumn($columnName)
    {
        if (isset($this->addedColumns[$columnName])) {
            unset($this->addedColumns[$columnName]);
        }
    }

    /**
     * Returns the list of added columns
     *
     * @return \Propel\Generator\Model\Column[]
     */
    public function getAddedColumns()
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
    public function getAddedColumn($columnName)
    {
        if (isset($this->addedColumns[$columnName])) {
            return $this->addedColumns[$columnName];
        }

        return null;
    }

    /**
     * Setter for the removedColumns property
     *
     * @param \Propel\Generator\Model\Column[] $removedColumns
     *
     * @return void
     */
    public function setRemovedColumns(array $removedColumns)
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
    public function addRemovedColumn($columnName, Column $removedColumn)
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
    public function removeRemovedColumn($columnName)
    {
        unset($this->removedColumns[$columnName]);
    }

    /**
     * Getter for the removedColumns property.
     *
     * @return \Propel\Generator\Model\Column[]
     */
    public function getRemovedColumns()
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
    public function getRemovedColumn($columnName)
    {
        if (isset($this->removedColumns[$columnName])) {
            return $this->removedColumns[$columnName];
        }

        return null;
    }

    /**
     * Sets the list of modified columns.
     *
     * @param \Propel\Generator\Model\Diff\ColumnDiff[] $modifiedColumns An associative array of ColumnDiff objects
     *
     * @return void
     */
    public function setModifiedColumns(array $modifiedColumns)
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
    public function addModifiedColumn($columnName, ColumnDiff $modifiedColumn)
    {
        $this->modifiedColumns[$columnName] = $modifiedColumn;
    }

    /**
     * Getter for the modifiedColumns property
     *
     * @return \Propel\Generator\Model\Diff\ColumnDiff[]
     */
    public function getModifiedColumns()
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
    public function setRenamedColumns(array $renamedColumns)
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
    public function addRenamedColumn(Column $fromColumn, Column $toColumn)
    {
        $this->renamedColumns[] = [ $fromColumn, $toColumn ];
    }

    /**
     * Getter for the renamedColumns property
     *
     * @return array
     */
    public function getRenamedColumns()
    {
        return $this->renamedColumns;
    }

    /**
     * Sets the list of added primary key columns.
     *
     * @param \Propel\Generator\Model\Column[] $addedPkColumns
     *
     * @return void
     */
    public function setAddedPkColumns(array $addedPkColumns)
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
    public function addAddedPkColumn($columnName, Column $addedPkColumn)
    {
        if (!$addedPkColumn->isPrimaryKey()) {
            throw new DiffException(sprintf('Column %s is not a valid primary key column.', $columnName));
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
    public function removeAddedPkColumn($columnName)
    {
        if (isset($this->addedPkColumns[$columnName])) {
            unset($this->addedPkColumns[$columnName]);
        }
    }

    /**
     * Getter for the addedPkColumns property
     *
     * @return array
     */
    public function getAddedPkColumns()
    {
        return $this->addedPkColumns;
    }

    /**
     * Sets the list of removed primary key columns.
     *
     * @param \Propel\Generator\Model\Column[] $removedPkColumns
     *
     * @return void
     */
    public function setRemovedPkColumns(array $removedPkColumns)
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
    public function addRemovedPkColumn($columnName, Column $removedPkColumn)
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
    public function removeRemovedPkColumn($columnName)
    {
        if (isset($this->removedPkColumns[$columnName])) {
            unset($this->removedPkColumns[$columnName]);
        }
    }

    /**
     * Getter for the removedPkColumns property
     *
     * @return array
     */
    public function getRemovedPkColumns()
    {
        return $this->removedPkColumns;
    }

    /**
     * Sets the list of all renamed primary key columns.
     *
     * @param \Propel\Generator\Model\Column[][] $renamedPkColumns
     *
     * @return void
     */
    public function setRenamedPkColumns(array $renamedPkColumns)
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
    public function addRenamedPkColumn(Column $fromColumn, Column $toColumn)
    {
        $this->renamedPkColumns[] = [ $fromColumn, $toColumn ];
    }

    /**
     * Getter for the renamedPkColumns property
     *
     * @return array
     */
    public function getRenamedPkColumns()
    {
        return $this->renamedPkColumns;
    }

    /**
     * Whether the primary key was modified
     *
     * @return bool
     */
    public function hasModifiedPk()
    {
        return $this->renamedPkColumns || $this->removedPkColumns || $this->addedPkColumns;
    }

    /**
     * Sets the list of new added indices.
     *
     * @param \Propel\Generator\Model\Index[] $addedIndices
     *
     * @return void
     */
    public function setAddedIndices(array $addedIndices)
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
    public function addAddedIndex($indexName, Index $addedIndex)
    {
        $this->addedIndices[$indexName] = $addedIndex;
    }

    /**
     * Getter for the addedIndices property
     *
     * @return \Propel\Generator\Model\Index[]
     */
    public function getAddedIndices()
    {
        return $this->addedIndices;
    }

    /**
     * Sets the list of removed indices.
     *
     * @param \Propel\Generator\Model\Index[] $removedIndices
     *
     * @return void
     */
    public function setRemovedIndices(array $removedIndices)
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
    public function addRemovedIndex($indexName, Index $removedIndex)
    {
        $this->removedIndices[$indexName] = $removedIndex;
    }

    /**
     * Getter for the removedIndices property
     *
     * @return \Propel\Generator\Model\Index[]
     */
    public function getRemovedIndices()
    {
        return $this->removedIndices;
    }

    /**
     * Sets the list of modified indices.
     *
     * Array must be [ [ Index $fromIndex, Index $toIndex ], [ ... ] ]
     *
     * @param \Propel\Generator\Model\Index[][] $modifiedIndices An aray of modified indices
     *
     * @return void
     */
    public function setModifiedIndices(array $modifiedIndices)
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
    public function addModifiedIndex($indexName, Index $fromIndex, Index $toIndex)
    {
        $this->modifiedIndices[$indexName] = [ $fromIndex, $toIndex ];
    }

    /**
     * Getter for the modifiedIndices property
     *
     * @return array
     */
    public function getModifiedIndices()
    {
        return $this->modifiedIndices;
    }

    /**
     * Sets the list of added foreign keys.
     *
     * @param \Propel\Generator\Model\ForeignKey[] $addedFks
     *
     * @return void
     */
    public function setAddedFks(array $addedFks)
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
    public function addAddedFk($fkName, ForeignKey $addedFk)
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
    public function removeAddedFk($fkName)
    {
        if (isset($this->addedFks[$fkName])) {
            unset($this->addedFks[$fkName]);
        }
    }

    /**
     * Getter for the addedFks property
     *
     * @return \Propel\Generator\Model\ForeignKey[]
     */
    public function getAddedFks()
    {
        return $this->addedFks;
    }

    /**
     * Sets the list of removed foreign keys.
     *
     * @param \Propel\Generator\Model\ForeignKey[] $removedFks
     *
     * @return void
     */
    public function setRemovedFks(array $removedFks)
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
    public function addRemovedFk($fkName, ForeignKey $removedFk)
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
    public function removeRemovedFk($fkName)
    {
        unset($this->removedFks[$fkName]);
    }

    /**
     * Returns the list of removed foreign keys.
     *
     * @return \Propel\Generator\Model\ForeignKey[]
     */
    public function getRemovedFks()
    {
        return $this->removedFks;
    }

    /**
     * Sets the list of modified foreign keys.
     *
     * Array must be [ [ ForeignKey $fromFk, ForeignKey $toFk ], [ ... ] ]
     *
     * @param \Propel\Generator\Model\ForeignKey[][] $modifiedFks
     *
     * @return void
     */
    public function setModifiedFks(array $modifiedFks)
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
    public function addModifiedFk($fkName, ForeignKey $fromFk, ForeignKey $toFk)
    {
        $this->modifiedFks[$fkName] = [ $fromFk, $toFk ];
    }

    /**
     * Returns the list of modified foreign keys.
     *
     * @return array
     */
    public function getModifiedFks()
    {
        return $this->modifiedFks;
    }

    /**
     * Returns whether or not there are
     * some modified foreign keys.
     *
     * @return bool
     */
    public function hasModifiedFks()
    {
        return !empty($this->modifiedFks);
    }

    /**
     * Returns whether or not there are
     * some modified indices.
     *
     * @return bool
     */
    public function hasModifiedIndices()
    {
        return !empty($this->modifiedIndices);
    }

    /**
     * Returns whether or not there are
     * some modified columns.
     *
     * @return bool
     */
    public function hasModifiedColumns()
    {
        return !empty($this->modifiedColumns);
    }

    /**
     * Returns whether or not there are
     * some removed foreign keys.
     *
     * @return bool
     */
    public function hasRemovedFks()
    {
        return !empty($this->removedFks);
    }

    /**
     * Returns whether or not there are
     * some removed indices.
     *
     * @return bool
     */
    public function hasRemovedIndices()
    {
        return !empty($this->removedIndices);
    }

    /**
     * Returns whether or not there are
     * some renamed columns.
     *
     * @return bool
     */
    public function hasRenamedColumns()
    {
        return !empty($this->renamedColumns);
    }

    /**
     * Returns whether or not there are
     * some removed columns.
     *
     * @return bool
     */
    public function hasRemovedColumns()
    {
        return !empty($this->removedColumns);
    }

    /**
     * Returns whether or not there are
     * some added columns.
     *
     * @return bool
     */
    public function hasAddedColumns()
    {
        return !empty($this->addedColumns);
    }

    /**
     * Returns whether or not there are
     * some added indices.
     *
     * @return bool
     */
    public function hasAddedIndices()
    {
        return !empty($this->addedIndices);
    }

    /**
     * Returns whether or not there are
     * some added foreign keys.
     *
     * @return bool
     */
    public function hasAddedFks()
    {
        return !empty($this->addedFks);
    }

    /**
     * Returns whether or not there are
     * some added primary key columns.
     *
     * @return bool
     */
    public function hasAddedPkColumns()
    {
        return !empty($this->addedPkColumns);
    }

    /**
     * Returns whether or not there are
     * some removed primary key columns.
     *
     * @return bool
     */
    public function hasRemovedPkColumns()
    {
        return !empty($this->removedPkColumns);
    }

    /**
     * Returns whether or not there are
     * some renamed primary key columns.
     *
     * @return bool
     */
    public function hasRenamedPkColumns()
    {
        return !empty($this->renamedPkColumns);
    }

    /**
     * Returns the reverse diff for this diff.
     *
     * @return \Propel\Generator\Model\Diff\TableDiff
     */
    public function getReverseDiff()
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
    public function __toString()
    {
        $ret = '';
        $ret .= sprintf("  %s:\n", $this->fromTable->getName());
        if ($addedColumns = $this->getAddedColumns()) {
            $ret .= "    addedColumns:\n";
            foreach ($addedColumns as $colname => $column) {
                $ret .= sprintf("      - %s\n", $colname);
            }
        }
        if ($removedColumns = $this->getRemovedColumns()) {
            $ret .= "    removedColumns:\n";
            foreach ($removedColumns as $colname => $column) {
                $ret .= sprintf("      - %s\n", $colname);
            }
        }
        if ($modifiedColumns = $this->getModifiedColumns()) {
            $ret .= "    modifiedColumns:\n";
            foreach ($modifiedColumns as $colDiff) {
                $ret .= (string)$colDiff;
            }
        }
        if ($renamedColumns = $this->getRenamedColumns()) {
            $ret .= "    renamedColumns:\n";
            foreach ($renamedColumns as $columnRenaming) {
                [$fromColumn, $toColumn] = $columnRenaming;
                $ret .= sprintf("      %s: %s\n", $fromColumn->getName(), $toColumn->getName());
            }
        }
        if ($addedIndices = $this->getAddedIndices()) {
            $ret .= "    addedIndices:\n";
            foreach ($addedIndices as $indexName => $index) {
                $ret .= sprintf("      - %s\n", $indexName);
            }
        }
        if ($removedIndices = $this->getRemovedIndices()) {
            $ret .= "    removedIndices:\n";
            foreach ($removedIndices as $indexName => $index) {
                $ret .= sprintf("      - %s\n", $indexName);
            }
        }
        if ($modifiedIndices = $this->getModifiedIndices()) {
            $ret .= "    modifiedIndices:\n";
            foreach ($modifiedIndices as $indexName => $indexDiff) {
                $ret .= sprintf("      - %s\n", $indexName);
            }
        }
        if ($addedFks = $this->getAddedFks()) {
            $ret .= "    addedFks:\n";
            foreach ($addedFks as $fkName => $fk) {
                $ret .= sprintf("      - %s\n", $fkName);
            }
        }
        if ($removedFks = $this->getRemovedFks()) {
            $ret .= "    removedFks:\n";
            foreach ($removedFks as $fkName => $fk) {
                $ret .= sprintf("      - %s\n", $fkName);
            }
        }
        if ($modifiedFks = $this->getModifiedFks()) {
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

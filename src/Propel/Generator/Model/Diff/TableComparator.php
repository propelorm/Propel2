<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Model\Diff;

use Propel\Generator\Model\Table;

/**
 * Service class for comparing Table objects
 * Heavily inspired by Doctrine2's Migrations
 * (see http://github.com/doctrine/dbal/tree/master/lib/Doctrine/DBAL/Schema/)
 */
class TableComparator
{
    /**
     * The table difference.
     *
     * @var TableDiff
     */
    protected $tableDiff;

    /**
     * Constructor.
     *
     * @param TableDiff $tableDiff
     */
    public function __construct(TableDiff $tableDiff = null)
    {
        $this->tableDiff = (null === $tableDiff) ? new TableDiff() : $tableDiff;
    }

    /**
     * Returns the table difference.
     *
     * @return TableDiff
     */
    public function getTableDiff()
    {
        return $this->tableDiff;
    }

    /**
     * Sets the table the comparator starts from.
     *
     * @param Table $fromTable
     */
    public function setFromTable(Table $fromTable)
    {
        $this->tableDiff->setFromTable($fromTable);
    }

    /**
     * Returns the table the comparator starts from.
     *
     * @return Table
     */
    public function getFromTable()
    {
        return $this->tableDiff->getFromTable();
    }

    /**
     * Sets the table the comparator goes to.
     *
     * @param Table $toTable
     */
    public function setToTable(Table $toTable)
    {
        $this->tableDiff->setToTable($toTable);
    }

    /**
     * Returns the table the comparator goes to.
     *
     * @return Table
     */
    public function getToTable()
    {
        return $this->tableDiff->getToTable();
    }

    /**
     * Returns the computed difference between two table objects.
     *
     * @param  Table             $fromTable
     * @param  Table             $toTable
     * @param  boolean           $caseInsensitive
     * @return TableDiff|Boolean
     */
    public static function computeDiff(Table $fromTable, Table $toTable, $caseInsensitive = false)
    {
        $tc = new self();

        $tc->setFromTable($fromTable);
        $tc->setToTable($toTable);

        $differences = 0;
        $differences += $tc->compareColumns($caseInsensitive);
        $differences += $tc->comparePrimaryKeys($caseInsensitive);
        $differences += $tc->compareIndices($caseInsensitive);
        $differences += $tc->compareForeignKeys($caseInsensitive);

        return ($differences > 0) ? $tc->getTableDiff() : false;
    }

    /**
     * Returns the number of differences.
     *
     * Compares the columns of the fromTable and the toTable,
     * and modifies the inner tableDiff if necessary.
     *
     * @param  boolean $caseInsensitive
     * @return integer
     */
    public function compareColumns($caseInsensitive = false)
    {
        $fromTableColumns = $this->getFromTable()->getColumns();
        $toTableColumns = $this->getToTable()->getColumns();
        $columnDifferences = 0;

        // check for new columns in $toTable
        foreach ($toTableColumns as $column) {
            if (!$this->getFromTable()->hasColumn($column->getName(), $caseInsensitive)) {
                $this->tableDiff->addAddedColumn($column->getName(), $column);
                $columnDifferences++;
            }
        }

        // check for removed columns in $toTable
        foreach ($fromTableColumns as $column) {
            if (!$this->getToTable()->hasColumn($column->getName(), $caseInsensitive)) {
                $this->tableDiff->addRemovedColumn($column->getName(), $column);
                $columnDifferences++;
            }
        }

        // check for column differences
        foreach ($fromTableColumns as $fromColumn) {
            if ($this->getToTable()->hasColumn($fromColumn->getName(), $caseInsensitive)) {
                $toColumn = $this->getToTable()->getColumn($fromColumn->getName(), $caseInsensitive);
                $columnDiff = ColumnComparator::computeDiff($fromColumn, $toColumn, $caseInsensitive);
                if ($columnDiff) {
                    $this->tableDiff->addModifiedColumn($fromColumn->getName(), $columnDiff);
                    $columnDifferences++;
                }
            }
        }

        // check for column renamings
        foreach ($this->tableDiff->getAddedColumns() as $addedColumnName => $addedColumn) {
            foreach ($this->tableDiff->getRemovedColumns() as $removedColumnName => $removedColumn) {
                if (!ColumnComparator::computeDiff($addedColumn, $removedColumn, $caseInsensitive)) {
                    // no difference except the name, that's probably a renaming
                    $this->tableDiff->addRenamedColumn($removedColumn, $addedColumn);
                    $this->tableDiff->removeAddedColumn($addedColumnName);
                    $this->tableDiff->removeRemovedColumn($removedColumnName);
                    $columnDifferences--;
                    // skip to the next added column
                    break;
                }
            }
        }

        return $columnDifferences;
    }

    /**
     * Returns the number of differences.
     *
     * Compares the primary keys of the fromTable and the toTable,
     * and modifies the inner tableDiff if necessary.
     *
     * @param  boolean $caseInsensitive
     * @return integer
     */
    public function comparePrimaryKeys($caseInsensitive = false)
    {
        $pkDifferences = 0;
        $fromTablePk = $this->getFromTable()->getPrimaryKey();
        $toTablePk = $this->getToTable()->getPrimaryKey();

        // check for new pk columns in $toTable
        foreach ($toTablePk as $column) {
            if (!$this->getFromTable()->hasColumn($column->getName(), $caseInsensitive) ||
                !$this->getFromTable()->getColumn($column->getName(), $caseInsensitive)->isPrimaryKey()) {
                    $this->tableDiff->addAddedPkColumn($column->getName(), $column);
                    $pkDifferences++;
            }
        }

        // check for removed pk columns in $toTable
        foreach ($fromTablePk as $column) {
            if (!$this->getToTable()->hasColumn($column->getName(), $caseInsensitive) ||
                !$this->getToTable()->getColumn($column->getName(), $caseInsensitive)->isPrimaryKey()) {
                    $this->tableDiff->addRemovedPkColumn($column->getName(), $column);
                    $pkDifferences++;
            }
        }

        // check for column renamings
        foreach ($this->tableDiff->getAddedPkColumns() as $addedColumnName => $addedColumn) {
            foreach ($this->tableDiff->getRemovedPkColumns() as $removedColumnName => $removedColumn) {
                if (!ColumnComparator::computeDiff($addedColumn, $removedColumn, $caseInsensitive)) {
                    // no difference except the name, that's probably a renaming
                    $this->tableDiff->addRenamedPkColumn($removedColumn, $addedColumn);
                    $this->tableDiff->removeAddedPkColumn($addedColumnName);
                    $this->tableDiff->removeRemovedPkColumn($removedColumnName);
                    $pkDifferences--;
                    // skip to the next added column
                    break;
                }
            }
        }

        return $pkDifferences;
    }

    /**
     * Returns the number of differences.
     *
     * Compare the indices and unique indices of the fromTable and the toTable,
     * and modifies the inner tableDiff if necessary.
     *
     * @param  boolean $caseInsensitive
     * @return integer
     */
    public function compareIndices($caseInsensitive = false)
    {
        $indexDifferences = 0;
        $fromTableIndices = array_merge($this->getFromTable()->getIndices(), $this->getFromTable()->getUnices());
        $toTableIndices = array_merge($this->getToTable()->getIndices(), $this->getToTable()->getUnices());

        foreach ($fromTableIndices as $fromTableIndexPos => $fromTableIndex) {
            foreach ($toTableIndices as $toTableIndexPos => $toTableIndex) {
                $sameName = $caseInsensitive ?
                    strtolower($fromTableIndex->getName()) == strtolower($toTableIndex->getName()) :
                    $fromTableIndex->getName() == $toTableIndex->getName();
                if ($sameName) {
                    if (false === IndexComparator::computeDiff($fromTableIndex, $toTableIndex, $caseInsensitive)) {
                        //no changes
                        unset($fromTableIndices[$fromTableIndexPos]);
                        unset($toTableIndices[$toTableIndexPos]);
                    } else {
                        // same name, but different columns
                        $this->tableDiff->addModifiedIndex($fromTableIndex->getName(), $fromTableIndex, $toTableIndex);
                        unset($fromTableIndices[$fromTableIndexPos]);
                        unset($toTableIndices[$toTableIndexPos]);
                        $indexDifferences++;
                    }
                }
            }
        }

        foreach ($fromTableIndices as $fromTableIndex) {
            $this->tableDiff->addRemovedIndex($fromTableIndex->getName(), $fromTableIndex);
            $indexDifferences++;
        }

        foreach ($toTableIndices as $toTableIndex) {
            $this->tableDiff->addAddedIndex($toTableIndex->getName(), $toTableIndex);
            $indexDifferences++;
        }

        return $indexDifferences;
    }

    /**
     * Returns the number of differences.
     *
     * Compare the foreign keys of the fromTable and the toTable,
     * and modifies the inner tableDiff if necessary.
     *
     * @param  boolean $caseInsensitive
     * @return integer
     */
    public function compareForeignKeys($caseInsensitive = false)
    {
        $fkDifferences = 0;
        $fromTableFks = $this->getFromTable()->getForeignKeys();
        $toTableFks = $this->getToTable()->getForeignKeys();

        foreach ($fromTableFks as $fromTableFkPos => $fromTableFk) {
            foreach ($toTableFks as $toTableFkPos => $toTableFk) {
                $sameName = $caseInsensitive ?
                    strtolower($fromTableFk->getName()) == strtolower($toTableFk->getName()) :
                    $fromTableFk->getName() == $toTableFk->getName();
                if ($sameName && !$toTableFk->isPolymorphic()) {
                    if (false === ForeignKeyComparator::computeDiff($fromTableFk, $toTableFk, $caseInsensitive)) {
                        unset($fromTableFks[$fromTableFkPos]);
                        unset($toTableFks[$toTableFkPos]);
                    } else {
                        // same name, but different columns
                        $this->tableDiff->addModifiedFk($fromTableFk->getName(), $fromTableFk, $toTableFk);
                        unset($fromTableFks[$fromTableFkPos]);
                        unset($toTableFks[$toTableFkPos]);
                        $fkDifferences++;
                    }
                }
            }
        }

        foreach ($fromTableFks as $fromTableFk) {
            if (!$fromTableFk->isSkipSql() && !$fromTableFk->isPolymorphic() && !in_array($fromTableFk, $toTableFks)) {
                $this->tableDiff->addRemovedFk($fromTableFk->getName(), $fromTableFk);
                $fkDifferences++;
            }
        }

        foreach ($toTableFks as $toTableFk) {
            if (!$toTableFk->isSkipSql() && !$toTableFk->isPolymorphic() && !in_array($toTableFk, $fromTableFks)) {
                $this->tableDiff->addAddedFk($toTableFk->getName(), $toTableFk);
                $fkDifferences++;
            }
        }

        return $fkDifferences;
    }
}

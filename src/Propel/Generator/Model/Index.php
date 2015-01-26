<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Model;

/**
 * Information about indices of a table.
 *
 * @author Jason van Zyl <vanzyl@apache.org>
 * @author Daniel Rall <dlr@finemaltcoding.com>
 * @author Hugo Hamon <webmaster@apprendre-php.com> (Propel)
 */
class Index extends MappingModel
{
    /**
     * @var string
     */
    protected $name;

    /**
     * The Table instance.
     *
     * @var Table
     */
    protected $table;

    /**
     * @var string[]
     */
    protected $columns;

    /**
     * @var Column[]
     */
    protected $columnObjects = [];

    /**
     * @var string[]
     */
    protected $columnsSize;

    /**
     * @var bool
     */
    protected $autoNaming = false;

    /**
     * Creates a new Index instance.
     *
     * @param string $name Name of the index
     */
    public function __construct($name = null)
    {
        parent::__construct();

        $this->columns     = [];
        $this->columnsSize = [];

        if (null !== $name) {
            $this->setName($name);
        }
    }

    /**
     * Returns the uniqueness of this index.
     *
     * @return boolean
     */
    public function isUnique()
    {
        return false;
    }

    /**
     * Sets the index name.
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->autoNaming = !$name; //if no name we activate autoNaming
        $this->name = $name;
    }

    /**
     * Returns the index name.
     *
     * @return string
     */
    public function getName()
    {
        $this->doNaming();

        if ($this->table && $database = $this->table->getDatabase()) {
            return substr($this->name, 0, $database->getMaxColumnNameLength());
        }

        return $this->name;
    }

    protected function doNaming()
    {
        if (!$this->name || $this->autoNaming) {
            $newName = sprintf('%s_', $this instanceof Unique ? 'u' : 'i');

            if ($this->columns) {
                $hash = [];
                $hash[] = implode(',', (array)$this->columns);
                $hash[] = implode(',', (array)$this->columnsSize);

                $newName .= substr(md5(strtolower(implode(':', $hash))), 0, 6);
            } else {
                $newName .= 'no_columns';
            }

            if ($this->table) {
                $newName = $this->table->getCommonName() . '_' . $newName;
            }

            $this->name = $newName;
            $this->autoNaming = true;
        }
    }

    public function getFQName()
    {
        $table = $this->getTable();
        if ($table->getDatabase()
            && ($table->getSchema() || $table->getDatabase()->getSchema())
            && $table->getDatabase()->getPlatform()
            && $table->getDatabase()->getPlatform()->supportsSchemas()
        ) {
            return ($table->getSchema() ?: $table->getDatabase()->getSchema()) . '.' . $this->getName();
        }

        return $this->getName();
    }

    /**
     * Sets the index parent Table.
     *
     * @param Table $table
     */
    public function setTable(Table $table)
    {
        $this->table = $table;
    }

    /**
     * Returns the index parent table.
     *
     * @return Table
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * Returns the name of the index parent table.
     *
     * @return string
     */
    public function getTableName()
    {
        return $this->table->getName();
    }

    /**
     * Adds a new column to the index.
     *
     * @param Column|array $data Column or attributes from XML.
     */
    public function addColumn($data)
    {
        if ($data instanceof Column) {
            $column = $data;
            $this->columns[] = $column->getName();
            if ($column->getSize()) {
                $this->columnsSize[$column->getName()] = $column->getSize();
            }
            $this->columnObjects[] = $column;
        } else {
            $this->columns[] = $name = $data['name'];
            if (isset($data['size']) && $data['size'] > 0) {
                $this->columnsSize[$name] = $data['size'];
            }
            if ($this->getTable()) {
                $this->columnObjects[] = $this->getTable()->getColumn($name);
            }
        }
    }

    /**
     * @param  string $name
     * @return bool
     */
    public function hasColumn($name)
    {
        return in_array($name, $this->columns);
    }

    /**
     * Sets an array of columns to use for the index.
     *
     * @param array $columns array of array definitions $columns[]['name'] = 'columnName'
     */
    public function setColumns(array $columns)
    {
        $this->columns     = [];
        $this->columnsSize = [];
        foreach ($columns as $column) {
            $this->addColumn($column);
        }
    }

    /**
     * Returns whether or not there is a size for the specified column.
     *
     * @param  string  $name
     * @return boolean
     */
    public function hasColumnSize($name)
    {
        return isset($this->columnsSize[$name]);
    }

    /**
     * Returns the size for the specified column.
     *
     * @param  string  $name
     * @param  boolean $caseInsensitive
     * @return integer
     */
    public function getColumnSize($name, $caseInsensitive = false)
    {
        if ($caseInsensitive) {
            foreach ($this->columnsSize as $forName => $size) {
                if (0 === strcasecmp($forName, $name)) {
                    return $size;
                }
            }
            return null;
        }
        return isset($this->columnsSize[$name]) ? $this->columnsSize[$name] : null;
    }

    /**
     * Resets the columns sizes.
     *
     * This method is useful for generated indices for FKs.
     */
    public function resetColumnsSize()
    {
        $this->columnsSize = [];
    }

    /**
     * Returns whether or not this index has a given column at a given position.
     *
     * @param  integer $pos             Position in the column list
     * @param  string  $name            Column name
     * @param  integer $size            Optional size check
     * @param  boolean $caseInsensitive Whether or not the comparison is case insensitive (false by default)
     * @return boolean
     */
    public function hasColumnAtPosition($pos, $name, $size = null, $caseInsensitive = false)
    {
        if (!isset($this->columns[$pos])) {
            return false;
        }

        if ($caseInsensitive) {
            $test = 0 === strcasecmp($this->columns[$pos], $name);
        } else {
            $test = $this->columns[$pos] == $name;
        }

        if (!$test) {
            return false;
        }

        if ($this->getColumnSize($name, $caseInsensitive) != $size) {
            return false;
        }

        return true;
    }

    /**
     * Returns whether or not the index has columns.
     *
     * @return boolean
     */
    public function hasColumns()
    {
        return count($this->columns) > 0;
    }

    /**
     * Returns the list of local columns.
     *
     * You should not edit this list.
     *
     * @return array
     */
    public function getColumns()
    {
        return $this->columns;
    }

    protected function setupObject()
    {
        $this->setName($this->getAttribute('name'));
    }

    /**
     * @return Column[]
     */
    public function getColumnObjects()
    {
        return $this->columnObjects;
    }

    /**
     * @param Column[] $columnObjects
     */
    public function setColumnObjects($columnObjects)
    {
        $this->columnObjects = $columnObjects;
    }
}

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
     * The Entity instance.
     *
     * @var Entity
     */
    protected $table;

    /**
     * @var string[]
     */
    protected $columns;

    /**
     * @var Field[]
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
            return substr($this->name, 0, $database->getMaxFieldNameLength());
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
                $newName = $this->table->getTableName() . '_' . $newName;
            }

            $this->name = $newName;
            $this->autoNaming = true;
        }
    }

    public function getFQName()
    {
        $table = $this->getEntity();
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
     * Sets the index parent Entity.
     *
     * @param Entity $table
     */
    public function setEntity(Entity $table)
    {
        $this->table = $table;
    }

    /**
     * Returns the index parent table.
     *
     * @return Entity
     */
    public function getEntity()
    {
        return $this->table;
    }

    /**
     * Returns the name of the index parent table.
     *
     * @return string
     */
    public function getEntityName()
    {
        return $this->table->getName();
    }

    /**
     * Adds a new column to the index.
     *
     * @param Field|array $data Field or attributes from XML.
     */
    public function addField($data)
    {
        if ($data instanceof Field) {
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
            if ($this->getEntity()) {
                $this->columnObjects[] = $this->getEntity()->getField($name);
            }
        }
    }

    /**
     * @param  string $name
     * @return bool
     */
    public function hasField($name)
    {
        return in_array($name, $this->columns);
    }

    /**
     * Sets an array of columns to use for the index.
     *
     * @param array $columns array of array definitions $columns[]['name'] = 'columnName'
     */
    public function setFields(array $columns)
    {
        $this->columns     = [];
        $this->columnsSize = [];
        foreach ($columns as $column) {
            $this->addField($column);
        }
    }

    /**
     * Returns whether or not there is a size for the specified column.
     *
     * @param  string  $name
     * @return boolean
     */
    public function hasFieldSize($name)
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
    public function getFieldSize($name, $caseInsensitive = false)
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
    public function resetFieldsSize()
    {
        $this->columnsSize = [];
    }

    /**
     * Returns whether or not this index has a given column at a given position.
     *
     * @param  integer $pos             Position in the column list
     * @param  string  $name            Field name
     * @param  integer $size            Optional size check
     * @param  boolean $caseInsensitive Whether or not the comparison is case insensitive (false by default)
     * @return boolean
     */
    public function hasFieldAtPosition($pos, $name, $size = null, $caseInsensitive = false)
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

        if ($this->getFieldSize($name, $caseInsensitive) != $size) {
            return false;
        }

        return true;
    }

    /**
     * Returns whether or not the index has columns.
     *
     * @return boolean
     */
    public function hasFields()
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
    public function getFields()
    {
        return $this->columns;
    }

    protected function setupObject()
    {
        $this->setName($this->getAttribute('name'));
    }

    /**
     * @return Field[]
     */
    public function getFieldObjects()
    {
        return $this->columnObjects;
    }

    /**
     * @param Field[] $columnObjects
     */
    public function setFieldObjects($columnObjects)
    {
        $this->columnObjects = $columnObjects;
    }
}

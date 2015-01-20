<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Runtime\Map;

use Propel\Runtime\ActiveQuery\Criterion\AbstractCriterion;
use Propel\Runtime\ActiveQuery\ModelCriteria;

/**
 * RelationMap is used to model a database relationship.
 *
 * GENERAL NOTE
 * ------------
 * The propel.map classes are abstract building-block classes for modeling
 * the database at runtime.  These classes are similar (a lite version) to the
 * propel.engine.database.model classes, which are build-time modeling classes.
 * These classes in themselves do not do any database metadata lookups.
 *
 * @author Francois Zaninotto
 */
class RelationMap
{
    // types
    const MANY_TO_ONE       = 1;

    const ONE_TO_MANY       = 2;

    const ONE_TO_ONE        = 3;

    const MANY_TO_MANY      = 4;

    // representations
    const LOCAL_TO_FOREIGN  = 0;

    const LEFT_TO_RIGHT     = 1;

    protected $name;

    protected $pluralName;

    protected $type;

    protected $localTable;

    protected $foreignTable;

    protected $polymorphic = false;

    /**
     * @var ColumnMap[]
     */
    protected $localColumns = array();

    /**
     * Values used for polymorphic associations.
     *
     * @var array
     */
    protected $localValues = array();

    /**
     * @var ColumnMap[]
     */
    protected $foreignColumns = array();

    protected $onUpdate;

    protected $onDelete;

    /**
     * Constructor.
     *
     * @param string $name Name of the relation.
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * @return boolean
     */
    public function isPolymorphic()
    {
        return $this->polymorphic;
    }

    /**
     * @param boolean $polymorphic
     */
    public function setPolymorphic($polymorphic)
    {
        $this->polymorphic = $polymorphic;
    }

    /**
     * Get the name of this relation.
     *
     * @return string The name of the relation.
     */
    public function getName()
    {
        return $this->name;
    }

    public function setPluralName($pluralName)
    {
        $this->pluralName = $pluralName;
    }

    /**
     * Get the plural name of this relation.
     *
     * @return string The plural name of the relation.
     */
    public function getPluralName()
    {
        return null !== $this->pluralName ? $this->pluralName : ($this->name . 's');
    }

    /**
     * Set the type
     *
     * @param int $type The relation type (either self::MANY_TO_ONE, self::ONE_TO_MANY, or self::ONE_TO_ONE)
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * Get the type
     *
     * @return int the relation type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set the local table
     *
     * @param \Propel\Runtime\Map\TableMap $table The local table for this relationship
     */
    public function setLocalTable(TableMap $table)
    {
        $this->localTable = $table;
    }

    /**
     * Get the local table
     *
     * @return \Propel\Runtime\Map\TableMap The local table for this relationship
     */
    public function getLocalTable()
    {
        return $this->localTable;
    }

    /**
     * Set the foreign table
     *
     * @param \Propel\Runtime\Map\TableMap $table The foreign table for this relationship
     */
    public function setForeignTable($table)
    {
        $this->foreignTable = $table;
    }

    /**
     * Get the foreign table
     *
     * @return \Propel\Runtime\Map\TableMap The foreign table for this relationship
     */
    public function getForeignTable()
    {
        return $this->foreignTable;
    }

    /**
     * Get the left table of the relation
     *
     * @return \Propel\Runtime\Map\TableMap The left table for this relationship
     */
    public function getLeftTable()
    {
        return RelationMap::MANY_TO_ONE === $this->getType() ? $this->getLocalTable() : $this->getForeignTable();
    }

    /**
     * Get the right table of the relation
     *
     * @return \Propel\Runtime\Map\TableMap The right table for this relationship
     */
    public function getRightTable()
    {
        return RelationMap::MANY_TO_ONE === $this->getType() ? $this->getForeignTable() : $this->getLocalTable();
    }

    /**
     * Add a column mapping
     *
     * @param ColumnMap       $local   The local column
     * @param ColumnMap|mixed $foreign The foreign column or value
     */
    public function addColumnMapping(ColumnMap $local, $foreign)
    {
        $this->localColumns[] = $local;

        if ($foreign instanceof ColumnMap) {
            $this->foreignColumns[] = $foreign;
            $this->localValues[] = null;
        } else {
            $this->localValues[] = $foreign;
            $this->foreignColumns[] = null;
        }
    }

    /**
     * Get an associative array mapping local column names to foreign column names
     * The arrangement of the returned array depends on the $direction parameter:
     *  - If the value is RelationMap::LOCAL_TO_FOREIGN, then the returned array is local => foreign
     *  - If the value is RelationMap::LEFT_TO_RIGHT, then the returned array is left => right
     *
     * @param  int   $direction How the associative array must return columns
     * @return array Associative array (local => foreign) of fully qualified column names
     */
    public function getColumnMappings($direction = RelationMap::LOCAL_TO_FOREIGN)
    {
        $h = array();
        if (RelationMap::LEFT_TO_RIGHT === $direction
            && RelationMap::MANY_TO_ONE === $this->getType()) {
            $direction = RelationMap::LOCAL_TO_FOREIGN;
        }

        for ($i = 0, $size = count($this->localColumns); $i < $size; $i++) {
            if (RelationMap::LOCAL_TO_FOREIGN === $direction) {
                $h[$this->localColumns[$i]->getFullyQualifiedName()] = $this->foreignColumns[$i]->getFullyQualifiedName();
            } else {
                $h[$this->foreignColumns[$i]->getFullyQualifiedName()] = $this->localColumns[$i]->getFullyQualifiedName();
            }
        }

        return $h;
    }

    /**
     * Returns true if the relation has more than one column mapping
     *
     * @return boolean
     */
    public function isComposite()
    {
        return $this->countColumnMappings() > 1;
    }

    /**
     * Return the number of column mappings
     *
     * @return int
     */
    public function countColumnMappings()
    {
        return count($this->localColumns);
    }

    /**
     * Get the local columns
     *
     * @return ColumnMap[]
     */
    public function getLocalColumns()
    {
        return $this->localColumns;
    }

    /**
     * Get the foreign columns
     *
     * @return ColumnMap[]
     */
    public function getForeignColumns()
    {
        return $this->foreignColumns;
    }

    /**
     * Get the left columns of the relation
     *
     * @return ColumnMap[]
     */
    public function getLeftColumns()
    {
        return RelationMap::MANY_TO_ONE === $this->getType() ? $this->getLocalColumns() : $this->getForeignColumns();
    }

    /**
     * Get the right columns of the relation
     *
     * @return ColumnMap[]
     */
    public function getRightColumns()
    {
        return RelationMap::MANY_TO_ONE === $this->getType() ? $this->getForeignColumns() : $this->getLocalColumns();
    }

    /**
     * @return array
     */
    public function getLocalValues()
    {
        return $this->localValues;
    }

    /**
     * Set the onUpdate behavior
     *
     * @param string $onUpdate
     */
    public function setOnUpdate($onUpdate)
    {
        $this->onUpdate = $onUpdate;
    }

    /**
     * Get the onUpdate behavior
     *
     * @return integer the relation type
     */
    public function getOnUpdate()
    {
        return $this->onUpdate;
    }

    /**
     * Set the onDelete behavior
     *
     * @param string $onDelete
     */
    public function setOnDelete($onDelete)
    {
        $this->onDelete = $onDelete;
    }

    /**
     * Get the onDelete behavior
     *
     * @return int the relation type
     */
    public function getOnDelete()
    {
        return $this->onDelete;
    }

    /**
     * Gets the symmetrical relation
     *
     * @return \Propel\Runtime\Map\RelationMap
     */
    public function getSymmetricalRelation()
    {
        $localMapping = array($this->getLeftColumns(), $this->getRightColumns());
        foreach ($this->getRightTable()->getRelations() as $relation) {
            if ($localMapping == array($relation->getRightColumns(), $relation->getLeftColumns())) {
                return $relation;
            }
        }
    }
}

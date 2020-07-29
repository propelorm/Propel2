<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\Map;

/**
 * RelationMap is used to model a database relationship.
 *
 * GENERAL NOTE
 * ------------
 * The propel.map classes are abstract building-block classes for modeling
 * the database at runtime. These classes are similar (a lite version) to the
 * propel.engine.database.model classes, which are build-time modeling classes.
 * These classes in themselves do not do any database metadata lookups.
 *
 * @author Francois Zaninotto
 */
class RelationMap
{
    // types
    public const MANY_TO_ONE = 1;

    public const ONE_TO_MANY = 2;

    public const ONE_TO_ONE = 3;

    public const MANY_TO_MANY = 4;

    // representations
    public const LOCAL_TO_FOREIGN = 0;

    public const LEFT_TO_RIGHT = 1;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $pluralName;

    /**
     * @var int
     */
    protected $type;

    /**
     * @var \Propel\Runtime\Map\TableMap
     */
    protected $localTable;

    /**
     * @var \Propel\Runtime\Map\TableMap
     */
    protected $foreignTable;

    /**
     * @var bool
     */
    protected $polymorphic = false;

    /**
     * @var \Propel\Runtime\Map\ColumnMap[]
     */
    protected $localColumns = [];

    /**
     * Values used for polymorphic associations.
     *
     * @var array
     */
    protected $localValues = [];

    /**
     * @var (\Propel\Runtime\Map\ColumnMap|null)[]
     */
    protected $foreignColumns = [];

    /**
     * @var string|null
     */
    protected $onUpdate;

    /**
     * @var string|null
     */
    protected $onDelete;

    /**
     * @param string $name Name of the relation.
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * @return bool
     */
    public function isPolymorphic()
    {
        return $this->polymorphic;
    }

    /**
     * @param bool $polymorphic
     *
     * @return void
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

    /**
     * @param string $pluralName
     *
     * @return void
     */
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
        return $this->pluralName !== null ? $this->pluralName : ($this->name . 's');
    }

    /**
     * Set the type
     *
     * @param int $type The relation type (either self::MANY_TO_ONE, self::ONE_TO_MANY, or self::ONE_TO_ONE)
     *
     * @return void
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
     *
     * @return void
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
     *
     * @return void
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
        return $this->getType() === RelationMap::MANY_TO_ONE ? $this->getLocalTable() : $this->getForeignTable();
    }

    /**
     * Get the right table of the relation
     *
     * @return \Propel\Runtime\Map\TableMap The right table for this relationship
     */
    public function getRightTable()
    {
        return $this->getType() === RelationMap::MANY_TO_ONE ? $this->getForeignTable() : $this->getLocalTable();
    }

    /**
     * Add a column mapping
     *
     * @param \Propel\Runtime\Map\ColumnMap $local The local column
     * @param \Propel\Runtime\Map\ColumnMap|mixed $foreign The foreign column or value
     *
     * @return void
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
     * @param int $direction How the associative array must return columns
     *
     * @return array Associative array (local => foreign) of fully qualified column names
     */
    public function getColumnMappings($direction = RelationMap::LOCAL_TO_FOREIGN)
    {
        $h = [];
        if (
            $direction === RelationMap::LEFT_TO_RIGHT
            && $this->getType() === RelationMap::MANY_TO_ONE
        ) {
            $direction = RelationMap::LOCAL_TO_FOREIGN;
        }

        for ($i = 0, $size = count($this->localColumns); $i < $size; $i++) {
            if ($direction === RelationMap::LOCAL_TO_FOREIGN) {
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
     * @return bool
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
     * @return \Propel\Runtime\Map\ColumnMap[]
     */
    public function getLocalColumns()
    {
        return $this->localColumns;
    }

    /**
     * Get the foreign columns
     *
     * @return \Propel\Runtime\Map\ColumnMap[]
     */
    public function getForeignColumns()
    {
        return $this->foreignColumns;
    }

    /**
     * Get the left columns of the relation
     *
     * @return \Propel\Runtime\Map\ColumnMap[]
     */
    public function getLeftColumns()
    {
        return $this->getType() === RelationMap::MANY_TO_ONE ? $this->getLocalColumns() : $this->getForeignColumns();
    }

    /**
     * Get the right columns of the relation
     *
     * @return \Propel\Runtime\Map\ColumnMap[]
     */
    public function getRightColumns()
    {
        return $this->getType() === RelationMap::MANY_TO_ONE ? $this->getForeignColumns() : $this->getLocalColumns();
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
     *
     * @return void
     */
    public function setOnUpdate($onUpdate)
    {
        $this->onUpdate = $onUpdate;
    }

    /**
     * Get the onUpdate behavior
     *
     * @return string|null
     */
    public function getOnUpdate()
    {
        return $this->onUpdate;
    }

    /**
     * Set the onDelete behavior
     *
     * @param string $onDelete
     *
     * @return void
     */
    public function setOnDelete($onDelete)
    {
        $this->onDelete = $onDelete;
    }

    /**
     * Get the onDelete behavior
     *
     * @return string|null
     */
    public function getOnDelete()
    {
        return $this->onDelete;
    }

    /**
     * Gets the symmetrical relation
     *
     * @return \Propel\Runtime\Map\RelationMap|null
     */
    public function getSymmetricalRelation()
    {
        $localMapping = [$this->getLeftColumns(), $this->getRightColumns()];
        foreach ($this->getRightTable()->getRelations() as $relation) {
            if ($localMapping == [$relation->getRightColumns(), $relation->getLeftColumns()]) {
                return $relation;
            }
        }

        return null;
    }
}

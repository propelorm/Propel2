<?php

/*
 *  $Id: ColumnMap.php,v 1.14 2004/12/04 13:53:27 micha Exp $
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information please see
 * <http://propel.phpdb.org>.
 */
 
include_once 'propel/map/ValidatorMap.php';

/**
 * ColumnMap is used to model a column of a table in a database.
 * 
 * GENERAL NOTE
 * ------------
 * The propel.map classes are abstract building-block classes for modeling
 * the database at runtime.  These classes are similar (a lite version) to the
 * propel.engine.database.model classes, which are build-time modeling classes.
 * These classes in themselves do not do any database metadata lookups, but instead 
 * are used by the MapBuilder classes that were generated for your datamodel. The 
 * MapBuilder that was created for your datamodel build a representation of your
 * database by creating instances of the DatabaseMap, TableMap, ColumnMap, etc. 
 * classes. See propel/templates/om/php5/MapBuilder.tpl and the classes generated
 * by that template for your datamodel to further understand how these are put 
 * together.
 * 
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author John D. McNally <jmcnally@collab.net> (Torque)
 * @version $Revision: 1.14 $
 * @package propel.map
 */
class ColumnMap {

    /** @var int Creole type for this column. */
    private $creoleType;
    
    /** @var string Native PHP type of the column. */
    private $type = null;

    /** Size of the column. */
    private $size = 0;

    /** Is it a primary key? */
    private $pk = false;

    /** Is null value allowed ?*/
    private $notNull = false;

    /** Name of the table that this column is related to. */
    private $relatedTableName = "";

    /** Name of the column that this column is related to. */
    private $relatedColumnName = "";

    /** The TableMap for this column. */
    private $table;

    /** The name of the column. */
    private $columnName;

    /** The php name of the column. */
    private $phpName;

    /** validators for this column */
    private $validators = array();


    /**
     * Constructor.
     *
     * @param string $name The name of the column.
     * @param TableMap containingTable TableMap of the table this column is in.
     */
    public function __construct($name, TableMap $containingTable)
    {
        $this->columnName = $name;
        $this->table = $containingTable;
    }

    /**
     * Get the name of a column.
     *
     * @return string A String with the column name.
     */
    public function getColumnName()
    {
        return $this->columnName;
    }

    /**
     * Set the php anme of this column.
     *
     * @param string $phpName A string representing the PHP name.
     * @return void
     */
    public function setPhpName($phpName)
    {
        $this->phpName = $phpName;
    }

    /**
     * Get the name of a column.
     *
     * @return string A String with the column name.
     */
    public function getPhpName()
    {
        return $this->phpName;
    }

    /**
     * Get the table name + column name.
     *
     * @return string A String with the full column name.
     */
    public function getFullyQualifiedName()
    {
        return $this->table->getName() . "." . $this->columnName;
    }

    /**
     * Get the table map this column belongs to.
     * @return TableMap
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * Get the name of the table this column is in.
     *
     * @return string A String with the table name.
     */
    public function getTableName()
    {
        return $this->table->getName();
    }

    /**
     * Set the type of this column.
     *
     * @param string $type A string representing the PHP native type.
     * @return void
     */
    public function setType($type)
    {
        $this->type = $type;
    }

     /**
     * Set the Creole type of this column.
     *
     * @param int $type An int representing Creole type for this column.
     * @return void
     */
    public function setCreoleType($type)
    {
        $this->creoleType = $type;
    }
    
    /**
     * Set the size of this column.
     *
     * @param int $size An int specifying the size.
     * @return void
     */
    public function setSize($size)
    {
        $this->size = $size;
    }

    /**
     * Set if this column is a primary key or not.
     *
     * @param boolean $pk True if column is a primary key.
     * @return void
     */
    public function setPrimaryKey($pk)
    {
        $this->pk = $pk;
    }

    /**
     * Set if this column may be null.
     *
     * @param boolean nn True if column may be null.
     * @return void
     */
    public function setNotNull($nn)
    {
        $this->notNull = $nn;
    }

    /**
     * Set the foreign key for this column.
     *
     * @param string tableName The name of the table that is foreign.
     * @param string columnName The name of the column that is foreign.
     * @return void
     */
    public function setForeignKey($tableName, $columnName)
    {
        if ($tableName && $columnName) {
            $this->relatedTableName = $tableName;
            $this->relatedColumnName = $columnName;
        } else {
            $this->relatedTableName = "";
            $this->relatedColumnName = "";
        }
    }

    public function addValidator($validator)
    {
      $this->validators[] = $validator;
    }

    public function hasValidators()
    {
      return count($this->validators) > 0;
    }

    public function getValidators()
    {
      return $this->validators;
    }

    
    /**
     * Get the native PHP type of this column.
     *
     * @return string A string specifying the native PHP type.
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Get the Creole type of this column.
     *
     * @return string A string specifying the native PHP type.
     */
    public function getCreoleType()
    {
        return $this->creoleType;
    }

    /**
     * Get the size of this column.
     *
     * @return int An int specifying the size.
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Is this column a primary key?
     *
     * @return boolean True if column is a primary key.
     */
    public function isPrimaryKey()
    {
        return $this->pk;
    }

    /**
     * Is null value allowed ?
     *
     * @return boolean True if column may be null.
     */
    public function isNotNull()
    {
        return ($this->notNull || $this->isPrimaryKey());
    }

    /**
     * Is this column a foreign key?
     *
     * @return boolean True if column is a foreign key.
     */
    public function isForeignKey()
    {
        if ($this->relatedTableName) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get the table.column that this column is related to.
     *
     * @return string A String with the full name for the related column.
     */
    public function getRelatedName()
    {
        return $this->relatedTableName . "." . $this->relatedColumnName;
    }

    /**
     * Get the table name that this column is related to.
     *
     * @return string A String with the name for the related table.
     */
    public function getRelatedTableName()
    {
        return $this->relatedTableName;
    }

    /**
     * Get the column name that this column is related to.
     *
     * @return string A String with the name for the related column.
     */
    public function getRelatedColumnName()
    {
        return $this->relatedColumnName;
    }
}

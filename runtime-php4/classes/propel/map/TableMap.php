<?php
/*
 *  $Id: TableMap.php,v 1.3 2004/06/14 16:49:44 micha Exp $
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

include_once 'propel/map/ColumnMap.php';
include_once 'propel/map/ValidatorMap.php';

/**
 * TableMap is used to model a table in a database.
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
 * classes. See propel/templates/om/php4/MapBuilder.tpl and the classes generated
 * by that template for your datamodel to further understand how these are put 
 * together.
 * 
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Michael Aichler <aichler@mediacluster.de> (Propel)
 * @author John D. McNally <jmcnally@collab.net> (Torque)
 * @author Daniel Rall <dlr@finemaltcoding.com> (Torque)
 * @version $Revision: 1.3 $
 * @package propel.map
 */
class TableMap
{
  /** The columns in the table. */
  var $columns;

  /** The database this table belongs to. */
  var $dbMap;

  /** The name of the table. */
  var $tableName;

  /** The PHP name of the table. */
  var $phpName;

  /** The prefix on the table name. */
  var $prefix;

  /** Whether to use an id generator for pkey. */
  var $useIdGenerator;

  /**
  * Object to store information that is needed if the
  * for generating primary keys.
  */
  var $pkInfo;

  /**
  * Constructor.
  *
  * @param string $tableName The name of the table.
  * @param DatabaseMap $containingDB A DatabaseMap that this table belongs to.
  */
  function TableMap($tableName, /*DatabaseMap*/ &$containingDB)
  {
    $this->tableName =& $tableName;
    $this->dbMap =& $containingDB;
    $this->columns = array();
  }

  /**
  * Normalizes the column name, removing table prefix and uppercasing.
  * @param string $name
  * @return string Normalized column name.
  */
  function normalizeColName($name)
  {
    if (false !== ($pos = strpos($name, '.'))) {
      $name = substr($name, $pos + 1);
    }
    $name = strtoupper($name);
    return $name;
  }

  /**
  * Does this table contain the specified column ? 
  *
  * @param $name A String with the name of the column.
  * @return boolean True if the table contains the column.
  */
  function containsColumn($name)
  {
    if (!is_string($name)) {
      $name = $name->getColumnName();
    }
    return isset($this->columns[$this->normalizeColName($name)]);
  }

  /**
  * Get the DatabaseMap containing this TableMap.
  *
  * @return DatabaseMap A DatabaseMap.
  */
  function & getDatabaseMap()
  {
    return $this->dbMap;
  }

  /**
  * Get the name of the Table.
  *
  * @return string A String with the name of the table.
  */
  function getName()
  {
    return $this->tableName;
  }

  /**
  * Get the PHP name of the Table.
  *
  * @return string A String with the name of the table.
  */
  function getPhpName()
  {
    return $this->phpName;
  }

  /**
  * Set the PHP name of the Table.
  *
  * @param string $phpName The PHP Name for this table
  */
  function setPhpName($phpName)
  {
    $this->phpName = $phpName;
  }

  /**
  * Get table prefix name.
  *
  * @return string A String with the prefix.
  */
  function getPrefix()
  {
    return $this->prefix;
  }

  /**
  * Set table prefix name.
  *
  * @param string $prefix The prefix for the table name (ie: SCARAB for SCARAB_PROJECT).
  * @return void
  */
  function setPrefix($prefix)
  {
    $this->prefix = $prefix;
  }

  /**
  * Whether to use Id generator for primary key.
  * @return boolean
  */
  function isUseIdGenerator() 
  {
    return $this->useIdGenerator;
  }

  /**
  * Get the information used to generate a primary key
  *
  * @return An Object.
  */
  function & getPrimaryKeyMethodInfo()
  {
    return $this->pkInfo;
  }

  /**
  * Get a ColumnMap[] of the columns in this table.
  *
  * @return array A ColumnMap[].
  */
  function & getColumns()
  {
    return $this->columns;
  }

  /**
  * Get a ColumnMap for the named table.
  *
  * @param string $name A String with the name of the table.
  * @return ColumnMap A ColumnMap.
  */
  function & getColumn($name)
  {
    $name = $this->normalizeColName($name);
    if (isset($this->columns[$name])) {
        return $this->columns[$name];
    }
    return null;
  }

  /**
  * Add a primary key column to this Table.
  *
  * @param string $columnName A String with the column name.
  * @param string $type A string specifying the PHP native type.
  * @param int $creoleType The integer representing the Creole type.
  * @param boolean $isNotNull Whether column does not allow NULL values.
  * @param $size An int specifying the size.
  * @return ColumnMap Newly added PrimaryKey column.
  */
  function & addPrimaryKey($columnName, $phpName, $type, $creoleType, $isNotNull = false, $size = null)
  {
    return $this->addColumn($columnName, $phpName, $type, $creoleType, $isNotNull, $size, true, null, null);
  }

  /**
  * Add a foreign key column to the table.
  *
  * @param string $columnName A String with the column name.
  * @param string $type A string specifying the PHP native type.
  * @param int $creoleType The integer representing the Creole type.
  * @param string $fkTable A String with the foreign key table name.
  * @param string $fkColumn A String with the foreign key column name.
  * @param boolean $isNotNull Whether column does not allow NULL values.
  * @param int $size An int specifying the size.
  * @return ColumnMap Newly added ForeignKey column.
  */
  function & addForeignKey($columnName, $phpName, $type, $creoleType, $fkTable, $fkColumn, $isNotNull = false, $size = 0)
  {
    return $this->addColumn($columnName, $phpName, $type, $creoleType, $isNotNull, $size, false, $fkTable, $fkColumn);
  }

  /**
  * Add a foreign primary key column to the table.
  *
  * @param string $columnName A String with the column name.
  * @param string $type A string specifying the PHP native type.
  * @param int $creoleType The integer representing the Creole type.
  * @param string $fkTable A String with the foreign key table name.
  * @param string $fkColumn A String with the foreign key column name.
  * @param boolean $isNotNull Whether column does not allow NULL values.
  * @param int $size An int specifying the size.
  * @return ColumnMap Newly created foreign pkey column.
  */
  function & addForeignPrimaryKey($columnName, $phpName, $type, $creoleType, $fkTable, $fkColumn, $isNotNull = false, $size = 0)
  {
    return $this->addColumn($columnName, $phpName, $type, $creoleType, $isNotNull, $size, true, $fkTable, $fkColumn);
  }

  /**
  * Add a pre-created column to this table.  It will replace any
  * existing column.
  *
  * @param ColumnMap $cmap A ColumnMap.
  * @return ColumnMap The added column map.
  */
  function & addConfiguredColumn(&$cmap)
  {
    $this->columns[ $cmap->getColumnName() ] =& $cmap;
    return $cmap;
  }

  /**
  * Add a column to the table.
  *
  * @param string name A String with the column name.
  * @param string $type A string specifying the PHP native type.
  * @param int $creoleType The integer representing the Creole type.
  * @param boolean $isNotNull Whether column does not allow NULL values.
  * @param int $size An int specifying the size.
  * @param boolean $pk True if column is a primary key.
  * @param string $fkTable A String with the foreign key table name.
  * @param $fkColumn A String with the foreign key column name.
  * @return ColumnMap The newly created column.
  */
  function & addColumn($name, $phpName, $type, $creoleType, $isNotNull = false, $size = null, $pk = null, $fkTable = null, $fkColumn = null)
  {
    $col =& new ColumnMap($name, $this);

    if ($fkTable && $fkColumn) {
      if (substr($fkColumn, '.') > 0 && substr($fkColumn, $fkTable) !== false) {
        $fkColumn = substr($fkColumn, strlen($fkTable) + 1);
      }
      $col->setForeignKey($fkTable, $fkColumn);
    }

    $col->setType($type);
    $col->setCreoleType($creoleType);
    $col->setPrimaryKey($pk);
    $col->setSize($size);
    $col->setPhpName($phpName);
    $col->setNotNull($isNotNull);

    $this->columns[$name] = $col;
    return $this->columns[$name];
  }

  /**
  * Add a validator to a table's column
  *
  * @param string $columnName The name of the validator's column
  * @param string $name The rule name of this validator
  * @param string $classname The dot-path name of class to use (e.g. myapp.propel.MyValidator)
  * @param string $value
  * @param string $message The error message which is returned on invalid values
  * @return void
  */
  function & addValidator($columnName, $name, $classname, $value, $message)
  {
    $col =& $this->getColumn($columnName);

    if ($col !== null)
    {
      $validator =& new ValidatorMap($col);
      $validator->setName($name);
      $validator->setClass($classname);
      $validator->setValue($value);
      $validator->setMessage($message);
      $col->addValidator($validator);
    }
  }

  /**
  * Set whether or not to use Id generator for primary key.
  * @param boolean $bit
  */
  function setUseIdGenerator($bit) 
  {
    $this->useIdGenerator = $bit;
  }

  /**
  * Sets the pk information needed to generate a key
  *
  * @param $pkInfo information needed to generate a key
  */
  function setPrimaryKeyMethodInfo($pkInfo)
  {
    $this->pkInfo = $pkInfo;
  }

  //---Utility methods for doing intelligent lookup of table names

  /**
  * Tell me if i have PREFIX in my string.
  *
  * @param data A String.
  * @return boolean True if prefix is contained in data.
  * @private
  */
  function hasPrefix($data)
  {
    return (substr($data, $this->getPrefix()) !== false);
  }

  /**
  * Removes the PREFIX.
  *
  * @param string $data A String.
  * @return string A String with data, but with prefix removed.
  * @private
  */
  function removePrefix($data)
  {
    return substr($data, strlen($this->getPrefix()));
  }

  /**
  * Removes the PREFIX, removes the underscores and makes
  * first letter caps.
  *
  * SCARAB_FOO_BAR becomes FooBar.
  *
  * @param data A String.
  * @return string A String with data processed.
  */
  function removeUnderScores($data)
  {
    $tmp = null;
    $out = "";
    if ($this->hasPrefix($data)) {
      $tmp = $this->removePrefix($data);
    } else {
      $tmp = $data;
    }

    $tok = strtok($tmp, "_");
    while ($tok) {
      $out .= ucfirst($tok);
      $tok = strtok("_");
    }
    return $out;
  }

  /**
  * Makes the first letter caps and the rest lowercase.
  *
  * @param string $data A String.
  * @return string A String with data processed.
  * @private
  */
  function firstLetterCaps($data)
  {
    return(ucfirst(strtolower($data)));
  }
}

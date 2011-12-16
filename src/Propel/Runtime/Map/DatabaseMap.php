<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Runtime\Map;

use Propel\Runtime\Map\Exception\TableNotFoundException;
use Propel\Runtime\Propel;

/**
 * DatabaseMap is used to model a database.
 *
 * GENERAL NOTE
 * ------------
 * The propel.map classes are abstract building-block classes for modeling
 * the database at runtime.  These classes are similar (a lite version) to the
 * propel.engine.database.model classes, which are build-time modeling classes.
 * These classes in themselves do not do any database metadata lookups.
 *
 * @author     Hans Lellelid <hans@xmpl.org> (Propel)
 * @author     John D. McNally <jmcnally@collab.net> (Torque)
 * @author     Daniel Rall <dlr@collab.net> (Torque)
 */
class DatabaseMap
{
    /**
     * Name of the database.
     *
     * @var string
     */
    protected $name;

    /**
     * Tables in the database, using table name as key
     *
     * @var array[\Propel\Runtime\Map\TableMap]
     */
    protected $tables = array();

    /**
     * Tables in the database, using table phpName as key
     *
     * @var array[\Propel\Runtime\Map\TableMap]
     */
    protected $tablesByPhpName = array();

    /**
     * Constructor.
     *
     * @param      string $name Name of the database.
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * Get the name of this database.
     *
     * @return     string The name of the database.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Add a new table to the database by name.
     *
     * @param      string $tableName The name of the table.
     * @return     \Propel\Runtime\Map\TableMap The newly created TableMap.
     */
    public function addTable($tableName)
    {
        $this->tables[$tableName] = new TableMap($tableName, $this);

        return $this->tables[$tableName];
    }

    /**
     * Add a new table object to the database.
     *
     * @param      \Propel\Runtime\Map\TableMap $table The table to add
     */
    public function addTableObject(TableMap $table)
    {
        $table->setDatabaseMap($this);
        $this->tables[$table->getName()] = $table;
        $this->tablesByPhpName[$table->getClassname()] = $table;
    }

    /**
     * Add a new table to the database, using the tablemap class name.
     *
     * @param      string $tableMapClass The name of the table map to add
     * @return     \Propel\Runtime\Map\TableMap The TableMap object
     */
    public function addTableFromMapClass($tableMapClass)
    {
        $table = new $tableMapClass();
        if(!$this->hasTable($table->getName())) {
            $this->addTableObject($table);

            return $table;
        } else {
            return $this->getTable($table->getName());
        }
    }

    /**
     * Does this database contain this specific table?
     *
     * @param      string $name The String representation of the table.
     * @return     boolean True if the database contains the table.
     */
    public function hasTable($name)
    {
        if ( strpos($name, '.') > 0) {
            $name = substr($name, 0, strpos($name, '.'));
        }

        return array_key_exists($name, $this->tables);
    }

    /**
     * Get a TableMap for the table by name.
     *
     * @param      string $name Name of the table.
     * @return     \Propel\Runtime\Map\TableMap	A TableMap
     * @throws     \Propel\Runtime\Map\Exception\TableNotFoundException	If the table is undefined
     */
    public function getTable($name)
    {
        if (!isset($this->tables[$name])) {
            throw new TableNotFoundException("Cannot fetch TableMap for undefined table: $name");
        }

        return $this->tables[$name];
    }

    /**
     * Get a TableMap[] of all of the tables in the database.
     *
     * @return     array[\Propel\Runtime\Map\TableMap].
     */
    public function getTables()
    {
        return $this->tables;
    }

    /**
     * Get a ColumnMap for the column by name.
     * Name must be fully qualified, e.g. book.AUTHOR_ID
     *
     * @param      $qualifiedColumnName Name of the column.
     * @return     \Propel\Runtime\Map\ColumnMap	A TableMap
     * @throws     \Propel\Runtime\Map\TableNotFoundException	If the table is undefined, or if the table is undefined
     */
    public function getColumn($qualifiedColumnName)
    {
        list($tableName, $columnName) = explode('.', $qualifiedColumnName);

        return $this->getTable($tableName)->getColumn($columnName, false);
    }

    public function getTableByPhpName($phpName)
    {
        if ('\\' === substr($phpName, 0, 1)) {
            $phpName = substr($phpName, 1);
        }
        if (array_key_exists($phpName, $this->tablesByPhpName)) {
            return $this->tablesByPhpName[$phpName];
        } elseif (class_exists($tmClass = $phpName . 'TableMap')) {
            $this->addTableFromMapClass($tmClass);

            return $this->tablesByPhpName[$phpName];
        } elseif (class_exists($tmClass = substr_replace($phpName, '\\Map\\', strrpos($phpName, '\\'), 1) . 'TableMap')) {
            $this->addTableFromMapClass($tmClass);

            return $this->tablesByPhpName[$phpName];
        } else {
            throw new TableNotFoundException("Cannot fetch TableMap for undefined table phpName: $phpName");
        }
    }

    /**
     * Convenience method to get the AbstractAdapter registered with Propel for this database.
     * @see     Propel::getServiceContainer()->getAdapter(string).
     *
     * @return  \Propel\Runtime\Adapter\AbstractAdapter
     */
    public function getAbstractAdapter()
    {
        return Propel::getServiceContainer()->getAdapter($this->name);
    }
}

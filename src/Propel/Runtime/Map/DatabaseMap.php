<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
 * the database at runtime. These classes are similar (a lite version) to the
 * propel.engine.database.model classes, which are build-time modeling classes.
 * These classes in themselves do not do any database metadata lookups.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author John D. McNally <jmcnally@collab.net> (Torque)
 * @author Daniel Rall <dlr@collab.net> (Torque)
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
     * @var \Propel\Runtime\Map\TableMap[]
     */
    protected $tables = [];

    /**
     * Tables in the database, using table phpName as key
     *
     * @var \Propel\Runtime\Map\TableMap[]
     */
    protected $tablesByPhpName = [];

    /**
     * @param string $name Name of the database.
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * Get the name of this database.
     *
     * @return string The name of the database.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Add a new table to the database by name.
     *
     * @param string $tableName The name of the table.
     *
     * @return \Propel\Runtime\Map\TableMap The newly created TableMap.
     */
    public function addTable($tableName)
    {
        $this->tables[$tableName] = new TableMap($tableName, $this);

        return $this->tables[$tableName];
    }

    /**
     * Add a new table object to the database.
     *
     * @param \Propel\Runtime\Map\TableMap $table The table to add
     *
     * @return void
     */
    public function addTableObject(TableMap $table)
    {
        $table->setDatabaseMap($this);
        $this->tables[$table->getName()] = $table;
        $phpName = $table->getClassName();
        if ($phpName && $phpName[0] !== '\\') {
            $phpName = '\\' . $phpName;
        }
        $this->tablesByPhpName[$phpName] = $table;
    }

    /**
     * Add a new table to the database, using the tablemap class name.
     *
     * @param string $tableMapClass The name of the table map to add
     *
     * @return \Propel\Runtime\Map\TableMap The TableMap object
     */
    public function addTableFromMapClass($tableMapClass)
    {
        $table = new $tableMapClass();
        if (!$this->hasTable($table->getName())) {
            $this->addTableObject($table);

            return $table;
        }

        return $this->getTable($table->getName());
    }

    /**
     * Does this database contain this specific table?
     *
     * @param string $name The String representation of the table.
     *
     * @return bool True if the database contains the table.
     */
    public function hasTable($name)
    {
        if (strpos($name, '.') > 0) {
            $name = substr($name, 0, strpos($name, '.'));
        }

        return isset($this->tables[$name]);
    }

    /**
     * Get a TableMap for the table by name.
     *
     * @param string $name Name of the table.
     *
     * @throws \Propel\Runtime\Map\Exception\TableNotFoundException If the table is undefined
     *
     * @return \Propel\Runtime\Map\TableMap A TableMap
     */
    public function getTable($name)
    {
        if (!isset($this->tables[$name])) {
            throw new TableNotFoundException(sprintf('Cannot fetch TableMap for undefined table: %s.', $name));
        }

        return $this->tables[$name];
    }

    /**
     * Get a TableMap[] of all of the tables in the database.
     *
     * @return \Propel\Runtime\Map\TableMap[]
     */
    public function getTables()
    {
        return $this->tables;
    }

    /**
     * Get a ColumnMap for the column by name.
     * Name must be fully qualified, e.g. book.AUTHOR_ID
     *
     * @param string $qualifiedColumnName Name of the column.
     *
     * @return \Propel\Runtime\Map\ColumnMap A TableMap
     */
    public function getColumn($qualifiedColumnName)
    {
        [$tableName, $columnName] = explode('.', $qualifiedColumnName);

        return $this->getTable($tableName)->getColumn($columnName, false);
    }

    /**
     * @param string $phpName
     *
     * @throws \Propel\Runtime\Map\Exception\TableNotFoundException
     *
     * @return \Propel\Runtime\Map\TableMap
     */
    public function getTableByPhpName($phpName)
    {
        if ($phpName[0] !== '\\') {
            $phpName = '\\' . $phpName;
        }
        if (isset($this->tablesByPhpName[$phpName])) {
            return $this->tablesByPhpName[$phpName];
        }

        if (class_exists($tmClass = $phpName . 'TableMap')) {
            $this->addTableFromMapClass($tmClass);

            return $this->tablesByPhpName[$phpName];
        }

        if (
            class_exists($tmClass = substr_replace($phpName, '\\Map\\', strrpos($phpName, '\\'), 1) . 'TableMap')
            || class_exists($tmClass = '\\Map\\' . $phpName . 'TableMap')
        ) {
            $this->addTableFromMapClass($tmClass);

            if (isset($this->tablesByPhpName[$phpName])) {
                return $this->tablesByPhpName[$phpName];
            }

            if (isset($this->tablesByPhpName[$phpName])) {
                return $this->tablesByPhpName[$phpName];
            }
        }

        throw new TableNotFoundException(sprintf('Cannot fetch TableMap for undefined table phpName: %s.', $phpName));
    }

    /**
     * Convenience method to get the AdapterInterface registered with Propel for this database.
     *
     * @see Propel::getServiceContainer()->getAdapter(string) .
     *
     * @return \Propel\Runtime\Adapter\AdapterInterface
     */
    public function getAbstractAdapter()
    {
        return Propel::getServiceContainer()->getAdapter($this->name);
    }
}

<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\Map;

use Propel\Runtime\Adapter\AdapterInterface;
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
 *
 * @psalm-consistent-constructor (instantiated by class name in StandardServiceContainer without arguments)
 *
 * @psalm-type \Propel\Runtime\Map\MapType = 'tablesByName' | 'tablesByPhpName'
 * @psalm-type \Propel\Runtime\Map\TableMapDump array<\Propel\Runtime\Map\MapType, array<string, class-string<\Propel\Runtime\Map\TableMap>>>
 */
class DatabaseMap
{
    /**
     * Name of the database.
     *
     * @var string
     */
    protected string $name;

    /**
     * Tables in the database, using table name as key
     *
     * @var array<string, \Propel\Runtime\Map\TableMap|class-string<\Propel\Runtime\Map\TableMap>>
     */
    protected $tables = [];

    /**
     * Tables in the database, using table phpName as key
     *
     * @var array<string, \Propel\Runtime\Map\TableMap|class-string<\Propel\Runtime\Map\TableMap>>
     */
    protected $tablesByPhpName = [];

    /**
     * @param string $name Name of the database.
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * Get the name of this database.
     *
     * @return string The name of the database.
     */
    public function getName(): string
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
    public function addTable(string $tableName): TableMap
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
    public function addTableObject(TableMap $table): void
    {
        $table->setDatabaseMap($this);

        $tableName = $table->getName();
        if ($tableName && (!$this->hasTable($tableName) || is_string($this->tables[$tableName]))) {
            $this->tables[$tableName] = $table;
        }

        $phpName = $table->getClassName();
        $this->addTableByPhpName($phpName, $table);
    }

    /**
     * @param string|null $phpName
     * @param \Propel\Runtime\Map\TableMap|class-string<\Propel\Runtime\Map\TableMap> $tableOrClassMap
     *
     * @return void
     */
    protected function addTableByPhpName(?string $phpName, $tableOrClassMap): void
    {
        if (!$phpName) {
            return;
        }
        if ($phpName[0] !== '\\') {
            $phpName = '\\' . $phpName;
        }
        $this->tablesByPhpName[$phpName] = $tableOrClassMap;
    }

    /**
     * Add a new table to the database, using the tablemap class name.
     *
     * @param class-string<\Propel\Runtime\Map\TableMap> $tableMapClass The name of the table map to add
     *
     * @return \Propel\Runtime\Map\TableMap The TableMap object
     */
    public function addTableFromMapClass(string $tableMapClass): TableMap
    {
        /** @var \Propel\Runtime\Map\TableMap $table */
        $table = new $tableMapClass();
        $this->addTableObject($table);

        return $this->getTable((string)$table->getName());
    }

    /**
     * Dump table maps. Used during configuration generation.
     *
     * @psalm-return \Propel\Runtime\Map\TableMapDump
     *
     * @return array<string, array<string, class-string<\Propel\Runtime\Map\TableMap>>> A dump that can be loaded again with {@link DatabaseMap::loadMapsFromDump()}
     */
    public function dumpMaps(): array
    {
        /**
         * @psalm-var \Closure( class-string<\Propel\Runtime\Map\TableMap>|\Propel\Runtime\Map\TableMap ): class-string<\Propel\Runtime\Map\TableMap>
         */
        $toClassString = fn ($tableMap) => is_string($tableMap) ? $tableMap : get_class($tableMap);

        return [
            'tablesByName' => array_map($toClassString, $this->tables),
            'tablesByPhpName' => array_map($toClassString, $this->tablesByPhpName),
        ];
    }

    /**
     * Load internal table maps from dump. Used during Propel initialization.
     *
     * @psalm-param \Propel\Runtime\Map\TableMapDump $mapsDump
     *
     * @param array<string, array<string, class-string<\Propel\Runtime\Map\TableMap>>> $mapsDump Table map dump as created by {@link DatabaseMap::dumpMaps()}
     *
     * @return void
     */
    public function loadMapsFromDump(array $mapsDump): void
    {
        $this->tables = $mapsDump['tablesByName'];
        $this->tablesByPhpName = $mapsDump['tablesByPhpName'];
    }

    /**
     * Registers a table map classes (by qualified name) as table belonging
     * to this database.
     *
     * Classes added like this will only be instantiated when accessed
     * through {@link DatabaseMap::getTable()},
     * {@link DatabaseMap::getTableByPhpName()}, or
     * {@link DatabaseMap::getTables()}
     *
     * @param class-string<\Propel\Runtime\Map\TableMap> $tableMapClass The name of the table map to add
     *
     * @return void
     */
    public function registerTableMapClass(string $tableMapClass): void
    {
        $tableName = $tableMapClass::TABLE_NAME;
        $tablePhpName = $tableMapClass::TABLE_PHP_NAME;
        $this->registerTableMapClassByName($tableName, $tablePhpName, $tableMapClass);
    }

    /**
     * Registers a table map classes (by qualified name) as table belonging
     * to this database.
     *
     * Classes added like this will only be instantiated when accessed
     * through {@link DatabaseMap::getTable()},
     * {@link DatabaseMap::getTableByPhpName()}, or
     * {@link DatabaseMap::getTables()}
     *
     * @param string $tableName Internal name of the table, i.e. 'bookstore_schemas.book'
     * @param string|null $tablePhpName PHP name of the table, i.e. 'Book'
     * @param class-string<\Propel\Runtime\Map\TableMap> $tableMapClass The name of the table map to add
     *
     * @return void
     */
    public function registerTableMapClassByName(string $tableName, ?string $tablePhpName, string $tableMapClass): void
    {
        $this->tables[$tableName] = $tableMapClass;
        $this->addTableByPhpName($tablePhpName, $tableMapClass);
    }

    /**
     * Registers a list of table map classes (by qualified name) as table maps
     * belonging to this database.
     *
     * @param array<class-string<\Propel\Runtime\Map\TableMap>> $tableMapClasses
     *
     * @return void
     */
    public function registerTableMapClasses(array $tableMapClasses): void
    {
        array_map([$this, 'registerTableMapClass'], $tableMapClasses);
    }

    /**
     * Does this database contain this specific table?
     *
     * @param string $name The String representation of the table.
     *
     * @return bool True if the database contains the table.
     */
    public function hasTable(string $name): bool
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
    public function getTable(string $name): TableMap
    {
        if (!isset($this->tables[$name])) {
            throw new TableNotFoundException(sprintf('Cannot fetch TableMap for undefined table `%s` in database `%s`.', $name, $this->getName()));
        }

        $tableOrClass = $this->tables[$name];

        return is_string($tableOrClass) ? $this->addTableFromMapClass($tableOrClass) : $tableOrClass;
    }

    /**
     * Get a TableMap[] of all of the tables in the database.
     *
     * If tables are registered by class map name, they will be instantiated.
     *
     * @return array<\Propel\Runtime\Map\TableMap>
     */
    public function getTables(): array
    {
        foreach ($this->tables as $tableOrClassMap) {
            if (!is_string($tableOrClassMap)) {
                continue;
            }
            $this->addTableFromMapClass($tableOrClassMap);
        }

        /** @var array<\Propel\Runtime\Map\TableMap> */
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
    public function getColumn(string $qualifiedColumnName): ColumnMap
    {
        [$tableName, $columnName] = explode('.', $qualifiedColumnName);

        return $this->getTable($tableName)->getColumn($columnName, false);
    }

    /**
     * @param string $phpName
     *
     * @return \Propel\Runtime\Map\TableMap
     */
    public function getTableByPhpName(string $phpName): TableMap
    {
        if ($phpName[0] !== '\\') {
            $phpName = '\\' . $phpName;
        }

        /** @var \Propel\Runtime\Map\TableMap|class-string<\Propel\Runtime\Map\TableMap> $tableMapOrTableMapClassName */
        $tableMapOrTableMapClassName = (isset($this->tablesByPhpName[$phpName]))
            ? $this->tablesByPhpName[$phpName]
            : $this->determineTableMapClassNameByPhpName($phpName);

        if (is_string($tableMapOrTableMapClassName)) {
            return $this->addTableFromMapClass($tableMapOrTableMapClassName);
        }

        return $tableMapOrTableMapClassName;
    }

    /**
     * @param string $phpName
     *
     * @throws \Propel\Runtime\Map\Exception\TableNotFoundException
     *
     * @return string
     */
    protected function determineTableMapClassNameByPhpName(string $phpName): string
    {
        foreach ($this->buildTableMapClassNamesByPhpName($phpName) as $tableMapClassName) {
            if (class_exists($tableMapClassName)) {
                return $tableMapClassName;
            }
        }

        throw new TableNotFoundException(sprintf('Cannot fetch TableMap for undefined table phpName: %s in database %s.', $phpName, $this->getName()));
    }

    /**
     * @param string $phpName
     *
     * @return list<string>
     */
    protected function buildTableMapClassNamesByPhpName(string $phpName): array
    {
        return [
            $phpName . 'TableMap',
            substr_replace($phpName, '\\Map\\', (int)strrpos($phpName, '\\'), 1) . 'TableMap',
            '\\Map\\' . $phpName . 'TableMap',
        ];
    }

    /**
     * Convenience method to get the AdapterInterface registered with Propel for this database.
     *
     * @see Propel::getServiceContainer()->getAdapter(string) .
     *
     * @return \Propel\Runtime\Adapter\AdapterInterface
     */
    public function getAbstractAdapter(): AdapterInterface
    {
        return Propel::getServiceContainer()->getAdapter($this->name);
    }
}

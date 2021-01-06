<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\Map;

use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Map\Exception\ColumnNotFoundException;
use Propel\Runtime\Map\Exception\RelationNotFoundException;
use Propel\Runtime\Propel;

/**
 * TableMap is used to model a table in a database.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author John D. McNally <jmcnally@collab.net> (Torque)
 * @author Daniel Rall <dlr@finemaltcoding.com> (Torque)
 * @author William Durand <william.durand1@gmail.com>
 */
class TableMap
{
    /**
     * phpname type
     * e.g. 'AuthorId'
     */
    public const TYPE_PHPNAME = 'phpName';

    /**
     * camelCase type
     * e.g. 'authorId'
     */
    public const TYPE_CAMELNAME = 'camelName';

    /**
     * column (tableMap) name type
     * e.g. 'book.AUTHOR_ID'
     */
    public const TYPE_COLNAME = 'colName';

    /**
     * column fieldname type
     * e.g. 'author_id'
     */
    public const TYPE_FIELDNAME = 'fieldName';

    /**
     * num type
     * simply the numerical array index, e.g. 4
     */
    public const TYPE_NUM = 'num';

    /**
     * Columns in the table
     *
     * @var \Propel\Runtime\Map\ColumnMap[]
     */
    protected $columns = [];

    /**
     * Columns in the table, using table phpName as key
     *
     * @var \Propel\Runtime\Map\ColumnMap[]
     */
    protected $columnsByPhpName = [];

    /**
     * Map of normalized column names
     *
     * @var string[]
     */
    protected $normalizedColumnNameMap = [];

    /**
     * The database this table belongs to
     *
     * @var \Propel\Runtime\Map\DatabaseMap
     */
    protected $dbMap;

    /**
     * The name of the table
     *
     * @var string
     */
    protected $tableName;

    /**
     * The PHP name of the table
     *
     * @var string
     */
    protected $phpName;

    /**
     * The ClassName for this table
     *
     * @var string
     */
    protected $classname;

    /**
     * The Package for this table
     *
     * @var string
     */
    protected $package;

    /**
     * Whether to use an id generator for pkey
     *
     * @var bool
     */
    protected $useIdGenerator = false;

    /**
     * Whether the table uses single table inheritance
     *
     * @var bool
     */
    protected $isSingleTableInheritance = false;

    /**
     * Whether the table is a Many to Many table
     *
     * @var bool
     */
    protected $isCrossRef = false;

    /**
     * The primary key columns in the table
     *
     * @var \Propel\Runtime\Map\ColumnMap[]
     */
    protected $primaryKeys = [];

    /**
     * The foreign key columns in the table
     *
     * @var \Propel\Runtime\Map\ColumnMap[]
     */
    protected $foreignKeys = [];

    /**
     *  The relationships in the table
     *
     * @var \Propel\Runtime\Map\RelationMap[]
     */
    protected $relations = [];

    /**
     *  Relations are lazy loaded. This property tells if the relations are loaded or not
     *
     * @var bool
     */
    protected $relationsBuilt = false;

    /**
     *  Object to store information that is needed if the for generating primary keys
     *
     * @var mixed
     */
    protected $pkInfo;

    /**
     * @var bool
     */
    protected $identifierQuoting = false;

    /**
     * Construct a new TableMap.
     *
     * @param string|null $name
     * @param \Propel\Runtime\Map\DatabaseMap|null $dbMap
     */
    public function __construct($name = null, $dbMap = null)
    {
        if ($name !== null) {
            $this->setName($name);
        }
        if ($dbMap !== null) {
            $this->setDatabaseMap($dbMap);
        }
        $this->initialize();
    }

    /**
     * Initialize the TableMap to build columns, relations, etc
     * This method should be overridden by descendants
     *
     * @return void
     */
    public function initialize()
    {
    }

    /**
     * Set the DatabaseMap containing this TableMap.
     *
     * @param \Propel\Runtime\Map\DatabaseMap $dbMap A DatabaseMap.
     *
     * @return void
     */
    public function setDatabaseMap(DatabaseMap $dbMap)
    {
        $this->dbMap = $dbMap;
    }

    /**
     * Get the DatabaseMap containing this TableMap.
     *
     * @return \Propel\Runtime\Map\DatabaseMap A DatabaseMap.
     */
    public function getDatabaseMap()
    {
        return $this->dbMap;
    }

    /**
     * Set the name of the Table.
     *
     * @param string $name The name of the table.
     *
     * @return void
     */
    public function setName($name)
    {
        $this->tableName = $name;
    }

    /**
     * Get the name of the Table.
     *
     * @return string A String with the name of the table.
     */
    public function getName()
    {
        return $this->tableName;
    }

    /**
     * Set the PHP name of the Table.
     *
     * @param string $phpName The PHP Name for this table
     *
     * @return void
     */
    public function setPhpName($phpName)
    {
        $this->phpName = $phpName;
    }

    /**
     * Get the PHP name of the Table.
     *
     * @return string A String with the name of the table.
     */
    public function getPhpName()
    {
        return $this->phpName;
    }

    /**
     * Set the ClassName of the Table. Could be useful for calling
     * tableMap and Object methods dynamically.
     *
     * @param string $classname The ClassName
     *
     * @return void
     */
    public function setClassName($classname)
    {
        $this->classname = $classname;
    }

    /**
     * Get the ClassName of the Propel Class belonging to this table.
     *
     * @return string
     */
    public function getClassName()
    {
        return $this->classname;
    }

    /**
     * Get the Collection ClassName to this table.
     *
     * @return string
     */
    public function getCollectionClassName()
    {
        $collectionClass = $this->getClassName() . 'Collection';
        if (class_exists($collectionClass)) {
            return $collectionClass;
        }

        return '\Propel\Runtime\Collection\ObjectCollection';
    }

    /**
     * Set the Package of the Table
     *
     * @param string $package The Package
     *
     * @return void
     */
    public function setPackage($package)
    {
        $this->package = $package;
    }

    /**
     * Get the Package of the table.
     *
     * @return string
     */
    public function getPackage()
    {
        return $this->package;
    }

    /**
     * Set whether or not to use Id generator for primary key.
     *
     * @param bool $bit
     *
     * @return void
     */
    public function setUseIdGenerator($bit)
    {
        $this->useIdGenerator = (bool)$bit;
    }

    /**
     * Whether to use Id generator for primary key.
     *
     * @return bool
     */
    public function isUseIdGenerator()
    {
        return $this->useIdGenerator;
    }

    /**
     * Set whether or not to this table uses single table inheritance
     *
     * @param bool $bit
     *
     * @return void
     */
    public function setSingleTableInheritance($bit)
    {
        $this->isSingleTableInheritance = $bit;
    }

    /**
     * Whether this table uses single table inheritance
     *
     * @return bool
     */
    public function isSingleTableInheritance()
    {
        return $this->isSingleTableInheritance;
    }

    /**
     * Sets the name of the sequence used to generate a key
     *
     * @param mixed $pkInfo information needed to generate a key
     *
     * @return void
     */
    public function setPrimaryKeyMethodInfo($pkInfo)
    {
        $this->pkInfo = $pkInfo;
    }

    /**
     * Get the name of the sequence used to generate a primary key
     *
     * @return mixed
     */
    public function getPrimaryKeyMethodInfo()
    {
        return $this->pkInfo;
    }

    /**
     * Helper method which returns the primary key contained
     * in the given Criteria object.
     *
     * @param \Propel\Runtime\ActiveQuery\Criteria $criteria A Criteria.
     *
     * @return \Propel\Runtime\Map\ColumnMap If the Criteria object contains a primary key, or null if it doesn't.
     */
    private static function getPrimaryKey(Criteria $criteria)
    {
        // Assume all the keys are for the same table.
        $keys = $criteria->keys();
        $key = $keys[0];
        $table = $criteria->getTableName($key);

        $pk = null;

        if (!empty($table)) {
            $dbMap = Propel::getServiceContainer()->getDatabaseMap($criteria->getDbName());

            $pks = $dbMap->getTable($table)->getPrimaryKeys();
            if (!empty($pks)) {
                $pk = array_shift($pks);
            }
        }

        return $pk;
    }

    /**
     * @param string $columnName
     *
     * @return string
     */
    protected function getNormalizedColumnName(string $columnName): string
    {
        return $this->normalizedColumnNameMap[$columnName] ?? ColumnMap::normalizeName($columnName);
    }

    /**
     * Add a column to the table.
     *
     * @param string $name A String with the column name.
     * @param string $phpName A string representing the PHP name.
     * @param string $type A string specifying the Propel type.
     * @param bool $isNotNull Whether column does not allow NULL values.
     * @param int|null $size An int specifying the size.
     * @param string|null $defaultValue
     * @param bool|null $pk True if column is a primary key.
     * @param string|bool|null $fkTable A String with the foreign key table name.
     * @param string|null $fkColumn A String with the foreign key column name.
     *
     * @return \Propel\Runtime\Map\ColumnMap The newly created column.
     */
    public function addColumn($name, $phpName, $type, $isNotNull = false, $size = null, $defaultValue = null, $pk = false, $fkTable = null, $fkColumn = null)
    {
        $col = new ColumnMap($name, $this);
        $col->setType($type);
        $col->setSize($size);
        $col->setPhpName($phpName);
        $col->setNotNull($isNotNull);
        $col->setDefaultValue($defaultValue);

        if ($pk) {
            $col->setPrimaryKey(true);
            $this->primaryKeys[$name] = $col;
        }

        if ($fkTable && $fkColumn) {
            $col->setForeignKey($fkTable, $fkColumn);
            $this->foreignKeys[$name] = $col;
        }

        $this->columns[$this->getNormalizedColumnName($name)] = $col;
        $this->columnsByPhpName[$phpName] = $col;

        return $col;
    }

    /**
     * Add a pre-created column to this table. It will replace any
     * existing column.
     *
     * @param \Propel\Runtime\Map\ColumnMap $cmap A ColumnMap.
     *
     * @return \Propel\Runtime\Map\ColumnMap The added column map.
     */
    public function addConfiguredColumn(ColumnMap $cmap)
    {
        $this->columns[$cmap->getName()] = $cmap;

        return $cmap;
    }

    /**
     * Does this table contain the specified column?
     *
     * @param mixed $name name of the column or ColumnMap instance
     * @param bool $normalize Normalize the column name (if column name not like FIRST_NAME)
     *
     * @return bool True if the table contains the column.
     */
    public function hasColumn($name, $normalize = true)
    {
        if ($name instanceof ColumnMap) {
            $name = $name->getName();
        } elseif ($normalize) {
            $name = $this->getNormalizedColumnName($name);
        }

        return isset($this->columns[$name]);
    }

    /**
     * Get a ColumnMap for the table.
     *
     * @param string $name A String with the name of the table.
     * @param bool $normalize Normalize the column name (if column name not like FIRST_NAME)
     *
     * @throws \Propel\Runtime\Map\Exception\ColumnNotFoundException If the column is undefined
     *
     * @return \Propel\Runtime\Map\ColumnMap A ColumnMap.
     */
    public function getColumn($name, $normalize = true)
    {
        if ($normalize) {
            $name = $this->getNormalizedColumnName($name);
        }
        if (!$this->hasColumn($name, false)) {
            throw new ColumnNotFoundException(sprintf('Cannot fetch ColumnMap for undefined column: %s in table %s.', $name, $this->getName()));
        }

        return $this->columns[$name];
    }

    /**
     * Does this table contain the specified column?
     *
     * @param mixed $phpName name of the column
     *
     * @return bool True if the table contains the column.
     */
    public function hasColumnByPhpName($phpName)
    {
        return isset($this->columnsByPhpName[$phpName]);
    }

    /**
     * Get a ColumnMap for the table.
     *
     * @param string $phpName A String with the name of the table.
     *
     * @throws \Propel\Runtime\Map\Exception\ColumnNotFoundException If the column is undefined
     *
     * @return \Propel\Runtime\Map\ColumnMap A ColumnMap.
     */
    public function getColumnByPhpName($phpName)
    {
        if (!isset($this->columnsByPhpName[$phpName])) {
            throw new ColumnNotFoundException("Cannot fetch ColumnMap for undefined column phpName: $phpName");
        }

        return $this->columnsByPhpName[$phpName];
    }

    /**
     * Get a ColumnMap[] of the columns in this table.
     *
     * @return \Propel\Runtime\Map\ColumnMap[]
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * Add a primary key column to this Table.
     *
     * @param string $columnName A String with the column name.
     * @param string $phpName A string representing the PHP name.
     * @param string $type A string specifying the Propel type.
     * @param bool $isNotNull Whether column does not allow NULL values.
     * @param int|null $size An int specifying the size.
     * @param string|null $defaultValue The default value for this column.
     *
     * @return \Propel\Runtime\Map\ColumnMap Newly added PrimaryKey column.
     */
    public function addPrimaryKey($columnName, $phpName, $type, $isNotNull = false, $size = null, $defaultValue = null)
    {
        return $this->addColumn($columnName, $phpName, $type, $isNotNull, $size, $defaultValue, true, null, null);
    }

    /**
     * Add a foreign key column to the table.
     *
     * @param string $columnName A String with the column name.
     * @param string $phpName A string representing the PHP name.
     * @param string $type A string specifying the Propel type.
     * @param string $fkTable A String with the foreign key table name.
     * @param string $fkColumn A String with the foreign key column name.
     * @param bool $isNotNull Whether column does not allow NULL values.
     * @param int $size An int specifying the size.
     * @param string|null $defaultValue The default value for this column.
     *
     * @return \Propel\Runtime\Map\ColumnMap Newly added ForeignKey column.
     */
    public function addForeignKey($columnName, $phpName, $type, $fkTable, $fkColumn, $isNotNull = false, $size = 0, $defaultValue = null)
    {
        return $this->addColumn($columnName, $phpName, $type, $isNotNull, $size, $defaultValue, false, $fkTable, $fkColumn);
    }

    /**
     * Add a foreign primary key column to the table.
     *
     * @param string $columnName A String with the column name.
     * @param string $phpName A string representing the PHP name.
     * @param string $type A string specifying the Propel type.
     * @param string $fkTable A String with the foreign key table name.
     * @param string $fkColumn A String with the foreign key column name.
     * @param bool $isNotNull Whether column does not allow NULL values.
     * @param int $size An int specifying the size.
     * @param string|null $defaultValue The default value for this column.
     *
     * @return \Propel\Runtime\Map\ColumnMap Newly created foreign pkey column.
     */
    public function addForeignPrimaryKey($columnName, $phpName, $type, $fkTable, $fkColumn, $isNotNull = false, $size = 0, $defaultValue = null)
    {
        return $this->addColumn($columnName, $phpName, $type, $isNotNull, $size, $defaultValue, true, $fkTable, $fkColumn);
    }

    /**
     * @return bool true if the table is a many to many
     */
    public function isCrossRef()
    {
        return $this->isCrossRef;
    }

    /**
     * Set the isCrossRef

     * @param bool $isCrossRef
     *
     * @return void
     */
    public function setIsCrossRef($isCrossRef)
    {
        $this->isCrossRef = $isCrossRef;
    }

    /**
     * Returns array of ColumnMap objects that make up the primary key for this table
     *
     * @return \Propel\Runtime\Map\ColumnMap[]
     */
    public function getPrimaryKeys()
    {
        return $this->primaryKeys;
    }

    /**
     * Returns array of ColumnMap objects that are foreign keys for this table
     *
     * @return \Propel\Runtime\Map\ColumnMap[]
     */
    public function getForeignKeys()
    {
        return $this->foreignKeys;
    }

    /**
     * Build relations
     * Relations are lazy loaded for performance reasons
     * This method should be overridden by descendants
     *
     * @return void
     */
    public function buildRelations()
    {
    }

    /**
     * Adds a RelationMap to the table
     *
     * @param string $name The relation name
     * @param string $tablePhpName The related table name
     * @param int $type The relation type (either RelationMap::MANY_TO_ONE, RelationMap::ONE_TO_MANY, or RelationMAp::ONE_TO_ONE)
     * @param array $joinConditionMapping Arrays in array defining a normalize join condition [[':foreign_id', ':id', '='], [':foreign_type', 'value', '=']]
     * @param string|null $onDelete SQL behavior upon deletion ('SET NULL', 'CASCADE', ...)
     * @param string|null $onUpdate SQL behavior upon update ('SET NULL', 'CASCADE', ...)
     * @param string|null $pluralName Optional plural name for *_TO_MANY relationships
     * @param bool $polymorphic Optional plural name for *_TO_MANY relationships
     *
     * @return \Propel\Runtime\Map\RelationMap the built RelationMap object
     */
    public function addRelation(
        $name,
        $tablePhpName,
        $type,
        $joinConditionMapping = [],
        $onDelete = null,
        $onUpdate = null,
        $pluralName = null,
        $polymorphic = false
    ) {
        // note: using phpName for the second table allows the use of DatabaseMap::getTableByPhpName()
        // and this method autoloads the TableMap if the table isn't loaded yet
        $relation = new RelationMap($name);
        $relation->setType($type);
        $relation->setOnUpdate($onUpdate);
        $relation->setOnDelete($onDelete);
        $relation->setPolymorphic($polymorphic);

        if ($pluralName !== null) {
            $relation->setPluralName($pluralName);
        }
        // set tables
        if ($type === RelationMap::MANY_TO_ONE) {
            $relation->setLocalTable($this);
            $relation->setForeignTable($this->dbMap->getTableByPhpName($tablePhpName));
        } else {
            $relation->setLocalTable($this->dbMap->getTableByPhpName($tablePhpName));
            $relation->setForeignTable($this);
        }
        // set columns
        foreach ($joinConditionMapping as $map) {
            [$local, $foreign] = $map;
            $relation->addColumnMapping(
                $this->getColumnOrValue($local, $relation->getLocalTable()),
                $this->getColumnOrValue($foreign, $relation->getForeignTable())
            );
        }
        $this->relations[$name] = $relation;

        return $relation;
    }

    /**
     * @param string $value values with starting ':' mean a column name, otherwise a regular value.
     * @param \Propel\Runtime\Map\TableMap $table
     *
     * @return \Propel\Runtime\Map\ColumnMap|mixed
     */
    protected function getColumnOrValue($value, TableMap $table)
    {
        if (substr($value, 0, 1) === ':') {
            return $table->getColumn(substr($value, 1));
        } else {
            return $value;
        }
    }

    /**
     * Gets a RelationMap of the table by relation name
     * This method will build the relations if they are not built yet
     *
     * @param string $name The relation name
     *
     * @return bool true if the relation exists
     */
    public function hasRelation($name)
    {
        return array_key_exists($name, $this->getRelations());
    }

    /**
     * Gets a RelationMap of the table by relation name
     * This method will build the relations if they are not built yet
     *
     * @param string $name The relation name
     *
     * @throws \Propel\Runtime\Map\Exception\RelationNotFoundException When called on an inexistent relation
     *
     * @return \Propel\Runtime\Map\RelationMap The relation object
     */
    public function getRelation($name)
    {
        if (!array_key_exists($name, $this->getRelations())) {
            throw new RelationNotFoundException(sprintf('Calling getRelation() on an unknown relation: %s.', $name));
        }

        return $this->relations[$name];
    }

    /**
     * Gets the RelationMap objects of the table
     * This method will build the relations if they are not built yet
     *
     * @return \Propel\Runtime\Map\RelationMap[] list of RelationMap objects
     */
    public function getRelations()
    {
        if (!$this->relationsBuilt) {
            $this->buildRelations();
            $this->relationsBuilt = true;
        }

        return $this->relations;
    }

    /**
     * Gets the list of behaviors registered for this table
     *
     * @return array
     */
    public function getBehaviors()
    {
        return [];
    }

    /**
     * Does this table has a primaryString column?
     *
     * @return bool True if the table has a primaryString column.
     */
    public function hasPrimaryStringColumn()
    {
        return $this->getPrimaryStringColumn() !== null;
    }

    /**
     * Gets the ColumnMap for the primary string column.
     *
     * @return \Propel\Runtime\Map\ColumnMap|null
     */
    public function getPrimaryStringColumn()
    {
        foreach ($this->getColumns() as $column) {
            if ($column->isPrimaryString()) {
                return $column;
            }
        }

        return null;
    }

    /**
     * @param string $classname
     * @param string $type
     *
     * @return mixed
     */
    public static function getFieldnamesForClass($classname, $type = TableMap::TYPE_PHPNAME)
    {
        $callable = [$classname::TABLE_MAP, 'getFieldnames'];

        return call_user_func($callable, $type);
    }

    /**
     * @param string $classname
     * @param string $fieldname
     * @param string $fromType
     * @param string $toType
     *
     * @return mixed
     */
    public static function translateFieldnameForClass($classname, $fieldname, $fromType, $toType)
    {
        $callable = [$classname::TABLE_MAP, 'translateFieldname'];
        $args = [$fieldname, $fromType, $toType];

        return call_user_func_array($callable, $args);
    }

    /**
     * @return bool
     */
    public function isIdentifierQuotingEnabled()
    {
        return $this->identifierQuoting;
    }

    /**
     * @param bool $identifierQuoting
     *
     * @return void
     */
    public function setIdentifierQuoting($identifierQuoting)
    {
        $this->identifierQuoting = $identifierQuoting;
    }

    /**
     * @param \Propel\Runtime\ActiveQuery\Criteria $criteria
     *
     * @return array|null null if not covered by only pk
     */
    public function extractPrimaryKey(Criteria $criteria)
    {
        $pkCols = $this->getPrimaryKeys();
        if (count($pkCols) !== count($criteria->getMap())) {
            return null;
        }

        $pk = [];
        foreach ($pkCols as $pkCol) {
            $fqName = $pkCol->getFullyQualifiedName();
            $name = $pkCol->getName();

            if ($criteria->containsKey($fqName)) {
                $value = $criteria->getValue($fqName);
            } elseif ($criteria->containsKey($name)) {
                $value = $criteria->getValue($name);
            } else {
                return null;
            }

            $pk[$name] = $value;
        }

        return $pk;
    }
}

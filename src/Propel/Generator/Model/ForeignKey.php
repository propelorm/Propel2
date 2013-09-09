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
 * A class for information about table foreign keys.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Fedor <fedor.karpelevitch@home.com>
 * @author Daniel Rall <dlr@finemaltcoding.com>
 * @author Ulf Hermann <ulfhermann@kulturserver.de>
 * @author Hugo Hamon <webmaster@apprendre-php.com> (Propel)
 */
class ForeignKey extends MappingModel
{
    /**
     * These constants are the uppercase equivalents of the onDelete / onUpdate
     * values in the schema definition.
     *
     */
    const NONE       = '';           // No 'ON [ DELETE | UPDATE]' behavior
    const NOACTION   = 'NO ACTION';
    const CASCADE    = 'CASCADE';
    const RESTRICT   = 'RESTRICT';
    const SETDEFAULT = 'SET DEFAULT';
    const SETNULL    = 'SET NULL';

    /**
     * @var string
     */
    private $foreignTableCommonName;

    /**
     * @var string
     */
    private $foreignSchemaName;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $phpName;

    /**
     * @var string
     */
    private $refPhpName;

    /**
     * @var string
     */
    private $defaultJoin;

    /**
     * @var string
     */
    private $onUpdate = '';

    /**
     * @var string
     */
    private $onDelete = '';

    /**
     * @var Table
     */
    private $parentTable;

    /**
     * @var string[]
     */
    private $localColumns;

    /**
     * @var string[]
     */
    private $foreignColumns;

    /**
     * @var bool
     */
    private $skipSql;

    /**
     * Constructs a new ForeignKey object.
     *
     * @param string $name
     */
    public function __construct($name = null)
    {
        parent::__construct();

        if (null !== $name) {
            $this->setName($name);
        }

        $this->onUpdate       = self::NONE;
        $this->onDelete       = self::NONE;
        $this->localColumns   = [];
        $this->foreignColumns = [];
        $this->skipSql        = false;
    }

    protected function setupObject()
    {
        $this->foreignTableCommonName = $this->parentTable->getDatabase()->getTablePrefix() . $this->getAttribute('foreignTable');
        $this->foreignSchemaName      = $this->getAttribute('foreignSchema');

        if (!$this->foreignSchemaName && $schema = $this->getSchemaName()) {
            $this->foreignSchemaName = $schema;
        }

        $this->name        = $this->getAttribute('name');
        $this->phpName     = $this->getAttribute('phpName');
        $this->refPhpName  = $this->getAttribute('refPhpName');
        $this->defaultJoin = $this->getAttribute('defaultJoin');
        $this->onUpdate    = $this->normalizeFKey($this->getAttribute('onUpdate'));
        $this->onDelete    = $this->normalizeFKey($this->getAttribute('onDelete'));
        $this->skipSql     = $this->booleanValue($this->getAttribute('skipSql'));
    }

    /**
     * Returns the normalized input of onDelete and onUpdate behaviors.
     *
     * @param  string $behavior
     * @return string
     */
    public function normalizeFKey($behavior)
    {
        if (null === $behavior) {
            return self::NONE;
        }

        $behavior = strtoupper($behavior);

        if ('NONE' === $behavior) {
            return self::NONE;
        }

        if ('SETNULL' === $behavior) {
            return self::SETNULL;
        }

        return $behavior;
    }

    /**
     * Returns whether or not the onUpdate behavior is set.
     *
     * @return boolean
     */
    public function hasOnUpdate()
    {
        return self::NONE !== $this->onUpdate;
    }

    /**
     * Returns whether or not the onDelete behavior is set.
     *
     * @return boolean
     */
    public function hasOnDelete()
    {
        return self::NONE !== $this->onDelete;
    }

    /**
     * Returns the onUpdate behavior.
     *
     * @return string
     */
    public function getOnUpdate()
    {
        return $this->onUpdate;
    }

    /**
     * Returns the onDelete behavior.
     *
     * @return string
     */
    public function getOnDelete()
    {
        return $this->onDelete;
    }

    /**
     * Sets the onDelete behavior.
     *
     * @param string $behavior
     */
    public function setOnDelete($behavior)
    {
        $this->onDelete = $this->normalizeFKey($behavior);
    }

    /**
     * Sets the onUpdate behavior.
     *
     * @param string $behavior
     */
    public function setOnUpdate($behavior)
    {
        $this->onUpdate = $this->normalizeFKey($behavior);
    }

    /**
     * Returns the foreign key name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the foreign key name.
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Returns the phpName for this foreign key (if any).
     *
     * @return string
     */
    public function getPhpName()
    {
        return $this->phpName;
    }

    /**
     * Sets a phpName to use for this foreign key.
     *
     * @param string $name
     */
    public function setPhpName($name)
    {
        $this->phpName = $name;
    }

    /**
     * Returns the refPhpName for this foreign key (if any).
     *
     * @return string
     */
    public function getRefPhpName()
    {
        return $this->refPhpName;
    }

    /**
     * Sets a refPhpName to use for this foreign key.
     *
     * @param string $name
     */
    public function setRefPhpName($name)
    {
        $this->refPhpName = $name;
    }

    /**
     * Returns the default join strategy for this foreign key (if any).
     *
     * @return string
     */
    public function getDefaultJoin()
    {
        return $this->defaultJoin;
    }

    /**
     * Sets the default join strategy for this foreign key (if any).
     *
     * @param string $join
     */
    public function setDefaultJoin($join)
    {
        $this->defaultJoin = $join;
    }

    /**
     * Returns the PlatformInterface instance.
     *
     * @return PlatformInterface
     */
    private function getPlatform()
    {
        return $this->parentTable->getPlatform();
    }

    /**
     * Returns the Database object of this Column.
     *
     * @return Database
     */
    public function getDatabase()
    {
        return $this->parentTable->getDatabase();
    }

    /**
     * Returns the foreign table name of the FK.
     *
     * @return string
     */
    public function getForeignTableName()
    {
        $platform = $this->getPlatform();
        if ($this->foreignSchemaName && $platform->supportsSchemas()) {
            return $this->foreignSchemaName
                . $platform->getSchemaDelimiter()
                . $this->foreignTableCommonName
            ;
        }

        $database = $this->getDatabase();
        if ($database && ($schema = $database->getSchema()) && $platform->supportsSchemas()) {
            return $schema
                . $platform->getSchemaDelimiter()
                . $this->foreignTableCommonName
            ;
        }

        return $this->foreignTableCommonName;
    }

    /**
     * Returns the foreign table name without schema.
     *
     * @return string
     */
    public function getForeignTableCommonName()
    {
        return $this->foreignTableCommonName;
    }

    /**
     * Sets the foreign table common name of the FK.
     *
     * @param string $tableName
     */
    public function setForeignTableCommonName($tableName)
    {
        $this->foreignTableCommonName = $tableName;
    }

    /**
     * Returns the resolved foreign Table model object.
     *
     * @return Table
     */
    public function getForeignTable()
    {
        if ($database = $this->parentTable->getDatabase()) {
            return $database->getTable($this->getForeignTableName());
        }
    }

    /**
     * Returns the foreign schema name of the FK.
     *
     * @return string
     */
    public function getForeignSchemaName()
    {
        return $this->foreignSchemaName;
    }

    /**
     * Set the foreign schema name of the foreign key.
     *
     * @param string $schemaName
     */
    public function setForeignSchemaName($schemaName)
    {
        $this->foreignSchemaName = $schemaName;
    }

    /**
     * Sets the parent Table of the foreign key.
     *
     * @param Table $table
     */
    public function setTable(Table $parent)
    {
        $this->parentTable = $parent;
    }

    /**
     * Returns the parent Table of the foreign key.
     *
     * @return Table
     */
    public function getTable()
    {
        return $this->parentTable;
    }

    /**
     * Returns the name of the table the foreign key is in.
     *
     * @return string
     */
    public function getTableName()
    {
        return $this->parentTable->getName();
    }

    /**
     * Returns the name of the schema the foreign key is in.
     *
     * @return string
     */
    public function getSchemaName()
    {
        return $this->parentTable->getSchema();
    }

    /**
     * Adds a new reference entry to the foreign key.
     *
     * @param mixed $ref1 A Column object or an associative array or a string
     * @param mixed $ref2 A Column object or a single string name
     */
    public function addReference($ref1, $ref2 = null)
    {
        if (is_array($ref1)) {
            $this->localColumns[] = $ref1['local'] ? $ref1['local'] : null;
            $this->foreignColumns[] = $ref1['foreign'] ? $ref1['foreign'] : null;

            return;
        }

        if (is_string($ref1)) {
            $this->localColumns[] = $ref1;
            $this->foreignColumns[] = is_string($ref2) ? $ref2 : null;

            return;
        }

        $local = null;
        $foreign = null;
        if ($ref1 instanceof Column) {
            $local = $ref1->getName();
        }

        if ($ref2 instanceof Column) {
            $foreign = $ref2->getName();
        }

        $this->localColumns[] = $local;
        $this->foreignColumns[] = $foreign;
    }

    /**
     * Clears the references of this foreign key.
     *
     */
    public function clearReferences()
    {
        $this->localColumns   = [];
        $this->foreignColumns = [];
    }

    /**
     * Returns an array of local column names.
     *
     * @return array
     */
    public function getLocalColumns()
    {
        return $this->localColumns;
    }

    /**
     * Returns an array of local column objects.
     *
     * @return array
     */
    public function getLocalColumnObjects()
    {
        $columns = [];
        foreach ($this->localColumns as $columnName) {
            $columns[] = $this->parentTable->getColumn($columnName);
        }

        return $columns;
    }

    /**
     * Returns a local column name identified by a position.
     *
     * @param  integer $index
     * @return string
     */
    public function getLocalColumnName($index = 0)
    {
        return $this->localColumns[$index];
    }

    /**
     * Returns a local Column object identified by a position.
     *
     * @param  integer $index
     * @return Column
     */
    public function getLocalColumn($index = 0)
    {
        return $this->parentTable->getColumn($this->getLocalColumnName($index));
    }

    /**
     * Returns an array of local column to foreign column
     * mapping for this foreign key.
     *
     * @return array
     */
    public function getLocalForeignMapping()
    {
        $h = [];
        for ($i = 0, $size = count($this->localColumns); $i < $size; $i++) {
            $h[$this->localColumns[$i]] = $this->foreignColumns[$i];
        }

        return $h;
    }

    /**
     * Returns an array of local column to foreign column
     * mapping for this foreign key.
     *
     * @return array
     */
    public function getForeignLocalMapping()
    {
        $h = [];
        for ($i = 0, $size = count($this->localColumns); $i < $size; $i++) {
            $h[$this->foreignColumns[$i]] = $this->localColumns[$i];
        }

        return $h;
    }

    /**
     * Returns an array of local and foreign column objects
     * mapped for this foreign key.
     *
     * @return array
     */
    public function getColumnObjectsMapping()
    {
        $mapping = [];
        $foreignTable = $this->getForeignTable();
        for ($i = 0, $size = count($this->localColumns); $i < $size; $i++) {
            $mapping[] = [
                'local'   => $this->parentTable->getColumn($this->localColumns[$i]),
                'foreign' => $foreignTable->getColumn($this->foreignColumns[$i]),
            ];
        }

        return $mapping;
    }

    /**
     * Returns the foreign column name mapped to a specified local column.
     *
     * @param  string $local
     * @return string
     */
    public function getMappedForeignColumn($local)
    {
        $m = $this->getLocalForeignMapping();

        return isset($m[$local]) ? $m[$local] : null;
    }

    /**
     * Returns the local column name mapped to a specified foreign column.
     *
     * @param  string $foreign
     * @return string
     */
    public function getMappedLocalColumn($foreign)
    {
        $mapping = $this->getForeignLocalMapping();

        return isset($mapping[$foreign]) ? $mapping[$foreign] : null;
    }

    /**
     * Returns an array of foreign column names.
     *
     * @return array
     */
    public function getForeignColumns()
    {
        return $this->foreignColumns;
    }

    /**
     * Returns an array of foreign column objects.
     *
     * @return array
     */
    public function getForeignColumnObjects()
    {
        $columns = [];
        $foreignTable = $this->getForeignTable();
        foreach ($this->foreignColumns as $columnName) {
            $columns[] = $foreignTable->getColumn($columnName);
        }

        return $columns;
    }

    /**
     * Returns a foreign column name.
     *
     * @param  integer $index
     * @return string
     */
    public function getForeignColumnName($index = 0)
    {
        return $this->foreignColumns[$index];
    }

    /**
     * Returns a foreign column object.
     *
     * @return Column
     */
    public function getForeignColumn($index = 0)
    {
        return $this->getForeignTable()->getColumn($this->getForeignColumnName($index));
    }

    /**
     * Returns whether this foreign key uses a required column, or a list of
     * required columns.
     *
     * @return boolean
     */
    public function isLocalColumnsRequired()
    {
        foreach ($this->localColumns as $columnName) {
            if (!$this->parentTable->getColumn($columnName)->isNotNull()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns whether this foreign key is also the primary key of the foreign
     * table.
     *
     * @return boolean Returns true if all columns inside this foreign key are primary keys of the foreign table
     */
    public function isForeignPrimaryKey()
    {
        $lfmap = $this->getLocalForeignMapping();
        $foreignTable = $this->getForeignTable();

        $foreignPKCols = [];
        foreach ($foreignTable->getPrimaryKey() as $fPKCol) {
            $foreignPKCols[] = $fPKCol->getName();
        }

        $foreignCols = array ();
        foreach ($this->localColumns as $colName) {
            $foreignCols[] = $foreignTable->getColumn($lfmap[$colName])->getName();
        }

        return ((count($foreignPKCols) === count($foreignCols))
            && !array_diff($foreignPKCols, $foreignCols))
        ;
    }

    /**
     * Returns whether or not this foreign key relies on more than one
     * column binding.
     *
     * @return boolean
     */
    public function isComposite()
    {
        return count($this->localColumns) > 1;
    }

    /**
     * Returns whether or not this foreign key is also the primary key of
     * the local table.
     *
     * @return boolean True if all local columns are at the same time a primary key
     */
    public function isLocalPrimaryKey()
    {
        $localPKCols = [];
        foreach ($this->parentTable->getPrimaryKey() as $lPKCol) {
            $localPKCols[] = $lPKCol->getName();
        }

        return count($localPKCols) === count($this->localColumns) && !array_diff($localPKCols, $this->localColumns);
    }

    /**
     * Sets whether or not this foreign key should have its creation SQL
     * generated.
     *
     * @param boolean $skip
     */
    public function setSkipSql($skip)
    {
        $this->skipSql = (Boolean) $skip;
    }

    /**
     * Returns whether or not the SQL generation must be skipped for this
     * foreign key.
     *
     * @return boolean
     */
    public function isSkipSql()
    {
        return $this->skipSql;
    }

    /**
     * Whether this foreign key is matched by an inverted foreign key (on foreign table).
     *
     * This is to prevent duplicate columns being generated for a 1:1 relationship that is represented
     * by foreign keys on both tables.  I don't know if that's good practice ... but hell, why not
     * support it.
     *
     * @return boolean
     * @link http://propel.phpdb.org/trac/ticket/549
     */
    public function isMatchedByInverseFK()
    {
        return (Boolean) $this->getInverseFK();
    }

    public function getInverseFK()
    {
        $foreignTable = $this->getForeignTable();
        $map = $this->getForeignLocalMapping();

        foreach ($foreignTable->getForeignKeys() as $refFK) {
            $fkMap = $refFK->getLocalForeignMapping();
            // compares keys and values, but doesn't care about order, included check to make sure it's the same table (fixes #679)
            if (($refFK->getTableName() === $this->getTableName()) && ($map === $fkMap)) {
                return $refFK;
            }
        }
    }

    /**
     * Returns the list of other foreign keys starting on the same table.
     * Used in many-to-many relationships.
     *
     * @return array
     */
    public function getOtherFks()
    {
        $fks = [];
        foreach ($this->parentTable->getForeignKeys() as $fk) {
            if ($fk !== $this) {
                $fks[]= $fk;
            }
        }

        return $fks;
    }

    /**
     * Whether at least one foreign column is also the primary key of the foreign table.
     *
     * @return boolean True if there is at least one column that is a primary key of the foreign table
     */
    public function isAtLeastOneForeignPrimaryKey()
    {
        $cols = $this->getForeignPrimaryKeys();

        return count($cols) !== 0;
    }

    /**
     * Returns all foreign columns which are also a primary key of the foreign table.
     *
     * @return array Column[]
     */
    public function getForeignPrimaryKeys()
    {

        $lfmap = $this->getLocalForeignMapping();
        $foreignTable = $this->getForeignTable();

        $foreignPKCols = [];
        foreach ($foreignTable->getPrimaryKey() as $fPKCol) {
            $foreignPKCols[$fPKCol->getName()] = true;
        }

        $foreignCols = [];
        foreach ($this->getLocalColumns() as $colName) {
            if ($foreignPKCols[$lfmap[$colName]]) {
                $foreignCols[] = $foreignTable->getColumn($lfmap[$colName]);
            }
        }

        return $foreignCols;
    }

    /**
     * Whether at least one local column is also a primary key.
     *
     * @return boolean True if there is at least one column that is a primary key
     */
    public function isAtLeastOneLocalPrimaryKey()
    {
        $localCols = $this->getLocalColumnObjects();

        foreach ($localCols as $localCol) {
            if ($this->getTable()->getColumn($localCol->getName())->isPrimaryKey()) {
                return true;
            }
        }

        return false;
    }
}

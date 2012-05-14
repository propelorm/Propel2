<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Generator\Model;

use Propel\Generator\Exception\BuildException;
use Propel\Generator\Exception\EngineException;
use Propel\Generator\Platform\MysqlPlatform;

/**
 * Data about a table used in an application.
 *
 * @author     Hans Lellelid <hans@xmpl.org> (Propel)
 * @author     Leon Messerschmidt <leon@opticode.co.za> (Torque)
 * @author     Jason van Zyl <jvanzyl@apache.org> (Torque)
 * @author     Martin Poeschl <mpoeschl@marmot.at> (Torque)
 * @author     John McNally <jmcnally@collab.net> (Torque)
 * @author     Daniel Rall <dlr@collab.net> (Torque)
 * @author     Byron Foster <byron_foster@yahoo.com> (Torque)
 */
class Table extends ScopedElement implements IdMethod
{
    /**
     * Enables some debug printing.
     */
    const DEBUG = false;

    /**
     * Columns for this table.
     *
     * @var       array Column[]
     */
    private $columns = array();

    /**
     * Foreign keys for this table.
     *
     * @var       array ForeignKey[]
     */
    private $foreignKeys = array();

    /**
     * Indexes for this table.
     *
     * @var       array Index[]
     */
    private $indices = array();

    /**
     * Unique indexes for this table.
     *
     * @var       array Unique[]
     */
    private $unices = array();

    /**
     * Any parameters for the ID method (currently supports changing sequence name).
     *
     * @var       array
     */
    private $idMethodParameters = array();

    /**
     * Table name.
     *
     * @var       string
     */
    private $commonName;

    /**
     * Table description.
     *
     * @var       string
     */
    private $description;

    /**
     * phpName for the table.
     *
     * @var       string
     */
    private $phpName;

    /**
     * ID method for the table (e.g. IdMethod::NATIVE, IdMethod::NONE).
     *
     * @var       string
     */
    private $idMethod;

    /**
     * Wether an INSERT with set PK is allowed on tables with IdMethod::NATIVE
     *
     * @var       Boolean
     */
    private $allowPkInsert;

    /**
     * Strategry to use for converting column name to phpName.
     *
     * @var       string
     */
    private $phpNamingMethod;

    /**
     * The Database that this table belongs to.
     *
     * @var       Database
     */
    private $database;

    /**
     * Foreign Keys that refer to this table.
     *
     * @var       array ForeignKey[]
     */
    private $referrers = array();

    /**
     * Names of foreign tables.
     *
     * @var       array string[]
     */
    private $foreignTableNames;

    /**
     * Whether this table contains a foreign primary key.
     *
     * @var       Boolean
     */
    private $containsForeignPK;

    /**
     * The inheritance column for this table (if any).
     *
     * @var       Column
     */
    private $inheritanceColumn;

    /**
     * Whether to skip generation of SQL for this table.
     *
     * @var       Boolean
     */
    private $skipSql;

    /**
     * Whether this table is "read-only".
     *
     * @var       Boolean
     */
    private $readOnly;

    /**
     * Whether this table should result in abstract OM classes.
     *
     * @var       Boolean
     */
    private $abstractValue;

    /**
     * Whether this table is an alias for another table.
     *
     * @var       string
     */
    private $alias;

    /**
     * The interface that the generated "object" class should implement.
     *
     * @var       string
     */
    private $interface;

    /**
     * The base class to extend for the generated "object" class.
     *
     * @var       string
     */
    private $baseClass;

    /**
     * The base peer class to extend for generated "peer" class.
     *
     * @var       string
     */
    private $basePeer;

    /**
     * Map of columns by name.
     *
     * @var       array
     */
    private $columnsByName = array();

    /**
     * Map of columns by lowercase name.
     *
     * @var       array
     */
    private $columnsByLowercaseName = array();

    /**
     * Map of columns by phpName.
     *
     * @var       array
     */
    private $columnsByPhpName = array();

    /**
     * Whether this table needs to use transactions in Postgres.
     *
     * @var       string
     * @deprecated
     */
    private $needsTransactionInPostgres;

    /**
     * Whether to perform additional indexing on this table.
     *
     * @var       Boolean
     */
    private $heavyIndexing;

    /**
     * Whether this table is for reference only.
     *
     * @var       Boolean
     */
    private $forReferenceOnly;

    /**
     * Whether to reload the rows in this table after insert.
     *
     * @var       Boolean
     */
    private $reloadOnInsert;

    /**
     * Whether to reload the rows in this table after update.
     *
     * @var       Boolean
     */
    private $reloadOnUpdate;

    /**
     * List of behaviors registered for this table
     *
     * @var array
     */
    protected $behaviors = array();

    /**
     * Whether this table is a cross-reference table for a many-to-many relationship
     *
     * @var       Boolean
     */
    protected $isCrossRef = false;

    /**
     * The default string format for objects based on this table
     * (e.g. 'XML', 'YAML', 'CSV', 'JSON')
     *
     * @var       string
     */
    protected $defaultStringFormat;

    /**
     * Constructs a table object with a name
     *
     * @param     string $name table name
     */
    public function __construct($name = null)
    {
        $this->commonName = $name;
    }

    /**
     * get a qualified name of this table with scheme and common name separated by '_'
     * if schemaAutoPrefix is set. Otherwise get the common name.
     * @return string
     */
    private function getStdSeparatedName()
    {
        if ($this->schema && $this->getBuildProperty('schemaAutoPrefix')) {
            return $this->schema . NameGenerator::STD_SEPARATOR_CHAR . $this->getCommonName();
        }

        return $this->getCommonName();
    }

    /**
     * Sets up the Rule object based on the attributes that were passed to loadFromXML().
     * @see       parent::loadFromXML()
     */
    public function setupObject()
    {
        parent::setupObject();

        $this->commonName = $this->getDatabase()->getTablePrefix() . $this->getAttribute('name');

        // retrieves the method for converting from specified name to a PHP name.
        $this->phpNamingMethod = $this->getAttribute('phpNamingMethod', $this->getDatabase()->getDefaultPhpNamingMethod());

        $this->phpName = $this->getAttribute('phpName', $this->buildPhpName($this->getStdSeparatedName()));

        $this->idMethod = $this->getAttribute('idMethod', $this->getDatabase()->getDefaultIdMethod());
        $this->allowPkInsert = $this->booleanValue($this->getAttribute('allowPkInsert'));


        $this->skipSql = $this->booleanValue($this->getAttribute('skipSql'));
        $this->readOnly = $this->booleanValue($this->getAttribute('readOnly'));

        $this->abstractValue = $this->booleanValue($this->getAttribute('abstract'));
        $this->baseClass = $this->getAttribute('baseClass');
        $this->basePeer = $this->getAttribute('basePeer');
        $this->alias = $this->getAttribute('alias');

        $this->heavyIndexing = (
            $this->booleanValue($this->getAttribute('heavyIndexing'))
            || (
                'false' !== $this->getAttribute('heavyIndexing')
                && $this->getDatabase()->isHeavyIndexing()
            )
        );

        $this->description = $this->getAttribute('description');
        $this->interface = $this->getAttribute('interface'); // sic ('interface' is reserved word)

        $this->reloadOnInsert = $this->booleanValue($this->getAttribute('reloadOnInsert'));
        $this->reloadOnUpdate = $this->booleanValue($this->getAttribute('reloadOnUpdate'));
        $this->isCrossRef = $this->booleanValue($this->getAttribute('isCrossRef', false));
        $this->defaultStringFormat = $this->getAttribute('defaultStringFormat');
    }

    /**
     * get a build property for the database this table belongs to
     *
     * @param string $key key of the build property
     * @return string value of the property
     */
    public function getBuildProperty($key)
    {
        return $this->getDatabase() ? $this->getDatabase()->getBuildProperty($key) : '';
    }

    /**
     * Execute behavior table modifiers
     */
    public function applyBehaviors()
    {
        foreach ($this->getBehaviors() as $behavior) {
            if (!$behavior->isTableModified()) {
                $behavior->getTableModifier()->modifyTable();
                $behavior->setTableModified(true);
            }
        }
    }

    /**
     * <p>A hook for the SAX XML parser to call when this table has
     * been fully loaded from the XML, and all nested elements have
     * been processed.</p>
     *
     * <p>Performs heavy indexing and naming of elements which weren't
     * provided with a name.</p>
     */
    public function doFinalInitialization()
    {
        // Heavy indexing must wait until after all columns composing
        // a table's primary key have been parsed.
        if ($this->heavyIndexing) {
            $this->doHeavyIndexing();
        }

        // Name any indices which are missing a name using the
        // appropriate algorithm.
        $this->doNaming();

        // if idMethod is "native" and in fact there are no autoIncrement
        // columns in the table, then change it to "none"
        $anyAutoInc = false;
        foreach ($this->getColumns() as $col) {
            if ($col->isAutoIncrement()) {
                $anyAutoInc = true;
            }
        }
        if ( IdMethod::NATIVE === $this->getIdMethod() && !$anyAutoInc) {
            $this->setIdMethod(IdMethod::NO_ID_METHOD);
        }
    }

    /**
     * Adds extra indices for multi-part primary key columns.
     *
     * For databases like MySQL, values in a where clause much
     * match key part order from the left to right.	 So, in the key
     * definition <code>PRIMARY KEY (FOO_ID, BAR_ID)</code>,
     * <code>FOO_ID</code> <i>must</i> be the first element used in
     * the <code>where</code> clause of the SQL query used against
     * this table for the primary key index to be used.	 This feature
     * could cause problems under MySQL with heavily indexed tables,
     * as MySQL currently only supports 16 indices per table (i.e. it
     * might cause too many indices to be created).
     *
     * See the mysqm manual http://www.mysql.com/doc/E/X/EXPLAIN.html
     * for a better description of why heavy indexing is useful for
     * quickly searchable database tables.
     */
    private function doHeavyIndexing()
    {
        if (self::DEBUG) {
            // @TODO remove hardcoded print statements
            print("doHeavyIndex() called on table " . $this->getName()."\n");
        }

        $pk = $this->getPrimaryKey();
        $size = count($pk);

        // We start at an offset of 1 because the entire column
        // list is generally implicitly indexed by the fact that
        // it's a primary key.
        for ($i = 1; $i < $size; $i++) {
            $idx = new Index();
            $idx->setColumns(array_slice($pk, $i, $size));
            $this->addIndex($idx);
        }
    }

    /**
     * Adds extra indices for reverse foreign keys
     * This is required for MySQL databases,
     * and is called from Database::doFinalInitialization()
     */
    public function addExtraIndices()
    {
        /**
         * A collection of indexed columns. The keys is the column name
         * (concatenated with a comma in the case of multi-col index), the value is
         * an array with the names of the indexes that index these columns. We use
         * it to determine which additional indexes must be created for foreign
         * keys. It could also be used to detect duplicate indexes, but this is not
         * implemented yet.
         * @var array
         */
        $_indices = array();

        $this->collectIndexedColumns('PRIMARY', $this->getPrimaryKey(), $_indices);

        $_tableIndices = array_merge($this->getIndices(), $this->getUnices());
        foreach ($_tableIndices as $_index) {
            $this->collectIndexedColumns($_index->getName(), $_index->getColumns(), $_indices);
        }

        // we're determining which tables have foreign keys that point to this table,
        // since MySQL needs an index on any column that is referenced by another table
        // (yep, MySQL _is_ a PITA)
        $counter = 0;
        foreach ($this->getReferrers() as $foreignKey) {
            $referencedColumns = $foreignKey->getForeignColumnObjects();
            $referencedColumnsHash = $this->getColumnList($referencedColumns);
            if (!isset($_indices[$referencedColumnsHash])) {
                // no matching index defined in the schema, so we have to create one
                $index = new Index();
                $index->setName(sprintf('I_referenced_%s_%s', $foreignKey->getName(), ++$counter));
                $index->setColumns($referencedColumns);
                $index->resetColumnSize();
                $this->addIndex($index);
                // Add this new index to our collection, otherwise we might add it again (bug #725)
                $this->collectIndexedColumns($index->getName(), $referencedColumns, $_indices);
            }
        }

        // we're adding indices for this table foreign keys
        foreach ($this->getForeignKeys() as $foreignKey) {
            $localColumns = $foreignKey->getLocalColumnObjects();
            $localColumnsHash = $this->getColumnList($localColumns);
            if (!isset($_indices[$localColumnsHash])) {
                // no matching index defined in the schema, so we have to create one. MySQL needs indices on any columns that serve as foreign keys. these are not auto-created prior to 4.1.2
                $index = new Index();
                $index->setName(substr_replace($foreignKey->getName(), 'FI_', strrpos($foreignKey->getName(), 'FK_'), 3));
                $index->setColumns($localColumns);
                $index->resetColumnSize();
                $this->addIndex($index);
                $this->collectIndexedColumns($index->getName(), $localColumns, $_indices);
            }
        }
    }

    /**
     * Helper function to collect indexed columns.
     *
     * @param string $indexName The name of the index
     * @param array $columns The column names or objects
     * @param array $collectedIndexes The collected indexes
     */
    protected function collectIndexedColumns($indexName, $columns, &$collectedIndexes)
    {
        /**
         * "If the table has a multiple-column index, any leftmost prefix of the
         * index can be used by the optimizer to find rows. For example, if you
         * have a three-column index on (col1, col2, col3), you have indexed search
         * capabilities on (col1), (col1, col2), and (col1, col2, col3)."
         * @link http://dev.mysql.com/doc/refman/5.5/en/mysql-indexes.html
         */
        $indexedColumns = array();
        foreach ($columns as $column) {
            $indexedColumns[] = $column;
            $indexedColumnsHash = $this->getColumnList($indexedColumns);
            if (!isset($collectedIndexes[$indexedColumnsHash])) {
                $collectedIndexes[$indexedColumnsHash] = array();
            }
            $collectedIndexes[$indexedColumnsHash][] = $indexName;
        }
    }

    /**
     * Creates a delimiter-delimited string list of column names
     *
     * @see        Platform::getColumnList() if quoting is required
     * @param      array Column[] or string[]
     * @param      string $delim The delimiter to use in separating the column names.
     * @return     string
     */
    public function getColumnList($columns, $delim = ',')
    {
        $list = array();
        foreach ($columns as $col) {
            if ($col instanceof Column) {
                $col = $col->getName();
            }
            $list[] = $col;
        }

        return implode($delim, $list);
    }

    /**
     * Names composing objects which haven't yet been named.	This
     * currently consists of foreign-key and index entities.
     */
    public function doNaming()
    {
        // Assure names are unique across all databases.
        try {
            for ($i = 0, $size = count($this->foreignKeys); $i < $size; $i++) {
                $fk = $this->foreignKeys[$i];
                $name = $fk->getName();
                if (empty($name)) {
                    $name = $this->acquireConstraintName("FK", $i + 1);
                    $fk->setName($name);
                }
            }

            for ($i = 0, $size = count($this->indices); $i < $size; $i++) {
                $index = $this->indices[$i];
                $name = $index->getName();
                if (empty($name)) {
                    $name = $this->acquireConstraintName("I", $i + 1);
                    $index->setName($name);
                }
            }

            for ($i = 0, $size = count($this->unices); $i < $size; $i++) {
                $index = $this->unices[$i];
                $name = $index->getName();
                if (empty($name)) {
                    $name = $this->acquireConstraintName("U", $i + 1);
                    $index->setName($name);
                }
            }

            // NOTE: Most RDBMSes can apparently name unique column
            // constraints/indices themselves (using MySQL and Oracle
            // as test cases), so we'll assume that we needn't add an
            // entry to the system name list for these.
        } catch (EngineException $nameAlreadyInUse) {
            // @TODO remove hardcoded print statements
            print $nameAlreadyInUse->getMessage() . "\n";
            print $nameAlreadyInUse->getTraceAsString();
        }
    }

    /**
     * Macro to a constraint name.
     *
     * @param     nameType constraint type
     * @param     nbr unique number for this constraint type
     * @return    unique name for constraint
     * @throws		 EngineException
     */
    private function acquireConstraintName($nameType, $nbr)
    {
        $inputs   = array();
        $inputs[] = $this->getDatabase();
        $inputs[] = $this->getCommonName();
        $inputs[] = $nameType;
        $inputs[] = $nbr;

        return NameFactory::generateName(NameFactory::CONSTRAINT_GENERATOR, $inputs);
    }

    /**
     * Gets the value of base class for classes produced from this table.
     *
     * @return    The base class for classes produced from this table.
     */
    public function getBaseClass()
    {
        if ($this->isAlias() && null === $this->baseClass) {
            return $this->alias;
        }

        if (null === $this->baseClass) {
            return $this->getDatabase()->getBaseClass();
        }

        return $this->baseClass;
    }

    /**
     * Set the value of baseClass.
     * @param     v Value to assign to baseClass.
     */
    public function setBaseClass($v)
    {
        $this->baseClass = $v;
    }

    /**
     * Get the value of basePeer.
     * @return    value of basePeer.
     */
    public function getBasePeer()
    {
        if ($this->isAlias() && null === $this->basePeer) {
            return $this->alias . 'Peer';
        }

        if (null === $this->basePeer) {
            return $this->getDatabase()->getBasePeer();
        }

        return $this->basePeer;
    }

    /**
     * Set the value of basePeer.
     * @param     v    Value to assign to basePeer.
     */
    public function setBasePeer($v)
    {
        $this->basePeer = $v;
    }

    /**
     * A utility function to create a new column from attrib and add it to this
     * table.
     *
     * @param     $coldata xml attributes or Column class for the column to add
     * @return    the added column
     */
    public function addColumn($data)
    {
        if ($data instanceof Column) {
            $col = $data;
            if (isset($this->columnsByName[$col->getName()])) {
                throw new EngineException(sprintf('Column "%s" declared twice in table "%s"', $col->getName(), $this->getName()));
            }
            $col->setTable($this);
            if ($col->isInheritance()) {
                $this->inheritanceColumn = $col;
            }
            if (isset($this->columnsByName[$col->getName()])) {
                throw new EngineException('Duplicate column declared: ' . $col->getName());
            }
            $this->columns[] = $col;
            $this->columnsByName[$col->getName()] = $col;
            $this->columnsByLowercaseName[strtolower($col->getName())] = $col;
            $this->columnsByPhpName[$col->getPhpName()] = $col;
            $col->setPosition(count($this->columns));
            $this->needsTransactionInPostgres |= $col->requiresTransactionInPostgres();

            return $col;
        }

        $col = new Column();
        $col->setTable($this);
        $col->loadFromXML($data);

        return $this->addColumn($col); // call self w/ different param
    }

    /**
     * Removed a column from the table
     * @param Column|string $col the column to remove
     */
    public function removeColumn($col)
    {
        if (is_string($col)) {
            $col = $this->getColumn($col);
        }

        $pos = array_search($col, $this->columns);
        if (false === $pos) {
            throw new EngineException(sprintf('No column named %s found in table %s', $col->getName(), $col->getTableName()));
        }

        unset($this->columns[$pos]);
        unset($this->columnsByName[$col->getName()]);
        unset($this->columnsByLowercaseName[strtolower($col->getName())]);
        unset($this->columnsByPhpName[$col->getPhpName()]);
        $this->adjustColumnPositions();
        // @FIXME: also remove indexes and validators on this column?
    }

    public function adjustColumnPositions()
    {
        $this->columns = array_values($this->columns);
        $columnCount = $this->getNumColumns();
        for ($i = 0; $i < $columnCount; $i++) {
            $this->columns[$i]->setPosition($i + 1);
        }
    }

    /**
     * A utility function to create a new foreign key
     * from attrib and add it to this table.
     */
    public function addForeignKey($fkdata)
    {
        if ($fkdata instanceof ForeignKey) {
            $fk = $fkdata;
            $fk->setTable($this);
            $this->foreignKeys[] = $fk;

            if (null === $this->foreignTableNames) {
                $this->foreignTableNames = array();
            }

            if (!in_array($fk->getForeignTableName(), $this->foreignTableNames)) {
                $this->foreignTableNames[] = $fk->getForeignTableName();
            }

            return $fk;
        }

        $fk = new ForeignKey();
        $fk->setTable($this);
        $fk->loadFromXML($fkdata);

        return $this->addForeignKey($fk);
    }

    /**
     * Gets the column that subclasses of the class representing this
     * table can be produced from.
     * @return    Column
     */
    public function getChildrenColumn()
    {
        return $this->inheritanceColumn;
    }

    /**
     * Get the subclasses that can be created from this table.
     * @return    array string[] Class names
     */
    public function getChildrenNames()
    {
        if (null === $this->inheritanceColumn
            || !$this->inheritanceColumn->isEnumeratedClasses()) {
            return null;
        }

        $children = $this->inheritanceColumn->getChildren();
        $names = array();
        for ($i = 0, $size = count($children); $i < $size; $i++) {
            $names[] = get_class($children[$i]);
        }

        return $names;
    }

    /**
     * Adds the foreign key from another table that refers to this table.
     */
    public function addReferrer(ForeignKey $fk)
    {
        if (null === $this->referrers) {
            $this->referrers = array();
        }

        $this->referrers[] = $fk;
    }

    /**
     * Get list of references to this table.
     */
    public function getReferrers()
    {
        return $this->referrers;
    }

    /**
     * Browses the foreign keys and creates referrers for the foreign table.
     * This method can be called several times on the same table. It only
     * adds the missing referrers and is non-destructive.
     * Warning: only use when all the tables were created.
     */
    public function setupReferrers($throwErrors = false)
    {
        foreach ($this->getForeignKeys() as $foreignKey) {

            // table referrers
            $foreignTable = $this->getDatabase()->getTable($foreignKey->getForeignTableName());
            if (null !== $foreignTable) {
                $referrers = $foreignTable->getReferrers();
                if (null === $referrers || !in_array($foreignKey, $referrers, true) ) {
                    $foreignTable->addReferrer($foreignKey);
                }
            } elseif ($throwErrors) {
                throw new BuildException(sprintf(
                    'Table "%s" contains a foreign key to nonexistent table "%s"',
                    $this->getName(),
                    $foreignKey->getForeignTableName()
                ));
            }

            // foreign pk's
            $localColumnNames = $foreignKey->getLocalColumns();
            foreach ($localColumnNames as $localColumnName) {
                $localColumn = $this->getColumn($localColumnName);
                if (null !== $localColumn) {
                    if ($localColumn->isPrimaryKey() && !$this->getContainsForeignPK()) {
                        $this->setContainsForeignPK(true);
                    }
                } elseif ($throwErrors) {
                    // give notice of a schema inconsistency.
                    // note we do not prevent the npe as there is nothing
                    // that we can do, if it is to occur.
                    throw new BuildException(sprintf(
                        'Table "%s" contains a foreign key with nonexistent local column "%s"',
                        $this->getName(),
                        $localColumnName
                    ));
                }
            }

            // foreign column references
            $foreignColumnNames = $foreignKey->getForeignColumns();
            foreach ($foreignColumnNames as $foreignColumnName) {
                if (null === $foreignTable) {
                    continue;
                }
                $foreignColumn = $foreignTable->getColumn($foreignColumnName);
                if (null !== $foreignColumn) {
                    if (!$foreignColumn->hasReferrer($foreignKey)) {
                        $foreignColumn->addReferrer($foreignKey);
                    }
                } elseif ($throwErrors) {
                    // if the foreign column does not exist, we may have an
                    // external reference or a misspelling
                    throw new BuildException(sprintf(
                        'Table "%s" contains a foreign key to table "%s" with nonexistent column "%s"',
                        $this->getName(),
                        $foreignTable->getName(),
                        $foreignColumnName
                    ));
                }
            }

            if ($this->getDatabase()->getPlatform() instanceof MysqlPlatform) {
                $this->addExtraIndices();
            }
        }
    }

    public function getCrossFks()
    {
        $crossFks = array();
        foreach ($this->getReferrers() as $refFK) {
            if ($refFK->getTable()->getIsCrossRef()) {
                foreach ($refFK->getOtherFks() as $crossFK) {
                    $crossFks[]= array($refFK, $crossFK);
                }
            }
        }

        return $crossFks;
    }

    /**
     * Set whether this table contains a foreign PK
     */
    public function setContainsForeignPK($b)
    {
        $this->containsForeignPK = (Boolean) $b;
    }

    /**
     * Determine if this table contains a foreign PK
     */
    public function getContainsForeignPK()
    {
        return $this->containsForeignPK;
    }

    /**
     * A list of tables referenced by foreign keys in this table
     */
    public function getForeignTableNames()
    {
        if (null === $this->foreignTableNames) {
            $this->foreignTableNames = array();
        }

        return $this->foreignTableNames;
    }

    /**
     * Return true if the column requires a transaction in Postgres
     */
    public function requiresTransactionInPostgres()
    {
        return $this->needsTransactionInPostgres;
    }

    /**
     * A utility function to create a new id method parameter
     * from attrib or object and add it to this table.
     */
    public function addIdMethodParameter($impdata)
    {
        if ($impdata instanceof IdMethodParameter) {
            $imp = $impdata;
            $imp->setTable($this);
            if (null === $this->idMethodParameters) {
                $this->idMethodParameters = array();
            }
            $this->idMethodParameters[] = $imp;

            return $imp;
        }

        $imp = new IdMethodParameter();
        $imp->loadFromXML($impdata);

        return $this->addIdMethodParameter($imp); // call self w/ diff param
    }

    /**
     * Adds a new index to the index list and set the
     * parent table of the column to the current table
     */
    public function addIndex($idxdata)
    {
        if ($idxdata instanceof Index) {
            $index = $idxdata;
            $index->setTable($this);
            $index->getName(); // we call this method so that the name is created now if it doesn't already exist.
            $this->indices[] = $index;

            return $index;
        }

        $index = new Index($this);
        $index->loadFromXML($idxdata);

        return $this->addIndex($index);
    }

    /**
     * Adds a new Unique to the Unique list and set the
     * parent table of the column to the current table
     */
    public function addUnique($unqdata)
    {
        if ($unqdata instanceof Unique) {
            $unique = $unqdata;
            $unique->setTable($this);
            $unique->getName(); // we call this method so that the name is created now if it doesn't already exist.
            $this->unices[] = $unique;

            return $unique;
        }

        $unique = new Unique($this);
        $unique->loadFromXML($unqdata);

        return $this->addUnique($unique);
    }

    /**
     * Retrieves the configuration object, filled by build.properties
     *
     * @return    GeneratorConfig
     */
    public function getGeneratorConfig()
    {
        return $this->getDatabase()->getParentSchema()->getGeneratorConfig();
    }

    /**
     * Adds a new Behavior to the table
     * @return    Behavior A behavior instance
     */
    public function addBehavior($bdata)
    {
        if ($bdata instanceof Behavior) {
            $behavior = $bdata;
            $behavior->setTable($this);
            $this->behaviors[$behavior->getName()] = $behavior;

            return $behavior;
        }

        $class = $this->getConfiguredBehavior($bdata['name']);
        $behavior = new $class();
        $behavior->loadFromXML($bdata);

        return $this->addBehavior($behavior);
    }

    /**
     * Get the table behaviors
     * @return    Array of Behavior objects
     */
    public function getBehaviors()
    {
        return $this->behaviors;
    }

    /**
     * Get the early table behaviors
     * @return    Array of Behavior objects
     */
    public function getEarlyBehaviors()
    {
        $behaviors = array();
        foreach ($this->behaviors as $name => $behavior) {
            if ($behavior->isEarly()) {
                $behaviors[$name] = $behavior;
            }
        }

        return $behaviors;
    }

    /**
     * check if the table has a behavior by name
     *
     * @param     string $name the behavior name
     * @return    Boolean True if the behavior exists
     */
    public function hasBehavior($name)
    {
        return isset($this->behaviors[$name]);
    }

    /**
     * Get one table behavior by name
     *
     * @param     string $name the behavior name
     * @return    Behavior a behavior object
     */
    public function getBehavior($name)
    {
        return $this->behaviors[$name];
    }

    /**
     * Check whether one of the table behaviors offer an additional builder
     *
     * @return Boolean true in the table has at least one behavior
     *                with an additional builder, false otherwise
     */
    public function hasAdditionalBuilders()
    {
        foreach ($this->getBehaviors() as $behavior) {
            if ($behavior->hasAdditionalBuilders()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the additional builders provided by the table behaviors
     *
     * @return array list of builder class names
     */
    public function getAdditionalBuilders()
    {
        $additionalBuilders = array();
        foreach ($this->getBehaviors() as $behavior) {
            $additionalBuilders = array_merge($additionalBuilders, $behavior->getAdditionalBuilders());
        }

        return $additionalBuilders;
    }

    /**
     * Get the name of the Table
     */
    public function getName()
    {
        if ($this->schema
            && $this->getDatabase()
            && $this->getDatabase()->getPlatform()
            && $this->getDatabase()->getPlatform()->supportsSchemas()
        ) {
            return $this->schema . '.' . $this->commonName;
        }

        return $this->commonName;
    }

    /**
     * Get the description for the Table
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Whether the Table has a description
     */
    public function hasDescription()
    {
        return (bool) $this->description;
    }

    /**
     * Set the description for the Table
     *
     * @param     newDescription description for the Table
     */
    public function setDescription($newDescription)
    {
        $this->description = $newDescription;
    }

    /**
     * Get name to use in PHP sources
     * @return    string
     */
    public function getPhpName()
    {
        if (null === $this->phpName) {
            $inputs = array();
            $inputs[] = $this->getStdSeparatedName();
            $inputs[] = $this->phpNamingMethod;
            try {
                $this->phpName = NameFactory::generateName(NameFactory::PHP_GENERATOR, $inputs);
            } catch (EngineException $e) {
                // @TODO remove these print statements?
                print $e->getMessage() . "\n";
                print $e->getTraceAsString();
            }
        }

        return $this->phpName;
    }

    /**
     * Set name to use in PHP sources
     * @param     string $phpName
     */
    public function setPhpName($phpName)
    {
        $this->phpName = $phpName;
    }

    public function buildPhpName($name)
    {
        return NameFactory::generateName(NameFactory::PHP_GENERATOR, array($name, $this->phpNamingMethod));
    }

    /**
     * Get studly version of PHP name.
     *
     * The studly name is the PHP name with the first character lowercase.
     *
     * @return    string
     */
    public function getStudlyPhpName()
    {
        $phpname = $this->getPhpName();
        if (strlen($phpname) > 1) {
            return strtolower(substr($phpname, 0, 1)) . substr($phpname, 1);
        }

        // 0 or 1 chars (I suppose that's rare)
        return strtolower($phpname);
    }

    /**
     * Get the name without schema
     */
    public function getCommonName()
    {
        return $this->commonName;
    }

    /**
     * Set the common name of the table (without schema)
     */
    public function setCommonName($v)
    {
        $this->commonName = $v;
    }

    /**
     * Set the default string format for ActiveRecord objects in this Table.
     *
     * @param      string $defaultStringFormat Any of 'XML', 'YAML', 'JSON', or 'CSV'
     */
    public function setDefaultStringFormat($defaultStringFormat)
    {
        $this->defaultStringFormat = $defaultStringFormat;
    }

    /**
     * Get the default string format for ActiveRecord objects in this Table,
     * or the one for the whole database if not set.
     *
     * @return     string The default string representation
     */
    public function getDefaultStringFormat()
    {
        if (!$this->defaultStringFormat
            && $this->getDatabase()
            && $this->getDatabase()->getDefaultStringFormat()
        ) {
            return $this->getDatabase()->getDefaultStringFormat();
        }

        return $this->defaultStringFormat;
    }

    /**
     * Get the method for generating pk's
     * [HL] changing behavior so that Database default method is returned
     * if no method has been specified for the table.
     *
     * @return    string
     */
    public function getIdMethod()
    {
        return null === $this->idMethod ? IdMethod::NO_ID_METHOD : $this->idMethod;
    }

    /**
     * Whether we allow to insert primary keys on tables with
     * idMethod=native
     *
     * @return    Boolean
     */
    public function isAllowPkInsert()
    {
        return $this->allowPkInsert;
    }


    /**
     * Set the method for generating pk's
     */
    public function setIdMethod($idMethod)
    {
        $this->idMethod = $idMethod;
    }

    /**
     * Skip generating sql for this table (in the event it should
     * not be created from scratch).
     * @return    Boolean Value of skipSql.
     */
    public function isSkipSql()
    {
        return ($this->skipSql || $this->isAlias() || $this->isForReferenceOnly());
    }

    /**
     * Is table read-only, in which case only accessors (and relationship setters)
     * will be created.
     * @return    boolan Value of readOnly.
     */
    public function isReadOnly()
    {
        return $this->readOnly;
    }

    /**
     * Set whether this table should have its creation sql generated.
     * @param     Boolean $v Value to assign to skipSql.
     */
    public function setSkipSql($v)
    {
        $this->skipSql = (Boolean) $v;
    }

    /**
     * Whether to force object to reload on INSERT.
     * @return    Boolean
     */
    public function isReloadOnInsert()
    {
        return $this->reloadOnInsert;
    }

    /**
     * Whether to force object to reload on UPDATE.
     * @return    Boolean
     */
    public function isReloadOnUpdate()
    {
        return $this->reloadOnUpdate;
    }

    /**
     * PhpName of om object this entry references.
     * @return    value of external.
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * Is this table specified in the schema or is there just
     * a foreign key reference to it.
     * @return    value of external.
     */
    public function isAlias()
    {
        return null !== $this->alias;
    }

    /**
     * Set whether this table specified in the schema or is there just
     * a foreign key reference to it.
     * @param     v	Value to assign to alias.
     */
    public function setAlias($v)
    {
        $this->alias = $v;
    }


    /**
     * Interface which objects for this table will implement
     * @return    value of interface.
     */
    public function getInterface()
    {
        return $this->interface;
    }

    /**
     * Interface which objects for this table will implement
     * @param     v	Value to assign to interface.
     */
    public function setInterface($v)
    {
        $this->interface = $v;
    }

    /**
     * When a table is abstract, it marks the business object class that is
     * generated as being abstract. If you have a table called "FOO", then the
     * Foo BO will be <code>public abstract class Foo</code>
     * This helps support class hierarchies
     *
     * @return    value of abstractValue.
     */
    public function isAbstract()
    {
        return $this->abstractValue;
    }

    /**
     * When a table is abstract, it marks the business object
     * class that is generated as being abstract. If you have a
     * table called "FOO", then the Foo BO will be
     * <code>public abstract class Foo</code>
     * This helps support class hierarchies
     *
     * @param     v	Value to assign to abstractValue.
     */
    public function setAbstract($v)
    {
        $this->abstractValue = (Boolean) $v;
    }

    /**
     * Returns an Array containing all the columns in the table
     * @return    array Column[]
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * Utility method to get the number of columns in this table
     */
    public function getNumColumns()
    {
        return count($this->columns);
    }

    /**
     * Utility method to get the number of columns in this table
     */
    public function getNumLazyLoadColumns()
    {
        $count = 0;
        foreach ($this->columns as $col) {
            if ($col->isLazyLoad()) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Checks whether one of the columns is of type ENUM
     * @return Boolean
     */
    public function hasEnumColumns()
    {
        foreach ($this->getColumns() as $col) {
            if ($col->isEnumType()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns an Array containing all the FKs in the table.
     * @return    array ForeignKey[]
     */
    public function getForeignKeys()
    {
        return $this->foreignKeys;
    }

    /**
     * Returns a Collection of parameters relevant for the chosen
     * id generation method.
     */
    public function getIdMethodParameters()
    {
        return $this->idMethodParameters;
    }

    /**
     * Returns an Array containing all the FKs in the table
     * @return    array Index[]
     */
    public function getIndices()
    {
        return $this->indices;
    }

    /**
     * Returns an Array containing all the UKs in the table
     * @return    array Unique[]
     */
    public function getUnices()
    {
        return $this->unices;
    }

    /**
     * Check whether the table has a column.
     * @param      Column|string $col the column object or name (e.g. 'my_column')
     * @param      Boolean $caseInsensitive Whether the check is case insensitive. False by default.
     *
     * @return     Boolean
     */
    public function hasColumn($col, $caseInsensitive = false)
    {
        if ($col instanceof Column) {
            $col = $col->getName();
        }

        if ($caseInsensitive) {
            return isset($this->columnsByLowercaseName[strtolower($col)]);
        }

        return isset($this->columnsByName[$col]);
    }

    /**
     * Return the column with the specified name.
     * @param      string $name The name of the column (e.g. 'my_column')
     * @param      Boolean $caseInsensitive Whether the check is case insensitive. False by default.
     *
     * @return     Column a Column object or null if it doesn't exist
     */
    public function getColumn($name, $caseInsensitive = false)
    {
        if (!$this->hasColumn($name, $caseInsensitive)) {
            return null; // just to be explicit
        }

        if ($caseInsensitive) {
            return $this->columnsByLowercaseName[strtolower($name)];
        }

        return $this->columnsByName[$name];
    }

    /**
     * Returns a specified column.
     * @return    Column Return a Column object or null if it does not exist.
     */
    public function getColumnByPhpName($phpName)
    {
        if (isset($this->columnsByPhpName[$phpName])) {
            return $this->columnsByPhpName[$phpName];
        }

        return null; // just to be explicit
    }

    /**
     * Get all the foreign keys from this table to the specified table.
     * @return    array ForeignKey[]
     */
    public function getForeignKeysReferencingTable($tablename)
    {
        $matches = array();
        $keys = $this->getForeignKeys();
        foreach ($keys as $fk) {
            if ($fk->getForeignTableName() === $tablename) {
                $matches[] = $fk;
            }
        }

        return $matches;
    }

    /**
     * Return the foreign keys that includes col in it's list of local columns.
     * Eg. Foreign key (a,b,c) refrences tbl(x,y,z) will be returned of col is either a,b or c.
     * @param     string $col
     * @return    array ForeignKey[] or null if there is no FK for specified column.
     */
    public function getColumnForeignKeys($colname)
    {
        $matches = array();
        foreach ($this->foreignKeys as $fk) {
            if (in_array($colname, $fk->getLocalColumns())) {
                $matches[] = $fk;
            }
        }

        return $matches;
    }

    /**
     * Set the database that contains this table.
     *
     * @param     Database $db
     */
    public function setDatabase(Database $db)
    {
        $this->database = $db;
    }

    /**
     * Get the database that contains this table.
     *
     * @return    Database
     */
    public function getDatabase()
    {
        return $this->database;
    }

    /**
     * Flag to determine if code/sql gets created for this table.
     * Table will be skipped, if return true.
     * @return    Boolean
     */
    public function isForReferenceOnly()
    {
        return $this->forReferenceOnly;
    }

    /**
     * Flag to determine if code/sql gets created for this table.
     * Table will be skipped, if set to true.
     * @param     Boolean $v
     */
    public function setForReferenceOnly($v)
    {
        $this->forReferenceOnly = (Boolean) $v;
    }

    /**
     * Appends XML nodes to passed-in DOMNode.
     *
     * @param     DOMNode $node
     */
    public function appendXml(\DOMNode $node)
    {
        $doc = ($node instanceof \DOMDocument) ? $node : $node->ownerDocument;

        $tableNode = $node->appendChild($doc->createElement('table'));
        $tableNode->setAttribute('name', $this->getCommonName());

        if (null !== $this->getSchema()) {
            $tableNode->setAttribute('schema', $this->getSchema());
        }

        if (null !== $this->phpName) {
            $tableNode->setAttribute('phpName', $this->phpName);
        }

        if (null !== $this->idMethod) {
            $tableNode->setAttribute('idMethod', $this->idMethod);
        }

        if (null !== $this->skipSql) {
            $tableNode->setAttribute('idMethod', var_export($this->skipSql, true));
        }

        if (null !== $this->readOnly) {
            $tableNode->setAttribute('readOnly', var_export($this->readOnly, true));
        }

        if (null !== $this->reloadOnInsert) {
            $tableNode->setAttribute('reloadOnInsert', var_export($this->reloadOnInsert, true));
        }

        if (null !== $this->reloadOnUpdate) {
            $tableNode->setAttribute('reloadOnUpdate', var_export($this->reloadOnUpdate, true));
        }

        if (null !== $this->forReferenceOnly) {
            $tableNode->setAttribute('forReferenceOnly', var_export($this->forReferenceOnly, true));
        }

        if (null !== $this->abstractValue) {
            $tableNode->setAttribute('abstract', var_export($this->abstractValue, true));
        }

        if (null !== $this->interface) {
            $tableNode->setAttribute('interface', $this->interface);
        }

        if (null !== $this->description) {
            $tableNode->setAttribute('description', $this->description);
        }

        if (null !== $this->namespace) {
            $tableNode->setAttribute('namespace', $this->namespace);
        }

        if (null !== $this->pkg && !$this->pkgOverridden) {
            $tableNode->setAttribute('package', $this->pkg);
        }

        if (null !== $this->baseClass) {
            $tableNode->setAttribute('baseClass', $this->baseClass);
        }

        if (null !== $this->basePeer) {
            $tableNode->setAttribute('basePeer', $this->basePeer);
        }

        if ($this->getIsCrossRef()) {
            $tableNode->setAttribute('isCrossRef', $this->getIsCrossRef());
        }


        foreach ($this->columns as $col) {
            $col->appendXml($tableNode);
        }

        foreach ($this->foreignKeys as $fk) {
            $fk->appendXml($tableNode);
        }

        foreach ($this->idMethodParameters as $param) {
            $param->appendXml($tableNode);
        }

        foreach ($this->indices as $index) {
            $index->appendXml($tableNode);
        }

        foreach ($this->unices as $unique) {
            $unique->appendXml($tableNode);
        }

        foreach ($this->vendorInfos as $vi) {
            $vi->appendXml($tableNode);
        }
    }

    /**
     * Returns the collection of Columns which make up the single primary
     * key for this table.
     *
     * @return    array Column[] A list of the primary key parts.
     */
    public function getPrimaryKey()
    {
        $pk = array();
        foreach ($this->columns as $col) {
            if ($col->isPrimaryKey()) {
                $pk[] = $col;
            }
        }

        return $pk;
    }

    /**
     * Determine whether this table has a primary key.
     *
     * @return    Boolean Whether this table has any primary key parts.
     */
    public function hasPrimaryKey()
    {
        return count($this->getPrimaryKey()) > 0;
    }

    /**
     * Determine whether this table has a composite primary key.
     *
     * @return    Boolean Whether this table has more than one primary key parts.
     */
    public function hasCompositePrimaryKey()
    {
        return count($this->getPrimaryKey()) > 1;
    }

    /**
     * Get the first column of the primary key.
     * Useful for tables with a PK using a single column.
     */
    public function getFirstPrimaryKeyColumn()
    {
        foreach ($this->columns as $col) {
            if ($col->isPrimaryKey()) {
                return $col;
            }
        }
    }

    /**
     * Determine whether this table has any auto-increment primary key(s).
     *
     * @return    Boolean Whether this table has a non-"none" id method and has a primary key column that is auto-increment.
     */
    public function hasAutoIncrementPrimaryKey()
    {
        if (IdMethod::NO_ID_METHOD !== $this->getIdMethod()) {
            foreach ($this->getPrimaryKey() as $pk) {
                if ($pk->isAutoIncrement()) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Gets the auto increment PK
     *
     * @return   Column if any auto increment PK column
     */
    public function getAutoIncrementPrimaryKey()
    {
        if (IdMethod::NO_ID_METHOD !== $this->getIdMethod()) {
            foreach ($this->getPrimaryKey() as $pk) {
                if ($pk->isAutoIncrement()) {
                    return $pk;
                }
            }
        }

        return null;
    }

    /**
     * Returns all parts of the primary key, separated by commas.
     *
     * @return    A CSV list of primary key parts.
     * @deprecated Use the Platform::getColumnListDDL() with the #getPrimaryKey() method.
     */
    public function printPrimaryKey()
    {
        return $this->printList($this->columns);
    }

    /**
     * Gets the crossRef status for this foreign key
     * @return    Boolean
     */
    public function getIsCrossRef()
    {
        return $this->isCrossRef;
    }

    /**
     * Sets a crossref status for this foreign key.
     * @param     Boolean $isCrossRef
     */
    public function setIsCrossRef($isCrossRef)
    {
        $this->isCrossRef = (Boolean) $isCrossRef;
    }

    /**
     * Returns the elements of the list, separated by commas.
     * @param     array $list
     * @return    A CSV list.
     * @deprecated Use the Platform::getColumnListDDL() method.
     */
    private function printList($list)
    {
        $result = '';
        $comma  = 0;
        for ($i = 0, $_i = count($list); $i < $_i; $i++) {
            $col = $list[$i];
            if ($col->isPrimaryKey()) {
                $result .= ($comma++ ? ',' : '') . $this->getDatabase()->getPlatform()->quoteIdentifier($col->getName());
            }
        }

        return $result;
    }

    /**
     * Returns whether the table has foreign keys or not.
     * @return Boolean
     */
    public function hasForeignKeys()
    {
        return 0 !== count($this->getForeignKeys());
    }

    /**
     * Returns whether the table has cross foreign keys or not.
     * @return Boolean
     */
    public function hasCrossForeignKeys()
    {
        return 0 !== count($this->getCrossFks());
    }
}

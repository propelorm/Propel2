<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Propel\Generator\Model;

use Propel\Generator\Config\GeneratorConfigInterface;
use Propel\Generator\Exception\BuildException;
use Propel\Generator\Exception\EngineException;
use Propel\Generator\Exception\InvalidArgumentException;
use Propel\Generator\Exception\LogicException;
use Propel\Generator\Platform\MysqlPlatform;
use Propel\Generator\Platform\PlatformInterface;
use Propel\Runtime\Exception\RuntimeException;
use Propel\Runtime\Util\UuidConverter;

/**
 * Data about a table used in an application.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Leon Messerschmidt <leon@opticode.co.za> (Torque)
 * @author Jason van Zyl <jvanzyl@apache.org> (Torque)
 * @author Martin Poeschl <mpoeschl@marmot.at> (Torque)
 * @author John McNally <jmcnally@collab.net> (Torque)
 * @author Daniel Rall <dlr@collab.net> (Torque)
 * @author Byron Foster <byron_foster@yahoo.com> (Torque)
 * @author Hugo Hamon <webmaster@apprendre-php.com> (Propel)
 */
class Table extends ScopedMappingModel implements IdMethod
{
    use BehaviorableTrait;

    /**
     * @var array<\Propel\Generator\Model\Column>
     */
    private array $columns = [];

    /**
     * @var array<\Propel\Generator\Model\ForeignKey>
     */
    private array $foreignKeys = [];

    /**
     * @var array<\Propel\Generator\Model\ForeignKey>
     */
    private array $foreignKeysByName = [];

    /**
     * @var array<string>
     */
    private array $foreignTableNames = [];

    /**
     * @var array<\Propel\Generator\Model\Index>
     */
    private array $indices = [];

    /**
     * @var array<\Propel\Generator\Model\Unique>
     */
    private array $unices = [];

    /**
     * @var array<\Propel\Generator\Model\IdMethodParameter>
     */
    private array $idMethodParameters = [];

    private ?string $commonName = null;

    private string $originCommonName;

    private ?string $description = null;

    private ?string $phpName = null;

    private string $idMethod;

    private bool $allowPkInsert = false;

    private ?string $phpNamingMethod = null;

    private ?Database $database = null;

    /**
     * @var array<\Propel\Generator\Model\ForeignKey>
     */
    private array $referrers = [];

    private bool $containsForeignPK = false;

    private ?Column $inheritanceColumn = null;

    private bool $skipSql = false;

    private bool $readOnly = false;

    private bool $isAbstract = false;

    private ?string $alias = null;

    private ?string $interface = null;

    private ?string $baseClass = null;

    private ?string $baseQueryClass = null;

    /**
     * @var array<string, \Propel\Generator\Model\Column>
     */
    private array $columnsByName = [];

    /**
     * @var array<string, \Propel\Generator\Model\Column>
     */
    private array $columnsByLowercaseName = [];

    /**
     * @var array<string, \Propel\Generator\Model\Column>
     */
    private array $columnsByPhpName = [];

    private bool $needsTransactionInPostgres = false;

    private bool $heavyIndexing = false;

    /**
     * It's important that this remains nullable so we can determine the intent. If it is explicitly set to false, we'll ignore identifier quoting, otherwise it'll take the value specified at the database level.
     *
     * @var bool|null
     */
    private ?bool $identifierQuoting = null;

    private ?bool $forReferenceOnly = null;

    private bool $reloadOnInsert = false;

    private bool $reloadOnUpdate = false;

    /**
     * The default accessor visibility.
     *
     * It may be one of public, private and protected.
     */
    private string $defaultAccessorVisibility;

    /**
     * The default mutator visibility.
     *
     * It may be one of public, private and protected.
     */
    private string $defaultMutatorVisibility;

    protected bool $isCrossRef = false;

    protected ?string $defaultStringFormat = null;

    /**
     * Constructs a table object with a name
     *
     * @param string $name table name
     */
    public function __construct(string $name)
    {
        parent::__construct();

        $this->setCommonName($name);

        $this->idMethod = IdMethod::NO_ID_METHOD;
        $this->defaultAccessorVisibility = static::VISIBILITY_PUBLIC;
        $this->defaultMutatorVisibility = static::VISIBILITY_PUBLIC;
    }

    /**
     * Returns a qualified name of this table with scheme and common name
     * separated by '_'.
     *
     * If autoPrefix is set. Otherwise get the common name.
     *
     * @return string
     */
    private function getStdSeparatedName(): string
    {
        if ($this->schema && $this->getBuildProperty('generator.schema.autoPrefix')) {
            return $this->schema . NameGeneratorInterface::STD_SEPARATOR_CHAR . $this->getCommonName();
        }

        return $this->getCommonName();
    }

    /**
     * @return void
     */
    public function setupObject(): void
    {
        parent::setupObject();

        $this->commonName = $this->originCommonName = $this->getAttribute('name');

        // retrieves the method for converting from specified name to a PHP name.
        $this->phpNamingMethod = $this->getAttribute('phpNamingMethod', $this->database->getDefaultPhpNamingMethod());

        $this->phpName = $this->getAttribute('phpName', $this->buildPhpName($this->getStdSeparatedName()));

        if ($this->database->getTablePrefix()) {
            $this->commonName = $this->database->getTablePrefix() . $this->commonName;
        }

        $this->idMethod = $this->getAttribute('idMethod', $this->database->getDefaultIdMethod());
        $this->allowPkInsert = $this->booleanValue($this->getAttribute('allowPkInsert'));

        $this->skipSql = $this->booleanValue($this->getAttribute('skipSql'));
        $this->readOnly = $this->booleanValue($this->getAttribute('readOnly'));

        $this->isAbstract = $this->booleanValue($this->getAttribute('abstract'));
        $this->baseClass = $this->getAttribute('baseClass');
        $this->baseQueryClass = $this->getAttribute('baseQueryClass');
        $this->alias = $this->getAttribute('alias');

        $this->heavyIndexing = (
            $this->booleanValue($this->getAttribute('heavyIndexing'))
            || (
                $this->getAttribute('heavyIndexing') !== 'false'
                && $this->database->isHeavyIndexing()
            )
        );

        if ($this->getAttribute('identifierQuoting')) {
            $this->identifierQuoting = $this->booleanValue($this->getAttribute('identifierQuoting'));
        }

        $this->description = $this->getAttribute('description');
        $this->interface = $this->getAttribute('interface'); // sic ('interface' is reserved word)

        $this->reloadOnInsert = $this->booleanValue($this->getAttribute('reloadOnInsert'));
        $this->reloadOnUpdate = $this->booleanValue($this->getAttribute('reloadOnUpdate'));
        $this->isCrossRef = $this->booleanValue($this->getAttribute('isCrossRef', false));
        $this->defaultStringFormat = $this->getAttribute('defaultStringFormat');
        $this->defaultAccessorVisibility = $this->getAttribute('defaultAccessorVisibility', $this->database->getAttribute('defaultAccessorVisibility', static::VISIBILITY_PUBLIC));
        $this->defaultMutatorVisibility = $this->getAttribute('defaultMutatorVisibility', $this->database->getAttribute('defaultMutatorVisibility', static::VISIBILITY_PUBLIC));
    }

    /**
     * Returns a build property value for the database this table belongs to.
     *
     * @param string $name
     *
     * @return string
     */
    public function getBuildProperty(string $name): string
    {
        return $this->database ? $this->database->getBuildProperty($name) : '';
    }

    /**
     * Executes behavior table modifiers.
     *
     * @return void
     */
    public function applyBehaviors(): void
    {
        foreach ($this->behaviors as $behavior) {
            if (!$behavior->isTableModified()) {
                $behavior->getTableModifier()->modifyTable();
                $behavior->setTableModified(true);
            }
        }
    }

    /**
     * @param \Propel\Generator\Model\Behavior $behavior
     *
     * @return void
     */
    protected function registerBehavior(Behavior $behavior): void
    {
        $behavior->setTable($this);
    }

    /**
     * <p>A hook for the SAX XML parser to call when this table has
     * been fully loaded from the XML, and all nested elements have
     * been processed.</p>
     *
     * <p>Performs heavy indexing and naming of elements which weren't
     * provided with a name.</p>
     *
     * @return void
     */
    public function doFinalInitialization(): void
    {
        // Heavy indexing must wait until after all columns composing
        // a table's primary key have been parsed.
        if ($this->heavyIndexing) {
            $this->doHeavyIndexing();
        }

        // if idMethod is "native" and in fact there are no autoIncrement
        // columns in the table, then change it to "none"
        $anyAutoInc = false;
        foreach ($this->columns as $column) {
            if ($column->isAutoIncrement()) {
                $anyAutoInc = true;
            }
        }
        if ($this->getIdMethod() === IdMethod::NATIVE && !$anyAutoInc) {
            $this->setIdMethod(IdMethod::NO_ID_METHOD);
        }
    }

    /**
     * Adds extra indices for multi-part primary key columns.
     *
     * For databases like MySQL, values in a where clause much
     * match key part order from the left to right. So, in the key
     * definition <code>PRIMARY KEY (FOO_ID, BAR_ID)</code>,
     * <code>FOO_ID</code> <i>must</i> be the first element used in
     * the <code>where</code> clause of the SQL query used against
     * this table for the primary key index to be used. This feature
     * could cause problems under MySQL with heavily indexed tables,
     * as MySQL currently only supports 16 indices per table (i.e. it
     * might cause too many indices to be created).
     *
     * See the mysql manual http://www.mysql.com/doc/E/X/EXPLAIN.html
     * for a better description of why heavy indexing is useful for
     * quickly searchable database tables.
     *
     * @return void
     */
    private function doHeavyIndexing(): void
    {
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
     *
     * @return void
     */
    public function addExtraIndices(): void
    {
        /**
         * A collection of indexed columns. The key is the column name
         * (concatenated with a comma in the case of multi-col index), the value is
         * an array with the names of the indexes that index these columns. We use
         * it to determine which additional indexes must be created for foreign
         * keys. It could also be used to detect duplicate indexes, but this is not
         * implemented yet.
         *
         * @var array<string, array<string>> $_indices
         */
        $_indices = [];

        $this->collectIndexedColumns('PRIMARY', $this->getPrimaryKey(), $_indices);

        $_tableIndices = array_merge($this->getIndices(), $this->getUnices());
        foreach ($_tableIndices as $_index) {
            $this->collectIndexedColumns($_index->getName(), $_index->getColumns(), $_indices);
        }

        // we're determining which tables have foreign keys that point to this table,
        // since MySQL needs an index on any column that is referenced by another table
        // (yep, MySQL _is_ a PITA)
        $counter = 0;
        foreach ($this->referrers as $foreignKey) {
            $referencedColumns = $foreignKey->getForeignColumnObjects();
            $referencedColumnsHash = $this->getColumnList($referencedColumns);
            if (!$referencedColumns || isset($_indices[$referencedColumnsHash])) {
                continue;
            }

            // no matching index defined in the schema, so we have to create one
            $name = sprintf('i_referenced_%s_%s', $foreignKey->getName(), ++$counter);
            if ($this->hasIndex($name)) {
                // if we have already a index with this name, then it looks like the columns of this index have just
                // been changed, so remove it and inject it again. This is the case if a referenced table is handled
                // later than the referencing table.
                $this->removeIndex($name);
            }

            $index = $this->createIndex($name, $referencedColumns);
            // Add this new index to our collection, otherwise we might add it again (bug #725)
            $this->collectIndexedColumns($index->getName(), $referencedColumns, $_indices);
        }

        // we're adding indices for this table foreign keys
        foreach ($this->foreignKeys as $foreignKey) {
            $localColumns = $foreignKey->getLocalColumnObjects();
            $localColumnsHash = $this->getColumnList($localColumns);
            if (!$localColumns || isset($_indices[$localColumnsHash])) {
                continue;
            }

            // No matching index defined in the schema, so we have to create one.
            // MySQL needs indices on any columns that serve as foreign keys.
            // These are not auto-created prior to 4.1.2.

            $name = substr_replace($foreignKey->getName(), 'fi_', (int)strrpos($foreignKey->getName(), 'fk_'), 3);
            if ($this->hasIndex($name)) {
                // if we already have an index with this name, then it looks like the columns of this index have just
                // been changed, so remove it and inject it again. This is the case if a referenced table is handled
                // later than the referencing table.
                $this->removeIndex($name);
            }

            $index = $this->createIndex($name, $localColumns);
            $this->collectIndexedColumns($index->getName(), $localColumns, $_indices);
        }
    }

    /**
     * Creates a new index.
     *
     * @param string $name The index name
     * @param array<\Propel\Generator\Model\Column> $columns The list of columns to index
     *
     * @return \Propel\Generator\Model\Index The created index
     */
    protected function createIndex(string $name, array $columns): Index
    {
        $index = new Index($name);
        $index->setColumns($columns);
        $index->resetColumnsSize();

        $this->addIndex($index);

        return $index;
    }

    /**
     * Helper function to collect indexed columns.
     *
     * @param string $indexName The name of the index
     * @param array<\Propel\Generator\Model\Column|string> $columns The column names or objects
     * @param array $collectedIndexes The collected indexes
     *
     * @return void
     */
    protected function collectIndexedColumns(string $indexName, array $columns, array &$collectedIndexes): void
    {
        /**
         * "If the table has a multiple-column index, any leftmost prefix of the
         * index can be used by the optimizer to find rows. For example, if you
         * have a three-column index on (col1, col2, col3), you have indexed search
         * capabilities on (col1), (col1, col2), and (col1, col2, col3)."
         * @link http://dev.mysql.com/doc/refman/5.5/en/mysql-indexes.html
         */
        $indexedColumns = [];
        foreach ($columns as $column) {
            $indexedColumns[] = $column;
            $indexedColumnsHash = $this->getColumnList($indexedColumns);
            if (!isset($collectedIndexes[$indexedColumnsHash])) {
                $collectedIndexes[$indexedColumnsHash] = [];
            }
            $collectedIndexes[$indexedColumnsHash][] = $indexName;
        }
    }

    /**
     * Returns a delimiter-delimited string list of column names.
     *
     * @see \Propel\Generator\Platform\PlatformInterface::getColumnListDDL() if quoting is required
     *
     * @param array<\Propel\Generator\Model\Column|string> $columns
     * @param string $delimiter
     *
     * @return string
     */
    public function getColumnList(array $columns, string $delimiter = ','): string
    {
        $list = [];
        foreach ($columns as $col) {
            if ($col instanceof Column) {
                $col = $col->getName();
            }
            $list[] = $col;
        }

        return implode($delimiter, $list);
    }

    /**
     * Returns the name of the base class used for superclass of all objects
     * of this table.
     *
     * @return string|null
     */
    public function getBaseClass(): ?string
    {
        if ($this->isAlias() && $this->baseClass === null) {
            return $this->alias;
        }

        if ($this->baseClass === null) {
            return $this->database->getBaseClass();
        }

        return $this->baseClass;
    }

    /**
     * Returns the name of the base query class used for superclass of all query objects
     * of this table.
     *
     * @return string|null
     */
    public function getBaseQueryClass(): ?string
    {
        if ($this->baseQueryClass === null) {
            return $this->database->getBaseQueryClass();
        }

        return $this->baseQueryClass;
    }

    /**
     * Sets the base class name.
     *
     * @param string $class
     *
     * @return void
     */
    public function setBaseClass(string $class): void
    {
        $this->baseClass = $this->makeNamespaceAbsolute($class);
    }

    /**
     * Sets the base query class name.
     *
     * @param string $class
     *
     * @return void
     */
    public function setBaseQueryClass(string $class): void
    {
        $this->baseQueryClass = $this->makeNamespaceAbsolute($class);
    }

    /**
     * Adds a new column to the table.
     *
     * @param \Propel\Generator\Model\Column|array $col
     *
     * @throws \Propel\Generator\Exception\EngineException
     *
     * @return \Propel\Generator\Model\Column
     */
    public function addColumn($col): Column
    {
        if (is_array($col)) {
            $column = new Column($col['name']);
            $column->setTable($this);
            $column->loadMapping($col);

            $col = $column;
        }

        if (isset($this->columnsByName[$col->getName()])) {
            throw new EngineException(sprintf('Column "%s" declared twice in table "%s"', $col->getName(), $this->getName()));
        }

        $col->setTable($this);

        if ($col->isInheritance()) {
            $this->inheritanceColumn = $col;
        }

        $this->columns[] = $col;
        $this->columnsByName[(string)$col->getName()] = $col;
        $this->columnsByLowercaseName[strtolower((string)$col->getName())] = $col;
        $this->columnsByPhpName[(string)$col->getPhpName()] = $col;
        $col->setPosition(count($this->columns));

        if ($col->requiresTransactionInPostgres()) {
            $this->needsTransactionInPostgres = true;
        }

        return $col;
    }

    /**
     * Adds several columns at once.
     *
     * @param array<\Propel\Generator\Model\Column> $columns An array of Column instance
     *
     * @return void
     */
    public function addColumns(array $columns): void
    {
        foreach ($columns as $column) {
            $this->addColumn($column);
        }
    }

    /**
     * Removes a column from the table.
     *
     * @param \Propel\Generator\Model\Column|string $column The Column or its name
     *
     * @throws \Propel\Generator\Exception\EngineException
     *
     * @return void
     */
    public function removeColumn($column): void
    {
        if (is_string($column)) {
            $column = $this->getColumn($column);
        }

        $pos = $this->getColumnPosition($column);
        if ($pos === false) {
            throw new EngineException(sprintf('No column named %s found in table %s.', $column->getName(), $this->getName()));
        }

        unset($this->columns[$pos]);
        unset($this->columnsByName[$column->getName()]);
        unset($this->columnsByLowercaseName[strtolower($column->getName())]);
        unset($this->columnsByPhpName[$column->getPhpName()]);

        $this->adjustColumnPositions();

        // @FIXME: also remove indexes and validators on this column?
    }

    /**
     * @param \Propel\Generator\Model\Column $column
     *
     * @return int|false
     */
    private function getColumnPosition(Column $column)
    {
        $position = false;
        $nbColumns = $this->getNumColumns();
        for ($pos = 0; $pos < $nbColumns; $pos++) {
            if ($this->columns[$pos] === $column) {
                $position = $pos;
            }
        }

        return $position;
    }

    /**
     * @return void
     */
    public function adjustColumnPositions(): void
    {
        $this->columns = array_values($this->columns);
        $nbColumns = $this->getNumColumns();
        for ($i = 0; $i < $nbColumns; $i++) {
            $this->columns[$i]->setPosition($i + 1);
        }
    }

    /**
     * Adds a new foreign key to this table.
     *
     * @param \Propel\Generator\Model\ForeignKey|array $foreignKey The foreign key mapping
     *
     * @throws \Propel\Generator\Exception\EngineException
     *
     * @return \Propel\Generator\Model\ForeignKey
     */
    public function addForeignKey($foreignKey): ForeignKey
    {
        if ($foreignKey instanceof ForeignKey) {
            $fk = $foreignKey;
            $fk->setTable($this);

            $name = $fk->getPhpName() ?: $fk->getName();
            if (isset($this->foreignKeysByName[$name])) {
                throw new EngineException(sprintf('Foreign key "%s" declared twice in table "%s". Please specify a different php name!', $name, $this->getName()));
            }

            $this->foreignKeys[] = $fk;
            $this->foreignKeysByName[$name] = $fk;

            if (!in_array($fk->getForeignTableName(), $this->foreignTableNames, true)) {
                /** @var string $foreignTableName */
                $foreignTableName = $fk->getForeignTableName();
                $this->foreignTableNames[] = $foreignTableName;
            }

            return $fk;
        }

        $fk = new ForeignKey($foreignKey['name'] ?? null);
        $fk->setTable($this);
        $fk->loadMapping($foreignKey);

        return $this->addForeignKey($fk);
    }

    /**
     * Adds several foreign keys at once.
     *
     * @param array<\Propel\Generator\Model\ForeignKey> $foreignKeys An array of ForeignKey objects
     *
     * @return void
     */
    public function addForeignKeys(array $foreignKeys): void
    {
        foreach ($foreignKeys as $foreignKey) {
            $this->addForeignKey($foreignKey);
        }
    }

    /**
     * Returns the column that subclasses the class representing this
     * table can be produced from.
     *
     * @return \Propel\Generator\Model\Column|null
     */
    public function getChildrenColumn(): ?Column
    {
        return $this->inheritanceColumn;
    }

    /**
     * Checks whether the table uses concrete inheritance
     *
     * @return bool
     */
    public function usesConcreteInheritance(): bool
    {
        return ($this->inheritanceColumn !== null);
    }

    /**
     * Returns the subclasses that can be created from this table.
     *
     * @return array|null
     */
    public function getChildrenNames(): ?array
    {
        if (
            $this->inheritanceColumn === null
            || !$this->inheritanceColumn->isEnumeratedClasses()
        ) {
            return null;
        }

        $names = [];
        foreach ($this->inheritanceColumn->getChildren() as $child) {
            $names[] = get_class($child);
        }

        return $names;
    }

    /**
     * Adds the foreign key from another table that refers to this table.
     *
     * @param \Propel\Generator\Model\ForeignKey $fk
     *
     * @return void
     */
    public function addReferrer(ForeignKey $fk): void
    {
        $this->referrers[] = $fk;
    }

    /**
     * Returns the list of references to this table.
     *
     * @return array<\Propel\Generator\Model\ForeignKey>
     */
    public function getReferrers(): array
    {
        return $this->referrers;
    }

    /**
     * Browses the foreign keys and creates referrers for the foreign table.
     * This method can be called several times on the same table. It only
     * adds the missing referrers and is non-destructive.
     * Warning: only use when all the tables were created.
     *
     * @param bool $throwErrors
     *
     * @throws \Propel\Generator\Exception\BuildException
     *
     * @return void
     */
    public function setupReferrers(bool $throwErrors = false): void
    {
        foreach ($this->foreignKeys as $foreignKey) {
            // table referrers
            $foreignTable = $this->database->getTable($foreignKey->getForeignTableName());

            if ($foreignTable !== null) {
                $referrers = $foreignTable->getReferrers();
                if (!$referrers || !in_array($foreignKey, $referrers, true)) {
                    $foreignTable->addReferrer($foreignKey);
                }
            } elseif ($throwErrors) {
                throw new BuildException(sprintf(
                    'Table "%s" contains a foreign key to nonexistent table "%s"',
                    $this->getName(),
                    $foreignKey->getForeignTableName(),
                ));
            }

            // foreign pk's
            $localColumnNames = $foreignKey->getLocalColumns();
            foreach ($localColumnNames as $localColumnName) {
                $localColumn = $this->getColumn($localColumnName);
                if ($localColumn !== null) {
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
                        $localColumnName,
                    ));
                }
            }

            // foreign column references
            $foreignColumnNames = $foreignKey->getForeignColumns();
            foreach ($foreignColumnNames as $foreignColumnName) {
                if ($foreignTable === null) {
                    continue;
                }
                $foreignColumn = $foreignColumnName ? $foreignTable->getColumn($foreignColumnName) : null;
                if ($foreignColumn !== null) {
                    if (!$foreignColumn->hasReferrer($foreignKey)) {
                        $foreignColumn->addReferrer($foreignKey);
                    }
                } elseif ($throwErrors && !$foreignKey->isPolymorphic()) {
                    // if the foreign column does not exist, we may have an
                    // external reference or a misspelling
                    throw new BuildException(sprintf(
                        'Table "%s" contains a foreign key to table "%s" with nonexistent column "%s"',
                        $this->getName(),
                        $foreignTable->getName(),
                        $foreignColumnName,
                    ));
                }
            }

            // check for incomplete foreign key references when foreign table
            // has a composite primary key
            if ($foreignTable->hasCompositePrimaryKey() && !$foreignKey->isForeignNonPrimaryKey()) {
                // get composite foreign key's keys
                $foreignPrimaryKeys = $foreignTable->getPrimaryKey();
                // check all keys are referenced in foreign key
                foreach ($foreignPrimaryKeys as $foreignPrimaryKey) {
                    if (!$foreignPrimaryKey->hasReferrer($foreignKey) && $throwErrors) {
                        // foreign primary key is not being referenced in foreign key
                        throw new BuildException(sprintf(
                            'Table "%s" contains a foreign key to table "%s" but does not have a reference to foreign primary key "%s"',
                            $this->getName(),
                            $foreignTable->getName(),
                            $foreignPrimaryKey->getName(),
                        ));
                    }
                }
            }

            if ($this->getPlatform() instanceof MysqlPlatform) {
                $this->addExtraIndices();
            }
        }
    }

    /**
     * Returns the list of cross foreign keys.
     *
     * @return array<\Propel\Generator\Model\CrossForeignKeys>
     */
    public function getCrossFks(): array
    {
        $crossFks = [];
        foreach ($this->referrers as $refFK) {
            if ($refFK->getTable()->isCrossRef()) {
                $crossFK = new CrossForeignKeys($refFK, $this);
                foreach ($refFK->getOtherFks() as $fk) {
                    if (
                        $fk->isAtLeastOneLocalPrimaryKeyIsRequired() &&
                        $crossFK->isAtLeastOneLocalPrimaryKeyNotCovered($fk)
                    ) {
                        $crossFK->addCrossForeignKey($fk);
                    }
                }
                if ($crossFK->hasCrossForeignKeys()) {
                    $crossFks[] = $crossFK;
                }
            }
        }

        return $crossFks;
    }

    /**
     * Returns all required(notNull && no defaultValue) primary keys which are not in $primaryKeys.
     *
     * @param array<\Propel\Generator\Model\Column> $primaryKeys
     *
     * @return array<\Propel\Generator\Model\Column>
     */
    public function getOtherRequiredPrimaryKeys(array $primaryKeys): array
    {
        /** @var array<\Propel\Generator\Model\Column> $pks */
        $pks = [];
        foreach ($this->getPrimaryKey() as $primaryKey) {
            if ($primaryKey->isNotNull() && !$primaryKey->hasDefaultValue() && !in_array($primaryKey, $primaryKeys, true)) {
                $pks[] = $primaryKey;
            }
        }

        return $pks;
    }

    /**
     * Sets whether this table contains a foreign primary key.
     *
     * @param bool $containsForeignPK
     *
     * @return void
     */
    public function setContainsForeignPK(bool $containsForeignPK): void
    {
        $this->containsForeignPK = $containsForeignPK;
    }

    /**
     * Returns whether this table contains a foreign primary key.
     *
     * @return bool
     */
    public function getContainsForeignPK(): bool
    {
        return $this->containsForeignPK;
    }

    /**
     * Returns the list of tables referenced by foreign keys in this table.
     *
     * @return array
     */
    public function getForeignTableNames(): array
    {
        return $this->foreignTableNames;
    }

    /**
     * @param \Propel\Generator\Model\ForeignKey $fk
     *
     * @return bool
     */
    public function containsForeignKeyWithSameName(ForeignKey $fk): bool
    {
        $name = $fk->getPhpName() ?: $fk->getName();

        return isset($this->foreignKeysByName[$name]);
    }

    /**
     * Return true if the column requires a transaction in Postgres.
     *
     * @return bool
     */
    public function requiresTransactionInPostgres(): bool
    {
        return $this->needsTransactionInPostgres;
    }

    /**
     * Adds a new parameter for the strategy that generates primary keys.
     *
     * @param \Propel\Generator\Model\IdMethodParameter|array $idMethodParameter
     *
     * @return \Propel\Generator\Model\IdMethodParameter
     */
    public function addIdMethodParameter($idMethodParameter): IdMethodParameter
    {
        if ($idMethodParameter instanceof IdMethodParameter) {
            $idMethodParameter->setTable($this);
            $this->idMethodParameters[] = $idMethodParameter;

            return $idMethodParameter;
        }

        $imp = new IdMethodParameter();
        $imp->setTable($this);
        $imp->loadMapping($idMethodParameter);

        return $this->addIdMethodParameter($imp);
    }

    /**
     * Removes a index from the table.
     *
     * @param string $name
     *
     * @return void
     */
    public function removeIndex(string $name): void
    {
        // check if we have a index with this name already, then delete it
        foreach ($this->indices as $n => $idx) {
            if ($idx->getName() == $name) {
                unset($this->indices[$n]);

                return;
            }
        }
    }

    /**
     * Checks if the table has a index by name.
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasIndex(string $name): bool
    {
        foreach ($this->indices as $idx) {
            if ($idx->getName() == $name) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get indexes on a column
     *
     * @param \Propel\Generator\Model\Column $column
     *
     * @return array<\Propel\Generator\Model\Index>
     */
    public function getIndexesOnColumn(Column $column): array
    {
        $columnName = $column->getName();

        return array_filter($this->indices, fn ($idx) => $idx->hasColumn($columnName));
    }

    /**
     * Adds a new index to the indices list and set the
     * parent table of the column to the current table.
     *
     * @param \Propel\Generator\Model\Index|array $index
     *
     * @throws \Propel\Generator\Exception\InvalidArgumentException
     *
     * @return \Propel\Generator\Model\Index
     */
    public function addIndex($index): Index
    {
        if ($index instanceof Index) {
            if ($this->hasIndex($index->getName())) {
                throw new InvalidArgumentException(sprintf('Index "%s" already exist.', $index->getName()));
            }
            if (!$index->getColumns()) {
                throw new InvalidArgumentException(sprintf('Index "%s" has no columns.', $index->getName()));
            }
            $index->setTable($this);
            // force the name to be created if empty.
            $this->indices[] = $index;

            return $index;
        }

        $idx = new Index();
        $idx->loadMapping($index);
        $columns = !empty($index['columns']) ? (array)$index['columns'] : [];
        foreach ($columns as $column) {
            $idx->addColumn($column);
        }

        return $this->addIndex($idx);
    }

    /**
     * Adds a new Unique index to the list of unique indices and set the
     * parent table of the column to the current table.
     *
     * @param \Propel\Generator\Model\Unique|array $unique
     *
     * @return \Propel\Generator\Model\Unique
     */
    public function addUnique($unique): Unique
    {
        if ($unique instanceof Unique) {
            $unique->setTable($this);
            $unique->getName(); // we call this method so that the name is created now if it doesn't already exist.
            $this->unices[] = $unique;

            return $unique;
        }

        $unik = new Unique();
        $unik->loadMapping($unique);

        return $this->addUnique($unik);
    }

    /**
     * Retrieves the configuration object.
     *
     * @return \Propel\Generator\Config\GeneratorConfigInterface|null
     */
    public function getGeneratorConfig(): ?GeneratorConfigInterface
    {
        return $this->database->getGeneratorConfig();
    }

    /**
     * Returns whether the table behaviors offer additional builders.
     *
     * @return bool
     */
    public function hasAdditionalBuilders(): bool
    {
        foreach ($this->behaviors as $behavior) {
            if ($behavior->hasAdditionalBuilders()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the list of additional builders provided by the table behaviors.
     *
     * @return array
     */
    public function getAdditionalBuilders(): array
    {
        $additionalBuilders = [];
        foreach ($this->behaviors as $behavior) {
            $additionalBuilders = array_merge($additionalBuilders, $behavior->getAdditionalBuilders());
        }

        return $additionalBuilders;
    }

    /**
     * Returns the full table name (including schema name when possible).
     *
     * @return string
     */
    public function getName(): string
    {
        $tableName = '';
        if ($this->hasSchema()) {
            $tableName = $this->guessSchemaName() . $this->database->getSchemaDelimiter();
        }

        $tableName .= $this->commonName;

        return $tableName;
    }

    /**
     * Returns the schema name from this table or from its database.
     *
     * @return string|null
     */
    public function guessSchemaName(): ?string
    {
        if ($this->schema) {
            return $this->schema;
        }

        return $this->database ? $this->database->getSchema() : null;
    }

    /**
     * Returns whether this table is linked to a schema.
     *
     * @return bool
     */
    private function hasSchema(): bool
    {
        return $this->database
            && ($this->schema ?: $this->database->getSchema())
            && ($platform = $this->getPlatform())
            && $platform->supportsSchemas();
    }

    /**
     * Returns the table description.
     *
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Returns whether the table has a description.
     *
     * @return bool
     */
    public function hasDescription(): bool
    {
        return (bool)$this->description;
    }

    /**
     * Sets the table description.
     *
     * @param string $description
     *
     * @return void
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /**
     * Returns the name to use in PHP sources.
     *
     * @return string
     */
    public function getPhpName(): string
    {
        if ($this->phpName === null) {
            $this->phpName = $this->buildPhpName($this->getStdSeparatedName());
        }

        return $this->phpName;
    }

    /**
     * Sets the name to use in PHP sources.
     *
     * @param string $phpName
     *
     * @return void
     */
    public function setPhpName(string $phpName): void
    {
        $this->phpName = $phpName;
    }

    /**
     * Returns the auto generated PHP name value for a given name.
     *
     * @param string $name
     *
     * @return string
     */
    private function buildPhpName(string $name): string
    {
        return NameFactory::generateName(NameFactory::PHP_GENERATOR, [$name, (string)$this->phpNamingMethod]);
    }

    /**
     * Returns the camelCase version of PHP name.
     *
     * The studly name is the PHP name with the first character lowercase.
     *
     * @return string
     */
    public function getCamelCaseName(): string
    {
        return lcfirst($this->getPhpName());
    }

    /**
     * Returns the common name (without schema name), but with table prefix if defined.
     *
     * @return string
     */
    public function getCommonName(): string
    {
        return $this->commonName;
    }

    /**
     * Sets the table common name (without schema name).
     *
     * @param string $name
     *
     * @return void
     */
    public function setCommonName(string $name): void
    {
        $this->commonName = $this->originCommonName = $name;
    }

    /**
     * Returns the unmodified common name (not modified by table prefix).
     *
     * @return string|null
     */
    public function getOriginCommonName(): ?string
    {
        return $this->originCommonName;
    }

    /**
     * Sets the default string format for ActiveRecord objects in this table.
     *
     * Any of 'XML', 'YAML', 'JSON', or 'CSV'.
     *
     * @param string $format
     *
     * @throws \Propel\Generator\Exception\InvalidArgumentException
     *
     * @return void
     */
    public function setDefaultStringFormat(string $format): void
    {
        $formats = Database::getSupportedStringFormats();

        $format = strtoupper($format);
        if (!in_array($format, $formats, true)) {
            throw new InvalidArgumentException(sprintf('Given "%s" default string format is not supported. Only "%s" are valid string formats.', $format, implode(', ', $formats)));
        }

        $this->defaultStringFormat = $format;
    }

    /**
     * Returns the default string format for ActiveRecord objects in this table,
     * or the one for the whole database if not set.
     *
     * @return string
     */
    public function getDefaultStringFormat(): string
    {
        if ($this->defaultStringFormat !== null) {
            return $this->defaultStringFormat;
        }

        return $this->database->getDefaultStringFormat();
    }

    /**
     * Returns the method strategy for generating primary keys.
     *
     * [HL] changing behavior so that Database default method is returned
     * if no method has been specified for the table.
     *
     * @return string
     */
    public function getIdMethod(): string
    {
        return $this->idMethod;
    }

    /**
     * Returns whether we allow to insert primary keys on tables with
     * native id method.
     *
     * @return bool
     */
    public function isAllowPkInsert(): bool
    {
        return $this->allowPkInsert;
    }

    /**
     * Sets the method strategy for generating primary keys.
     *
     * @param string $idMethod
     *
     * @return void
     */
    public function setIdMethod(string $idMethod): void
    {
        $this->idMethod = $idMethod;
    }

    /**
     * Returns whether Propel has to skip DDL SQL generation for this
     * table (in the event it should not be created from scratch).
     *
     * @return bool
     */
    public function isSkipSql(): bool
    {
        return ($this->skipSql || $this->isAlias() || $this->isForReferenceOnly());
    }

    /**
     * Sets whether this table should have its SQL DDL code generated.
     *
     * @param bool $skip
     *
     * @return void
     */
    public function setSkipSql(bool $skip): void
    {
        $this->skipSql = $skip;
    }

    /**
     * Returns whether this table is read-only. If yes, only only
     * accessors and relationship accessors and mutators will be generated.
     *
     * @return bool
     */
    public function isReadOnly(): bool
    {
        return $this->readOnly;
    }

    /**
     * Makes this database in read-only mode.
     *
     * @param bool $flag True by default
     *
     * @return void
     */
    public function setReadOnly(bool $flag): void
    {
        $this->readOnly = $flag;
    }

    /**
     * Whether to force object to reload on INSERT.
     *
     * @return bool
     */
    public function isReloadOnInsert(): bool
    {
        return $this->reloadOnInsert;
    }

    /**
     * Makes this database reload on insert statement.
     *
     * @param bool $flag True by default
     *
     * @return void
     */
    public function setReloadOnInsert(bool $flag): void
    {
        $this->reloadOnInsert = $flag;
    }

    /**
     * Returns whether to force object to reload on UPDATE.
     *
     * @return bool
     */
    public function isReloadOnUpdate(): bool
    {
        return $this->reloadOnUpdate;
    }

    /**
     * Makes this database reload on update statement.
     *
     * @param bool $flag True by default
     *
     * @return void
     */
    public function setReloadOnUpdate(bool $flag): void
    {
        $this->reloadOnUpdate = $flag;
    }

    /**
     * Returns the PHP name of an active record object this entry references.
     *
     * @return string|null
     */
    public function getAlias(): ?string
    {
        return $this->alias;
    }

    /**
     * Returns whether this table is specified in the schema or if there
     * is just a foreign key reference to it.
     *
     * @return bool
     */
    public function isAlias(): bool
    {
        return $this->alias !== null;
    }

    /**
     * Sets whether this table is specified in the schema or if there is
     * just a foreign key reference to it.
     *
     * @param string $alias
     *
     * @return void
     */
    public function setAlias(string $alias): void
    {
        $this->alias = $alias;
    }

    /**
     * Returns the interface objects of this table will implement.
     *
     * @return string|null
     */
    public function getInterface(): ?string
    {
        return $this->interface;
    }

    /**
     * Sets the interface objects of this table will implement.
     *
     * @param string $interface
     *
     * @return void
     */
    public function setInterface(string $interface): void
    {
        $this->interface = $interface;
    }

    /**
     * Returns whether a table is abstract, it marks the business object
     * class that is generated as being abstract. If you have a table called
     * "FOO", then the Foo business object class will be declared abstract. This
     * helps support class hierarchies
     *
     * @return bool
     */
    public function isAbstract(): bool
    {
        return $this->isAbstract;
    }

    /**
     * Sets whether a table is abstract, it marks the business object
     * class that is generated as being abstract. If you have a
     * table called "FOO", then the Foo business object class will be
     * declared abstract. This helps support class hierarchies
     *
     * @param bool $flag
     *
     * @return void
     */
    public function setAbstract(bool $flag): void
    {
        $this->isAbstract = $flag;
    }

    /**
     * Returns an array containing all Column objects in the table.
     *
     * @return array<\Propel\Generator\Model\Column>
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * Returns the number of columns in this table.
     *
     * @return int
     */
    public function getNumColumns(): int
    {
        return count($this->columns);
    }

    /**
     * Returns the number of lazy loaded columns in this table.
     *
     * @return int
     */
    public function getNumLazyLoadColumns(): int
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
     * Returns whether one of the columns is of type ENUM or SET.
     *
     * @return bool
     */
    public function hasValueSetColumns(): bool
    {
        foreach ($this->columns as $col) {
            if ($col->isValueSetType()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the list of all foreign keys.
     *
     * @return array<\Propel\Generator\Model\ForeignKey>
     */
    public function getForeignKeys(): array
    {
        return $this->foreignKeys;
    }

    /**
     * Returns a Collection of parameters relevant for the chosen
     * id generation method.
     *
     * @return array<\Propel\Generator\Model\IdMethodParameter>
     */
    public function getIdMethodParameters(): array
    {
        return $this->idMethodParameters;
    }

    /**
     * Returns the list of all indices of this table.
     *
     * @return array<\Propel\Generator\Model\Index>
     */
    public function getIndices(): array
    {
        return $this->indices;
    }

    /**
     * Returns the list of all unique indices of this table.
     *
     * @return array<\Propel\Generator\Model\Unique>
     */
    public function getUnices(): array
    {
        return $this->unices;
    }

    /**
     * Checks if $keys are a unique constraint in the table.
     * (through primaryKey, through a regular unices constraints or for single keys when it has isUnique=true)
     *
     * @param array<\Propel\Generator\Model\Column>|array<string> $keys
     *
     * @return bool
     */
    public function isUnique(array $keys): bool
    {
        if (count($keys) === 1) {
            $column = $keys[0] instanceof Column ? $keys[0] : $this->getColumn($keys[0]);
            if ($column) {
                if ($column->isUnique()) {
                    return true;
                }

                if ($column->isPrimaryKey() && count($column->getTable()->getPrimaryKey()) === 1) {
                    return true;
                }
            }
        }

        // check if pk == $keys
        if (count($this->getPrimaryKey()) === count($keys)) {
            $allPk = true;
            $stringArray = is_string($keys[0]);
            foreach ($this->getPrimaryKey() as $pk) {
                if ($stringArray) {
                    if (!in_array($pk->getName(), $keys, true)) {
                        $allPk = false;

                        break;
                    }
                } else {
                    if (!in_array($pk, $keys)) {
                        $allPk = false;

                        break;
                    }
                }
            }

            if ($allPk) {
                return true;
            }
        }

        // check if there is a unique constraints that contains exactly the $keys
        if ($this->unices) {
            foreach ($this->unices as $unique) {
                if (count($unique->getColumns()) === count($keys)) {
                    $allAvailable = true;
                    foreach ($keys as $key) {
                        if (!$unique->hasColumn($key instanceof Column ? $key->getName() : $key)) {
                            $allAvailable = false;

                            break;
                        }
                    }
                    if ($allAvailable) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Checks if a index exists with the given $keys.
     *
     * @param array $keys
     *
     * @return bool
     */
    public function isIndex(array $keys): bool
    {
        if ($this->indices) {
            foreach ($this->indices as $index) {
                if (count($keys) === count($index->getColumns())) {
                    $allAvailable = true;
                    foreach ($keys as $key) {
                        if (!$index->hasColumn($key instanceof Column ? $key->getName() : $key)) {
                            $allAvailable = false;

                            break;
                        }
                    }
                    if ($allAvailable) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Returns whether the table has a column.
     *
     * @param \Propel\Generator\Model\Column|string $column The Column object or its name
     * @param bool $caseInsensitive Whether the check is case insensitive.
     *
     * @return bool
     */
    public function hasColumn($column, bool $caseInsensitive = false): bool
    {
        if ($column instanceof Column) {
            $column = $column->getName();
        }

        if ($caseInsensitive) {
            return isset($this->columnsByLowercaseName[strtolower($column)]);
        }

        return isset($this->columnsByName[$column]);
    }

    /**
     * Returns the Column object with the specified name.
     *
     * @param string|null $name The name of the column (e.g. 'my_column')
     * @param bool $caseInsensitive Whether the check is case insensitive.
     *
     * @return \Propel\Generator\Model\Column|null
     */
    public function getColumn(?string $name, bool $caseInsensitive = false): ?Column
    {
        if ($name === null || !$this->hasColumn($name, $caseInsensitive)) {
            return null;
        }

        if ($caseInsensitive) {
            return $this->columnsByLowercaseName[strtolower($name)];
        }

        return $this->columnsByName[$name];
    }

    /**
     * Returns a specified column by its php name.
     *
     * @param string $phpName
     *
     * @return \Propel\Generator\Model\Column|null
     */
    public function getColumnByPhpName(string $phpName): ?Column
    {
        if (isset($this->columnsByPhpName[$phpName])) {
            return $this->columnsByPhpName[$phpName];
        }

        return null;
    }

    /**
     * Returns all foreign keys from this table that reference the table passed
     * in argument.
     *
     * @param string $tableName
     *
     * @return array<\Propel\Generator\Model\ForeignKey>
     */
    public function getForeignKeysReferencingTable(string $tableName): array
    {
        $filter = fn (ForeignKey $fk) => $fk->getForeignTableName() === $tableName;

        return array_values(array_filter($this->foreignKeys, $filter));
    }

    /**
     * Returns the foreign keys that include $column in it's list of local
     * columns.
     *
     * Eg. Foreign key (a, b, c) references tbl(x, y, z) will be returned of $column
     * is either a, b or c.
     *
     * @param string $column Name of the column
     *
     * @return array<\Propel\Generator\Model\ForeignKey>
     */
    public function getColumnForeignKeys(string $column): array
    {
        $filter = fn (ForeignKey $fk) => in_array($column, $fk->getLocalColumns(), true);

        return array_values(array_filter($this->foreignKeys, $filter));
    }

    /**
     * Set the database that contains this table.
     *
     * @param \Propel\Generator\Model\Database $database
     *
     * @return void
     */
    public function setDatabase(Database $database): void
    {
        $this->database = $database;
    }

    /**
     * Get the database that contains this table.
     *
     * @return \Propel\Generator\Model\Database|null
     */
    public function getDatabase(): ?Database
    {
        return $this->database;
    }

    /**
     * Returns a VendorInfo object by its vendor type id (i.e. "mysql").
     *
     * Vendor information is set in schema.xml for the table or the whole
     * database. The method returns database-wide vendor information extended
     * and possibly overridden by table vendor information.
     *
     * @see \Propel\Generator\Model\MappingModel::getVendorInfoForType()
     *
     * @param string $type Vendor id, i.e. "mysql"
     *
     * @return \Propel\Generator\Model\VendorInfo
     */
    public function getVendorInfoForType(string $type): VendorInfo
    {
        $tableVendorInfo = parent::getVendorInfoForType($type);
        $db = $this->getDatabase();
        if (!$db) {
            return $tableVendorInfo;
        }
        $databaseVendorInfo = $db->getVendorInfoForType($type);

        return $databaseVendorInfo->getMergedVendorInfo($tableVendorInfo);
    }

    /**
     * Get the database that contains this table.
     *
     * @throws \Propel\Generator\Exception\LogicException
     *
     * @return \Propel\Generator\Model\Database
     */
    public function getDatabaseOrFail(): Database
    {
        $database = $this->getDatabase();

        if ($database === null) {
            throw new LogicException('Database is not defined.');
        }

        return $database;
    }

    /**
     * Returns the Database platform.
     *
     * @return \Propel\Generator\Platform\PlatformInterface|null
     */
    public function getPlatform(): ?PlatformInterface
    {
        return $this->database ? $this->database->getPlatform() : null;
    }

    /**
     * Quotes a identifier depending on identifierQuotingEnabled.
     *
     * Needs a platform assigned to its database.
     *
     * @param string $text
     *
     * @throws \Propel\Runtime\Exception\RuntimeException
     *
     * @return string
     */
    public function quoteIdentifier(string $text): string
    {
        if (!$this->getPlatform()) {
            throw new RuntimeException('No platform specified. Cannot quote without knowing which platform this table\'s database is using.');
        }

        if ($this->isIdentifierQuotingEnabled()) {
            return $this->getPlatform()->doQuoting($text);
        }

        return $text;
    }

    /**
     * Returns whether code and SQL must be created for this table.
     *
     * Table will be skipped, if return true.
     *
     * @return bool|null
     */
    public function isForReferenceOnly(): ?bool
    {
        return $this->forReferenceOnly;
    }

    /**
     * Returns whether to determine if code/sql gets created for this table.
     * Table will be skipped, if set to true.
     *
     * @param bool $flag
     *
     * @return void
     */
    public function setForReferenceOnly(bool $flag): void
    {
        $this->forReferenceOnly = $flag;
    }

    /**
     * Returns the collection of Columns which make up the single primary
     * key for this table.
     *
     * @return array<\Propel\Generator\Model\Column>
     */
    public function getPrimaryKey(): array
    {
        $pk = [];
        foreach ($this->columns as $col) {
            if ($col->isPrimaryKey()) {
                $pk[] = $col;
            }
        }

        return $pk;
    }

    /**
     * Returns whether this table has a primary key.
     *
     * @return bool
     */
    public function hasPrimaryKey(): bool
    {
        return count($this->getPrimaryKey()) > 0;
    }

    /**
     * Returns whether this table has a composite primary key.
     *
     * @return bool
     */
    public function hasCompositePrimaryKey(): bool
    {
        return count($this->getPrimaryKey()) > 1;
    }

    /**
     * Returns the first primary key column.
     *
     * Useful for tables with a PK using a single column.
     *
     * @return \Propel\Generator\Model\Column|null
     */
    public function getFirstPrimaryKeyColumn(): ?Column
    {
        foreach ($this->columns as $col) {
            if ($col->isPrimaryKey()) {
                return $col;
            }
        }

        return null;
    }

    /**
     * @return void
     */
    public function __clone()
    {
        $columns = [];
        foreach ($this->columns as $oldCol) {
            $col = clone $oldCol;
            $columns[] = $col;
            $this->columnsByName[(string)$col->getName()] = $col;
            $this->columnsByLowercaseName[strtolower((string)$col->getName())] = $col;
            $this->columnsByPhpName[(string)$col->getPhpName()] = $col;
        }
        $this->columns = $columns;
    }

    /**
     * Returns whether this table has any auto-increment primary keys.
     *
     * @return bool
     */
    public function hasAutoIncrementPrimaryKey(): bool
    {
        return $this->getAutoIncrementPrimaryKey() !== null;
    }

    /**
     * Returns the auto incremented primary key.
     *
     * @return \Propel\Generator\Model\Column|null
     */
    public function getAutoIncrementPrimaryKey(): ?Column
    {
        if ($this->getIdMethod() !== IdMethod::NO_ID_METHOD) {
            foreach ($this->getPrimaryKey() as $pk) {
                if ($pk->isAutoIncrement()) {
                    return $pk;
                }
            }
        }

        return null;
    }

    /**
     * Returns the auto incremented primary key.
     *
     * @throws \Propel\Generator\Exception\LogicException
     *
     * @return \Propel\Generator\Model\Column
     */
    public function getAutoIncrementPrimaryKeyOrFail(): Column
    {
        $column = $this->getAutoIncrementPrimaryKey();

        if ($column === null) {
            throw new LogicException('Autoincrement primary key is not defined.');
        }

        return $column;
    }

    /**
     * Returns whether there is a cross reference status for this foreign
     * key.
     *
     * @return bool
     */
    public function getIsCrossRef(): bool
    {
        return $this->isCrossRef;
    }

    /**
     * Alias for Table::getIsCrossRef.
     *
     * @return bool
     */
    public function isCrossRef(): bool
    {
        return $this->isCrossRef;
    }

    /**
     * Sets a cross reference status for this foreign key.
     *
     * @param bool $flag
     *
     * @return void
     */
    public function setIsCrossRef(bool $flag): void
    {
        $this->setCrossRef($flag);
    }

    /**
     * Sets a cross reference status for this foreign key.
     *
     * @param bool $flag
     *
     * @return void
     */
    public function setCrossRef(bool $flag): void
    {
        $this->isCrossRef = $flag;
    }

    /**
     * Returns whether the table has foreign keys.
     *
     * @return bool
     */
    public function hasForeignKeys(): bool
    {
        return count($this->foreignKeys) !== 0;
    }

    /**
     * Returns whether the table has cross foreign keys or not.
     *
     * @return bool
     */
    public function hasCrossForeignKeys(): bool
    {
        return count($this->getCrossFks()) !== 0;
    }

    /**
     * Returns the PHP naming method.
     *
     * @return string|null
     */
    public function getPhpNamingMethod(): ?string
    {
        return $this->phpNamingMethod;
    }

    /**
     * Sets the PHP naming method.
     *
     * @param string $phpNamingMethod
     *
     * @return void
     */
    public function setPhpNamingMethod(string $phpNamingMethod): void
    {
        $this->phpNamingMethod = $phpNamingMethod;
    }

    /**
     * Sets the default accessor visibility.
     *
     * @param string $defaultAccessorVisibility
     *
     * @return void
     */
    public function setDefaultAccessorVisibility(string $defaultAccessorVisibility): void
    {
        $this->defaultAccessorVisibility = $defaultAccessorVisibility;
    }

    /**
     * Returns the default accessor visibility.
     *
     * @return string
     */
    public function getDefaultAccessorVisibility(): string
    {
        return $this->defaultAccessorVisibility;
    }

    /**
     * Sets the default mutator visibility.
     *
     * @param string $defaultMutatorVisibility
     *
     * @return void
     */
    public function setDefaultMutatorVisibility(string $defaultMutatorVisibility): void
    {
        $this->defaultMutatorVisibility = $defaultMutatorVisibility;
    }

    /**
     * Returns the default mutator visibility.
     *
     * @return string
     */
    public function getDefaultMutatorVisibility(): string
    {
        return $this->defaultMutatorVisibility;
    }

    /**
     * Checks if identifierQuoting is enabled. Looks up to its database->isIdentifierQuotingEnabled
     * if identifierQuoting is null hence undefined.
     *
     * @return bool
     */
    public function isIdentifierQuotingEnabled(): bool
    {
        return $this->identifierQuoting
            ?? $this->getDatabase() && $this->getDatabase()->isIdentifierQuotingEnabled();
    }

    /**
     * @param bool|null $identifierQuoting Setting to null will use the database default
     *
     * @return void
     */
    public function setIdentifierQuoting(?bool $identifierQuoting): void
    {
        $this->identifierQuoting = $identifierQuoting;
    }

    /**
     * Check if this table contains columns of the given type.
     *
     * @param string $type The type to check for, i.e. PropelTypes::BOOLEAN
     *
     * @return bool
     */
    public function containsColumnsOfType(string $type): bool
    {
        foreach ($this->columns as $column) {
            if ($column->getType() === $type) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get additional class imports for model and query classes needed by the columns.
     *
     * @psalm-return array<class-string>
     *
     * @see \Propel\Generator\Builder\Om\ObjectBuilder::addClassBody()
     * @see \Propel\Generator\Builder\Om\QueryBuilder::addClassBody()
     *
     * @return array<string>|null
     */
    public function getAdditionalModelClassImports(): ?array
    {
        if ($this->containsColumnsOfType(PropelTypes::UUID_BINARY)) {
            return [
                UuidConverter::class,
            ];
        }

        return null;
    }

    /**
     * Check if there is a FK rellation between the current table and the given
     * table in either direction.
     *
     * @param \Propel\Generator\Model\Table $table
     *
     * @return bool
     */
    public function isConnectedWithTable(Table $table): bool
    {
        return $this->getForeignKeysReferencingTable($table->getName()) ||
            $table->getForeignKeysReferencingTable($this->getName());
    }
}

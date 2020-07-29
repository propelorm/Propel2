<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Model;

use Propel\Generator\Exception\EngineException;
use Propel\Generator\Exception\InvalidArgumentException;
use Propel\Generator\Platform\PlatformInterface;

/**
 * A class for holding application data structures.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Leon Messerschmidt <leon@opticode.co.za> (Torque)
 * @author John McNally<jmcnally@collab.net> (Torque)
 * @author Martin Poeschl<mpoeschl@marmot.at> (Torque)
 * @author Daniel Rall<dlr@collab.net> (Torque)
 * @author Byron Foster <byron_foster@yahoo.com> (Torque)
 * @author Hugo Hamon <webmaster@apprendre-php.com> (Propel)
 */
class Database extends ScopedMappingModel
{
    use BehaviorableTrait;

    /**
     * The database's platform.
     *
     * @var \Propel\Generator\Platform\PlatformInterface|null
     */
    private $platform;

    /**
     * @var \Propel\Generator\Model\Table[]
     */
    private $tables = [];

    /**
     * @var string|null
     */
    private $name;

    /**
     * @var string|null
     */
    private $baseClass;

    /**
     * @var string|null
     */
    private $baseQueryClass;

    /**
     * @var string
     */
    private $defaultIdMethod;

    /**
     * @var string
     */
    private $defaultPhpNamingMethod;

    /**
     * The default accessor visibility.
     *
     * It may be one of public, private and protected.
     *
     * @var string
     */
    private $defaultAccessorVisibility;

    /**
     * The default mutator visibility.
     *
     * It may be one of public, private and protected.
     *
     * @var string
     */
    private $defaultMutatorVisibility;

    /**
     * @var array
     */
    private $domainMap = [];

    /**
     * @var bool
     */
    private $heavyIndexing = false;

    /**
     * @var bool
     */
    private $identifierQuoting = false;

    /**
     * @var \Propel\Generator\Model\Schema
     */
    private $parentSchema;

    /**
     * @var \Propel\Generator\Model\Table[]
     */
    private $tablesByName = [];

    /**
     * @var \Propel\Generator\Model\Table[]
     */
    private $tablesByLowercaseName = [];

    /**
     * @var \Propel\Generator\Model\Table[]
     */
    private $tablesByPhpName = [];

    /**
     * @var string[]
     */
    private $sequences = [];

    /**
     * @var string
     */
    protected $defaultStringFormat;

    /**
     * @var string|null
     */
    protected $tablePrefix;

    /**
     * Constructs a new Database object.
     *
     * @param string|null $name The database's name
     * @param \Propel\Generator\Platform\PlatformInterface|null $platform The database's platform
     */
    public function __construct($name = null, ?PlatformInterface $platform = null)
    {
        parent::__construct();

        if ($name !== null) {
            $this->setName($name);
        }

        if ($platform !== null) {
            $this->setPlatform($platform);
        }

        $this->defaultPhpNamingMethod = NameGeneratorInterface::CONV_METHOD_UNDERSCORE;
        $this->defaultIdMethod = IdMethod::NATIVE;
        $this->defaultStringFormat = static::DEFAULT_STRING_FORMAT;
        $this->defaultAccessorVisibility = static::VISIBILITY_PUBLIC;
        $this->defaultMutatorVisibility = static::VISIBILITY_PUBLIC;
    }

    /**
     * @return void
     */
    protected function setupObject()
    {
        parent::setupObject();

        $this->name = $this->getAttribute('name');
        $this->baseClass = $this->getAttribute('baseClass');
        $this->baseQueryClass = $this->getAttribute('baseQueryClass');
        $this->defaultIdMethod = $this->getAttribute('defaultIdMethod', IdMethod::NATIVE);
        $this->defaultPhpNamingMethod = $this->getAttribute('defaultPhpNamingMethod', NameGeneratorInterface::CONV_METHOD_UNDERSCORE);
        $this->heavyIndexing = $this->booleanValue($this->getAttribute('heavyIndexing'));
        $this->identifierQuoting = $this->getAttribute('identifierQuoting') ? $this->booleanValue($this->getAttribute('identifierQuoting')) : false;
        $this->tablePrefix = $this->getAttribute('tablePrefix', $this->getBuildProperty('generator.tablePrefix'));
        $this->defaultStringFormat = $this->getAttribute('defaultStringFormat', static::DEFAULT_STRING_FORMAT);
    }

    /**
     * Returns the PlatformInterface implementation for this database.
     *
     * @return \Propel\Generator\Platform\PlatformInterface|null
     */
    public function getPlatform()
    {
        return $this->platform;
    }

    /**
     * Sets the PlatformInterface implementation for this database.
     *
     * @param \Propel\Generator\Platform\PlatformInterface|null $platform A Platform implementation
     *
     * @return void
     */
    public function setPlatform(?PlatformInterface $platform = null)
    {
        $this->platform = $platform;
    }

    /**
     * Returns the max column name's length.
     *
     * @return int
     */
    public function getMaxColumnNameLength()
    {
        return $this->platform->getMaxColumnNameLength();
    }

    /**
     * Returns the database name.
     *
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the database name.
     *
     * @param string $name
     *
     * @return void
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Returns the name of the base super class inherited by active record
     * objects. This parameter is overridden at the table level.
     *
     * @return string
     */
    public function getBaseClass()
    {
        return $this->baseClass;
    }

    /**
     * Returns the name of the base super class inherited by query
     * objects. This parameter is overridden at the table level.
     *
     * @return string
     */
    public function getBaseQueryClass()
    {
        return $this->baseQueryClass;
    }

    /**
     * Sets the name of the base super class inherited by active record objects.
     * This parameter is overridden at the table level.
     *
     * @param string $class
     *
     * @return void
     */
    public function setBaseClass($class)
    {
        $this->baseClass = $class;
    }

    /**
     * Sets the name of the base super class inherited by query objects.
     * This parameter is overridden at the table level.
     *
     * @param string $class
     *
     * @return void
     */
    public function setBaseQueryClass($class)
    {
        $this->baseQueryClass = $class;
    }

    /**
     * Returns the name of the default ID method strategy.
     * This parameter can be overridden at the table level.
     *
     * @return string
     */
    public function getDefaultIdMethod()
    {
        return $this->defaultIdMethod;
    }

    /**
     * Sets the name of the default ID method strategy.
     * This parameter can be overridden at the table level.
     *
     * @param string $strategy
     *
     * @return void
     */
    public function setDefaultIdMethod($strategy)
    {
        $this->defaultIdMethod = $strategy;
    }

    /**
     * Returns the name of the default PHP naming method strategy, which
     * specifies the method for converting schema names for table and column to
     * PHP names. This parameter can be overridden at the table layer.
     *
     * @return string
     */
    public function getDefaultPhpNamingMethod()
    {
        return $this->defaultPhpNamingMethod;
    }

    /**
     * Sets name of the default PHP naming method strategy.
     *
     * @param string $strategy
     *
     * @return void
     */
    public function setDefaultPhpNamingMethod($strategy)
    {
        $this->defaultPhpNamingMethod = $strategy;
    }

    /**
     * Returns the list of supported string formats
     *
     * @return string[]
     */
    public static function getSupportedStringFormats()
    {
        return [ 'XML', 'YAML', 'JSON', 'CSV' ];
    }

    /**
     * Sets the default string format for ActiveRecord objects in this table.
     * This parameter can be overridden at the table level.
     *
     * Any of 'XML', 'YAML', 'JSON', or 'CSV'.
     *
     * @param string $format
     *
     * @throws \Propel\Generator\Exception\InvalidArgumentException
     *
     * @return void
     */
    public function setDefaultStringFormat($format)
    {
        $formats = static::getSupportedStringFormats();

        $format = strtoupper($format);
        if (!in_array($format, $formats)) {
            throw new InvalidArgumentException(sprintf('Given "%s" default string format is not supported. Only "%s" are valid string formats.', $format, implode(', ', $formats)));
        }

        $this->defaultStringFormat = $format;
    }

    /**
     * Returns the default string format for ActiveRecord objects in this table.
     * This parameter can be overridden at the table level.
     *
     * @return string
     */
    public function getDefaultStringFormat()
    {
        return $this->defaultStringFormat;
    }

    /**
     * Returns whether or not heavy indexing is enabled.
     *
     * This is an alias for getHeavyIndexing().
     *
     * @return bool
     */
    public function isHeavyIndexing()
    {
        return $this->getHeavyIndexing();
    }

    /**
     * Returns whether or not heavy indexing is enabled.
     *
     * This is an alias for isHeavyIndexing().
     *
     * @return bool
     */
    public function getHeavyIndexing()
    {
        return $this->heavyIndexing;
    }

    /**
     * Sets whether or not heavy indexing is enabled.
     *
     * @param bool $flag
     *
     * @return void
     */
    public function setHeavyIndexing($flag = true)
    {
        $this->heavyIndexing = (bool)$flag;
    }

    /**
     * Return the list of all tables.
     *
     * @return \Propel\Generator\Model\Table[]
     */
    public function getTables()
    {
        return $this->tables;
    }

    /**
     * Return the number of tables in the database.
     *
     * Read-only tables are excluded from the count.
     *
     * @return int
     */
    public function countTables()
    {
        $count = 0;
        foreach ($this->tables as $table) {
            if (!$table->isReadOnly()) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Returns the list of all tables that have a SQL representation.
     *
     * @return \Propel\Generator\Model\Table[]
     */
    public function getTablesForSql()
    {
        $tables = [];
        foreach ($this->tables as $table) {
            if (!$table->isSkipSql()) {
                $tables[] = $table;
            }
        }

        return $tables;
    }

    /**
     * Returns whether or not the database has a table.
     *
     * @param string $name
     * @param bool $caseInsensitive
     *
     * @return bool
     */
    public function hasTable($name, $caseInsensitive = false)
    {
        if ($caseInsensitive) {
            return isset($this->tablesByLowercaseName[strtolower($name)]);
        }

        return isset($this->tablesByName[$name]);
    }

    /**
     * Returns the table with the specified name.
     *
     * @param string $name
     * @param bool $caseInsensitive
     *
     * @return \Propel\Generator\Model\Table|null
     */
    public function getTable($name, $caseInsensitive = false)
    {
        if (
            $this->getSchema() && $this->getPlatform()->supportsSchemas()
            && strpos($name, $this->getPlatform()->getSchemaDelimiter()) === false
        ) {
            $name = $this->getSchema() . $this->getPlatform()->getSchemaDelimiter() . $name;
        }

        if (!$this->hasTable($name, $caseInsensitive)) {
            return null;
        }

        if ($caseInsensitive) {
            return $this->tablesByLowercaseName[strtolower($name)];
        }

        return $this->tablesByName[$name];
    }

    /**
     * Returns whether or not the database has a table identified by its
     * PHP name.
     *
     * @param string $phpName
     *
     * @return bool
     */
    public function hasTableByPhpName($phpName)
    {
        return isset($this->tablesByPhpName[$phpName]);
    }

    /**
     * Returns the table object with the specified PHP name.
     *
     * @param string $phpName
     *
     * @return \Propel\Generator\Model\Table|null
     */
    public function getTableByPhpName($phpName)
    {
        if (isset($this->tablesByPhpName[$phpName])) {
            return $this->tablesByPhpName[$phpName];
        }

        return null;
    }

    /**
     * Adds several tables at once.
     *
     * @param \Propel\Generator\Model\Table[] $tables An array of Table instances
     *
     * @return void
     */
    public function addTables(array $tables)
    {
        foreach ($tables as $table) {
            $this->addTable($table);
        }
    }

    /**
     * @param \Propel\Generator\Model\Table $table
     *
     * @return void
     */
    public function removeTable(Table $table)
    {
        if ($this->hasTable($table->getName(), true)) {
            foreach ($this->tables as $id => $tableExam) {
                if ($table->getName() === $tableExam->getName()) {
                    unset($this->tables[$id]);
                }
            }

            unset($this->tablesByName[$table->getName()]);
            unset($this->tablesByLowercaseName[strtolower($table->getName())]);
            unset($this->tablesByPhpName[$table->getPhpName()]);
        }
    }

    /**
     * Adds a new table to this database.
     *
     * @param \Propel\Generator\Model\Table|array $table
     *
     * @throws \Propel\Generator\Exception\EngineException
     *
     * @return \Propel\Generator\Model\Table
     */
    public function addTable($table)
    {
        if (!$table instanceof Table) {
            $tbl = new Table();
            $tbl->setDatabase($this);
            $tbl->loadMapping($table);

            return $this->addTable($tbl);
        }

        $table->setDatabase($this);

        if (isset($this->tablesByName[$table->getName()])) {
            throw new EngineException(sprintf('Table "%s" declared twice', $table->getName()));
        }

        $this->tables[] = $table;
        $this->tablesByName[$table->getName()] = $table;
        $this->tablesByLowercaseName[strtolower($table->getName())] = $table;
        $this->tablesByPhpName[$table->getPhpName()] = $table;

        $this->computeTableNamespace($table);

        if ($table->getPackage() === null) {
            $table->setPackage($this->getPackage());
        }

        return $table;
    }

    /**
     * @param string[] $sequences
     *
     * @return void
     */
    public function setSequences($sequences)
    {
        $this->sequences = $sequences;
    }

    /**
     * @return string[]
     */
    public function getSequences()
    {
        return $this->sequences;
    }

    /**
     * @param string $sequence
     *
     * @return void
     */
    public function addSequence($sequence)
    {
        $this->sequences[] = $sequence;
    }

    /**
     * @param string $sequence
     *
     * @return void
     */
    public function removeSequence($sequence)
    {
        if ($this->sequences) {
            if (($idx = array_search($sequence, $this->sequences)) !== false) {
                unset($this->sequences[$idx]);
            }
        }
    }

    /**
     * @param string $sequence
     *
     * @return bool
     */
    public function hasSequence($sequence)
    {
        return $this->sequences && in_array($sequence, $this->sequences);
    }

    /**
     * Returns the schema delimiter character.
     *
     * For example, the dot character with mysql when
     * naming tables. For instance: schema.the_table.
     *
     * @return string
     */
    public function getSchemaDelimiter()
    {
        return $this->platform->getSchemaDelimiter();
    }

    /**
     * Sets the database's schema.
     *
     * @param string $schema
     *
     * @return void
     */
    public function setSchema($schema)
    {
        $oldSchema = $this->schema;
        if ($this->schema !== $schema && $this->getPlatform()) {
            $schemaDelimiter = $this->getPlatform()->getSchemaDelimiter();
            $fixHash = function (&$array) use ($schema, $oldSchema, $schemaDelimiter) {
                foreach ($array as $k => $v) {
                    if ($schema && $this->getPlatform()->supportsSchemas()) {
                        if (strpos($k, $schemaDelimiter) === false) {
                            $array[$schema . $schemaDelimiter . $k] = $v;
                            unset($array[$k]);
                        }
                    } elseif ($oldSchema) {
                        if (strpos($k, $schemaDelimiter) !== false) {
                            $array[explode($schemaDelimiter, $k)[1]] = $v;
                            unset($array[$k]);
                        }
                    }
                }
            };

            $fixHash($this->tablesByName);
            $fixHash($this->tablesByLowercaseName);
        }
        parent::setSchema($schema);
    }

    /**
     * Computes the table namespace based on the current relative or
     * absolute table namespace and the database namespace.
     *
     * @param \Propel\Generator\Model\Table $table
     *
     * @return string
     */
    private function computeTableNamespace(Table $table)
    {
        $namespace = $table->getNamespace();
        if ($this->isAbsoluteNamespace($namespace)) {
            $namespace = ltrim($namespace, '\\');
            $table->setNamespace($namespace);

            return $namespace;
        }

        if ($namespace = $this->getNamespace()) {
            if ($table->getNamespace()) {
                $namespace .= '\\' . $table->getNamespace();
            }

            $table->setNamespace($namespace);
        }

        return $namespace;
    }

    /**
     * Sets the parent schema
     *
     * @param \Propel\Generator\Model\Schema $parent The parent schema
     *
     * @return void
     */
    public function setParentSchema(Schema $parent)
    {
        $this->parentSchema = $parent;
    }

    /**
     * Returns the parent schema
     *
     * @return \Propel\Generator\Model\Schema
     */
    public function getParentSchema()
    {
        return $this->parentSchema;
    }

    /**
     * Adds a domain object to this database.
     *
     * @param \Propel\Generator\Model\Domain|array $data
     *
     * @return \Propel\Generator\Model\Domain
     */
    public function addDomain($data)
    {
        if ($data instanceof Domain) {
            $domain = $data; // alias
            $domain->setDatabase($this);
            $this->domainMap[$domain->getName()] = $domain;

            return $domain;
        }

        $domain = new Domain();
        $domain->setDatabase($this);
        $domain->loadMapping($data);

        return $this->addDomain($domain); // call self w/ different param
    }

    /**
     * Returns the already configured domain object by its name.
     *
     * @param string $name
     *
     * @return \Propel\Generator\Model\Domain|null
     */
    public function getDomain($name)
    {
        if (isset($this->domainMap[$name])) {
            return $this->domainMap[$name];
        }

        return null;
    }

    /**
     * Returns the GeneratorConfigInterface object.
     *
     * @return \Propel\Generator\Config\GeneratorConfigInterface|null
     */
    public function getGeneratorConfig()
    {
        if ($this->parentSchema) {
            return $this->parentSchema->getGeneratorConfig();
        }

        return null;
    }

    /**
     * Returns the configuration property identified by its name.
     *
     * @see \Propel\Common\Config\ConfigurationManager::getConfigProperty() method
     *
     * @param string $name
     *
     * @return string
     */
    public function getBuildProperty($name)
    {
        $config = $this->getGeneratorConfig();
        if ($config) {
            return $config->getConfigProperty($name);
        }

        return '';
    }

    /**
     * Returns the table prefix for this database.
     *
     * @return string
     */
    public function getTablePrefix()
    {
        return $this->tablePrefix;
    }

    /**
     * Sets the tables' prefix.
     *
     * @param string $tablePrefix
     *
     * @return void
     */
    public function setTablePrefix($tablePrefix)
    {
        $this->tablePrefix = $tablePrefix;
    }

    /**
     * Returns the next behavior on all tables, ordered by behavior priority,
     * and skipping the ones that were already executed.
     *
     * @return \Propel\Generator\Model\Behavior
     */
    public function getNextTableBehavior()
    {
        // order the behaviors according to Behavior::$tableModificationOrder
        $behaviors = [];
        $nextBehavior = null;
        foreach ($this->tables as $table) {
            foreach ($table->getBehaviors() as $behavior) {
                if (!$behavior->isTableModified()) {
                    $behaviors[$behavior->getTableModificationOrder()][] = $behavior;
                }
            }
        }
        ksort($behaviors);
        if (count($behaviors)) {
            $nextBehavior = $behaviors[key($behaviors)][0];
        }

        return $nextBehavior;
    }

    /**
     * Finalizes the setup process.
     *
     * @return void
     */
    public function doFinalInitialization()
    {
        // add the referrers for the foreign keys
        $this->setupTableReferrers();

        // execute database behaviors
        foreach ($this->getBehaviors() as $behavior) {
            $behavior->modifyDatabase();
        }

        // execute table behaviors (may add new tables and new behaviors)
        while ($behavior = $this->getNextTableBehavior()) {
            $behavior->getTableModifier()->modifyTable();
            $behavior->setTableModified(true);
        }

        // do naming and heavy indexing
        foreach ($this->tables as $table) {
            $table->doFinalInitialization();
            // setup referrers again, since final initialization may have added columns
            $table->setupReferrers(true);
        }
    }

    /**
     * @param \Propel\Generator\Model\Behavior $behavior
     *
     * @return void
     */
    protected function registerBehavior(Behavior $behavior)
    {
        $behavior->setDatabase($this);
    }

    /**
     * Setups all table referrers.
     *
     * @return void
     */
    protected function setupTableReferrers(): void
    {
        foreach ($this->tables as $table) {
            $table->setupReferrers();
        }
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $tables = [];
        foreach ($this->getTables() as $table) {
            $columns = [];
            foreach ($table->getColumns() as $column) {
                $columns[] = sprintf(
                    '      %s %s %s %s %s %s %s',
                    $column->getName(),
                    $column->getType(),
                    $column->getSize() ? '(' . $column->getSize() . ')' : '',
                    $column->isPrimaryKey() ? 'PK' : '',
                    $column->isNotNull() ? 'NOT NULL' : '',
                    $column->getDefaultValueString() ? "'" . $column->getDefaultValueString() . "'" : '',
                    $column->isAutoIncrement() ? 'AUTO_INCREMENT' : ''
                );
            }

            $fks = [];
            foreach ($table->getForeignKeys() as $fk) {
                $fks[] = sprintf(
                    '      %s to %s.%s (%s => %s)',
                    $fk->getName(),
                    $fk->getForeignSchemaName(),
                    $fk->getForeignTableCommonName(),
                    implode(', ', $fk->getLocalColumns()),
                    implode(', ', $fk->getForeignColumns())
                );
            }

            $indices = [];
            foreach ($table->getIndices() as $index) {
                $indexColumns = [];
                foreach ($index->getColumns() as $indexColumnName) {
                    $indexColumns[] = sprintf('%s (%s)', $indexColumnName, $index->getColumnSize($indexColumnName));
                }
                $indices[] = sprintf(
                    '      %s (%s)',
                    $index->getName(),
                    implode(', ', $indexColumns)
                );
            }

            $unices = [];
            foreach ($table->getUnices() as $index) {
                $unices[] = sprintf(
                    '      %s (%s)',
                    $index->getName(),
                    implode(', ', $index->getColumns())
                );
            }

            $tableDef = sprintf(
                "  %s (%s):\n%s",
                $table->getName(),
                $table->getCommonName(),
                implode("\n", $columns)
            );

            if ($fks) {
                $tableDef .= "\n    FKs:\n" . implode("\n", $fks);
            }

            if ($indices) {
                $tableDef .= "\n    indices:\n" . implode("\n", $indices);
            }

            if ($unices) {
                $tableDef .= "\n    unices:\n" . implode("\n", $unices);
            }

            $tables[] = $tableDef;
        }

        return sprintf(
            "%s:\n%s",
            $this->getName() . ($this->getSchema() ? '.' . $this->getSchema() : ''),
            implode("\n", $tables)
        );
    }

    /**
     * Sets the default accessor visibility.
     *
     * @param string $defaultAccessorVisibility
     *
     * @return void
     */
    public function setDefaultAccessorVisibility($defaultAccessorVisibility)
    {
        $this->defaultAccessorVisibility = $defaultAccessorVisibility;
    }

    /**
     * Returns the default accessor visibility.
     *
     * @return string
     */
    public function getDefaultAccessorVisibility()
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
    public function setDefaultMutatorVisibility($defaultMutatorVisibility)
    {
        $this->defaultMutatorVisibility = $defaultMutatorVisibility;
    }

    /**
     * Returns the default mutator visibility.
     *
     * @return string
     */
    public function getDefaultMutatorVisibility()
    {
        return $this->defaultMutatorVisibility;
    }

    /**
     * @return void
     */
    public function __clone()
    {
        $tables = [];
        foreach ($this->tables as $oldTable) {
            $table = clone $oldTable;
            $tables[] = $table;
            $this->tablesByName[$table->getName()] = $table;
            $this->tablesByLowercaseName[strtolower($table->getName())] = $table;
            $this->tablesByPhpName[$table->getPhpName()] = $table;
        }
        $this->tables = $tables;
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
}

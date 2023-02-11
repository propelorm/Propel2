<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Builder;

use Propel\Common\Pluralizer\PluralizerInterface;
use Propel\Generator\Builder\Om\AbstractObjectBuilder;
use Propel\Generator\Builder\Om\AbstractOMBuilder;
use Propel\Generator\Builder\Om\MultiExtendObjectBuilder;
use Propel\Generator\Builder\Om\ObjectBuilder;
use Propel\Generator\Builder\Om\QueryBuilder;
use Propel\Generator\Builder\Om\TableMapBuilder;
use Propel\Generator\Config\GeneratorConfigInterface;
use Propel\Generator\Exception\LogicException;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\Inheritance;
use Propel\Generator\Model\Table;
use Propel\Generator\Platform\PlatformInterface;

/**
 * This is the base class for any builder class that is using the data model.
 *
 * This could be extended by classes that build SQL DDL, PHP classes, configuration
 * files, input forms, etc.
 *
 * The GeneratorConfig needs to be set on this class in order for the builders
 * to be able to access the propel generator build properties. You should be
 * safe if you always use the GeneratorConfig to get a configured builder class
 * anyway.
 *
 * @author Hans Lellelid <hans@xmpl.org>
 */
abstract class DataModelBuilder
{
    /**
     * The current table.
     *
     * @var \Propel\Generator\Model\Table
     */
    private Table $table;

    /**
     * The generator config object holding build properties, etc.
     *
     * @var \Propel\Generator\Config\GeneratorConfigInterface|null
     */
    private ?GeneratorConfigInterface $generatorConfig = null;

    /**
     * An array of warning messages that can be retrieved for display.
     *
     * @var list<string>
     */
    private array $warnings = [];

    /**
     * Object builder class for current table.
     *
     * @var \Propel\Generator\Builder\Om\ObjectBuilder|null
     */
    private ?ObjectBuilder $objectBuilder = null;

    /**
     * Stub Object builder class for current table.
     *
     * @var \Propel\Generator\Builder\Om\AbstractObjectBuilder|null
     */
    private ?AbstractObjectBuilder $stubObjectBuilder = null;

    /**
     * Query builder class for current table.
     *
     * @var \Propel\Generator\Builder\Om\AbstractOMBuilder|null
     */
    private ?AbstractOMBuilder $queryBuilder = null;

    /**
     * Stub Query builder class for current table.
     *
     * @var \Propel\Generator\Builder\Om\AbstractOMBuilder|null
     */
    private ?AbstractOMBuilder $stubQueryBuilder = null;

    /**
     * TableMap builder class for current table.
     *
     * @var \Propel\Generator\Builder\Om\TableMapBuilder|null
     */
    protected ?TableMapBuilder $tablemapBuilder = null;

    /**
     * Stub Interface builder class for current table.
     *
     * @var \Propel\Generator\Builder\Om\AbstractOMBuilder|null
     */
    private ?AbstractOMBuilder $interfaceBuilder = null;

    /**
     * Stub child object for current table.
     *
     * @var \Propel\Generator\Builder\Om\MultiExtendObjectBuilder|null
     */
    private ?MultiExtendObjectBuilder $multiExtendObjectBuilder = null;

    /**
     * The Pluralizer class to use.
     *
     * @var \Propel\Common\Pluralizer\PluralizerInterface|null
     */
    private ?PluralizerInterface $pluralizer = null;

    /**
     * The platform class
     *
     * @var \Propel\Generator\Platform\PlatformInterface|null
     */
    protected ?PlatformInterface $platform = null;

    /**
     * Creates new instance of DataModelBuilder subclass.
     *
     * @param \Propel\Generator\Model\Table $table The Table which we are using to build [OM, DDL, etc.].
     */
    public function __construct(Table $table)
    {
        $this->table = $table;
    }

    /**
     * Returns new or existing Pluralizer class.
     *
     * @return \Propel\Common\Pluralizer\PluralizerInterface
     */
    public function getPluralizer(): PluralizerInterface
    {
        if ($this->pluralizer === null) {
            $this->pluralizer = $this->getGeneratorConfig()->getConfiguredPluralizer();
        }

        return $this->pluralizer;
    }

    /**
     * Returns new or existing Object builder class for this table.
     *
     * @return \Propel\Generator\Builder\Om\ObjectBuilder
     */
    public function getObjectBuilder(): ObjectBuilder
    {
        if ($this->objectBuilder === null) {
            /** @var \Propel\Generator\Builder\Om\ObjectBuilder $builder */
            $builder = $this->getGeneratorConfig()->getConfiguredBuilder($this->getTable(), 'object');
            $this->objectBuilder = $builder;
        }

        return $this->objectBuilder;
    }

    /**
     * Returns new or existing stub Object builder class for this table.
     *
     * @return \Propel\Generator\Builder\Om\AbstractObjectBuilder
     */
    public function getStubObjectBuilder(): AbstractObjectBuilder
    {
        if ($this->stubObjectBuilder === null) {
            /** @var \Propel\Generator\Builder\Om\ObjectBuilder $builder */
            $builder = $this->getGeneratorConfig()->getConfiguredBuilder($this->getTable(), 'objectstub');
            $this->stubObjectBuilder = $builder;
        }

        return $this->stubObjectBuilder;
    }

    /**
     * Returns new or existing Query builder class for this table.
     *
     * @return \Propel\Generator\Builder\Om\AbstractOMBuilder
     */
    public function getQueryBuilder(): AbstractOMBuilder
    {
        if ($this->queryBuilder === null) {
            /** @var \Propel\Generator\Builder\Om\ObjectBuilder $builder */
            $builder = $this->getGeneratorConfig()->getConfiguredBuilder($this->getTable(), 'query');
            $this->queryBuilder = $builder;
        }

        return $this->queryBuilder;
    }

    /**
     * Returns new or existing stub Query builder class for this table.
     *
     * @return \Propel\Generator\Builder\Om\AbstractOMBuilder
     */
    public function getStubQueryBuilder(): AbstractOMBuilder
    {
        if ($this->stubQueryBuilder === null) {
            /** @var \Propel\Generator\Builder\Om\ObjectBuilder $builder */
            $builder = $this->getGeneratorConfig()->getConfiguredBuilder($this->getTable(), 'querystub');
            $this->stubQueryBuilder = $builder;
        }

        return $this->stubQueryBuilder;
    }

    /**
     * Returns new or existing Object builder class for this table.
     *
     * @return \Propel\Generator\Builder\Om\TableMapBuilder
     */
    public function getTableMapBuilder(): TableMapBuilder
    {
        if ($this->tablemapBuilder === null) {
            /** @var \Propel\Generator\Builder\Om\TableMapBuilder $builder */
            $builder = $this->getGeneratorConfig()->getConfiguredBuilder($this->getTable(), 'tablemap');
            $this->tablemapBuilder = $builder;
        }

        return $this->tablemapBuilder;
    }

    /**
     * Returns new or existing stub Interface builder class for this table.
     *
     * @return \Propel\Generator\Builder\Om\AbstractOMBuilder
     */
    public function getInterfaceBuilder(): AbstractOMBuilder
    {
        if ($this->interfaceBuilder === null) {
            /** @var \Propel\Generator\Builder\Om\ObjectBuilder $builder */
            $builder = $this->getGeneratorConfig()->getConfiguredBuilder($this->getTable(), 'interface');
            $this->interfaceBuilder = $builder;
        }

        return $this->interfaceBuilder;
    }

    /**
     * Returns new or existing stub child object builder class for this table.
     *
     * @return \Propel\Generator\Builder\Om\MultiExtendObjectBuilder
     */
    public function getMultiExtendObjectBuilder(): MultiExtendObjectBuilder
    {
        if ($this->multiExtendObjectBuilder === null) {
            /** @var \Propel\Generator\Builder\Om\MultiExtendObjectBuilder $builder */
            $builder = $this->getGeneratorConfig()->getConfiguredBuilder($this->getTable(), 'objectmultiextend');
            $this->multiExtendObjectBuilder = $builder;
        }

        return $this->multiExtendObjectBuilder;
    }

    /**
     * Gets a new data model builder class for specified table and classname.
     *
     * @param \Propel\Generator\Model\Table $table
     * @param string $classname The class of builder
     *
     * @return static
     */
    public function getNewBuilder(Table $table, string $classname)
    {
        /** @var static $builder */
        $builder = new $classname($table);
        $builder->setGeneratorConfig($this);

        return $builder;
    }

    /**
     * Convenience method to return a NEW Object class builder instance.
     *
     * This is used very frequently from the tableMap and object builders to get
     * an object builder for a RELATED table.
     *
     * @param \Propel\Generator\Model\Table $table
     *
     * @return \Propel\Generator\Builder\Om\ObjectBuilder
     */
    public function getNewObjectBuilder(Table $table): ObjectBuilder
    {
        /** @var \Propel\Generator\Builder\Om\ObjectBuilder $builder */
        $builder = $this->getGeneratorConfig()->getConfiguredBuilder($table, 'object');

        return $builder;
    }

    /**
     * Convenience method to return a NEW Object stub class builder instance.
     *
     * This is used from the query builders to get
     * an object builder for a RELATED table.
     *
     * @param \Propel\Generator\Model\Table $table
     *
     * @return \Propel\Generator\Builder\Om\AbstractObjectBuilder
     */
    public function getNewStubObjectBuilder(Table $table): AbstractObjectBuilder
    {
        /** @var \Propel\Generator\Builder\Om\AbstractObjectBuilder $builder */
        $builder = $this->getGeneratorConfig()->getConfiguredBuilder($table, 'objectstub');

        return $builder;
    }

    /**
     * Convenience method to return a NEW query class builder instance.
     *
     * This is used from the query builders to get
     * a query builder for a RELATED table.
     *
     * @param \Propel\Generator\Model\Table $table
     *
     * @return \Propel\Generator\Builder\Om\QueryBuilder
     */
    public function getNewQueryBuilder(Table $table): QueryBuilder
    {
        /** @var \Propel\Generator\Builder\Om\QueryBuilder $builder */
        $builder = $this->getGeneratorConfig()->getConfiguredBuilder($table, 'query');

        return $builder;
    }

    /**
     * Convenience method to return a NEW query stub class builder instance.
     *
     * This is used from the query builders to get
     * a query builder for a RELATED table.
     *
     * @param \Propel\Generator\Model\Table $table
     *
     * @return \Propel\Generator\Builder\Om\AbstractOMBuilder
     */
    public function getNewStubQueryBuilder(Table $table): AbstractOMBuilder
    {
        $builder = $this->getGeneratorConfig()->getConfiguredBuilder($table, 'querystub');

        return $builder;
    }

    /**
     * Returns new Query Inheritance builder class for this table.
     *
     * @param \Propel\Generator\Model\Inheritance $child
     *
     * @return \Propel\Generator\Builder\Om\AbstractOMBuilder
     */
    public function getNewQueryInheritanceBuilder(Inheritance $child): AbstractOMBuilder
    {
        /** @var \Propel\Generator\Builder\Om\QueryInheritanceBuilder $queryInheritanceBuilder */
        $queryInheritanceBuilder = $this->getGeneratorConfig()->getConfiguredBuilder($this->getTable(), 'queryinheritance');
        $queryInheritanceBuilder->setChild($child);

        return $queryInheritanceBuilder;
    }

    /**
     * Returns new stub Query Inheritance builder class for this table.
     *
     * @param \Propel\Generator\Model\Inheritance $child
     *
     * @return \Propel\Generator\Builder\Om\AbstractOMBuilder
     */
    public function getNewStubQueryInheritanceBuilder(Inheritance $child): AbstractOMBuilder
    {
        /** @var \Propel\Generator\Builder\Om\QueryInheritanceBuilder $stubQueryInheritanceBuilder */
        $stubQueryInheritanceBuilder = $this->getGeneratorConfig()->getConfiguredBuilder($this->getTable(), 'queryinheritancestub');
        $stubQueryInheritanceBuilder->setChild($child);

        return $stubQueryInheritanceBuilder;
    }

    /**
     * Returns new stub Query Inheritance builder class for this table.
     *
     * @param \Propel\Generator\Model\Table $table
     *
     * @return \Propel\Generator\Builder\Om\TableMapBuilder
     */
    public function getNewTableMapBuilder(Table $table): TableMapBuilder
    {
        /** @var \Propel\Generator\Builder\Om\TableMapBuilder $builder */
        $builder = $this->getGeneratorConfig()->getConfiguredBuilder($table, 'tablemap');

        return $builder;
    }

    /**
     * Gets the GeneratorConfig object.
     *
     * @return \Propel\Generator\Config\GeneratorConfigInterface|null
     */
    public function getGeneratorConfig(): ?GeneratorConfigInterface
    {
        return $this->generatorConfig;
    }

    /**
     * Get a specific configuration property.
     *
     * The name of the requested property must be given as a string, representing its hierarchy in the configuration
     * array, with each level separated by a dot. I.e.:
     * <code> $config['database']['adapter']['mysql']['tableType']</code>
     * is expressed by:
     * <code>'database.adapter.mysql.tableType</code>
     *
     * @param string $name
     *
     * @return string|null
     */
    public function getBuildProperty(string $name): ?string
    {
        if ($this->getGeneratorConfig()) {
            return $this->getGeneratorConfig()->getConfigProperty($name);
        }

        return null;
    }

    /**
     * Sets the GeneratorConfig object.
     *
     * @param \Propel\Generator\Config\GeneratorConfigInterface $v
     *
     * @return void
     */
    public function setGeneratorConfig(GeneratorConfigInterface $v): void
    {
        $this->generatorConfig = $v;
    }

    /**
     * Sets the table for this builder.
     *
     * @param \Propel\Generator\Model\Table $table
     *
     * @return void
     */
    public function setTable(Table $table): void
    {
        $this->table = $table;
    }

    /**
     * Returns the current Table object.
     *
     * @return \Propel\Generator\Model\Table
     */
    public function getTable(): Table
    {
        return $this->table;
    }

    /**
     * Convenience method to return the Platform class for this table (database).
     *
     * @return \Propel\Generator\Platform\PlatformInterface|null
     */
    public function getPlatform(): ?PlatformInterface
    {
        if ($this->platform === null) {
            // try to load the platform from the table
            $table = $this->table;
            $database = $table->getDatabase();
            if ($database) {
                $this->setPlatform($database->getPlatform());
            }
        }

        if (!$this->table->isIdentifierQuotingEnabled()) {
            $this->platform->setIdentifierQuoting(false);
        }

        return $this->platform;
    }

    /**
     * Convenience method to return the Platform class for this table (database).
     *
     * @throws \Propel\Generator\Exception\LogicException
     *
     * @return \Propel\Generator\Platform\PlatformInterface
     */
    public function getPlatformOrFail(): PlatformInterface
    {
        $platform = $this->getPlatform();
        if ($platform === null) {
            throw new LogicException('Platform is not set');
        }

        return $platform;
    }

    /**
     * Platform setter
     *
     * @param \Propel\Generator\Platform\PlatformInterface $platform
     *
     * @return void
     */
    public function setPlatform(PlatformInterface $platform): void
    {
        $this->platform = $platform;
    }

    /**
     * Quotes identifier based on $this->getTable()->isIdentifierQuotingEnabled.
     *
     * @param string $text
     *
     * @return string
     */
    public function quoteIdentifier(string $text): string
    {
        if ($this->getTable()->isIdentifierQuotingEnabled()) {
            return $this->getPlatform()->doQuoting($text);
        }

        return $text;
    }

    /**
     * Convenience method to return the database for current table.
     *
     * @return \Propel\Generator\Model\Database|null
     */
    public function getDatabase(): ?Database
    {
        return $this->getTable()->getDatabase();
    }

    /**
     * Convenience method to return the database for current table.
     *
     * @return \Propel\Generator\Model\Database
     */
    public function getDatabaseOrFail(): Database
    {
        return $this->getTable()->getDatabaseOrFail();
    }

    /**
     * Pushes a message onto the stack of warnings.
     *
     * @param string $msg The warning message.
     *
     * @return void
     */
    protected function warn(string $msg): void
    {
        $this->warnings[] = $msg;
    }

    /**
     * Gets array of warning messages.
     *
     * @return array<string>
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }

    /**
     * Returns the name of the current class being built, with a possible prefix.
     *
     * @see OMBuilder#getClassName()
     *
     * @param string $identifier
     *
     * @return string
     */
    public function prefixClassName(string $identifier): string
    {
        return $this->getBuildProperty('generator.objectModel.classPrefix') . $identifier;
    }
}

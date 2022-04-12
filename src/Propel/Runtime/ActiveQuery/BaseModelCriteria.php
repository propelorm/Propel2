<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\ActiveQuery;

use ArrayIterator;
use IteratorAggregate;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\InvalidArgumentException;
use Propel\Runtime\Exception\LogicException;
use Propel\Runtime\Formatter\AbstractFormatter;
use Propel\Runtime\Map\TableMap;
use Propel\Runtime\Propel;
use Traversable;

/**
 * @phpstan-template T of \Propel\Runtime\ActiveRecord\ActiveRecordInterface
 * @phpstan-template TColl of \Propel\Runtime\Collection\Collection
 * @phpstan-template TReturn
 */
abstract class BaseModelCriteria extends Criteria implements IteratorAggregate
{
    /**
     * @phpstan-var class-string<T>|null
     *
     * @var string|null
     */
    protected $modelName;

    /**
     * @phpstan-var class-string<\Propel\Runtime\Map\TableMap>|null
     *
     * @var string|null
     */
    protected $modelTableMapName;

    /**
     * @var bool
     */
    protected $useAliasInSQL = false;

    /**
     * @var string|null
     */
    protected $modelAlias;

    /**
     * @phpstan-var \Propel\Runtime\Map\TableMap<T>|null
     *
     * @var \Propel\Runtime\Map\TableMap|null
     */
    protected $tableMap;

    /**
     * @var \Propel\Runtime\Formatter\AbstractFormatter|null
     */
    protected $formatter;

    /**
     * @var array
     */
    protected $with = [];

    /**
     * @phpstan-var class-string<\Propel\Runtime\Formatter\AbstractFormatter>
     *
     * @var string
     */
    protected $defaultFormatterClass = ModelCriteria::FORMAT_OBJECT;

    /**
     * Creates a new instance with the default capacity which corresponds to
     * the specified database.
     *
     * @phpstan-param class-string<T> $modelName
     *
     * @param string|null $dbName The database name
     * @param string|null $modelName The phpName of a model, e.g. 'Book'
     * @param string|null $modelAlias The alias for the model in this query, e.g. 'b'
     */
    public function __construct(?string $dbName = null, ?string $modelName = null, ?string $modelAlias = null)
    {
        parent::__construct($dbName);
        $this->setModelName($modelName);
        $this->modelAlias = $modelAlias;
    }

    /**
     * Gets the array of ModelWith specifying which objects must be hydrated
     * together with the main object.
     *
     * @see with()
     *
     * @return array<\Propel\Runtime\ActiveQuery\ModelWith>
     */
    public function getWith(): array
    {
        return $this->with;
    }

    /**
     * Sets the array of ModelWith specifying which objects must be hydrated
     * together with the main object.
     *
     * @param array $with
     *
     * @return $this The current object, for fluid interface
     */
    public function setWith(array $with)
    {
        $this->with = $with;

        return $this;
    }

    /**
     * Sets the formatter to use for the find() output
     * Formatters must extend AbstractFormatter
     * Use the ModelCriteria constants for class names:
     * <code>
     * $c->setFormatter(ModelCriteria::FORMAT_ARRAY);
     * </code>
     *
     * @phpstan-template AColl of \Propel\Runtime\Collection\Collection
     * @phpstan-template AReturn
     *
     * @phpstan-param \Propel\Runtime\Formatter\AbstractFormatter<T, AColl, AReturn>|class-string<\Propel\Runtime\Formatter\AbstractFormatter> $formatter
     *
     * @phpstan-return $this<T, AColl, AReturn>
     *
     * @param \Propel\Runtime\Formatter\AbstractFormatter|string $formatter a formatter class name, or a formatter instance
     *
     * @throws \Propel\Runtime\Exception\InvalidArgumentException
     *
     * @return $this The current object, for fluid interface
     */
    public function setFormatter($formatter)
    {
        if (is_string($formatter)) {
            $formatter = new $formatter($this);
        }

        if (!$formatter instanceof AbstractFormatter) {
            throw new InvalidArgumentException('setFormatter() only accepts classes extending AbstractFormatter');
        }

        $this->formatter = $formatter;

        return $this;
    }

    /**
     * Gets the formatter to use for the find() output
     * Defaults to an instance of ModelCriteria::$defaultFormatterClass, i.e. PropelObjectsFormatter
     *
     * @return \Propel\Runtime\Formatter\AbstractFormatter
     */
    public function getFormatter(): AbstractFormatter
    {
        if ($this->formatter === null) {
            $formatterClass = $this->defaultFormatterClass;
            $this->formatter = new $formatterClass();
        }

        return $this->formatter;
    }

    /**
     * Returns the name of the class for this model criteria
     *
     * @phpstan-return class-string<T>
     *
     * @return string|null
     */
    public function getModelName(): ?string
    {
        return $this->modelName;
    }

    /**
     * Returns the name of the class for this model criteria
     *
     * @throws \Propel\Runtime\Exception\LogicException
     *
     * @return string
     */
    public function getModelNameOrFail(): string
    {
        $modelName = $this->getModelName();

        if ($modelName === null) {
            throw new LogicException('Model name is not defined.');
        }

        return $modelName;
    }

    /**
     * Sets the model name.
     * This also sets `this->modelTableMapName` and `this->tableMap`.
     *
     * @phpstan-param class-string<T>|null $modelName
     *
     * @param string|null $modelName
     *
     * @return $this The current object, for fluid interface
     */
    public function setModelName(?string $modelName)
    {
        if (!$modelName) {
            $this->modelName = null;

            return $this;
        }
        if (strpos($modelName, '\\') === 0) {
            /** @phpstan-var class-string<T> $modelName */
            $modelName = substr($modelName, 1);
        }

        $this->modelName = $modelName;
        if (!$this->modelTableMapName) {
            $this->modelTableMapName = $modelName::TABLE_MAP;
        }
        $dbName = $this->getDbName();
        $this->tableMap = Propel::getServiceContainer()->getDatabaseMap($dbName)->getTableByPhpName($modelName);
        $this->setPrimaryTableName($this->modelTableMapName::TABLE_NAME);

        return $this;
    }

    /**
     * @phpstan-return class-string<T>
     *
     * @return string
     */
    public function getFullyQualifiedModelName(): string
    {
        return '\\' . $this->getModelName();
    }

    /**
     * Sets the alias for the model in this query
     *
     * @param string $modelAlias The model alias
     * @param bool $useAliasInSQL Whether to use the alias in the SQL code (false by default)
     *
     * @return $this The current object, for fluid interface
     */
    public function setModelAlias(string $modelAlias, bool $useAliasInSQL = false)
    {
        if ($useAliasInSQL) {
            $this->addAlias($modelAlias, $this->tableMap->getName());
            $this->useAliasInSQL = true;
        }

        $this->modelAlias = $modelAlias;

        return $this;
    }

    /**
     * Returns the alias of the main class for this model criteria
     *
     * @return string|null The model alias
     */
    public function getModelAlias(): ?string
    {
        return $this->modelAlias;
    }

    /**
     * Return the string to use in a clause as a model prefix for the main model
     *
     * @return string|null The model alias if it exists, the model name if not
     */
    public function getModelAliasOrName(): ?string
    {
        return $this->modelAlias ?: $this->modelName;
    }

    /**
     * Return The short model name (the short ClassName for class with namespace)
     *
     * @return string The short model name
     */
    public function getModelShortName(): string
    {
        return static::getShortName($this->modelName ?: '');
    }

    /**
     * Return the short ClassName for class with namespace
     *
     * @param string $fullyQualifiedClassName The fully qualified class name
     *
     * @return string The short class name
     */
    public static function getShortName(string $fullyQualifiedClassName): string
    {
        $namespaceParts = explode('\\', $fullyQualifiedClassName);

        return array_pop($namespaceParts);
    }

    /**
     * Returns the TableMap object for this Criteria
     *
     * @phpstan-return \Propel\Runtime\Map\TableMap<T>|null
     *
     * @return \Propel\Runtime\Map\TableMap|null
     */
    public function getTableMap(): ?TableMap
    {
        return $this->tableMap;
    }

    /**
     * Returns the TableMap object for this Criteria
     *
     * @throws \Propel\Runtime\Exception\LogicException
     *
     * @return \Propel\Runtime\Map\TableMap
     */
    public function getTableMapOrFail(): TableMap
    {
        $tableMap = $this->getTableMap();

        if ($tableMap === null) {
            throw new LogicException('Table map is not defined.');
        }

        return $tableMap;
    }

    /**
     * Returns the name of the table as used in the query.
     *
     * Either the SQL name or an alias.
     *
     * @return string|null
     */
    public function getTableNameInQuery(): ?string
    {
        if ($this->useAliasInSQL && $this->modelAlias) {
            return $this->modelAlias;
        }

        return $this->getTableMap()->getName();
    }

    /**
     * Execute the query with a find(), and return a Traversable object.
     *
     * The return value depends on the query formatter. By default, this returns an ArrayIterator
     * constructed on a Propel\Runtime\Collection\PropelCollection.
     * Compulsory for implementation of \IteratorAggregate.
     *
     * @throws \Propel\Runtime\Exception\LogicException
     *
     * @return \Traversable<T>
     */
    public function getIterator(): Traversable
    {
        $res = $this->find(null); // use the default connection
        if ($res instanceof IteratorAggregate) {
            return $res->getIterator();
        }
        if ($res instanceof Traversable) {
            return $res;
        }
        if (is_array($res)) {
            return new ArrayIterator($res);
        }

        throw new LogicException('The current formatter doesn\'t return an iterable result');
    }

    /**
     * Issue a SELECT query based on the current ModelCriteria
     * and format the list of results with the current formatter
     * By default, returns an array of model objects
     *
     * @phpstan-return T|mixed
     *
     * @param \Propel\Runtime\Connection\ConnectionInterface|null $con an optional connection object
     *
     * @return \Propel\Runtime\Collection\ObjectCollection|\Propel\Runtime\ActiveRecord\ActiveRecordInterface[]|mixed the list of results, formatted by the current formatter
     */
    abstract public function find(?ConnectionInterface $con = null);
}

<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\ActiveQuery;

use ArrayIterator;
use IteratorAggregate;
use Propel\Runtime\Exception\InvalidArgumentException;
use Propel\Runtime\Exception\LogicException;
use Propel\Runtime\Formatter\AbstractFormatter;
use Propel\Runtime\Propel;
use Traversable;

class BaseModelCriteria extends Criteria implements IteratorAggregate
{
    /**
     * @var string|null
     */
    protected $modelName;

    /**
     * @var string|null
     */
    protected $modelTableMapName;

    /**
     * @var string|null
     */
    protected $modelAlias;

    /**
     * @var \Propel\Runtime\Map\TableMap
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
     * @param string|null $dbName The dabase name
     * @param string|null $modelName The phpName of a model, e.g. 'Book'
     * @param string|null $modelAlias The alias for the model in this query, e.g. 'b'
     */
    public function __construct($dbName = null, $modelName = null, $modelAlias = null)
    {
        $this->setDbName($dbName);
        $this->originalDbName = $dbName;
        $this->setModelName($modelName);
        $this->modelAlias = $modelAlias;
    }

    /**
     * Gets the array of ModelWith specifying which objects must be hydrated
     * together with the main object.
     *
     * @see with()
     *
     * @return \Propel\Runtime\ActiveQuery\ModelWith[]
     */
    public function getWith()
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
    public function setWith($with)
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
     * @param string|\Propel\Runtime\Formatter\AbstractFormatter $formatter a formatter class name, or a formatter instance
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
    public function getFormatter()
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
     * @return string
     */
    public function getModelName()
    {
        return $this->modelName;
    }

    /**
     * Sets the model name.
     * This also sets `this->modelTableMapName` and `this->tableMap`.
     *
     * @param string $modelName
     *
     * @return $this The current object, for fluid interface
     */
    public function setModelName($modelName)
    {
        if (strpos($modelName, '\\') === 0) {
            $this->modelName = substr($modelName, 1);
        } else {
            $this->modelName = $modelName;
        }
        if ($this->modelName && !$this->modelTableMapName) {
            $this->modelTableMapName = constant($this->modelName . '::TABLE_MAP');
        }
        if ($this->modelName) {
            $this->tableMap = Propel::getServiceContainer()->getDatabaseMap($this->getDbName())->getTableByPhpName($this->modelName);
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getFullyQualifiedModelName()
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
    public function setModelAlias($modelAlias, $useAliasInSQL = false)
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
     * @return string The model alias
     */
    public function getModelAlias()
    {
        return $this->modelAlias;
    }

    /**
     * Return the string to use in a clause as a model prefix for the main model
     *
     * @return string The model alias if it exists, the model name if not
     */
    public function getModelAliasOrName()
    {
        return $this->modelAlias ? $this->modelAlias : $this->modelName;
    }

    /**
     * Return The short model name (the short ClassName for class with namespace)
     *
     * @return string The short model name
     */
    public function getModelShortName()
    {
        return static::getShortName($this->modelName);
    }

    /**
     * Returns the TableMap object for this Criteria
     *
     * @return \Propel\Runtime\Map\TableMap
     */
    public function getTableMap()
    {
        return $this->tableMap;
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
     * @return \Traversable
     */
    public function getIterator()
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
}

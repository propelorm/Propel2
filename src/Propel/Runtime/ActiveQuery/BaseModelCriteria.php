<?php

namespace Propel\Runtime\ActiveQuery;

use Propel\Runtime\Propel;
use Propel\Runtime\Exception\InvalidArgumentException;
use Propel\Runtime\Exception\LogicException;
use Propel\Runtime\Formatter\AbstractFormatter;
use Propel\Runtime\Map\TableMap;
use Propel\Runtime\ActiveQuery\Criteria;

class BaseModelCriteria extends Criteria implements \IteratorAggregate
{
    protected $modelName;

    protected $modelTableMapName;

    protected $modelAlias;

    protected $tableMap;

    protected $formatter;

    protected $with = array();

    protected $defaultFormatterClass = ModelCriteria::FORMAT_OBJECT;

    /**
     * Creates a new instance with the default capacity which corresponds to
     * the specified database.
     *
     * @param string $dbName     The dabase name
     * @param string $modelName  The phpName of a model, e.g. 'Book'
     * @param string $modelAlias The alias for the model in this query, e.g. 'b'
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
     * @return array
     */
    public function getWith()
    {
        return $this->with;
    }

    /**
     * Sets the array of ModelWith specifying which objects must be hydrated
     * together with the main object.
     *
     * @param    array
     *
     * @return ModelCriteria The current object, for fluid interface
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
     * @param  string|AbstractFormatter $formatter a formatter class name, or a formatter instance
     * @return ModelCriteria            The current object, for fluid interface
     *
     * @throws InvalidArgumentException
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
     * @return AbstractFormatter
     */
    public function getFormatter()
    {
        if (null === $this->formatter) {
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
     * @return ModelCriteria The current object, for fluid interface
     */
    public function setModelName($modelName)
    {
        if (0 === strpos($modelName, '\\')) {
            $this->modelName = substr($modelName, 1);
        } else {
            $this->modelName = $modelName;
        }
        if ($this->modelName && !$this->modelTableMapName) {
            $this->modelTableMapName = constant($this->modelName . '::TABLE_MAP');
        }
        if (!$this->tableMap && $this->modelName) {
            $this->tableMap = Propel::getServiceContainer()->getDatabaseMap($this->getDbName())->getTableByPhpName($this->modelName);
        }

        return $this;
    }

    public function getFullyQualifiedModelName()
    {
        return '\\' . $this->getModelName();
    }

    /**
     * Sets the alias for the model in this query
     *
     * @param string  $modelAlias    The model alias
     * @param boolean $useAliasInSQL Whether to use the alias in the SQL code (false by default)
     *
     * @return ModelCriteria The current object, for fluid interface
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
     * @return TableMap
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
     * @return Traversable
     *
     * @throws LogicException
     */
    public function getIterator()
    {
        $res = $this->find(null); // use the default connection
        if ($res instanceof \IteratorAggregate) {
            return $res->getIterator();
        }
        if ($res instanceof \Traversable) {
            return $res;
        }
        if (is_array($res)) {
            return new \ArrayIterator($res);
        }
        throw new LogicException('The current formatter doesn\'t return an iterable result');
    }

}

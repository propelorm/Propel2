<?php

namespace Propel\Runtime\ActiveQuery;

use Propel\Generator\Model\NamingTool;
use Propel\Runtime\Propel;
use Propel\Runtime\Exception\InvalidArgumentException;
use Propel\Runtime\Exception\LogicException;
use Propel\Runtime\Formatter\AbstractFormatter;
use Propel\Runtime\Map\EntityMap;

class BaseModelCriteria extends Criteria implements \IteratorAggregate
{
    protected $entityName;

    protected $entityMap;

    protected $entityAlias;

    protected $formatter;

    protected $with = array();

    protected $defaultFormatterClass = ModelCriteria::FORMAT_OBJECT;

    protected $useAliasInSQL = false;

    /**
     * Creates a new instance with the default capacity which corresponds to
     * the specified database.
     *
     * @param string $dbName     The dabase name
     * @param string $entityName  The phpName of a model, e.g. 'Book'
     * @param string $entityAlias The alias for the model in this query, e.g. 'b'
     */
    public function __construct($dbName = null, $entityName = null, $entityAlias = null)
    {
        $this->setDbName($dbName);
        $this->originalDbName = $dbName;
        $this->setEntityName($entityName);
        $this->entityAlias = $entityAlias;
    }

    /**
     * Gets the array of ModelWith specifying which objects must be hydrated
     * together with the main object.
     *
     * @see with()
     * @return ModelWith[]
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
     * @return $this|ModelCriteria The current object, for fluid interface
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
     * @return $this|ModelCriteria      The current object, for fluid interface
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
    public function getEntityName()
    {
        return $this->entityName;
    }

    /**
     * Sets the model name.
     * This also sets `this->entityMap` and `this->entityMap`.
     *
     * @param string $entityName
     *
     * @return $this|ModelCriteria The current object, for fluid interface
     */
    public function setEntityName($entityName)
    {
        if (0 === strpos($entityName, '\\')) {
            $this->entityName = substr($entityName, 1);
        } else {
            $this->entityName = $entityName;
        }
        return $this;
    }

    public function getFullyQualifiedModelName()
    {
        return '\\' . $this->getEntityName();
    }

    /**
     * Sets the alias for the model in this query
     *
     * @param string  $modelAlias    The model alias
     * @param boolean $useAliasInSQL Whether to use the alias in the SQL code (false by default)
     *
     * @return $this|ModelCriteria The current object, for fluid interface
     */
    public function setEntityAlias($modelAlias, $useAliasInSQL = false)
    {
        if ($useAliasInSQL) {
            $this->addAlias($modelAlias, $this->getEntityMap()->getFullClassName());
            $this->useAliasInSQL = true;
        }

        $this->entityAlias = $modelAlias;

        return $this;
    }

    /**
     * Returns the alias of the main class for this model criteria
     *
     * @return string The model alias
     */
    public function getEntityAlias()
    {
        return $this->entityAlias;
    }

    /**
     * Return the string to use in a clause as a model prefix for the main model
     *
     * @return string The model alias if it exists, the model name if not
     */
    public function getModelAliasOrName()
    {
        return $this->entityAlias ? $this->entityAlias : $this->entityName;
    }

    /**
     * Return The short model name (the short ClassName for class with namespace)
     *
     * @return string The short model name
     */
    public function getModelShortName()
    {
        return NamingTool::shortClassName($this->entityName);
    }

    /**
     * Returns the EntityMap object for this Criteria
     *
     * @return EntityMap
     */
    public function getEntityMap()
    {
        if (null === $this->entityMap && $this->entityName) {
            return $this->getConfiguration()->getEntityMap($this->entityName);
        }

        return $this->entityMap;
    }

    /**
     * @param EntityMap $entityMap
     */
    public function setEntityMap(EntityMap $entityMap)
    {
        $this->entityMap = $entityMap;
    }

    /**
     * Execute the query with a find(), and return a Traversable object.
     *
     * The return value depends on the query formatter. By default, this returns an ArrayIterator
     * constructed on a Propel\Runtime\Collection\PropelCollection.
     * Compulsory for implementation of \IteratorAggregate.
     *
     * @return \Traversable
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

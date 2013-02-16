<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Runtime\Formatter;

use Propel\Runtime\Propel;
use Propel\Runtime\Connection\StatementInterface;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\ActiveQuery\ModelCriteria;

/**
 * Abstract class for query formatter
 *
 * @author Francois Zaninotto
 */
abstract class AbstractFormatter
{
    protected $dbName;

    protected $class;

    protected $peer;

    protected $with;

    protected $asColumns;

    protected $hasLimit;

    protected $currentObjects;

    protected $collectionName;

    public function __construct(ModelCriteria $criteria = null)
    {
        $this->with = array();
        $this->asColumns = array();
        $this->currentObjects = array();
        $this->hasLimit = false;

        if (null !== $criteria) {
            $this->init($criteria);
        }
    }

    /**
     * Define the hydration schema based on a query object.
     * Fills the Formatter's properties using a Criteria as source
     *
     * @param ModelCriteria $criteria
     *
     * @return AbstractFormatter The current formatter object
     */
    public function init(ModelCriteria $criteria)
    {
        $this->dbName = $criteria->getDbName();
        $this->setClass($criteria->getModelName());
        $this->setWith($criteria->getWith());
        $this->asColumns = $criteria->getAsColumns();
        $this->hasLimit = $criteria->getLimit() != 0;

        return $this;
    }

    // DataObject getters & setters

    public function setDbName($dbName)
    {
        $this->dbName = $dbName;
    }

    public function getDbName()
    {
        return $this->dbName;
    }

    public function setClass($class)
    {
        $this->class = $class;
        $this->peer = constant($this->class . '::PEER');
    }

    public function getClass()
    {
        return $this->class;
    }

    public function setPeer($peer)
    {
        $this->peer = $peer;
    }

    public function getPeer()
    {
        return $this->peer;
    }

    public function setWith($withs = array())
    {
        $this->with = $withs;
    }

    public function getWith()
    {
        return $this->with;
    }

    public function setAsColumns($asColumns = array())
    {
        $this->asColumns = $asColumns;
    }

    public function getAsColumns()
    {
        return $this->asColumns;
    }

    public function setHasLimit($hasLimit = false)
    {
        $this->hasLimit = $hasLimit;
    }

    public function hasLimit()
    {
        return $this->hasLimit;
    }

    /**
     * Returns a Collection object or a simple array.
     *
     * @return Collection|array
     */
    protected function getCollection()
    {
        $collection = array();

        if ($class = $this->getCollectionClassName()) {
            $collection = new $class();
            $collection->setModel($this->class);
            $collection->setFormatter($this);
        }

        return $collection;
    }

    public function getCollectionClassName()
    {

    }

    /**
     * Formats an ActiveRecord object
     *
     * @param BaseObject $record the object to format
     *
     * @return BaseObject The original record
     */
    public function formatRecord($record = null)
    {
        return $record;
    }

    abstract public function format(StatementInterface $stmt);

    abstract public function formatOne(StatementInterface $stmt);

    abstract public function isObjectFormatter();

    public function checkInit()
    {
        if (null === $this->peer) {
            throw new PropelException('You must initialize a formatter object before calling format() or formatOne()');
        }
    }

    public function getTableMap()
    {
        return Propel::getServiceContainer()->getDatabaseMap($this->dbName)->getTableByPhpName($this->class);
    }

    protected function isWithOneToMany()
    {
        foreach ($this->with as $modelWith) {
            if ($modelWith->isWithOneToMany()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Gets the worker object for the class.
     * To save memory, we don't create a new object for each row,
     * But we keep hydrating a single object per class.
     * The column offset in the row is used to index the array of classes
     * As there may be more than one object of the same class in the chain
     *
     * @param int    $col   Offset of the object in the list of objects to hydrate
     * @param string $class Propel model object class
     *
     * @return BaseObject
     */
    protected function getWorkerObject($col, $class)
    {
        if (isset($this->currentObjects[$col])) {
            $this->currentObjects[$col]->clear();
        } else {
            $this->currentObjects[$col] = new $class();
        }

        return $this->currentObjects[$col];
    }

    /**
     * Gets a Propel object hydrated from a selection of columns in statement row
     *
     * @param array $row associative array indexed by column number,
     *                   as returned by PDOStatement::fetch(PDO::FETCH_NUM)
     * @param string $class The classname of the object to create
     * @param int    $col   The start column for the hydration (modified)
     *
     * @return BaseObject
     */
    public function getSingleObjectFromRow($row, $class, &$col = 0)
    {
        $obj = $this->getWorkerObject($col, $class);
        $col = $obj->hydrate($row, $col);

        return $obj;
    }
}

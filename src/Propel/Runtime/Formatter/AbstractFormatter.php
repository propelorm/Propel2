<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Runtime\Formatter;

use Propel\Runtime\ActiveQuery\ModelWith;
use Propel\Runtime\Collection\Collection;
use Propel\Runtime\Collection\ObjectCollection;
use Propel\Runtime\Map\EntityMap;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\ActiveQuery\BaseModelCriteria;
use Propel\Runtime\DataFetcher\DataFetcherInterface;

/**
 * Abstract class for query formatter
 *
 * @author Francois Zaninotto
 */
abstract class AbstractFormatter
{
    protected $dbName;

    protected $entityName;

    protected $entityMap;

    /** @var ModelWith[] $with */
    protected $with;

    protected $asFields;

    protected $hasLimit;

    protected $currentObjects;

    protected $collectionName;

    /**
     * @var DataFetcherInterface
     */
    protected $dataFetcher;

    public function __construct(BaseModelCriteria $criteria = null, DataFetcherInterface $dataFetcher = null)
    {
        $this->with = array();
        $this->asFields = array();
        $this->currentObjects = array();
        $this->hasLimit = false;

        if (null !== $criteria) {
            $this->init($criteria, $dataFetcher);
        }
    }

    /**
     * Sets a DataFetcherInterface object.
     *
     * @param DataFetcherInterface $dataFetcher
     */
    public function setDataFetcher(DataFetcherInterface $dataFetcher)
    {
        $this->dataFetcher = $dataFetcher;
    }

    /**
     * Returns the current DataFetcherInterface object.
     *
     * @return DataFetcherInterface
     */
    public function getDataFetcher()
    {
        return $this->dataFetcher;
    }

    /**
     * Define the hydration schema based on a query object.
     * Fills the Formatter's properties using a Criteria as source
     *
     * @param BaseModelCriteria    $criteria
     * @param DataFetcherInterface $dataFetcher
     *
     * @return $this|AbstractFormatter The current formatter object
     */
    public function init(BaseModelCriteria $criteria, DataFetcherInterface $dataFetcher = null)
    {
        $this->dbName = $criteria->getDbName();
        $this->setEntityName($criteria->getEntityName());
        $this->entityMap = $criteria->getEntityMap();
        $this->setWith($criteria->getWith());
        $this->asFields = $criteria->getAsFields();
        $this->hasLimit = $criteria->getLimit() != -1;
        if ($dataFetcher) {
            $this->setDataFetcher($dataFetcher);
        }

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

    public function setEntityName($entityName)
    {
        $this->entityName = $entityName;
    }

    public function getEntityName()
    {
        return $this->entityName;
    }

    public function setWith($withs = array())
    {
        $this->with = $withs;
    }

    public function getWith()
    {
        return $this->with;
    }

    public function setAsFields($asFields = array())
    {
        $this->asFields = $asFields;
    }

    public function getAsFields()
    {
        return $this->asFields;
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
     * Returns a Collection objects.
     *
     * @return Collection|array
     */
    protected function getCollection()
    {
        $collection = new ObjectCollection();

        if ($entityName = $this->getCollectionEntityNameName()) {
            /** @var Collection $collection */
            $collection = new $entityName();
            $collection->setModel($this->entityName);
            $collection->setFormatter($this);
        }

        return $collection;
    }

    public function getCollectionEntityNameName()
    {
    }

    abstract public function format(DataFetcherInterface $dataFetcher = null);

    abstract public function formatOne(DataFetcherInterface $dataFetcher = null);

    abstract public function isObjectFormatter();

    public function checkInit()
    {
        if (null === $this->entityMap) {
            throw new PropelException('You must initialize a formatter object before calling format() or formatOne()');
        }
    }

    /**
     * @return EntityMap
     */
    public function getEntityMap()
    {
        return $this->entityMap;
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
     * Gets the worker object for the entityName.
     * To save memory, we don't create a new object for each row,
     * But we keep hydrating a single object per entityName.
     * The field offset in the row is used to index the array of entityNamees
     * As there may be more than one object of the same entityName in the chain
     *
     * @param int    $col        Offset of the object in the list of objects to hydrate
     * @param string $entityName Propel model object entityName
     *
     * @return object
     */
    protected function getWorkerObject($col, $entityName)
    {
        if (isset($this->currentObjects[$col])) {
            $this->currentObjects[$col] = null;
        }
        $this->currentObjects[$col] = new $entityName();

        return $this->currentObjects[$col];
    }

    /**
     * Gets a Propel object hydrated from a selection of fields in statement row
     *
     * @param array  $row        associative array indexed by field number,
     *                           as returned by DataFetcher::fetch()
     * @param string $entityName The entity name of the object to create
     * @param int    $col        The start field for the hydration (modified)
     *
     * @return object
     */
    public function getSingleObjectFromRow($row, $entityName, &$col = 0)
    {
        $obj = $this->getWorkerObject($col, $entityName);

        return $this->getEntityMap()->populateObject($row, $col, $this->getDataFetcher()->getIndexType(), $obj);
    }
}

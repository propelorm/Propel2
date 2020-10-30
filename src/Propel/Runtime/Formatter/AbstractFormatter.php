<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\Formatter;

use Propel\Runtime\ActiveQuery\BaseModelCriteria;
use Propel\Runtime\ActiveRecord\ActiveRecordInterface;
use Propel\Runtime\DataFetcher\DataFetcherInterface;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\Propel;

/**
 * Abstract class for query formatter
 *
 * @author Francois Zaninotto
 */
abstract class AbstractFormatter
{
    /**
     * @var string|null
     */
    protected $dbName;

    /**
     * @var string|null
     */
    protected $class;

    /**
     * @var \Propel\Runtime\Map\TableMap|null
     */
    protected $tableMap;

    /**
     * @var \Propel\Runtime\ActiveQuery\ModelWith[] $with
     */
    protected $with = [];

    /**
     * @var array
     */
    protected $asColumns = [];

    /**
     * @var bool
     */
    protected $hasLimit = false;

    /**
     * @var \Propel\Runtime\ActiveRecord\ActiveRecordInterface[]
     */
    protected $currentObjects = [];

    /**
     * @var \Propel\Runtime\DataFetcher\DataFetcherInterface
     */
    protected $dataFetcher;

    /**
     * @param \Propel\Runtime\ActiveQuery\BaseModelCriteria|null $criteria
     * @param \Propel\Runtime\DataFetcher\DataFetcherInterface|null $dataFetcher
     */
    public function __construct(?BaseModelCriteria $criteria = null, ?DataFetcherInterface $dataFetcher = null)
    {
        if ($criteria !== null) {
            $this->init($criteria, $dataFetcher);
        }
    }

    /**
     * Sets a DataFetcherInterface object.
     *
     * @param \Propel\Runtime\DataFetcher\DataFetcherInterface $dataFetcher
     *
     * @return void
     */
    public function setDataFetcher(DataFetcherInterface $dataFetcher)
    {
        $this->dataFetcher = $dataFetcher;
    }

    /**
     * Returns the current DataFetcherInterface object.
     *
     * @return \Propel\Runtime\DataFetcher\DataFetcherInterface
     */
    public function getDataFetcher()
    {
        return $this->dataFetcher;
    }

    /**
     * Define the hydration schema based on a query object.
     * Fills the Formatter's properties using a Criteria as source
     *
     * @param \Propel\Runtime\ActiveQuery\BaseModelCriteria $criteria
     * @param \Propel\Runtime\DataFetcher\DataFetcherInterface|null $dataFetcher
     *
     * @return $this The current formatter object
     */
    public function init(BaseModelCriteria $criteria, ?DataFetcherInterface $dataFetcher = null)
    {
        $this->dbName = $criteria->getDbName();
        $this->setClass($criteria->getModelName());
        $this->setWith($criteria->getWith());
        $this->asColumns = $criteria->getAsColumns();
        $this->hasLimit = $criteria->getLimit() != -1;
        if ($dataFetcher) {
            $this->setDataFetcher($dataFetcher);
        }

        return $this;
    }

    // DataObject getters & setters

    /**
     * @param string $dbName
     *
     * @return void
     */
    public function setDbName($dbName)
    {
        $this->dbName = $dbName;
    }

    /**
     * @return string
     */
    public function getDbName()
    {
        return $this->dbName;
    }

    /**
     * @param string $class
     *
     * @return void
     */
    public function setClass($class)
    {
        $this->class = $class;
        $this->tableMap = constant($this->class . '::TABLE_MAP');
    }

    /**
     * @return string|null
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * @param array $withs
     *
     * @return void
     */
    public function setWith($withs = [])
    {
        $this->with = $withs;
    }

    /**
     * @return \Propel\Runtime\ActiveQuery\ModelWith[]
     */
    public function getWith()
    {
        return $this->with;
    }

    /**
     * @param array $asColumns
     *
     * @return void
     */
    public function setAsColumns($asColumns = [])
    {
        $this->asColumns = $asColumns;
    }

    /**
     * @return array
     */
    public function getAsColumns()
    {
        return $this->asColumns;
    }

    /**
     * @param bool $hasLimit
     *
     * @return void
     */
    public function setHasLimit($hasLimit = false)
    {
        $this->hasLimit = $hasLimit;
    }

    /**
     * @return bool
     */
    public function hasLimit()
    {
        return $this->hasLimit;
    }

    /**
     * Returns a Collection object or a simple array.
     *
     * @return \Propel\Runtime\Collection\Collection|array
     */
    protected function getCollection()
    {
        $collection = [];

        $class = $this->getCollectionClassName();
        if ($class) {
            /** @var \Propel\Runtime\Collection\Collection $collection */
            $collection = new $class();
            $collection->setModel($this->class);
            $collection->setFormatter($this);
        }

        return $collection;
    }

    /**
     * @return string|null
     */
    public function getCollectionClassName()
    {
        return null;
    }

    /**
     * Formats an ActiveRecord object
     *
     * @param \Propel\Runtime\ActiveRecord\ActiveRecordInterface|null $record the object to format
     *
     * @return \Propel\Runtime\ActiveRecord\ActiveRecordInterface The original record
     */
    public function formatRecord(?ActiveRecordInterface $record = null)
    {
        return $record;
    }

    /**
     * @param \Propel\Runtime\DataFetcher\DataFetcherInterface|null $dataFetcher
     *
     * @return mixed
     */
    abstract public function format(?DataFetcherInterface $dataFetcher = null);

    /**
     * @param \Propel\Runtime\DataFetcher\DataFetcherInterface|null $dataFetcher
     *
     * @return mixed
     */
    abstract public function formatOne(?DataFetcherInterface $dataFetcher = null);

    /**
     * @return bool
     */
    abstract public function isObjectFormatter();

    /**
     * @throws \Propel\Runtime\Exception\PropelException
     *
     * @return void
     */
    public function checkInit()
    {
        if ($this->tableMap === null) {
            throw new PropelException('You must initialize a formatter object before calling format() or formatOne()');
        }
    }

    /**
     * @return \Propel\Runtime\Map\TableMap
     */
    public function getTableMap()
    {
        return Propel::getServiceContainer()->getDatabaseMap($this->dbName)->getTableByPhpName($this->class);
    }

    /**
     * @return bool
     */
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
     * Gets a Propel object hydrated from a selection of columns in statement row
     *
     * @param array $row associative array indexed by column number,
     *                      as returned by DataFetcher::fetch()
     * @param string $class The classname of the object to create
     * @param int $col The start column for the hydration (modified)
     *
     * @return \Propel\Runtime\ActiveRecord\ActiveRecordInterface
     */
    public function getSingleObjectFromRow($row, $class, &$col = 0)
    {
        $obj = new $class();
        $col = $obj->hydrate($row, $col, false, $this->getDataFetcher()->getIndexType());

        return $obj;
    }
}

<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\Formatter;

use Propel\Runtime\ActiveQuery\BaseModelCriteria;
use Propel\Runtime\ActiveRecord\ActiveRecordInterface;
use Propel\Runtime\Collection\OnDemandCollection;
use Propel\Runtime\DataFetcher\DataFetcherInterface;
use Propel\Runtime\Exception\LogicException;
use ReflectionClass;

/**
 * Object formatter for Propel query
 * format() returns a OnDemandCollection that hydrates objects as the use iterates on the collection
 * This formatter consumes less memory than the ObjectFormatter, but doesn't use Instance Pool
 *
 * @author Francois Zaninotto
 */
class OnDemandFormatter extends ObjectFormatter
{
    /**
     * @var bool
     */
    protected $isSingleTableInheritance = false;

    /**
     * @param \Propel\Runtime\ActiveQuery\BaseModelCriteria|null $criteria
     * @param \Propel\Runtime\DataFetcher\DataFetcherInterface|null $dataFetcher
     *
     * @return $this
     */
    public function init(?BaseModelCriteria $criteria = null, ?DataFetcherInterface $dataFetcher = null)
    {
        parent::init($criteria, $dataFetcher);

        $this->isSingleTableInheritance = $criteria->getTableMap()->isSingleTableInheritance();

        return $this;
    }

    /**
     * @param \Propel\Runtime\DataFetcher\DataFetcherInterface|null $dataFetcher
     *
     * @throws \Propel\Runtime\Exception\LogicException
     *
     * @return \Propel\Runtime\Collection\OnDemandCollection
     */
    public function format(?DataFetcherInterface $dataFetcher = null): OnDemandCollection
    {
        $this->checkInit();
        if ($dataFetcher) {
            $this->setDataFetcher($dataFetcher);
        } else {
            $dataFetcher = $this->getDataFetcher();
        }

        if ($this->isWithOneToMany()) {
            throw new LogicException('OnDemandFormatter cannot hydrate related objects using a one-to-many relationship. Try removing with() from your query.');
        }

        $collection = $this->getCollection();
        $collection->initIterator($this, $dataFetcher);

        return $collection;
    }

    /**
     * @psalm-return class-string<\Propel\Runtime\Collection\OnDemandCollection>
     *
     * @return string
     */
    public function getCollectionClassName(): string
    {
        return '\Propel\Runtime\Collection\OnDemandCollection';
    }

    /**
     * @return \Propel\Runtime\Collection\OnDemandCollection
     */
    public function getCollection(): OnDemandCollection
    {
        $class = $this->getCollectionClassName();

        /** @var \Propel\Runtime\Collection\OnDemandCollection $collection */
        $collection = new $class();
        $collection->setModel($this->class);

        return $collection;
    }

    /**
     * Hydrates a series of objects from a result row
     * The first object to hydrate is the model of the Criteria
     * The following objects (the ones added by way of ModelCriteria::with()) are linked to the first one
     *
     * @param array $row associative array with data
     *
     * @return \Propel\Runtime\ActiveRecord\ActiveRecordInterface
     */
    public function getAllObjectsFromRow(array $row): ActiveRecordInterface
    {
        $col = 0;

        // main object
        $this->checkInit();
        /** @var \Propel\Runtime\Map\TableMap $tableMap */
        $tableMap = $this->tableMap;
        $class = $this->isSingleTableInheritance ? $tableMap::getOMClass($row, $col, false) : $this->class;
        $obj = $this->getSingleObjectFromRow($row, $class, $col);

        //TODO: is this var even useable?
        /** @var array<string, object> $hydrationChain */
        $hydrationChain = [];

        // related objects using 'with'
        foreach ($this->getWith() as $modelWith) {
            if ($modelWith->isSingleTableInheritance()) {
                /** @var class-string<object>|object $class */
                $class = $modelWith->getTableMap()::getOMClass($row, $col, false);
                $reflectionClass = new ReflectionClass($class);
                $class = $reflectionClass->getName();
                if ($reflectionClass->isAbstract()) {
                    $tableMapClass = "Map\\{$class}TableMap";
                    $col += $tableMapClass::NUM_COLUMNS;

                    continue;
                }
            } else {
                $class = $modelWith->getModelName();
            }
            $endObject = $this->getSingleObjectFromRow($row, $class, $col);
            if ($modelWith->isPrimary()) {
                $startObject = $obj;
            } elseif ($hydrationChain && isset($hydrationChain[$modelWith->getLeftPhpName()])) {
                $startObject = $hydrationChain[$modelWith->getLeftPhpName()];
            } else {
                continue;
            }
            // as we may be in a left join, the endObject may be empty
            // in which case it should not be related to the previous object
            if ($endObject->isPrimaryKeyNull()) {
                if ($modelWith->isAdd()) {
                    $initMethod = $modelWith->getInitMethod();
                    $startObject->$initMethod(false);
                }

                continue;
            }

            $hydrationChain[$modelWith->getRightPhpName()] = $endObject;
            $relationMethod = $modelWith->getRelationMethod();
            $startObject->$relationMethod($endObject);
        }
        foreach ($this->getAsColumns() as $alias => $clause) {
            $obj->setVirtualColumn($alias, $row[$col]);
            $col++;
        }

        return $obj;
    }
}

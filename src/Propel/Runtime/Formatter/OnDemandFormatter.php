<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\Formatter;

use Propel\Runtime\ActiveQuery\BaseModelCriteria;
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
     * @return array|\Propel\Runtime\Collection\Collection|\Propel\Runtime\Collection\OnDemandCollection
     */
    public function format(?DataFetcherInterface $dataFetcher = null)
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
     * @return string|null
     */
    public function getCollectionClassName()
    {
        return '\Propel\Runtime\Collection\OnDemandCollection';
    }

    /**
     * @return \Propel\Runtime\Collection\OnDemandCollection
     */
    public function getCollection()
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
    public function getAllObjectsFromRow($row)
    {
        $col = 0;

        // main object
        $class = $this->isSingleTableInheritance ? call_user_func([$this->tableMap, 'getOMClass'], $row, $col, false) : $this->class;
        $obj = $this->getSingleObjectFromRow($row, $class, $col);
        // related objects using 'with'
        foreach ($this->getWith() as $modelWith) {
            if ($modelWith->isSingleTableInheritance()) {
                $class = call_user_func([$modelWith->getTableMap(), 'getOMClass'], $row, $col, false);
                $refl = new ReflectionClass($class);
                if ($refl->isAbstract()) {
                    $col += constant('Map\\' . $class . 'TableMap::NUM_COLUMNS');

                    continue;
                }
            } else {
                $class = $modelWith->getModelName();
            }
            $endObject = $this->getSingleObjectFromRow($row, $class, $col);
            if ($modelWith->isPrimary()) {
                $startObject = $obj;
            } elseif (isset($hydrationChain)) {
                $startObject = $hydrationChain[$modelWith->getLeftPhpName()];
            } else {
                continue;
            }
            // as we may be in a left join, the endObject may be empty
            // in which case it should not be related to the previous object
            if ($endObject->isPrimaryKeyNull()) {
                if ($modelWith->isAdd()) {
                    call_user_func([$startObject, $modelWith->getInitMethod()], false);
                }

                continue;
            }
            if (isset($hydrationChain)) {
                $hydrationChain[$modelWith->getRightPhpName()] = $endObject;
            } else {
                $hydrationChain = [$modelWith->getRightPhpName() => $endObject];
            }
            call_user_func([$startObject, $modelWith->getRelationMethod()], $endObject);
        }
        foreach ($this->getAsColumns() as $alias => $clause) {
            $obj->setVirtualColumn($alias, $row[$col]);
            $col++;
        }

        return $obj;
    }
}

<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Runtime\Formatter;

use Propel\Runtime\ActiveRecord\ActiveRecordInterface;
use Propel\Runtime\Collection\Collection;
use Propel\Runtime\Collection\OnDemandCollection;
use Propel\Runtime\Exception\LogicException;
use Propel\Runtime\ActiveQuery\BaseModelCriteria;
use Propel\Runtime\DataFetcher\DataFetcherInterface;

/**
 * Object formatter for Propel query
 * format() returns a OnDemandCollection that hydrates objects as the use iterates on the collection
 * This formatter consumes less memory than the ObjectFormatter, but doesn't use Instance Pool
 *
 * @author Francois Zaninotto
 */
class OnDemandFormatter extends ObjectFormatter
{
    protected $isSingleEntityInheritance = false;

    public function init(BaseModelCriteria $criteria = null, DataFetcherInterface $dataFetcher = null)
    {
        parent::init($criteria, $dataFetcher);

        $this->isSingleEntityInheritance = $criteria->getEntityMap()->isSingleEntityInheritance();

        return $this;
    }

    public function format(DataFetcherInterface $dataFetcher = null)
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

    public function getCollectionClassName()
    {
        return '\Propel\Runtime\Collection\OnDemandCollection';
    }

    /**
     * @return OnDemandCollection
     */
    public function getCollection()
    {
        $class = $this->getCollectionClassName();

        /** @var OnDemandCollection $collection */
        $collection = new $class();
        $collection->setModel($this->getEntityName());

        return $collection;
    }

    /**
     * Hydrates a series of objects from a result row
     * The first object to hydrate is the model of the Criteria
     * The following objects (the ones added by way of ModelCriteria::with()) are linked to the first one
     *
     *  @param    array  $row associative array with data
     *
     * @return ActiveRecordInterface
     */
    public function getAllObjectsFromRow(array $row)
    {
        $col = 0;

        // main object
        $class = $this->isSingleEntityInheritance ? call_user_func(array($this->entityMap, 'getOMClass'), $row, $col, false) : $this->getEntityName();
        $obj = $this->getSingleObjectFromRow($row, $class, $col);
        // related objects using 'with'
        foreach ($this->getWith() as $modelWith) {
            if ($modelWith->isSingleEntityInheritance()) {
                $class = call_user_func(array($modelWith->getEntityMap(), 'getOMClass'), $row, $col, false);
                $refl = new \ReflectionClass($class);
                if ($refl->isAbstract()) {
                    $col += constant('Map\\' . $class . 'EntityMap::NUM_COLUMNS');
                    continue;
                }
            } else {
                $class = $modelWith->getModelName();
            }
            $endObject = $this->getSingleObjectFromRow($row, $class, $col);
            if ($modelWith->isPrimary()) {
                $startObject = $obj;
            } elseif (isset($hydrationChain)) {
                $startObject = $hydrationChain[$modelWith->getLeftName()];
            } else {
                continue;
            }
            // as we may be in a left join, the endObject may be empty
            // in which case it should not be related to the previous object
            if (null === $endObject || $endObject->isPrimaryKeyNull()) {
                if ($modelWith->isAdd()) {
                    call_user_func(array($startObject, $modelWith->getInitMethod()), false);
                }
                continue;
            }
            if (isset($hydrationChain)) {
                $hydrationChain[$modelWith->getRightName()] = $endObject;
            } else {
                $hydrationChain = array($modelWith->getRightName() => $endObject);
            }
            call_user_func(array($startObject, $modelWith->getRelationMethod()), $endObject);
        }
        foreach ($this->getAsFields() as $alias => $clause) {
            $obj->setVirtualField($alias, $row[$col]);
            $col++;
        }

        return $obj;
    }
}

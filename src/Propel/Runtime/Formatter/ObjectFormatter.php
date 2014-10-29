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
use Propel\Runtime\Exception\LogicException;
use Propel\Runtime\DataFetcher\DataFetcherInterface;

/**
 * Object formatter for Propel query
 * format() returns a ObjectCollection of Propel model objects
 *
 * @author Francois Zaninotto
 */
class ObjectFormatter extends AbstractFormatter
{
    public function format(DataFetcherInterface $dataFetcher = null)
    {
        $this->checkInit();
        if ($dataFetcher) {
            $this->setDataFetcher($dataFetcher);
        } else {
            $dataFetcher = $this->getDataFetcher();
        }

        $collection = $this->getCollection();

        if ($this->isWithOneToMany()) {
            if ($this->hasLimit) {
                throw new LogicException('Cannot use limit() in conjunction with with() on a one-to-many relationship. Please remove the with() call, or the limit() call.');
            }
            $pks = array();
            foreach ($dataFetcher as $row) {
                $object = $this->getAllObjectsFromRow($row);
                $pk     = $object->getPrimaryKey();
                if (!in_array($pk, $pks)) {
                    $collection[] = $object;
                    $pks[]        = $pk;
                }
            }
        } else {
            // only many-to-one relationships
            foreach ($dataFetcher as $row) {
                var_dump($row);
                $collection[] = $this->getAllObjectsFromRow($row);
            }
        }
        $dataFetcher->close();

        return $collection;
    }

    public function getCollectionClassName()
    {
        $collectionClass = $this->getClass().'Collection';
        if (class_exists($collectionClass)) {
            return $collectionClass;
        }

        return '\Propel\Runtime\Collection\ObjectCollection';
    }

    public function formatOne(DataFetcherInterface $dataFetcher = null)
    {
        $this->checkInit();
        $result = null;

        if ($dataFetcher) {
            $this->setDataFetcher($dataFetcher);
        } else {
            $dataFetcher = $this->getDataFetcher();
        }

        foreach ($dataFetcher as $row) {
            $result = $this->getAllObjectsFromRow($row);
        }

        return $result;
    }

    public function isObjectFormatter()
    {
        return true;
    }

    /**
     * Hydrates a series of objects from a result row
     * The first object to hydrate is the model of the Criteria
     * The following objects (the ones added by way of ModelCriteria::with()) are linked to the first one
     *
     * @param array $row associative array indexed by field number,
     *                   as returned by DataFetcher::fetch()
     *
     * @return ActiveRecordInterface
     */
    public function getAllObjectsFromRow($row)
    {
        // main object
        $columnIndex = 0;
        $obj = $this->getEntityMap()->populateObject($row, $columnIndex, $this->getDataFetcher()->getIndexType());

        // related objects added using with()
        foreach ($this->getWith() as $modelWith) {
            if (!$modelWith->getEntityMap()->isValidRow($row, $columnIndex)) {
                //left joins can be NULL
                continue;
            }

            $joinedObject = $modelWith->getEntityMap()->populateObject($row, $columnIndex, $this->getDataFetcher()->getIndexType());

            if (null !== $modelWith->getLeftName() && !isset($hydrationChain[$modelWith->getLeftName()])) {
                continue;
            }

            if ($modelWith->isPrimary()) {
                $startObject = $obj;
            } elseif (isset($hydrationChain)) {
                $startObject = $hydrationChain[$modelWith->getLeftName()];
            } else {
                continue;
            }
//            // as we may be in a left join, the endObject may be empty
//            // in which case it should not be related to the previous object
//            if (null === $joinedObject || $joinedObject->isPrimaryKeyNull()) {
//                if ($modelWith->isAdd()) {
//                    call_user_func(array($startObject, $modelWith->getInitMethod()), false);
//                }
//                continue;
//            }
            if (isset($hydrationChain)) {
                $hydrationChain[$modelWith->getRightName()] = $joinedObject;
            } else {
                $hydrationChain = array($modelWith->getRightName() => $joinedObject);
            }

            $writer = $this->getEntityMap()->getPropWriter();
            $writer($obj, $modelWith->getRelationName(), $joinedObject);

//            call_user_func(array($startObject, $modelWith->getRelationMethod()), $joinedObject);

//            if ($modelWith->isAdd()) {
//                call_user_func(array($startObject, $modelWith->getResetPartialMethod()), false);
//            }
        }

        // fields added using withField()
        foreach ($this->getAsFields() as $alias => $clause) {
            $obj->setVirtualField($alias, $row[$columnIndex]);
            $columnIndex++;
        }

        return $obj;
    }
}

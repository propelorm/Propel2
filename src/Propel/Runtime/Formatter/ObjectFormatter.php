<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Runtime\Formatter;

use Propel\Runtime\Exception\LogicException;
use Propel\Runtime\Connection\StatementInterface;
use Propel\Runtime\Map\TableMap;

/**
 * Object formatter for Propel query
 * format() returns a ObjectCollection of Propel model objects
 *
 * @author Francois Zaninotto
 */
class ObjectFormatter extends AbstractFormatter
{
    public function format(DataFetcher $dataFetcher = null)
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
            while ($row = $dataFetcher->fetch()) {
                $object = $this->getAllObjectsFromRow($row);
                $pk = $object->getPrimaryKey();
                if (!in_array($pk, $pks)) {
                    $collection[] = $object;
                    $pks[] = $pk;
                }
            }
        } else {
            // only many-to-one relationships
            while ($row = $dataFetcher->fetch()) {
                $collection[] =  $this->getAllObjectsFromRow($row);
            }
        }
        $dataFetcher->close();

        return $collection;
    }

    public function getCollectionClassName()
    {
        return '\Propel\Runtime\Collection\ObjectCollection';
    }

    public function formatOne(DataFetcher $dataFetcher = null)
    {
        $this->checkInit();
        $result = null;
        if ($dataFetcher) {
            $this->setDataFetcher($dataFetcher);
        } else {
            $dataFetcher = $this->getDataFetcher();
        }

        while ($row = $dataFetcher->fetch()) {
            $result = $this->getAllObjectsFromRow($row);
        }
        $dataFetcher->close();

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
     *  @param    array  $row associative array indexed by column number,
     *                   as returned by PDOStatement::fetch(PDO::FETCH_NUM)
     *
     * @return BaseObject
     */
    public function getAllObjectsFromRow($row)
    {
        // main object
        list($obj, $col) = call_user_func(
            array($this->getTableMap(), 'populateObject'),
            $row,
            0,
            $this->getDataFetcher()->getIndexType()
        );

        // related objects added using with()
        foreach ($this->getWith() as $modelWith) {
            list($endObject, $col) = call_user_func(array($modelWith->getModelPeerName(), 'populateObject'), $row, $col);

            if (null !== $modelWith->getLeftPhpName() && !isset($hydrationChain[$modelWith->getLeftPhpName()])) {
                continue;
            }

            if ($modelWith->isPrimary()) {
                $startObject = $obj;
            } elseif (isset($hydrationChain)) {
                $startObject = $hydrationChain[$modelWith->getLeftPhpName()];
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
                $hydrationChain[$modelWith->getRightPhpName()] = $endObject;
            } else {
                $hydrationChain = array($modelWith->getRightPhpName() => $endObject);
            }

            call_user_func(array($startObject, $modelWith->getRelationMethod()), $endObject);
        }

        // columns added using withColumn()
        foreach ($this->getAsColumns() as $alias => $clause) {
            $obj->setVirtualColumn($alias, $row[$col]);
            $col++;
        }

        return $obj;
    }
}

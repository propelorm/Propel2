<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\Formatter;

use Propel\Runtime\ActiveRecord\ActiveRecordInterface;
use Propel\Runtime\DataFetcher\DataFetcherInterface;
use Propel\Runtime\Exception\LogicException;

/**
 * Object formatter for Propel query
 * format() returns a ObjectCollection of Propel model objects
 *
 * @author Francois Zaninotto
 */
class ObjectFormatter extends AbstractFormatter
{
    /**
     * @var array
     */
    protected $objects = [];

    /**
     * @param \Propel\Runtime\DataFetcher\DataFetcherInterface|null $dataFetcher
     *
     * @throws \Propel\Runtime\Exception\LogicException
     *
     * @return \Propel\Runtime\Collection\Collection|array
     */
    public function format(?DataFetcherInterface $dataFetcher = null)
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
            foreach ($dataFetcher as $row) {
                $object = $this->getAllObjectsFromRow($row);
                $pk = $object->getPrimaryKey();
                $serializedPk = serialize($pk);

                if (!isset($this->objects[$serializedPk])) {
                    $this->objects[$serializedPk] = $object;
                    $collection[] = $object;
                }
            }
        } else {
            // only many-to-one relationships
            foreach ($dataFetcher as $row) {
                $collection[] = $this->getAllObjectsFromRow($row);
            }
        }
        $dataFetcher->close();

        return $collection;
    }

    /**
     * @return string|null
     */
    public function getCollectionClassName(): ?string
    {
        return $this->getTableMap()->getCollectionClassName();
    }

    /**
     * @param \Propel\Runtime\DataFetcher\DataFetcherInterface|null $dataFetcher
     *
     * @throws \Propel\Runtime\Exception\LogicException
     *
     * @return \Propel\Runtime\ActiveRecord\ActiveRecordInterface|null
     */
    public function formatOne(?DataFetcherInterface $dataFetcher = null): ?ActiveRecordInterface
    {
        $this->checkInit();
        $result = null;

        if ($this->isWithOneToMany() && $this->hasLimit) {
            throw new LogicException('Cannot use limit() in conjunction with with() on a one-to-many relationship. Please remove the with() call, or the limit() call.');
        }

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

    /**
     * @return bool
     */
    public function isObjectFormatter(): bool
    {
        return true;
    }

    /**
     * Hydrates a series of objects from a result row
     * The first object to hydrate is the model of the Criteria
     * The following objects (the ones added by way of ModelCriteria::with()) are linked to the first one
     *
     * @param array $row associative array indexed by column number,
     *                   as returned by DataFetcher::fetch()
     *
     * @return \Propel\Runtime\ActiveRecord\ActiveRecordInterface
     */
    public function getAllObjectsFromRow(array $row): ActiveRecordInterface
    {
        // main object
        [$obj, $col] = $this->getTableMap()->populateObject($row, 0, $this->getDataFetcher()->getIndexType());

        $pk = $obj->getPrimaryKey();
        $serializedPk = serialize($pk);

        if (isset($this->objects[$serializedPk])) {
            //if instance pooling is disabled, we need to make sure we're working on the correct (already fetched) object
            //so one-to-many relations are correctly loaded.
            $obj = $this->objects[$serializedPk];
        }

        //TODO: is this var even useable? populateObject() also seems dead.
        /** @var array<string, object> $hydrationChain */
        $hydrationChain = [];

        // related objects added using with()
        foreach ($this->getWith() as $modelWith) {
            [$endObject, $col] = $modelWith->getTableMap()->populateObject($row, $col, $this->getDataFetcher()->getIndexType());

            if ($modelWith->getLeftPhpName() !== null && !isset($hydrationChain[$modelWith->getLeftPhpName()])) {
                continue;
            }

            if ($modelWith->isPrimary()) {
                $startObject = $obj;
            } elseif ($hydrationChain && !empty($hydrationChain[$modelWith->getLeftPhpName()])) {
                $startObject = $hydrationChain[$modelWith->getLeftPhpName()];
            } else {
                continue;
            }

            // as we may be in a left join, the endObject may be empty
            // in which case it should not be related to the previous object
            if ($endObject === null || $endObject->isPrimaryKeyNull()) {
                if ($modelWith->isAdd()) {
                    $initMethod = $modelWith->getInitMethod();
                    $startObject->$initMethod(false);
                }

                continue;
            }

            $hydrationChain[$modelWith->getRightPhpName()] = $endObject;

            $relationMethod = $modelWith->getRelationMethod();
            $startObject->$relationMethod($endObject);

            if ($modelWith->isAdd()) {
                $resetPartialMethod = $modelWith->getResetPartialMethod();
                $startObject->$resetPartialMethod(false);
            }
        }

        // columns added using withColumn()
        foreach ($this->getAsColumns() as $alias => $clause) {
            $obj->setVirtualColumn($alias, $row[$col]);
            $col++;
        }

        return $obj;
    }
}

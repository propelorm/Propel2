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
use ReflectionClass;

/**
 * Array formatter for Propel query
 * format() returns a ArrayCollection of associative arrays
 *
 * @author Francois Zaninotto
 */
class ArrayFormatter extends AbstractFormatter
{
    /**
     * @var array
     */
    protected $alreadyHydratedObjects = [];

    /**
     * @var mixed
     */
    protected $emptyVariable;

    /**
     * @param \Propel\Runtime\DataFetcher\DataFetcherInterface|null $dataFetcher
     *
     * @throws \Propel\Runtime\Exception\LogicException
     *
     * @return array|\Propel\Runtime\Collection\Collection
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

        if ($this->isWithOneToMany() && $this->hasLimit) {
            throw new LogicException('Cannot use limit() in conjunction with with() on a one-to-many relationship. Please remove the with() call, or the limit() call.');
        }

        $items = [];
        foreach ($dataFetcher as $row) {
            $object = &$this->getStructuredArrayFromRow($row);
            if ($object) {
                $items[] = &$object;
            }
        }

        foreach ($items as $item) {
            $collection[] = $item;
        }

        $this->currentObjects = [];
        $this->alreadyHydratedObjects = [];
        $dataFetcher->close();

        return $collection;
    }

    /**
     * @return string|null
     */
    public function getCollectionClassName()
    {
        return '\Propel\Runtime\Collection\ArrayCollection';
    }

    /**
     * @param \Propel\Runtime\DataFetcher\DataFetcherInterface|null $dataFetcher
     *
     * @throws \Propel\Runtime\Exception\LogicException
     *
     * @return array|null
     */
    public function formatOne(?DataFetcherInterface $dataFetcher = null)
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
            $object = &$this->getStructuredArrayFromRow($row);
            if ($object) {
                $result = &$object;
            }
        }
        $this->currentObjects = [];
        $this->alreadyHydratedObjects = [];
        $dataFetcher->close();

        return $result;
    }

    /**
     * Formats an ActiveRecord object
     *
     * @param \Propel\Runtime\ActiveRecord\ActiveRecordInterface|null $record the object to format
     *
     * @return array The original record turned into an array
     */
    public function formatRecord(?ActiveRecordInterface $record = null)
    {
        return $record ? $record->toArray() : [];
    }

    /**
     * @return bool
     */
    public function isObjectFormatter()
    {
        return false;
    }

    /**
     * Hydrates a series of objects from a result row
     * The first object to hydrate is the model of the Criteria
     * The following objects (the ones added by way of ModelCriteria::with()) are linked to the first one
     *
     * @param array $row associative array indexed by column number,
     *                   as returned by DataFetcher::fetch()
     *
     * @return array
     */
    public function &getStructuredArrayFromRow($row)
    {
        $col = 0;

        // hydrate main object or take it from registry
        $mainObjectIsNew = false;
        $tableMap = $this->tableMap;
        $mainKey = $tableMap::getPrimaryKeyHashFromRow($row, 0, $this->getDataFetcher()->getIndexType());
        // we hydrate the main object even in case of a one-to-many relationship
        // in order to get the $col variable increased anyway
        $obj = $this->getSingleObjectFromRow($row, $this->class, $col);

        if (!isset($this->alreadyHydratedObjects[$this->class][$mainKey])) {
            $this->alreadyHydratedObjects[$this->class][$mainKey] = $obj->toArray();
            $mainObjectIsNew = true;
        }

        $hydrationChain = [];

        // related objects added using with()
        foreach ($this->getWith() as $relAlias => $modelWith) {
            // determine class to use
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

            // hydrate related object or take it from registry
            $key = call_user_func(
                [$modelWith->getTableMap(), 'getPrimaryKeyHashFromRow'],
                $row,
                $col,
                $this->getDataFetcher()->getIndexType()
            );
            // we hydrate the main object even in case of a one-to-many relationship
            // in order to get the $col variable increased anyway
            $secondaryObject = $this->getSingleObjectFromRow($row, $class, $col);
            if (!isset($this->alreadyHydratedObjects[$relAlias][$key])) {
                if ($secondaryObject->isPrimaryKeyNull()) {
                    $this->alreadyHydratedObjects[$relAlias][$key] = [];
                } else {
                    $this->alreadyHydratedObjects[$relAlias][$key] = $secondaryObject->toArray();
                }
            }

            if ($modelWith->isPrimary()) {
                $arrayToAugment = &$this->alreadyHydratedObjects[$this->class][$mainKey];
            } else {
                $arrayToAugment = &$hydrationChain[$modelWith->getLeftPhpName()];
            }

            if ($modelWith->isAdd()) {
                if (
                    !isset($arrayToAugment[$modelWith->getRelationName()]) ||
                    !in_array(
                        $this->alreadyHydratedObjects[$relAlias][$key],
                        $arrayToAugment[$modelWith->getRelationName()],
                        true
                    )
                ) {
                    $arrayToAugment[$modelWith->getRelationName()][] = &$this->alreadyHydratedObjects[$relAlias][$key];
                }
            } else {
                $arrayToAugment[$modelWith->getRelationName()] = &$this->alreadyHydratedObjects[$relAlias][$key];
            }

            $hydrationChain[$modelWith->getRightPhpName()] = &$this->alreadyHydratedObjects[$relAlias][$key];
        }

        // columns added using withColumn()
        foreach ($this->getAsColumns() as $alias => $clause) {
            $this->alreadyHydratedObjects[$this->class][$mainKey][$alias] = $row[$col];
            $col++;
        }

        if ($mainObjectIsNew) {
            return $this->alreadyHydratedObjects[$this->class][$mainKey];
        }

        // we still need to return a reference to something to avoid a warning
        return $this->emptyVariable;
    }
}

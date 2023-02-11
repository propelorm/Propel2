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
 * Array formatter for Propel query
 * format() returns a ArrayCollection of associative arrays
 *
 * @author Francois Zaninotto
 */
class ArrayFormatter extends AbstractFormatterWithHydration
{
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
    public function getCollectionClassName(): ?string
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
    public function formatOne(?DataFetcherInterface $dataFetcher = null): ?array
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
    public function formatRecord(?ActiveRecordInterface $record = null): array
    {
        return $record ? $record->toArray() : [];
    }

    /**
     * @return bool
     */
    public function isObjectFormatter(): bool
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
    public function &getStructuredArrayFromRow(array $row): array
    {
        $result = &$this->hydratePropelObjectCollection($row);

        return $result;
    }
}

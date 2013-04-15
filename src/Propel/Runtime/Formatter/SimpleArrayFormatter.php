<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Runtime\Formatter;

use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\DataFetcher\DataFetcherInterface;

/**
 * Array formatter for Propel select query
 * format() returns a ArrayCollection of associative arrays, a string,
 * or an array
 *
 * @author Benjamin Runnels
 */
class SimpleArrayFormatter extends AbstractFormatter
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

        if ($this->isWithOneToMany() && $this->hasLimit) {
            throw new PropelException('Cannot use limit() in conjunction with with() on a one-to-many relationship. Please remove the with() call, or the limit() call.');
        }

        foreach ($dataFetcher as $row) {
            if (false !== $rowArray = $this->getStructuredArrayFromRow($row)) {
                $collection[] = $rowArray;
            }
        }
        $dataFetcher->close();

        return $collection;
    }

    public function getCollectionClassName()
    {
        return '\Propel\Runtime\Collection\ArrayCollection';
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
            if (false !== $rowArray = $this->getStructuredArrayFromRow($row)) {
                $result = $rowArray;
            }
        }
        $dataFetcher->close();

        return $result;
    }

    /**
     * Formats an ActiveRecord object
     *
     * @param BaseObject $record the object to format
     *
     * @return array The original record turned into an array
     */
    public function formatRecord($record = null)
    {
        return $record ? $record->toArray() : array();
    }

    public function isObjectFormatter()
    {
        return false;
    }

    public function getStructuredArrayFromRow($row)
    {
        $columnNames = array_keys($this->getAsColumns());
        if (count($columnNames) > 1 && count($row) > 1) {
            $finalRow = array();
            foreach ($row as $index => $value) {
                $finalRow[str_replace('"', '', $columnNames[$index])] = $value;
            }
        } else {
            $finalRow = $row[0];
        }

        return $finalRow;
    }
}

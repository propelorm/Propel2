<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\Collection;

use ArrayIterator;

/**
 * Iterator class for iterating over Collection data
 */
class CollectionIterator extends ArrayIterator
{
    /**
     * @var \Propel\Runtime\Collection\Collection
     */
    protected $collection;

    /**
     * @var array
     */
    protected $positions = [];

    /**
     * Constructor
     *
     * @param \Propel\Runtime\Collection\Collection $collection
     */
    public function __construct(Collection $collection)
    {
        parent::__construct($collection->getData());

        $this->collection = $collection;
        $this->refreshPositions();
    }

    /**
     * Returns the collection instance
     *
     * @return \Propel\Runtime\Collection\Collection
     */
    public function getCollection()
    {
        return $this->collection;
    }

    /**
     * Check if the collection is empty
     *
     * @return bool
     */
    public function isEmpty()
    {
        return $this->count() === 0;
    }

    /**
     * Gets the position of the internal pointer
     * This position can be later used in seek()
     *
     * @return int
     */
    public function getPosition()
    {
        if ($this->key() === null) {
            return 0;
        }

        return $this->positions[$this->key()];
    }

    /**
     * Move the internal pointer to the beginning of the list
     * And get the first element in the collection
     *
     * @return mixed
     */
    public function getFirst()
    {
        if ($this->isEmpty()) {
            return null;
        }
        $this->rewind();

        return $this->current();
    }

    /**
     * Check whether the internal pointer is at the beginning of the list
     *
     * @return bool
     */
    public function isFirst()
    {
        return $this->getPosition() === 0;
    }

    /**
     * Move the internal pointer backward
     * And get the previous element in the collection
     *
     * @return mixed
     */
    public function getPrevious()
    {
        if ($this->isFirst()) {
            return null;
        }

        $this->seek($this->getPosition() - 1);

        return $this->current();
    }

    /**
     * Get the current element in the collection
     *
     * @return mixed
     */
    public function getCurrent()
    {
        return $this->current();
    }

    /**
     * Move the internal pointer forward
     * And get the next element in the collection
     *
     * @return mixed
     */
    public function getNext()
    {
        $this->next();

        return $this->current();
    }

    /**
     * Move the internal pointer to the end of the list
     * And get the last element in the collection
     *
     * @return mixed
     */
    public function getLast()
    {
        if ($this->isEmpty()) {
            return null;
        }

        $this->seek(count($this->positions) - 1);

        return $this->current();
    }

    /**
     * Check whether the internal pointer is at the end of the list
     *
     * @return bool
     */
    public function isLast()
    {
        if ($this->isEmpty()) {
            // empty list... so yes, this is the last
            return true;
        }

        return $this->getPosition() === count($this->positions) - 1;
    }

    /**
     * Check if the current index is an odd integer
     *
     * @return bool
     */
    public function isOdd()
    {
        return (bool)($this->getPosition() % 2);
    }

    /**
     * Check if the current index is an even integer
     *
     * @return bool
     */
    public function isEven()
    {
        return !$this->isOdd();
    }

    /**
     * @param string $index
     * @param string $newval
     *
     * @return void
     */
    public function offsetSet($index, $newval)
    {
        $this->collection->offsetSet($index, $newval);
        parent::offsetSet($index, $newval);
        $this->refreshPositions();
    }

    /**
     * @param string $index
     *
     * @return void
     */
    public function offsetUnset($index)
    {
        $this->collection->offsetUnset($index);
        parent::offsetUnset($index);
        $this->refreshPositions();
    }

    /**
     * @param mixed $value
     *
     * @return void
     */
    public function append($value)
    {
        $this->collection->append($value);
        parent::append($value);
        $this->refreshPositions();
    }

    /**
     * @param int $sort_flags
     *
     * @return void
     */
    public function asort($sort_flags = SORT_REGULAR)
    {
        parent::asort($sort_flags);
        $this->refreshPositions();
    }

    /**
     * @param int $sort_flags
     *
     * @return void
     */
    public function ksort($sort_flags = SORT_REGULAR)
    {
        parent::ksort($sort_flags);
        $this->refreshPositions();
    }

    /**
     * @param string $cmp_function
     *
     * @return void
     */
    public function uasort($cmp_function)
    {
        parent::uasort($cmp_function);
        $this->refreshPositions();
    }

    /**
     * @param string $cmp_function
     *
     * @return void
     */
    public function uksort($cmp_function)
    {
        parent::uksort($cmp_function);
        $this->refreshPositions();
    }

    /**
     * @return void
     */
    public function natsort()
    {
        parent::natsort();
        $this->refreshPositions();
    }

    /**
     * @return void
     */
    public function natcasesort()
    {
        parent::natcasesort();
        $this->refreshPositions();
    }

    /**
     * @return void
     */
    private function refreshPositions()
    {
        $this->positions = array_flip(array_keys($this->getArrayCopy()));
    }
}

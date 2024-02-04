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
 *
 * @extends \ArrayIterator<(int|string), mixed>
 */
class CollectionIterator extends ArrayIterator implements IteratorInterface
{
    /**
     * @var \Propel\Runtime\Collection\Collection
     */
    protected Collection $collection;

    /**
     * @var array
     */
    protected array $positions = [];

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
    public function getCollection(): Collection
    {
        return $this->collection;
    }

    /**
     * Check if the collection is empty
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }

    /**
     * Gets the position of the internal pointer
     * This position can be later used in seek()
     *
     * @return int
     */
    public function getPosition(): int
    {
        if (!$this->key()) {
            return 0;
        }

        return $this->positions[$this->key()];
    }

    /**
     * Move the internal pointer to the beginning of the list
     * And get the first element in the collection
     *
     * @psalm-suppress ReservedWord
     *
     * @return mixed
     */
    #[\ReturnTypeWillChange]
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
    public function isFirst(): bool
    {
        return $this->getPosition() === 0;
    }

    /**
     * Move the internal pointer backward
     * And get the previous element in the collection
     *
     * @psalm-suppress ReservedWord
     *
     * @return mixed
     */
    #[\ReturnTypeWillChange]
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
     * @psalm-suppress ReservedWord
     *
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function getCurrent()
    {
        return $this->current();
    }

    /**
     * Move the internal pointer forward
     * And get the next element in the collection
     *
     * @psalm-suppress ReservedWord
     *
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function getNext()
    {
        $this->next();

        return $this->current();
    }

    /**
     * Move the internal pointer to the end of the list
     * And get the last element in the collection
     *
     * @psalm-suppress ReservedWord
     *
     * @return mixed
     */
    #[\ReturnTypeWillChange]
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
    public function isLast(): bool
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
    public function isOdd(): bool
    {
        return (bool)($this->getPosition() % 2);
    }

    /**
     * Check if the current index is an even integer
     *
     * @return bool
     */
    public function isEven(): bool
    {
        return !$this->isOdd();
    }

    /**
     * @param string $index
     * @param string $value
     *
     * @return void
     */
    public function offsetSet($index, $value): void
    {
        $this->collection->offsetSet($index, $value);
        parent::offsetSet($index, $value);
        $this->refreshPositions();
    }

    /**
     * @param string $index
     *
     * @return void
     */
    public function offsetUnset($index): void
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
    public function append($value): void
    {
        $this->collection->append($value);
        parent::append($value);
        $this->refreshPositions();
    }

    /**
     * @param int $flags Not used
     *
     * @return true
     */
    #[\ReturnTypeWillChange]
    public function asort(int $flags = SORT_REGULAR): bool
    {
        parent::asort();
        $this->refreshPositions();

        return true;
    }

    /**
     * @param int $flags Not used
     *
     * @return true
     */
    #[\ReturnTypeWillChange]
    public function ksort(int $flags = SORT_REGULAR): bool
    {
        parent::ksort();
        $this->refreshPositions();

        return true;
    }

    /**
     * @param callable $callback
     *
     * @return true
     */
    #[\ReturnTypeWillChange]
    public function uasort($callback): bool
    {
        parent::uasort($callback);
        $this->refreshPositions();

        return true;
    }

    /**
     * @param callable $callback
     *
     * @return true
     */
    #[\ReturnTypeWillChange]
    public function uksort($callback): bool
    {
        parent::uksort($callback);
        $this->refreshPositions();

        return true;
    }

    /**
     * @return true
     */
    #[\ReturnTypeWillChange]
    public function natsort(): bool
    {
        parent::natsort();
        $this->refreshPositions();

        return true;
    }

    /**
     * @return true
     */
    #[\ReturnTypeWillChange]
    public function natcasesort(): bool
    {
        parent::natcasesort();
        $this->refreshPositions();

        return true;
    }

    /**
     * @return void
     */
    private function refreshPositions(): void
    {
        $this->positions = array_flip(array_keys($this->getArrayCopy()));
    }
}

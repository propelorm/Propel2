<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\DataFetcher;

use Propel\Runtime\Map\TableMap;

/**
 * Class ArrayDataFetcher
 *
 * @package Propel\Runtime\Formatter
 */
class ArrayDataFetcher extends AbstractDataFetcher
{
    /**
     * @var string
     */
    protected $indexType = TableMap::TYPE_PHPNAME;

    /**
     * @return void
     */
    public function next(): void
    {
        if ($this->dataObject !== null) {
            next($this->dataObject);
        }
    }

    /**
     * @psalm-suppress ReservedWord
     *
     * @inheritDoc
     */
    #[\ReturnTypeWillChange]
    public function current()
    {
        return $this->dataObject === null ? null : current($this->dataObject);
    }

    /**
     * @return array|null
     */
    public function fetch(): ?array
    {
        $row = $this->valid() ? $this->current() : null;
        $this->next();

        return $row;
    }

    /**
     * @psalm-suppress ReservedWord
     *
     * @inheritDoc
     */
    #[\ReturnTypeWillChange]
    public function key()
    {
        return $this->dataObject === null ? null : key($this->dataObject);
    }

    /**
     * @inheritDoc
     */
    public function valid(): bool
    {
        return ($this->dataObject !== null && key($this->dataObject) !== null);
    }

    /**
     * @return void
     */
    public function rewind(): void
    {
        if ($this->dataObject === null) {
            return;
        }

        reset($this->dataObject);
    }

    /**
     * @inheritDoc
     */
    public function getIndexType(): string
    {
        return $this->indexType;
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return $this->dataObject === null ? 0 : count($this->dataObject);
    }

    /**
     * Sets the current index type.
     *
     * @param string $indexType one of TableMap::TYPE_*
     *
     * @return void
     */
    public function setIndexType(string $indexType): void
    {
        $this->indexType = $indexType;
    }

    /**
     * @return void
     */
    public function close(): void
    {
        $this->dataObject = null;
    }
}

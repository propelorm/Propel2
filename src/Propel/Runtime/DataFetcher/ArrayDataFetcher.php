<?php

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
     * {@inheritDoc}
     */
    public function next()
    {
        if (null !== $this->dataObject) {
            next($this->dataObject);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function current()
    {
        return null === $this->dataObject ? null : current($this->dataObject);
    }

    /**
     * {@inheritDoc}
     */
    public function fetch()
    {
        $row = $this->valid() ? $this->current() : null;
        $this->next();

        return $row;
    }

    /**
     * {@inheritDoc}
     */
    public function key()
    {
        return null === $this->dataObject ? null : key($this->dataObject);
    }

    /**
     * {@inheritDoc}
     */
    public function valid()
    {
        return (null !== $this->dataObject && null !== key($this->dataObject));
    }

    /**
     * {@inheritDoc}
     */
    public function rewind()
    {
        return null === $this->dataObject ? null : reset($this->dataObject);
    }

    /**
     * {@inheritDoc}
     */
    public function getIndexType()
    {
        return $this->indexType;
    }

    /**
     * {@inheritDoc}
     */
    public function count()
    {
        return null === $this->dataObject ? null : count($this->dataObject);
    }

    /**
     * Sets the current index type.
     *
     * @param string $indexType one of TableMap::TYPE_*
     */
    public function setIndexType($indexType)
    {
        $this->indexType = $indexType;
    }

    /**
     * {@inheritDoc}
     */
    public function close()
    {
        $this->dataObject = null;
    }
}

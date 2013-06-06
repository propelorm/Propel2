<?php

namespace Propel\Runtime\DataFetcher;

use Propel\Runtime\Map\TableMap;

/**
 * Class PDODataFetcher
 *
 * The PDO dataFetcher for PDOStatement.
 *
 * @package Propel\Runtime\Formatter
 */
class PDODataFetcher extends AbstractDataFetcher
{
    /**
     * @var array
     */
    private $current;

    /**
     * @var int
     */
    private $index = 0;

    /**
     * {@inheritDoc}
     */
    public function fetch($style = \PDO::FETCH_NUM)
    {
        return $this->getDataObject()->fetch($style);
    }

    /**
     * {@inheritDoc}
     */
    public function next()
    {
        if (null !== $this->dataObject) {
            $this->current = $this->dataObject->fetch(\PDO::FETCH_NUM);
            if ($this->current) {
                $this->index++;
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function current()
    {
        return $this->current;
    }

    /**
     * {@inheritDoc}
     */
    public function key()
    {
        return $this->index;
    }

    /**
     * {@inheritDoc}
     */
    public function valid()
    {
        return null !== $this->current && false !== $this->current;
    }

    /**
     * Not supported in PDODataFetcher.
     * It actually fetches the first row, since a foreach in php triggers that
     * function as init.
     */
    public function rewind()
    {
        $this->current = $this->dataObject->fetch(\PDO::FETCH_NUM);
    }

    /**
     * {@inheritDoc}
     */
    public function close()
    {
        $this->getDataObject()->closeCursor();
        $this->current = null;
        $this->index   = -1;
    }

    /**
     * {@inheritDoc}
     */
    public function count()
    {
        return $this->dataObject->rowCount();
    }

    /**
     * {@inheritDoc}
     */
    public function getIndexType()
    {
        return TableMap::TYPE_NUM;
    }

    /**
     * Bind a column to a PHP variable.
     *
     * @see http://www.php.net/manual/en/pdostatement.bindcolumn.php
     *
     * @param mixed $column
     * @param mixed $param
     * @param int   $type
     * @param int   $maxlen
     * @param mixed $driverdata
     */
    public function bindColumn($column, &$param, $type = null, $maxlen = null, $driverdata = null)
    {
        $this->dataObject->bindColumn($column, $param, $type, $maxlen, $driverdata);
    }
}

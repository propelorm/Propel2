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
     * For SQLITE rowCount emulation.
     * @var integer
     */
    private $cachedCount;

    /**
     * fetch style (default FETCH_NUM)
     * @var integer
     */
    private $style = \PDO::FETCH_NUM;

    /**
     * Sets a new fetch style (FETCH_NUM, FETCH_ASSOC or FETCH_BOTH). Returns previous fetch style.
     * @var integer
     */
    public function setStyle($style) {
        $old_style = $this->style;
        $this->style = $style;
        return $old_style;
    }

    /**
     * Returns current fetch style (FETCH_NUM, FETCH_ASSOC or FETCH_BOTH).
     * @var integer
     */
    public function getStyle() {
        return $this->style;
    }

    /**
     * {@inheritDoc}
     */
    public function fetch($style = null)
    {
        if (is_null($style)) {
            $style = $this->style;
        }
        return $this->getDataObject()->fetch($style);
    }

    /**
     * {@inheritDoc}
     */
    public function next()
    {
        if (null !== $this->dataObject) {
            $this->current = $this->dataObject->fetch($this->style);
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
        if ($this->dataObject)
            $this->current = $this->dataObject->fetch($this->style);
    }

    /**
     * {@inheritDoc}
     */
    public function close()
    {
        $this->getDataObject()->closeCursor();
        $this->setDataObject(null); //so the connection can be garbage collected
        $this->current = null;
        $this->index   = -1;
    }

    /**
     * {@inheritDoc}
     */
    public function count()
    {
        if ($this->dataObject && 'sqlite' === $this->dataObject->getConnection()->getAttribute(\PDO::ATTR_DRIVER_NAME)) {
            $lastQuery = $this->dataObject->getStatement()->queryString;
            if ('SELECT ' === substr(trim(strtoupper($lastQuery)), 0, 7)) {
                // SQLITE does not support rowCount() in 3.x on SELECTs anymore
                // so emulate it
                if (null === $this->cachedCount) {
                    $sql = sprintf("SELECT COUNT(*) FROM (%s)", $lastQuery);
                    $stmt = $this->dataObject->getConnection()->prepare($sql);
                    $stmt->execute($this->dataObject->getBoundValues());
                    $count = $stmt->fetchColumn();
                    $this->cachedCount = $count+0;
                }

                return $this->cachedCount;
            }
        }

        return ($this->dataObject ? $this->dataObject->rowCount() : 0);
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
        if ($this->dataObject)
            $this->dataObject->bindColumn($column, $param, $type, $maxlen, $driverdata);
    }
}

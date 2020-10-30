<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\DataFetcher;

use PDO;
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
     * @var array|null
     */
    private $current;

    /**
     * @var int
     */
    private $index = 0;

    /**
     * For SQLITE rowCount emulation.
     *
     * @var int
     */
    private $cachedCount;

    /**
     * fetch style (default FETCH_NUM)
     *
     * @var int
     */
    private $style = PDO::FETCH_NUM;

    /**
     * Sets a new fetch style (FETCH_NUM, FETCH_ASSOC or FETCH_BOTH). Returns previous fetch style.
     *
     * @param int $style
     *
     * @return int
     */
    public function setStyle($style)
    {
        $old_style = $this->style;
        $this->style = $style;

        return $old_style;
    }

    /**
     * Returns current fetch style (FETCH_NUM, FETCH_ASSOC or FETCH_BOTH).
     *
     * @return int
     */
    public function getStyle()
    {
        return $this->style;
    }

    /**
     * @param int|null $style
     *
     * @return array|null
     */
    public function fetch($style = null)
    {
        if ($style === null) {
            $style = $this->style;
        }

        return $this->getDataObject()->fetch($style);
    }

    /**
     * @return void
     */
    public function next()
    {
        if ($this->dataObject !== null) {
            $this->current = $this->dataObject->fetch($this->style);
            if ($this->current) {
                $this->index++;
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function current()
    {
        return $this->current;
    }

    /**
     * @inheritDoc
     */
    public function key()
    {
        return $this->index;
    }

    /**
     * @inheritDoc
     */
    public function valid()
    {
        return $this->current !== null && $this->current !== false;
    }

    /**
     * Not supported in PDODataFetcher.
     * It actually fetches the first row, since a foreach in php triggers that
     * function as init.
     *
     * @return void
     */
    public function rewind()
    {
        if ($this->dataObject) {
            $this->current = $this->dataObject->fetch($this->style);
        }
    }

    /**
     * @return void
     */
    public function close()
    {
        $this->getDataObject()->closeCursor();
        $this->setDataObject(null); //so the connection can be garbage collected
        $this->current = null;
        $this->index = -1;
    }

    /**
     * @inheritDoc
     */
    public function count()
    {
        if ($this->dataObject && $this->dataObject->getConnection()->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
            $lastQuery = $this->dataObject->getStatement()->queryString;
            if (substr(trim(strtoupper($lastQuery)), 0, 7) === 'SELECT ') {
                // SQLITE does not support rowCount() in 3.x on SELECTs anymore
                // so emulate it
                if ($this->cachedCount === null) {
                    $sql = sprintf('SELECT COUNT(*) FROM (%s)', $lastQuery);
                    $stmt = $this->dataObject->getConnection()->prepare($sql);
                    $stmt->execute($this->dataObject->getBoundValues());
                    $count = $stmt->fetchColumn();
                    $this->cachedCount = $count + 0;
                }

                return $this->cachedCount;
            }
        }

        return ($this->dataObject ? $this->dataObject->rowCount() : 0);
    }

    /**
     * @inheritDoc
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
     * @param int|null $type
     * @param int|null $maxlen
     * @param mixed $driverdata
     *
     * @return void
     */
    public function bindColumn($column, &$param, $type = null, $maxlen = null, $driverdata = null)
    {
        if ($this->dataObject) {
            $this->dataObject->bindColumn($column, $param, $type, $maxlen, $driverdata);
        }
    }
}

<?php

namespace Propel\Runtime\Formatter;

use Propel\Runtime\Map\TableMap;

class PDODataFetcher extends DataFetcher
{
    public function fetch($style = TableMap::TYPE_NUM)
    {
        if ($style == TableMap::TYPE_NUM) {
            $style = \PDO::FETCH_NUM;
        }

        return $this->getDataObject()->fetch($style);
    }

    public function close()
    {
        $this->getDataObject()->closeCursor();
    }

    public function count()
    {
        return $this->getDataObject()->rowCount();
    }

    public function getIndexType()
    {
        return TableMap::TYPE_NUM;
    }

    public function bindColumn($column, &$param, $type = null, $maxlen = null, $driverdata = null)
    {
        return $this->getDataObject()->bindColumn($column, $param, $type, $maxlen, $driverdata);
    }
}

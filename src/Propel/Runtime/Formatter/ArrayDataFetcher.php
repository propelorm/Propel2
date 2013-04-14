<?php

namespace Propel\Runtime\Formatter;

use Propel\Runtime\Map\TableMap;

class ArrayDataFetcher extends DataFetcher
{
    protected $indexType = TableMap::TYPE_PHPNAME;

    public function fetch()
    {
        if (is_array($this->dataObject)) {
            $row = current($this->dataObject);
            next($this->dataObject);
        }
    }

    public function getIndexType()
    {
        return $this->indexType;
    }

    public function count()
    {
        return count($this->indexType);
    }

    public function setIndexType($indexType)
    {
        $this->indexType = $indexType;
    }

    public function close()
    {
        reset($this->dataObject);
    }
}

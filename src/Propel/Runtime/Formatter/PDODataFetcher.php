<?php

namespace Propel\Runtime\Formatter;

use Propel\Runtime\Map\TableMap;

class PdoDataFetcher extends DataFetcher {
    public function fetch($style = \PDO::FETCH_NUM){
        return $this->getDataObject()->fetch($style);
    }

    public function close(){
        $this->getDataObject()->closeCursor();
    }

    public function getIndexType(){
        return TableMap::TYPE_NUM;
    }
}
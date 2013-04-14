<?php

namespace Propel\Runtime\Formatter;

use Propel\Runtime\Map\TableMap;

class MongoDataFetcher extends DataFetcher
{
    /**
     * @var MongoCursor
     */
    protected $dataObject;

    public function fetch()
    {
        if (is_array($this->dataObject)) {
            $row = current($this->dataObject);
            next($this->dataObject);
        } else {
            $row = $this->getDataObject()->getNext();
        }

        if ($row) {
            $row['Id'] = $row['_id']->{'$id'};
            unset($row['_id']);

            return $row;
        }
    }

    public function close()
    {
        if (is_array($this->dataObject)) {
            reset($this->dataObject);
        } else {
            $this->getDataObject()->reset();
        }
    }

    public function getIndexType()
    {
        return TableMap::TYPE_PHPNAME;
    }

    public function count()
    {
        return -1; //todo
    }
}

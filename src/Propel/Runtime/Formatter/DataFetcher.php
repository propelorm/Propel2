<?php

namespace Propel\Runtime\Formatter;

abstract class DataFetcher implements \IteratorAggregate
{
    protected $dataObject;

    public function __construct($dataObject)
    {
        $this->setDataObject($dataObject);
    }

    public function setDataObject($dataObject)
    {
        $this->dataObject = $dataObject;
    }

    public function getIterator()
    {
        return $this->dataObject;
    }

    public function getDataObject()
    {
        return $this->dataObject;
    }

    public function fetchColumn()
    {
        $next = $this->fetch();

        return $next ? current($next) : null;
    }

    abstract public function close();
    abstract public function count();
    abstract public function getIndexType();

}

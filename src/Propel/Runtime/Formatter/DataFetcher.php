<?php

namespace Propel\Runtime\Formatter;

abstract class DataFetcher {

    protected $dataObject;

    public function __construct($dataObject){
        $this->setDataObject($dataObject);
    }

    public function setDataObject($dataObject)
    {
        $this->dataObject = $dataObject;
    }

    public function getDataObject()
    {
        return $this->dataObject;
    }

    abstract public function close();
    abstract public function getIndexType();

}
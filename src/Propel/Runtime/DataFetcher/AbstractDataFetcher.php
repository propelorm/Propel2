<?php

namespace Propel\Runtime\DataFetcher;

/**
 * Abstract class for DataFetcher.
 */
abstract class AbstractDataFetcher implements DataFetcherInterface
{
    /**
     * @var mixed
     */
    protected $dataObject;

    /**
     * @param mixed $dataObject
     */
    public function __construct($dataObject)
    {
        $this->setDataObject($dataObject);
    }

    /**
     * {@inheritDoc}
     */
    public function setDataObject($dataObject)
    {
        $this->dataObject = $dataObject;
    }

    /**
     * {@inheritDoc}
     */
    public function getDataObject()
    {
        return $this->dataObject;
    }

    /**
     * {@inheritDoc}
     */
    public function fetchColumn($index = null)
    {
        $next = $this->fetch();

        if ($next) {
            return null === $index ? current($next) : (isset($next[$index]) ? $next[$index] : null);
        }
    }
}

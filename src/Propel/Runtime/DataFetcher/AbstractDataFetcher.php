<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\DataFetcher;

/**
 * Abstract class for DataFetcher.
 */
abstract class AbstractDataFetcher implements DataFetcherInterface
{
    /**
     * @var mixed|null
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
     * @param mixed|null $dataObject
     *
     * @return void
     */
    public function setDataObject($dataObject): void
    {
        $this->dataObject = $dataObject;
    }

    /**
     * @return mixed
     */
    public function getDataObject()
    {
        return $this->dataObject;
    }

    /**
     * @inheritDoc
     */
    public function fetchColumn($index = null)
    {
        /** @var array|null $next */
        $next = $this->fetch();

        if (!$next) {
            return null;
        }

        return $index === null ? current($next) : ($next[$index] ?? null);
    }
}

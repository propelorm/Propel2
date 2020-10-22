<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\Formatter;

use Propel\Runtime\ActiveRecord\ActiveRecordInterface;
use Propel\Runtime\DataFetcher\DataFetcherInterface;
use Propel\Runtime\Exception\PropelException;

/**
 * statement formatter for Propel query
 * format() returns a PDO statement
 *
 * @author Francois Zaninotto
 */
class StatementFormatter extends AbstractFormatter
{
    /**
     * @param \Propel\Runtime\DataFetcher\DataFetcherInterface|null $dataFetcher
     *
     * @return \Propel\Runtime\DataFetcher\DataFetcherInterface
     */
    public function format(?DataFetcherInterface $dataFetcher = null)
    {
        if ($dataFetcher) {
            $this->setDataFetcher($dataFetcher);
        } else {
            $dataFetcher = $this->getDataFetcher();
        }

        return $dataFetcher;
    }

    /**
     * @param \Propel\Runtime\DataFetcher\DataFetcherInterface|null $dataFetcher
     *
     * @return \Propel\Runtime\DataFetcher\DataFetcherInterface|null
     */
    public function formatOne(?DataFetcherInterface $dataFetcher = null)
    {
        if ($dataFetcher) {
            $this->setDataFetcher($dataFetcher);
        } else {
            $dataFetcher = $this->getDataFetcher();
        }

        return $dataFetcher->count() > 0 ? $dataFetcher : null;
    }

    /**
     * @param \Propel\Runtime\ActiveRecord\ActiveRecordInterface|null $record
     *
     * @throws \Propel\Runtime\Exception\PropelException
     *
     * @return \Propel\Runtime\ActiveRecord\ActiveRecordInterface|null|void
     */
    public function formatRecord(?ActiveRecordInterface $record = null)
    {
        throw new PropelException('The Statement formatter cannot transform a record into a statement');
    }

    /**
     * @return bool
     */
    public function isObjectFormatter()
    {
        return false;
    }
}

<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Runtime\Formatter;

use Propel\Runtime\ActiveRecord\ActiveRecordInterface;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\DataFetcher\DataFetcherInterface;

/**
 * statement formatter for Propel query
 * format() returns a PDO statement
 *
 * @author Francois Zaninotto
 */
class StatementFormatter extends AbstractFormatter
{
    public function format(DataFetcherInterface $dataFetcher = null)
    {
        if ($dataFetcher) {
            $this->setDataFetcher($dataFetcher);
        } else {
            $dataFetcher = $this->getDataFetcher();
        }

        return $dataFetcher;
    }

    public function formatOne(DataFetcherInterface $dataFetcher = null)
    {
        if ($dataFetcher) {
            $this->setDataFetcher($dataFetcher);
        } else {
            $dataFetcher = $this->getDataFetcher();
        }

        return $dataFetcher->count() > 0 ? $dataFetcher : null;
    }

    public function formatRecord(ActiveRecordInterface $record = null)
    {
        throw new PropelException('The Statement formatter cannot transform a record into a statement');
    }

    public function isObjectFormatter()
    {
        return false;
    }
}

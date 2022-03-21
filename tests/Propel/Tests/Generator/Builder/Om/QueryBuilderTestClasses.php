<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Builder\Om;

use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Tests\Bookstore\BookQuery;

class MyCustomBookQuery extends BookQuery
{
    public static function create(?string $modelAlias = null, ?Criteria $criteria = null): Criteria
    {
        if ($criteria instanceof MyCustomBookQuery) {
            return $criteria;
        }
        $query = new MyCustomBookQuery();
        if (null !== $modelAlias) {
            $query->setModelAlias($modelAlias);
        }
        if ($criteria !== null) {
            $query->mergeWith($criteria);
        }

        return $query;
    }
}

class MySecondBookQuery extends BookQuery
{
    public static $preSelectWasCalled = false;

    public function __construct($dbName = 'bookstore', $modelName = '\Propel\Tests\Bookstore\Book', $modelAlias = null)
    {
        self::$preSelectWasCalled = false;
        parent::__construct($dbName, $modelName, $modelAlias);
    }

    /**
     * @return void
     */
    public function preSelect(ConnectionInterface $con): void
    {
        self::$preSelectWasCalled = true;
    }
}

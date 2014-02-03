<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Builder\Om;

use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Tests\Bookstore\BookQuery;


class myCustomBookQuery extends BookQuery
{
    public static function create($modelAlias = null, Criteria $criteria = null)
    {
        if ($criteria instanceof myCustomBookQuery) {
            return $criteria;
        }
        $query = new myCustomBookQuery();
        if (null !== $modelAlias) {
            $query->setModelAlias($modelAlias);
        }
        if ($criteria instanceof Criteria) {
            $query->mergeWith($criteria);
        }

        return $query;
    }

}

class mySecondBookQuery extends BookQuery
{
    public static $preSelectWasCalled = false;

    public function __construct($dbName = 'bookstore', $modelName = '\Propel\Tests\Bookstore\Book', $modelAlias = null)
    {
        self::$preSelectWasCalled = false;
        parent::__construct($dbName, $modelName, $modelAlias);
    }

    public function preSelect(ConnectionInterface $con)
    {
        self::$preSelectWasCalled = true;
    }
}

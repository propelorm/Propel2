<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Runtime\ActiveQuery\QueryExecutor;

use Propel\Runtime\ActiveQuery\QueryExecutor\SelectQueryExecutor;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\ServiceContainer\ServiceContainerInterface;
use Propel\Tests\Bookstore\BookQuery;
use Propel\Tests\Helpers\Bookstore\BookstoreTestBase;

/**
 * @group database
 */
class SelectQueryExecutorTest extends BookstoreTestBase
{
    /**
     * @return void
     */
    public function testGetConnectionReturnsReadConnection(): void
    {
        $query = BookQuery::create();
        $executor = new class ($query) extends SelectQueryExecutor{
            public $isWriteConnection;

            protected function retrieveConnection(ServiceContainerInterface $sc, string $dbName, bool $getWritableConnection = false): ConnectionInterface
            {
                $this->isWriteConnection = $getWritableConnection;

                return parent::retrieveConnection($sc, $dbName, $getWritableConnection);
            }
        };

        $this->assertFalse($executor->isWriteConnection, 'SelectQueryExecutor should use a read connection');
    }
}

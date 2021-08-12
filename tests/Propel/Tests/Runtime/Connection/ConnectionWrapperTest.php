<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Runtime\Connection;

use Exception;
use Propel\Runtime\Connection\ConnectionWrapper;
use Propel\Tests\Helpers\Bookstore\BookstoreTestBase;

/**
 * @group database
 */
class ConnectionWrapperTest extends BookstoreTestBase
{
    /**
     * Make sure logging is done after execution, otherwise Profiler will give wrong data.
     *
     * @return void
     */
    public function testQueriesAreLoggedAfterExecution()
    {
        $wrapper = new class ($this->con) extends ConnectionWrapper{
            public $orderStack = [];

            public function pushToOrderStack(string $op)
            {
                $this->orderStack[] = $op;
            }

            public function log($msg)
            {
                $this->pushToOrderStack('log');
            }
        };
        $wrapper->callUserFunctionWithLogging([$wrapper, 'pushToOrderStack'], ['execute'], '');
        $this->assertEquals(['execute', 'log'], $wrapper->orderStack, 'Execute should run before logging');
    }

    /**
     * @return void
     */
    public function testQueryIsUnaffectedByDebugMode()
    {
        $con = new ConnectionWrapper($this->con->getWrappedConnection());

        $query = 'SELECT * FROM book';

        $con->useDebug(false);
        $resultWithoutDebug = null;
        try {
            $resultWithoutDebug = $con->query($query)->fetch();
        } catch (Exception $e) {
            $this->fail('Could not execute query with debug mode DISABLED: ' . $e->getMessage());
        }

        $con->useDebug(true);
        $resultWithDebug = null;
        try {
            $resultWithDebug = $con->query($query)->fetch();
        } catch (Exception $e) {
            $this->fail('Could not execute query with debug mode ENABLED: ' . $e->getMessage());
        }

        $this->assertEquals($resultWithoutDebug, $resultWithDebug);
    }

    /**
     * @return void
     */
    public function testExecuteRunsInDebugMode()
    {
        $this->assertExecSimpleInsertWithGivenDebugModeWorks('ENABLED', true);
    }

    /**
     * @return void
     */
    public function testExecuteRunsWithoutDebugMode()
    {
        $this->assertExecSimpleInsertWithGivenDebugModeWorks('DISABLED', false);
    }

    /**
     * @return void
     */
    public function assertExecSimpleInsertWithGivenDebugModeWorks(string $description, bool $debugMode): void
    {
        $con = new ConnectionWrapper($this->con->getWrappedConnection());

        $query = "INSERT INTO publisher(name) VALUES('Le Publisher')";

        $con->useDebug($debugMode);
        $affectedRows = -1;
        try {
            $affectedRows = $con->exec($query);
        } catch (Exception $e) {
            $this->fail("Could not execute query with debug mode $description: " . $e->getMessage());
        }

        $this->assertEquals(1, $affectedRows, "ConnectionWrapper::exec() should have inserted one rows with $description debug mode");
    }
}

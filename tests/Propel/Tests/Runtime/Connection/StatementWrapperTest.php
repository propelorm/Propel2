<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Runtime\Connection;

use Propel\Runtime\Connection\ConnectionWrapper;
use Propel\Tests\Helpers\Bookstore\BookstoreTestBase;
use Exception;

/**
 * @group database
 */
class StatementWrapperTest extends BookstoreTestBase
{
    /**
     * @return void
     */
    public function testExecuteRunsInDebugMode()
    {
        $exitCode = $this->executeSimpleQueryWithGivenDebugMode('ENABLED', true);
        $this->assertTrue($exitCode);
    }

    /**
     * @return void
     */
    public function testExecuteRunsWithoutDebugMode()
    {
        $exitCode = $this->executeSimpleQueryWithGivenDebugMode('DISABLED', false);
        $this->assertTrue($exitCode);
    }

    /**
     *
     * @return void
     */
    private function executeSimpleQueryWithGivenDebugMode(string $description, bool $debugMode): bool
    {
        $query = 'SELECT * FROM book';
        $wrapper = new ConnectionWrapper($this->con->getWrappedConnection());
        $wrapper->useDebug($debugMode);
        
        $stmt = $wrapper->prepare($query);
        try{
            return $stmt->execute();
        } catch (Exception $e) {
            $this->fail("Could not run query with debug mode $description: " . $e->getMessage());
        }
    }
}

<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Helpers\Bookstore;

use Propel\Runtime\Propel;
use Propel\Tests\Bookstore\Map\BookTableMap;
use Propel\Tests\TestCaseFixturesDatabase;

/**
 * Base class contains some methods shared by subclass test cases.
 */
abstract class BookstoreTestBase extends TestCaseFixturesDatabase
{
    /**
     * @var Boolean
     */
    protected static $isInitialized = false;
    /**
     * @var \PDO
     */
    protected $con;

    /**
     * This is run before each unit test; it populates the database.
     */
    protected function setUp()
    {
	    parent::setUp();
        if (true !== self::$isInitialized) {
            $file = __DIR__ . '/../../../../Fixtures/bookstore/build/conf/bookstore-conf.php';
            if (!file_exists($file)) {
                return;
            }
            Propel::init($file);
            self::$isInitialized = true;
        }
        $this->con = Propel::getServiceContainer()->getConnection(BookTableMap::DATABASE_NAME);
        $this->con->beginTransaction();
    }

    /**
     * This is run after each unit test. It empties the database.
     */
    protected function tearDown()
    {
        // Only commit if the transaction hasn't failed.
        // This is because tearDown() is also executed on a failed tests,
        // and we don't want to call ConnectionInterface::commit() in that case
        // since it will trigger an exception on its own
        // ('Cannot commit because a nested transaction was rolled back')
        if (null !== $this->con) {
            if ($this->con->isCommitable()) {
                $this->con->commit();
            }
            $this->con = null;
        }
    }

    public static function tearDownAfterClass()
    {
        Propel::getServiceContainer()->closeConnections();
    }
}

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

use Propel\Tests\Bookstore\BookPeer;

/**
 * Base class contains some methods shared by subclass test cases.
 */
abstract class BookstoreTestBase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Boolean
     */
    private static $isInitialized = false;
    /**
     * @var \PDO
     */
    protected $con;

    public static function setUpBeforeClass()
    {
        if (true !== self::$isInitialized) {
            Propel::init(__DIR__ . '/../../../../Fixtures/bookstore/build/conf/bookstore-conf.php');
            self::$isInitialized = true;
        }
    }

    /**
     * This is run before each unit test; it populates the database.
     */
    protected function setUp()
    {
        if (version_compare(PHP_VERSION, '5.3.6', '<')) {
            $adapterClass = Propel::getServiceContainer()->getAdapterClass(BookPeer::DATABASE_NAME);
            $propelConfig = Propel::getServiceContainer()->getConnectionManager(BookPeer::DATABASE_NAME)->getConfiguration();
            if (('mysql' == $adapterClass) && (isset($propelConfig['settings']['charset']))) {
                die('Connection option "charset" cannot be used for MySQL connections in PHP versions older than 5.3.6.
Please refer to http://www.propelorm.org/ticket/1360 for instructions and details
about the implications of using a SET NAMES statement in the "queries" setting.');
            }
        }
        $this->con = Propel::getServiceContainer()->getConnection(BookPeer::DATABASE_NAME);
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
        if ($this->con->isCommitable()) {
            $this->con->commit();
        }
        $this->con = null;
    }

    protected function getDriver()
    {
        return $this->con->getAttribute(\PDO::ATTR_DRIVER_NAME);
    }

    public static function tearDownAfterClass()
    {
        Propel::getServiceContainer()->closeConnections();
    }
}

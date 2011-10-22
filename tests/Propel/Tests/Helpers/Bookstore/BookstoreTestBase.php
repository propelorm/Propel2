<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Tests\Helpers\Bookstore;

use Propel\Runtime\Propel;

use Propel\Tests\Bookstore\BookPeer;

/**
 * Base class contains some methods shared by subclass test cases.
 */
abstract class BookstoreTestBase extends \PHPUnit_Framework_TestCase
{
    protected $con;

    public static function setUpBeforeClass()
    {
        Propel::init(__DIR__ . '/../../../../Fixtures/bookstore/build/conf/bookstore-conf.php');
    }

    /**
     * This is run before each unit test; it populates the database.
     */
    protected function setUp()
    {
        $this->con = Propel::getConnection(BookPeer::DATABASE_NAME);
        $this->con->beginTransaction();
    }

    /**
     * This is run after each unit test. It empties the database.
     */
    protected function tearDown()
    {
        parent::tearDown();
        // Only commit if the transaction hasn't failed.
        // This is because tearDown() is also executed on a failed tests,
        // and we don't want to call PropelPDO::commit() in that case
        // since it will trigger an exception on its own
        // ('Cannot commit because a nested transaction was rolled back')
        if ($this->con && $this->con->isCommitable()) {
            $this->con->commit();
        }
        $this->con = null;
    }
}

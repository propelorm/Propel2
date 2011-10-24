<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Tests\Helpers\Cms;

use Propel\Runtime\Propel;

use Propel\Tests\Bookstore\Cms\PagePeer;

/**
 * Base class contains some methods shared by subclass test cases.
 */
abstract class CmsTestBase extends \PHPUnit_Framework_TestCase
{
    static private $isInitialized = false;

    protected $con;

    static public function setUpBeforeClass()
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
        $this->markTestSkipped('Deprecated feature');

        $this->con = Propel::getConnection(PagePeer::DATABASE_NAME);
        $this->con->beginTransaction();

        CmsDataPopulator::depopulate($this->con);
        CmsDataPopulator::populate($this->con);
    }

    /**
     * This is run after each unit test.  It empties the database.
     */
    protected function tearDown()
    {
        if ($this->con) {
            CmsDataPopulator::depopulate($this->con);

            $this->con->commit();
            $this->con = null;
        }
    }
}

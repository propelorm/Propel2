<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Helpers\Bookstore;

use Propel\Tests\Bookstore\Map\BookEntityMap;
use Propel\Tests\TestCaseFixturesDatabase;

/**
 * Base class contains some methods shared by subclass test cases.
 */
abstract class BookstoreTestBase extends TestCaseFixturesDatabase
{
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
        $file = __DIR__ . '/../../../../Fixtures/bookstore/build/conf/bookstore-conf.php';
        $this->configuration = include $file;
    }
}

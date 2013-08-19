<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Helpers\Schemas;

use Propel\Runtime\Propel;
use Propel\Tests\TestCase;

/**
 * Bse class for tests on the schemas schema
 */
abstract class SchemasTestBase extends TestCase
{
    public static function setUpBeforeClass()
    {
        if (file_exists(dirname(__FILE__) . '/../../../../Fixtures/schemas/build/conf/bookstore-schemas-conf.php')) {
            Propel::init(dirname(__FILE__) . '/../../../../Fixtures/schemas/build/conf/bookstore-schemas-conf.php');
        }
    }

    protected function setUp()
    {
        if (!file_exists(dirname(__FILE__) . '/../../../../Fixtures/schemas/build/conf/bookstore-schemas-conf.php')) {
            $this->markTestSkipped('You must build the schemas project for this tests to run');
        }
    }

    protected function tearDown()
    {
    }

    public static function tearDownAfterClass()
    {
        Propel::getServiceContainer()->closeConnections();
        Propel::init(dirname(__FILE__) . '/../../../../Fixtures/bookstore/build/conf/bookstore-conf.php');
    }
}

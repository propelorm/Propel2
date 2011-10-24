<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Tests\Helpers\Namespaces;

use Propel\Runtime\Propel;

/**
 * Bse class for tests on the schemas schema
 */
abstract class NamespacesTestBase extends \PHPUnit_Framework_TestCase
{
    static private $isInitialized;

    protected $con;

    static public function setUpBeforeClass()
    {
        if (true !== self::$isInitialized) {
            if (file_exists(dirname(__FILE__) . '/../../../../Fixtures/namespaced/build/conf/bookstore_namespaced-conf.php')) {
                Propel::init(dirname(__FILE__) . '/../../../../Fixtures/namespaced/build/conf/bookstore_namespaced-conf.php');
                self::$isInitialized = true;
            }
        }
    }

    static public function tearDownAfterClass()
    {
        if (true === self::$isInitialized) {
            Propel::init(dirname(__FILE__) . '/../../../../Fixtures/bookstore/build/conf/bookstore-conf.php');
            self::$isInitialized = false;
        }
    }

    protected function setUp()
    {
        if (!file_exists(dirname(__FILE__) . '/../../../../Fixtures/namespaced/build/conf/bookstore_namespaced-conf.php')) {
            $this->markTestSkipped('You must build the namespaced project fot this tests to run');
        }
    }
}

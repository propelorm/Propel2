<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Helpers\Namespaces;

use Propel\Runtime\Propel;

/**
 * Bse class for tests on the schemas schema
 */
abstract class NamespacesTestBase extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        if (!file_exists(__DIR__ . '/../../../../Fixtures/namespaced/build/conf/bookstore_namespaced-conf.php')) {
            $this->markTestSkipped('You must build the namespaced project for this tests to run');
        }
    }

    protected function tearDown()
    {
        Propel::init(__DIR__ . '/../../../../Fixtures/bookstore/build/conf/bookstore-conf.php');
    }
}

<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Helpers\Namespaces;

use Propel\Runtime\Propel;
use Propel\Tests\TestCase;

/**
 * Bse class for tests on the schemas schema
 */
abstract class NamespacesTestBase extends TestCase
{
    /**
     * @return void
     */
    protected function setUp(): void
    {
        if (!file_exists(__DIR__ . '/../../../../Fixtures/namespaced/build/conf/bookstore_namespaced-conf.php')) {
            $this->markTestSkipped('You must build the namespaced project for this tests to run');
        }
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        Propel::init(__DIR__ . '/../../../../Fixtures/bookstore/build/conf/bookstore-conf.php');
    }
}

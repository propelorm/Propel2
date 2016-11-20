<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Helpers\Namespaces;

use Propel\Tests\TestCaseFixtures;

/**
 * Bse class for tests on the schemas schema
 */
abstract class NamespacesTestBase extends TestCaseFixtures
{
    protected function setUp()
    {
        parent::setUp();
        require __DIR__ . '/../../../../Fixtures/namespaced/build/conf/bookstore_namespaced-conf.php';
    }
}

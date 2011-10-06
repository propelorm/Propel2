<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Tests\Helpers\Schemas;

use Propel\Runtime\Propel;

/**
 * Bse class for tests on the schemas schema
 */
abstract class SchemasTestBase extends \PHPUnit_Framework_TestCase
{

	protected function setUp()
	{
		parent::setUp();
		if (!file_exists(__DIR__ . '/../../../../Fixtures/schemas/build/conf/bookstore-conf.php')) {
			$this->markTestSkipped('You must build the schemas project fot this tests to run');
		}
		Propel::init(dirname(__FILE__) . '/../../../../Fixtures/schemas/build/conf/bookstore-conf.php');
	}

	protected function tearDown()
	{
		parent::tearDown();
		Propel::init(dirname(__FILE__) . '/../../../../Fixtures/bookstore/build/conf/bookstore-conf.php');
	}
}

<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

require_once 'generator/platform/PlatformTestBase.php';
/**
 *
 * @package    generator.platform 
 */
class DefaultPlatformTest extends PlatformTestBase
{

	protected function setUp()
	{
		parent::setUp();
	}

	public function tearDown()
	{
		 parent::tearDown();
	}

	public function testQuote()
	{
		$p = $this->getPlatform();

		$unquoted = "Nice";
		$quoted = $p->quote($unquoted);

		$this->assertEquals("'$unquoted'", $quoted);


		$unquoted = "Naughty ' string";
		$quoted = $p->quote($unquoted);
		$expected = "'Naughty '' string'";
		$this->assertEquals($expected, $quoted);
	}

}

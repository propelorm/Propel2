<?php

require_once 'generator/engine/platform/PlatformTestBase.php';
/**
 *
 * @package    generator.engine.platform 
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

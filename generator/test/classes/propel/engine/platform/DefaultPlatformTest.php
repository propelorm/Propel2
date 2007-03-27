<?php

require_once 'propel/engine/platform/PlatformTestBase.php';

class DefaultPlatformTest extends PlatformTestBase {


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

<?php

require_once 'propel/engine/platform/DefaultPlatformTest.php';

class SqlitePlatformTest extends DefaultPlatformTest {

	/**
	 * @var        PDO The PDO connection to SQLite DB.
	 */
	private $pdo;

	protected function setUp()
	{
		parent::setUp();
		$this->pdo = new PDO("sqlite::memory:");

	}

	public function tearDown()
	{
		 parent::tearDown();
	}

	public function testQuoteConnected()
	{
		$p = $this->getPlatform();
		$p->setConnection($this->pdo);

		$unquoted = "Naughty ' string";
		$quoted = $p->quote($unquoted);

		$expected = "'Naughty '' string'";
		$this->assertEquals($expected, $quoted);
	}

}

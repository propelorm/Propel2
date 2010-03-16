<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

require_once 'generator/platform/DefaultPlatformTest.php';

/**
 * 
 * @package    generator.platform
 */
class SqlitePlatformTest extends DefaultPlatformTest
{
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

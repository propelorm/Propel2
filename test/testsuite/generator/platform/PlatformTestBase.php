<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

require_once 'PHPUnit/Framework/TestCase.php';

/**
 * 
 * @package    generator.platform
 */
class PlatformTestBase extends PHPUnit_Framework_TestCase
{
	/**
	 * Platform object.
	 *
	 * @var        Platform
	 */
	protected $platform;

	/**
	 *
	 */
	protected function setUp()
	{
		parent::setUp();

		$clazz = preg_replace('/Test$/', '', get_class($this));
		include_once 'platform/' . $clazz . '.php';
		$this->platform = new $clazz();
	}

	/**
	 *
	 */
	protected function tearDown()
	{
		parent::tearDown();
	}

	/**
	 *
	 * @return     Platform
	 */
	protected function getPlatform()
	{
		return $this->platform;
	}

}

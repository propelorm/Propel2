<?php

require_once 'PHPUnit/Framework/TestCase.php';

class PlatformTestBase extends PHPUnit_Framework_TestCase {

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
		include_once 'propel/engine/platform/' . $clazz . '.php';
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

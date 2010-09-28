<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

require_once 'PHPUnit/Framework.php';
require_once dirname(__FILE__) . '/../../../../generator/lib/model/Table.php';
require_once dirname(__FILE__) . '/../../../../generator/lib/builder/util/XmlToAppData.php';

/**
 * Base class for all Platform tests
 * @package    generator.platform
 */
abstract class PlatformTestBase extends PHPUnit_Framework_TestCase
{
	/**
	 * Platform object.
	 *
	 * @var        Platform
	 */
	protected $platform;

	protected function setUp()
	{
		parent::setUp();

		$clazz = preg_replace('/(Test|MigrationTest)$/', '', get_class($this));
		include_once dirname(__FILE__) . '/../../../../generator/lib/platform/' . $clazz . '.php';
		$this->platform = new $clazz();
	}

	/**
	 * Get the Platform object for this class
	 *
	 * @return     Platform
	 */
	protected function getPlatform()
	{
		return $this->platform;
	}

	protected function getDatabaseFromSchema($schema)
	{
		$xtad = new XmlToAppData($this->platform);
		$appData = $xtad->parseString($schema);
		return $appData->getDatabase();
	}
	
	protected function getTableFromSchema($schema, $tableName = 'foo')
	{
		return $this->getDatabaseFromSchema($schema)->getTable($tableName);
	}
	
}

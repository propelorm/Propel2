<?php

/*
 *	$Id$
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

require_once 'PHPUnit/Framework/TestCase.php';
require_once 'builder/util/XmlToAppData.php';
require_once 'platform/MysqlPlatform.php';
require_once 'config/GeneratorConfig.php';

/**
 * Tests for package handling.
 *
 * @author     Martin Poeschl (mpoeschl@marmot.at)
 * @version    $Revision$
 * @package    generator.model
 */
class TableTest extends PHPUnit_Framework_TestCase
{
	private $xmlToAppData;
	private $appData;

	/**
	 * test if the tables get the package name from the properties file
	 *
	 */
	public function testIdMethodHandling() {
		$this->xmlToAppData = new XmlToAppData(new MysqlPlatform(), "defaultpackage", null);

		//$this->appData = $this->xmlToAppData->parseFile(dirname(__FILE__) . "/tabletest-schema.xml");
		$this->appData = $this->xmlToAppData->parseFile("etc/schema/tabletest-schema.xml");

		$db = $this->appData->getDatabase("iddb");
		$expected = IDMethod::NATIVE;
		$result = $db->getDefaultIdMethod();
		$this->assertEquals($expected, $result);

		$table2 = $db->getTable("table_native");
		$expected = IDMethod::NATIVE;
		$result = $table2->getIdMethod();
		$this->assertEquals($expected, $result);

		$table = $db->getTable("table_none");
		$expected = IDMethod::NO_ID_METHOD;
		$result = $table->getIdMethod();
		$this->assertEquals($expected, $result);
	}
	
	public function testGeneratorConfig()
	{
		$xmlToAppData = new XmlToAppData(new MysqlPlatform(), "defaultpackage", null);
		$appData = $xmlToAppData->parseFile('fixtures/bookstore/behavior-timestampable-schema.xml');
		$table = $appData->getDatabase("bookstore-behavior")->getTable('table1');
		$config = new GeneratorConfig();
		$config->setBuildProperties(array('propel.foo.bar.class' => 'bazz'));
		$table->getDatabase()->getAppData()->getPlatform()->setGeneratorConfig($config);
		$this->assertThat($table->getGeneratorConfig(), $this->isInstanceOf('GeneratorConfig'), 'getGeneratorConfig() returns an instance of the generator configuration');
		$this->assertEquals($table->getGeneratorConfig()->getBuildProperty('fooBarClass'), 'bazz', 'getGeneratorConfig() returns the instance of the generator configuration used in the platform');
	}
	
	public function testAddBehavior()
	{
		$platform = new MysqlPlatform();
		$config = new GeneratorConfig();
		$config->setBuildProperties(array(
			'propel.behavior.timestampable.class' => 'behavior.TimestampableBehavior'
		));
		$platform->setGeneratorConfig($config);
		$xmlToAppData = new XmlToAppData($platform, "defaultpackage", null);
		$appData = $xmlToAppData->parseFile('fixtures/bookstore/behavior-timestampable-schema.xml');
		$table = $appData->getDatabase("bookstore-behavior")->getTable('table1');
		$this->assertThat($table->getBehavior('timestampable'), $this->isInstanceOf('TimestampableBehavior'), 'addBehavior() uses the behavior class defined in build.properties');
	}
	
	public function testUniqueColumnName()
	{
		$platform = new MysqlPlatform();
		$config = new GeneratorConfig();
		$platform->setGeneratorConfig($config);
		$xmlToAppData = new XmlToAppData($platform, 'defaultpackage', null);
		try
		{
			$appData = $xmlToAppData->parseFile('fixtures/unique-column/column-schema.xml');
			$this->fail('Parsing file with duplicate column names in one table throws exception');
		} catch (EngineException $e) {
			$this->assertTrue(true, 'Parsing file with duplicate column names in one table throws exception');
		}
	}
	
	public function testUniqueTableName()
	{
		$platform = new MysqlPlatform();
		$config = new GeneratorConfig();
		$platform->setGeneratorConfig($config);
		$xmlToAppData = new XmlToAppData($platform, 'defaultpackage', null);
		try {
			$appData = $xmlToAppData->parseFile('fixtures/unique-column/table-schema.xml');
			$this->fail('Parsing file with duplicate table name throws exception');
		} catch (EngineException $e) {
			$this->assertTrue(true, 'Parsing file with duplicate table name throws exception');
		}
	}
}

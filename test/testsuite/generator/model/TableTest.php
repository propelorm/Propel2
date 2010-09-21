<?php

/*
 *	$Id$
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

require_once 'PHPUnit/Framework.php';
require_once dirname(__FILE__) . '/../../../../generator/lib/builder/util/XmlToAppData.php';
require_once dirname(__FILE__) . '/../../../../generator/lib/config/GeneratorConfig.php';
require_once dirname(__FILE__) . '/../../../../generator/lib/platform/DefaultPlatform.php';

/**
 * Tests for package handling.
 *
 * @author     Martin Poeschl (mpoeschl@marmot.at)
 * @version    $Revision$
 * @package    generator.model
 */
class TableTest extends PHPUnit_Framework_TestCase
{

	/**
	 * test if the tables get the package name from the properties file
	 *
	 */
	public function testIdMethodHandling()
	{
		$xmlToAppData = new XmlToAppData();
		$schema = <<<EOF
<database name="iddb" defaultIdMethod="native">
  <table name="table_native">
    <column name="table_a_id" required="true" autoIncrement="true" primaryKey="true" type="INTEGER" />
    <column name="col_a" type="CHAR" size="5" />
  </table>
  <table name="table_none" idMethod="none">
    <column name="table_a_id" required="true" primaryKey="true" type="INTEGER" />
    <column name="col_a" type="CHAR" size="5" />
  </table>
</database>
EOF;
		$appData = $xmlToAppData->parseString($schema);

		$db = $appData->getDatabase("iddb");
		$this->assertEquals(IDMethod::NATIVE, $db->getDefaultIdMethod());

		$table1 = $db->getTable("table_native");
		$this->assertEquals(IDMethod::NATIVE, $table1->getIdMethod());

		$table2 = $db->getTable("table_none");
		$this->assertEquals(IDMethod::NO_ID_METHOD, $table2->getIdMethod());
	}
	
	public function testGeneratorConfig()
	{
		$xmlToAppData = new XmlToAppData();
		$schema = <<<EOF
<database name="test1">
  <table name="table1">
    <column name="id" type="INTEGER" primaryKey="true" />
  </table>
</database>
EOF;
		$appData = $xmlToAppData->parseString($schema);
		$table = $appData->getDatabase('test1')->getTable('table1');
		$config = new GeneratorConfig();
		$config->setBuildProperties(array('propel.foo.bar.class' => 'bazz'));
		$table->getDatabase()->getAppData()->setGeneratorConfig($config);
		$this->assertThat($table->getGeneratorConfig(), $this->isInstanceOf('GeneratorConfig'), 'getGeneratorConfig() returns an instance of the generator configuration');
		$this->assertEquals($table->getGeneratorConfig()->getBuildProperty('fooBarClass'), 'bazz', 'getGeneratorConfig() returns the instance of the generator configuration used in the platform');
	}
	
	public function testAddBehavior()
	{
		$include_path = get_include_path();
		set_include_path($include_path . PATH_SEPARATOR . realpath(dirname(__FILE__) . '/../../../../generator/lib'));
		$xmlToAppData = new XmlToAppData(new DefaultPlatform());
		$config = new GeneratorConfig();
		$config->setBuildProperties(array(
			'propel.platform.class' => 'propel.engine.platform.DefaultPlatform',
			'propel.behavior.timestampable.class' => 'behavior.TimestampableBehavior'
		));
		$xmlToAppData->setGeneratorConfig($config);
		$schema = <<<EOF
<database name="test1">
  <table name="table1">
    <behavior name="timestampable" />
    <column name="id" type="INTEGER" primaryKey="true" />
  </table>
</database>
EOF;
		$appData = $xmlToAppData->parseString($schema);
		set_include_path($include_path);
		$table = $appData->getDatabase('test1')->getTable('table1');
		$this->assertThat($table->getBehavior('timestampable'), $this->isInstanceOf('TimestampableBehavior'), 'addBehavior() uses the behavior class defined in build.properties');
	}
	
	/**
	 * @expectedException EngineException
	 */
	public function testUniqueColumnName()
	{
		$xmlToAppData = new XmlToAppData();
		$schema = <<<EOF
<database name="columnTest" defaultIdMethod="native">
	<table name="columnTestTable">
		<column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" description="Book Id" />
		<column name="title" type="VARCHAR" required="true" description="Book Title" />
		<column name="title" type="VARCHAR" required="true" description="Book Title" />
	</table>
</database>
EOF;
		// Parsing file with duplicate column names in one table throws exception
		$appData = $xmlToAppData->parseString($schema);
	}
	
	/**
	 * @expectedException EngineException
	 */
	public function testUniqueTableName()
	{
		$xmlToAppData = new XmlToAppData();
		$schema = <<<EOF
<database name="columnTest" defaultIdMethod="native">
	<table name="columnTestTable">
		<column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" description="Book Id" />
		<column name="title" type="VARCHAR" required="true" description="Book Title" />
	</table>
	<table name="columnTestTable">
		<column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" description="Book Id" />
		<column name="title" type="VARCHAR" required="true" description="Book Title" />
	</table>
</database>
EOF;
		// Parsing file with duplicate table name throws exception
		$appData = $xmlToAppData->parseString($schema);
	}
}

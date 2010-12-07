<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

require_once 'PHPUnit/Framework.php';
require_once dirname(__FILE__) . '/../../../../generator/lib/model/Column.php';
require_once dirname(__FILE__) . '/../../../../generator/lib/builder/util/XmlToAppData.php';
require_once dirname(__FILE__) . '/../../../../generator/lib/platform/DefaultPlatform.php';

/**
 * Tests for package handling.
 *
 * @author     <a href="mailto:mpoeschl@marmot.at>Martin Poeschl</a>
 * @version    $Revision$
 * @package    generator.model
 */
class ColumnTest extends PHPUnit_Framework_TestCase
{

	/**
	 * Tests static Column::makeList() method.
	 * @deprecated - Column::makeList() is deprecated and set to be removed in 1.3
	 */
	public function testMakeList()
	{
		$expected = '"Column0", "Column1", "Column2", "Column3", "Column4"';
		$objArray = array();
		for ($i=0; $i<5; $i++) {
			$c = new Column();
			$c->setName("Column" . $i);
			$objArray[] = $c;
		}

		$list = Column::makeList($objArray, new DefaultPlatform());
		$this->assertEquals($expected, $list, sprintf("Expected '%s' match, got '%s' ", var_export($expected, true), var_export($list,true)));

		$strArray = array();
		for ($i=0; $i<5; $i++) {
			$strArray[] = "Column" . $i;
		}

		$list = Column::makeList($strArray, new DefaultPlatform());
		$this->assertEquals($expected, $list, sprintf("Expected '%s' match, got '%s' ", var_export($expected, true), var_export($list,true)));

	}
	
	public function testPhpNamingMethod()
	{
		$xmlToAppData = new XmlToAppData(new DefaultPlatform());
		$schema = <<<EOF
<database name="test1">
  <behavior name="auto_add_pk" />
  <table name="table1">
    <column name="id" type="INTEGER" primaryKey="true" />
    <column name="author_id" type="INTEGER" />
    <column name="editor_id" type="INTEGER" phpNamingMethod="nochange" />
  </table>
</database>
EOF;
		$appData = $xmlToAppData->parseString($schema);
		$column = $appData->getDatabase('test1')->getTable('table1')->getColumn('author_id');
	  $this->assertEquals('AuthorId', $column->getPhpName(), 'setPhpName() uses the default phpNamingMethod');
		$column = $appData->getDatabase('test1')->getTable('table1')->getColumn('editor_id');
	  $this->assertEquals('editor_id', $column->getPhpName(), 'setPhpName() uses the column phpNamingMethod if given');
  }
  
	public function testDefaultPhpNamingMethod()
	{
		$xmlToAppData = new XmlToAppData(new DefaultPlatform());
		$schema = <<<EOF
<database name="test2" defaultPhpNamingMethod="nochange">
  <behavior name="auto_add_pk" />
  <table name="table1">
    <column name="id" primaryKey="true" />
    <column name="author_id" type="VARCHAR" />
  </table>
</database>
EOF;
		$appData = $xmlToAppData->parseString($schema);
		$column = $appData->getDatabase('test2')->getTable('table1')->getColumn('author_id');
	  $this->assertEquals('author_id', $column->getPhpName(), 'setPhpName() uses the database defaultPhpNamingMethod if given');
	}
	
	public function testGetConstantName()
	{
		$xmlToAppData = new XmlToAppData(new DefaultPlatform());
		$schema = <<<EOF
<database name="test">
  <table name="table1">
    <column name="id" primaryKey="true" />
    <column name="title" type="VARCHAR" />
  </table>
</database>
EOF;
    $appData = $xmlToAppData->parseString($schema);
    $column = $appData->getDatabase('test')->getTable('table1')->getColumn('title');
    $this->assertEquals('Table1Peer::TITLE', $column->getConstantName(), 'getConstantName() returns the complete constant name by default');
	}
	
	public function testIsLocalColumnsRequired()
	{
		$xmlToAppData = new XmlToAppData(new DefaultPlatform());
		$schema = <<<EOF
<database name="test">
  <table name="table1">
    <column name="id" primaryKey="true" />
    <column name="table2_foo" type="VARCHAR" />
    <foreign-key foreignTable="table2">
      <reference local="table2_foo" foreign="foo"/>
    </foreign-key>
    <column name="table2_bar" required="true" type="VARCHAR" />
    <foreign-key foreignTable="table2">
      <reference local="table2_bar" foreign="bar"/>
    </foreign-key>
  </table>
  <table name="table2">
    <column name="id" primaryKey="true" />
    <column name="foo" type="VARCHAR" />
    <column name="bar" type="VARCHAR" />
  </table>
</database>
EOF;
		$appData = $xmlToAppData->parseString($schema);
		$fk = $appData->getDatabase('test')->getTable('table1')->getColumnForeignKeys('table2_foo');
		$this->assertFalse($fk[0]->isLocalColumnsRequired());
		$fk = $appData->getDatabase('test')->getTable('table1')->getColumnForeignKeys('table2_bar');
		$this->assertTrue($fk[0]->isLocalColumnsRequired());
	}
	
	public function testIsNamePlural()
	{
		$column = new Column('foo');
		$this->assertFalse($column->isNamePlural());
		$column = new Column('foos');
		$this->assertTrue($column->isNamePlural());
		$column = new Column('foso');
		$this->assertFalse($column->isNamePlural());
	}

	public function testGetSingularName()
	{
		$column = new Column('foo');
		$this->assertEquals('foo', $column->getSingularName());
		$column = new Column('foos');
		$this->assertEquals('foo', $column->getSingularName());
		$column = new Column('foso');
		$this->assertEquals('foso', $column->getSingularName());
	}

}

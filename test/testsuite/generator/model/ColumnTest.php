<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

require_once 'PHPUnit/Framework/TestCase.php';
require_once 'model/Column.php';
require_once 'builder/util/XmlToAppData.php';
require_once 'platform/MysqlPlatform.php';


/**
 * Tests for package handling.
 *
 * @author     <a href="mailto:mpoeschl@marmot.at>Martin Poeschl</a>
 * @version    $Revision$
 * @package    generator.model
 */
class ColumnTest extends PHPUnit_Framework_TestCase {

	/**
	 * Tests static Column::makeList() method.
	 * @deprecated - Column::makeList() is deprecated and set to be removed in 1.3
	 */
	public function testMakeList()
	{
		$expected = "`Column0`, `Column1`, `Column2`, `Column3`, `Column4`";
		$objArray = array();
		for ($i=0; $i<5; $i++) {
			$c = new Column();
			$c->setName("Column" . $i);
			$objArray[] = $c;
		}

		$list = Column::makeList($objArray, new MySQLPlatform());
		$this->assertEquals($expected, $list, sprintf("Expected '%s' match, got '%s' ", var_export($expected, true), var_export($list,true)));

		$strArray = array();
		for ($i=0; $i<5; $i++) {
			$strArray[] = "Column" . $i;
		}

		$list = Column::makeList($strArray, new MySQLPlatform());
		$this->assertEquals($expected, $list, sprintf("Expected '%s' match, got '%s' ", var_export($expected, true), var_export($list,true)));

	}
	
	public function testPhpNamingMethod()
	{
		set_include_path(get_include_path() . PATH_SEPARATOR . "fixtures/bookstore/build/classes");		
		Propel::init('fixtures/bookstore/build/conf/bookstore-conf.php');	
	  $bookTmap = Propel::getDatabaseMap(BookPeer::DATABASE_NAME)->getTable(BookPeer::TABLE_NAME);
	  $this->assertEquals('AuthorId', $bookTmap->getColumn('AUTHOR_ID')->getPhpName(), 'setPhpName() uses the default phpNamingMethod');
	  $pageTmap = Propel::getDatabaseMap(PagePeer::DATABASE_NAME)->getTable(PagePeer::TABLE_NAME);
	  $this->assertEquals('LeftChild', $pageTmap->getColumn('LEFTCHILD')->getPhpName(), 'setPhpName() uses the configured phpNamingMethod');
	}
	
	public function testGetConstantName()
	{
		$xmlToAppData = new XmlToAppData(new MysqlPlatform(), "defaultpackage", null);
    $appData = $xmlToAppData->parseFile('fixtures/bookstore/behavior-timestampable-schema.xml');
    $column = $appData->getDatabase("bookstore-behavior")->getTable('table1')->getColumn('title');
    $this->assertEquals('Table1Peer::TITLE', $column->getConstantName(), 'getConstantName() returns the complete constant name by default');
	}
	
	public function testIsLocalColumnsRequired()
	{
		$xmlToAppData = new XmlToAppData(new MysqlPlatform(), "defaultpackage", null);
		$appData = $xmlToAppData->parseFile('fixtures/bookstore/schema.xml');
		$fk = $appData->getDatabase("bookstore")->getTable('book')->getColumnForeignKeys('publisher_id');
		$this->assertFalse($fk[0]->isLocalColumnsRequired());
		$fk = $appData->getDatabase("bookstore")->getTable('review')->getColumnForeignKeys('book_id');
		$this->assertTrue($fk[0]->isLocalColumnsRequired());
	}

}

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

/**
 * 
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

		$clazz = preg_replace('/Test$/', '', get_class($this));
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

	public function providerForTestGetUniqueDDL()
	{
		$table = new Table('foo');
		$column1 = new Column('bar1');
		$column1->getDomain()->copy(new Domain('FOOTYPE'));
		$table->addColumn($column1);
		$column2 = new Column('bar2');
		$column2->getDomain()->copy(new Domain('BARTYPE'));
		$table->addColumn($column2);
		$index = new Unique('babar');
		$index->addColumn($column1);
		$index->addColumn($column2);
		$table->addUnique($index);

		return array(
			array($index)
		);
	}
	
	public function providerForTestGetIndexDDL()
	{
		$table = new Table('foo');
		$column1 = new Column('bar1');
		$column1->getDomain()->copy(new Domain('FOOTYPE'));
		$table->addColumn($column1);
		$column2 = new Column('bar2');
		$column2->getDomain()->copy(new Domain('BARTYPE'));
		$table->addColumn($column2);
		$index = new Index('babar');
		$index->addColumn($column1);
		$index->addColumn($column2);
		$table->addIndex($index);

		return array(
			array($index)
		);
	}

	public function providerForTestGetForeignKeyDDL()
	{
		$table1 = new Table('foo');
		$column1 = new Column('bar_id');
		$column1->getDomain()->copy(new Domain('FOOTYPE'));
		$table1->addColumn($column1);
		$table2 = new Table('bar');
		$column2 = new Column('id');
		$column2->getDomain()->copy(new Domain('BARTYPE'));
		$table2->addColumn($column2);
		$fk = new ForeignKey('foo_bar_FK');
		$fk->setForeignTableName('bar');
		$fk->addReference($column1, $column2);
		$fk->setOnDelete('CASCADE');
		$table1->addForeignKey($fk);
		return array(
			array($fk)
		);
	}

	public function providerForTestGetForeignKeysDDL()
	{
		$table1 = new Table('foo');
		
		$column1 = new Column('bar_id');
		$column1->getDomain()->copy(new Domain('FOOTYPE'));
		$table1->addColumn($column1);
		$table2 = new Table('bar');
		$column2 = new Column('id');
		$column2->getDomain()->copy(new Domain('BARTYPE'));
		$table2->addColumn($column2);
		
		$fk = new ForeignKey('foo_bar_FK');
		$fk->setForeignTableName('bar');
		$fk->addReference($column1, $column2);
		$fk->setOnDelete('CASCADE');
		$table1->addForeignKey($fk);
		
		$column3 = new Column('baz_id');
		$column3->getDomain()->copy(new Domain('BAZTYPE'));
		$table1->addColumn($column3);
		$table3 = new Table('baz');
		$column4 = new Column('id');
		$column4->getDomain()->copy(new Domain('BAZTYPE'));
		$table3->addColumn($column4);

		$fk = new ForeignKey('foo_baz_FK');
		$fk->setForeignTableName('baz');
		$fk->addReference($column3, $column4);
		$fk->setOnDelete('SETNULL');
		$table1->addForeignKey($fk);
		
		return array(
			array($table1)
		);
	}
	
}

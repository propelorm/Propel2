<?php

/*
 *	$Id: TableTest.php 1965 2010-09-21 17:44:12Z francois $
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

require_once 'PHPUnit/Framework.php';
require_once dirname(__FILE__) . '/../../../../generator/lib/model/Database.php';
require_once dirname(__FILE__) . '/../../../tools/helpers/DummyPlatforms.php';

/**
 * Tests for Database model class.
 *
 * @version    $Revision$
 * @package    generator.model
 */
class DatabaseTest extends PHPUnit_Framework_TestCase
{
	public function providerForTestHasTable()
	{
		$database = new Database();
		$table = new Table('Foo');
		$database->addTable($table);
		return array(
			array($database, $table)
		);
	}
	
	public function testTableInheritsSchema()
	{
		$database = new Database();
		$database->setPlatform(new SchemaPlatform());
		$database->setSchema("Foo");
		$table = new Table("Bar");
		$database->addTable($table);
		$this->assertTrue($database->hasTable("Foo.Bar"));
		$this->assertFalse($database->hasTable("Bar"));

		$database = new Database();
		$database->setPlatform(new NoSchemaPlatform());
		$database->addTable($table);
		$this->assertFalse($database->hasTable("Foo.Bar"));
		$this->assertTrue($database->hasTable("Bar"));
	}

	/**
	 * @dataProvider providerForTestHasTable
	 */
	public function testHasTable($database, $table)
	{
		$this->assertTrue($database->hasTable('Foo'));
		$this->assertFalse($database->hasTable('foo'));
		$this->assertFalse($database->hasTable('FOO'));
	}

	/**
	 * @dataProvider providerForTestHasTable
	 */
	public function testHasTableCaseInsensitive($database, $table)
	{
		$this->assertTrue($database->hasTable('Foo', true));
		$this->assertTrue($database->hasTable('foo', true));
		$this->assertTrue($database->hasTable('FOO', true));
	}

	/**
	 * @dataProvider providerForTestHasTable
	 */
	public function testGetTable($database, $table)
	{
		$this->assertEquals($table, $database->getTable('Foo'));
		$this->assertNull($database->getTable('foo'));
		$this->assertNull($database->getTable('FOO'));
	}

	/**
	 * @dataProvider providerForTestHasTable
	 */
	public function testGetTableCaseInsensitive($database, $table)
	{
		$this->assertEquals($table, $database->getTable('Foo', true));
		$this->assertEquals($table, $database->getTable('foo', true));
		$this->assertEquals($table, $database->getTable('FOO', true));
	}
}
<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

require_once dirname(__FILE__) . '/PlatformTestBase.php';
require_once dirname(__FILE__) . '/../../../../generator/lib/model/Database.php';
require_once dirname(__FILE__) . '/../../../../generator/lib/model/Table.php';
require_once dirname(__FILE__) . '/../../../../generator/lib/model/VendorInfo.php';

/**
 *
 * @package    generator.platform 
 */
class PgsqlPlatformTest extends PlatformTestBase
{

	public function testGetColumnDDL()
	{
		$c = new Column('foo');
		$c->getDomain()->copy($this->getPlatform()->getDomainForType('DOUBLE'));
		$c->getDomain()->replaceScale(2);
		$c->getDomain()->replaceSize(3);
		$c->setNotNull(true);
		$c->getDomain()->setDefaultValue(new ColumnDefaultValue(123, ColumnDefaultValue::TYPE_VALUE));
		$expected = '"foo" DOUBLE PRECISION(3,2) DEFAULT 123 NOT NULL';
		$this->assertEquals($expected, $this->getPlatform()->getColumnDDL($c));
	}
	
	public function testGetColumnDDLAutoIncrement()
	{
		$database = new Database();
		$database->setPlatform($this->getPlatform());
		$table = new Table('foo_table');
		$table->setIdMethod(IDMethod::NATIVE);
		$database->addTable($table);
		$column = new Column('foo');
		$column->getDomain()->copy($this->getPlatform()->getDomainForType(PropelTypes::BIGINT));
		$column->setAutoIncrement(true);
		$table->addColumn($column);
		$expected = '"foo" bigserial';
		$this->assertEquals($expected, $this->getPlatform()->getColumnDDL($column));
	}
	
}

<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

require_once dirname(__FILE__) . '/PlatformTestBase.php';
require_once dirname(__FILE__) . '/../../../../generator/lib/model/Column.php';
require_once dirname(__FILE__) . '/../../../../generator/lib/model/VendorInfo.php';

/**
 *
 * @package    generator.platform 
 */
class MysqlPlatformTest extends PlatformTestBase
{
	public function testGetSequenceNameDefault()
	{
		$table = new Table('foo');
		$table->setIdMethod(IDMethod::NATIVE);
		$expected = 'foo_SEQ';
		$this->assertEquals($expected, $this->getPlatform()->getSequenceName($table));
	}

	public function testGetSequenceNameCustom()
	{
		$table = new Table('foo');
		$table->setIdMethod(IDMethod::NATIVE);
		$idMethodParameter = new IdMethodParameter();
		$idMethodParameter->setValue('foo_sequence');
		$table->addIdMethodParameter($idMethodParameter);
		$table->setIdMethod(IDMethod::NATIVE);
		$expected = 'foo_sequence';
		$this->assertEquals($expected, $this->getPlatform()->getSequenceName($table));
	}
	
	public function testGetDropTableDDL()
	{
		$table = new Table('foo');
		$expected = "
DROP TABLE IF EXISTS `foo`;
";
		$this->assertEquals($expected, $this->getPlatform()->getDropTableDDL($table));
	}
	
	public function testGetColumnDDL()
	{
		$column = new Column('foo');
		$column->getDomain()->copy($this->getPlatform()->getDomainForType('DOUBLE'));
		$column->getDomain()->replaceScale(2);
		$column->getDomain()->replaceSize(3);
		$column->setNotNull(true);
		$column->getDomain()->setDefaultValue(new ColumnDefaultValue(123, ColumnDefaultValue::TYPE_VALUE));
		$expected = '`foo` DOUBLE(3,2) DEFAULT 123 NOT NULL';
		$this->assertEquals($expected, $this->getPlatform()->getColumnDDL($column));
	}
	
	public function testGetColumnDDLCharsetVendor()
	{
		$column = new Column('foo');
		$column->getDomain()->copy($this->getPlatform()->getDomainForType('LONGVARCHAR'));
		$vendor = new VendorInfo('mysql');
		$vendor->setParameter('Charset', 'greek');
		$column->addVendorInfo($vendor);
		$expected = '`foo` TEXT CHARACTER SET \'greek\'';
		$this->assertEquals($expected, $this->getPlatform()->getColumnDDL($column));
	}

	public function testGetColumnDDLCharsetCollation()
	{
		$column = new Column('foo');
		$column->getDomain()->copy($this->getPlatform()->getDomainForType('LONGVARCHAR'));
		$vendor = new VendorInfo('mysql');
		$vendor->setParameter('Collate', 'latin1_german2_ci');
		$column->addVendorInfo($vendor);
		$expected = '`foo` TEXT COLLATE \'latin1_german2_ci\'';
		$this->assertEquals($expected, $this->getPlatform()->getColumnDDL($column));

		$column = new Column('foo');
		$column->getDomain()->copy($this->getPlatform()->getDomainForType('LONGVARCHAR'));
		$vendor = new VendorInfo('mysql');
		$vendor->setParameter('Collation', 'latin1_german2_ci');
		$column->addVendorInfo($vendor);
		$expected = '`foo` TEXT COLLATE \'latin1_german2_ci\'';
		$this->assertEquals($expected, $this->getPlatform()->getColumnDDL($column));
	}

	public function testGetColumnDDLComment()
	{
		$column = new Column('foo');
		$column->getDomain()->copy($this->getPlatform()->getDomainForType('INTEGER'));
		$column->setDescription('This is column Foo');
		$expected = '`foo` INTEGER COMMENT \'This is column Foo\'';
		$this->assertEquals($expected, $this->getPlatform()->getColumnDDL($column));
	}
	
	public function testGetPrimaryKeyDDLSimpleKey()
	{
		$table = new Table('foo');
		$column = new Column('bar');
		$column->setPrimaryKey(true);
		$table->addColumn($column);
		$expected = 'PRIMARY KEY (`bar`)';
		$this->assertEquals($expected, $this->getPlatform()->getPrimaryKeyDDL($table));
	}

	public function testGetPrimaryKeyDDLCompositeKey()
	{
		$table = new Table('foo');
		$column1 = new Column('bar1');
		$column1->setPrimaryKey(true);
		$table->addColumn($column1);
		$column2 = new Column('bar2');
		$column2->setPrimaryKey(true);
		$table->addColumn($column2);
		$expected = 'PRIMARY KEY (`bar1`,`bar2`)';
		$this->assertEquals($expected, $this->getPlatform()->getPrimaryKeyDDL($table));
	}

	/**
	 * @dataProvider providerForTestGetIndexDDL
	 */
	public function testGetIndexDDL($index)
	{
		$expected = 'KEY `babar` (`bar1`, `bar2`)';
		$this->assertEquals($expected, $this->getPLatform()->getIndexDDL($index));
	}

	public function testGetIndexDDLKeySize()
	{
		$table = new Table('foo');
		$column1 = new Column('bar1');
		$column1->getDomain()->copy($this->getPlatform()->getDomainForType('VARCHAR'));
		$column1->setSize(5);
		$table->addColumn($column1);
		$index = new Index('bar_index');
		$index->addColumn($column1);
		$table->addIndex($index);
		$expected = 'KEY `bar_index` (`bar1`(5))';
		$this->assertEquals($expected, $this->getPLatform()->getIndexDDL($index));
	}

	public function testGetIndexDDLFulltext()
	{
		$table = new Table('foo');
		$column1 = new Column('bar1');
		$column1->getDomain()->copy($this->getPlatform()->getDomainForType('LONGVARCHAR'));
		$table->addColumn($column1);
		$index = new Index('bar_index');
		$index->addColumn($column1);
		$vendor = new VendorInfo('mysql');
		$vendor->setParameter('Index_type', 'FULLTEXT');
		$index->addVendorInfo($vendor);
		$table->addIndex($index);
		$expected = 'FULLTEXT KEY `bar_index` (`bar1`)';
		$this->assertEquals($expected, $this->getPLatform()->getIndexDDL($index));
	}

	/**
	 * @dataProvider providerForTestGetUniqueDDL
	 */
	public function testGetUniqueDDL($index)
	{
		$expected = 'UNIQUE KEY `babar` (`bar1`, `bar2`)';
		$this->assertEquals($expected, $this->getPLatform()->getUniqueDDL($index));
	}

	/**
	 * @dataProvider providerForTestGetForeignKeyDDL
	 */
	public function testGetForeignKeyDDL($fk)
	{
		$expected = "CONSTRAINT `foo_bar_FK`
	FOREIGN KEY (`bar_id`)
	REFERENCES `bar` (`id`)
	ON DELETE CASCADE";
		$this->assertEquals($expected, $this->getPLatform()->getForeignKeyDDL($fk));
	}


}

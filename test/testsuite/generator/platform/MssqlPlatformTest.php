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
class MssqlPlatformTest extends PlatformTestBase
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
IF EXISTS (SELECT 1 FROM sysobjects WHERE type = 'U' AND name = 'foo')
BEGIN
	DECLARE @reftable_1 nvarchar(60), @constraintname_1 nvarchar(60)
	DECLARE refcursor CURSOR FOR
	select reftables.name tablename, cons.name constraintname
		from sysobjects tables,
			sysobjects reftables,
			sysobjects cons,
			sysreferences ref
		where tables.id = ref.rkeyid
			and cons.id = ref.constid
			and reftables.id = ref.fkeyid
			and tables.name = 'foo'
	OPEN refcursor
	FETCH NEXT from refcursor into @reftable_1, @constraintname_1
	while @@FETCH_STATUS = 0
	BEGIN
		exec ('alter table '+@reftable_1+' drop constraint '+@constraintname_1)
		FETCH NEXT from refcursor into @reftable_1, @constraintname_1
	END
	CLOSE refcursor
	DEALLOCATE refcursor
	DROP TABLE [foo]
END
";
		$this->assertEquals($expected, $this->getPlatform()->getDropTableDDL($table));
	}

	public function testGetPrimaryKeyDDLSimpleKey()
	{
		$table = new Table('foo');
		$column = new Column('bar');
		$column->setPrimaryKey(true);
		$table->addColumn($column);
		$expected = 'CONSTRAINT [foo_PK] PRIMARY KEY ([bar])';
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
		$expected = 'CONSTRAINT [foo_PK] PRIMARY KEY ([bar1],[bar2])';
		$this->assertEquals($expected, $this->getPlatform()->getPrimaryKeyDDL($table));
	}

	/**
	 * @dataProvider providerForTestGetIndexDDL
	 */
	public function testGetIndexDDL($index)
	{
		$expected = 'INDEX [babar] ON [foo] ([bar1],[bar2])';
		$this->assertEquals($expected, $this->getPLatform()->getIndexDDL($index));
	}

	/**
	 * @dataProvider providerForTestGetUniqueDDL
	 */
	public function testGetUniqueDDL($index)
	{
		$expected = 'UNIQUE ([bar1],[bar2])';
		$this->assertEquals($expected, $this->getPLatform()->getUniqueDDL($index));
	}

	/**
	 * @dataProvider providerForTestGetForeignKeyDDL
	 */
	public function testGetForeignKeyDDL($fk)
	{
		$expected = 'CONSTRAINT [foo_bar_FK] FOREIGN KEY ([bar_id]) REFERENCES [bar] ([id]) ON DELETE CASCADE';
		$this->assertEquals($expected, $this->getPLatform()->getForeignKeyDDL($fk));
	}

}

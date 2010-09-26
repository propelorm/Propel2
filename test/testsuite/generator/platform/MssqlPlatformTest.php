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
require_once dirname(__FILE__) . '/../../../../generator/lib/builder/util/XmlToAppData.php';

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

	/**
	 * @dataProvider providerForTestGetAddTablesDDL
	 */
	public function testGetAddTablesDDL($schema)
	{
		$database = $this->getDatabaseFromSchema($schema);
		$expected = <<<EOF

-----------------------------------------------------------------------
-- book
-----------------------------------------------------------------------

IF EXISTS (SELECT 1 FROM sysobjects WHERE type ='RI' AND name='book_FK_1')
	ALTER TABLE [book] DROP CONSTRAINT [book_FK_1];

IF EXISTS (SELECT 1 FROM sysobjects WHERE type = 'U' AND name = 'book')
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
			and tables.name = 'book'
	OPEN refcursor
	FETCH NEXT from refcursor into @reftable_1, @constraintname_1
	while @@FETCH_STATUS = 0
	BEGIN
		exec ('alter table '+@reftable_1+' drop constraint '+@constraintname_1)
		FETCH NEXT from refcursor into @reftable_1, @constraintname_1
	END
	CLOSE refcursor
	DEALLOCATE refcursor
	DROP TABLE [book]
END

CREATE TABLE [book]
(
	[id] INT NOT NULL IDENTITY,
	[title] VARCHAR(255) NOT NULL,
	[author_id] INT NULL,
	CONSTRAINT [book_PK] PRIMARY KEY ([id])
);

CREATE INDEX [book_I_1] ON [book] ([title]);

BEGIN
ALTER TABLE [book] ADD CONSTRAINT [book_FK_1] FOREIGN KEY ([author_id]) REFERENCES [author] ([id])
END
;

-----------------------------------------------------------------------
-- author
-----------------------------------------------------------------------

IF EXISTS (SELECT 1 FROM sysobjects WHERE type = 'U' AND name = 'author')
BEGIN
	DECLARE @reftable_2 nvarchar(60), @constraintname_2 nvarchar(60)
	DECLARE refcursor CURSOR FOR
	select reftables.name tablename, cons.name constraintname
		from sysobjects tables,
			sysobjects reftables,
			sysobjects cons,
			sysreferences ref
		where tables.id = ref.rkeyid
			and cons.id = ref.constid
			and reftables.id = ref.fkeyid
			and tables.name = 'author'
	OPEN refcursor
	FETCH NEXT from refcursor into @reftable_2, @constraintname_2
	while @@FETCH_STATUS = 0
	BEGIN
		exec ('alter table '+@reftable_2+' drop constraint '+@constraintname_2)
		FETCH NEXT from refcursor into @reftable_2, @constraintname_2
	END
	CLOSE refcursor
	DEALLOCATE refcursor
	DROP TABLE [author]
END

CREATE TABLE [author]
(
	[id] INT NOT NULL IDENTITY,
	[first_name] VARCHAR(100) NULL,
	[last_name] VARCHAR(100) NULL,
	CONSTRAINT [author_PK] PRIMARY KEY ([id])
);

EOF;
		$this->assertEquals($expected, $this->getPlatform()->getAddTablesDDL($database));
	}

	/**
	 * @dataProvider providerForTestGetAddTableDDLSimplePK
	 */
	public function testGetAddTableDDLSimplePK($schema)
	{
		$table = $this->getTableFromSchema($schema);
		$expected = "
-- This is foo table
CREATE TABLE [foo]
(
	[id] INT NOT NULL IDENTITY,
	[bar] VARCHAR(255) NOT NULL,
	CONSTRAINT [foo_PK] PRIMARY KEY ([id])
);
";
		$this->assertEquals($expected, $this->getPlatform()->getAddTableDDL($table));
	}

	/**
	 * @dataProvider providerForTestGetAddTableDDLCompositePK
	 */
	public function testGetAddTableDDLCompositePK($schema)
	{
		$table = $this->getTableFromSchema($schema);
		$expected = "
CREATE TABLE [foo]
(
	[foo] INT NOT NULL,
	[bar] INT NOT NULL,
	[baz] VARCHAR(255) NOT NULL,
	CONSTRAINT [foo_PK] PRIMARY KEY ([foo],[bar])
);
";
		$this->assertEquals($expected, $this->getPlatform()->getAddTableDDL($table));
	}

	/**
	 * @dataProvider providerForTestGetAddTableDDLUniqueIndex
	 */
	public function testGetAddTableDDLUniqueIndex($schema)
	{
		$table = $this->getTableFromSchema($schema);
		$expected = "
CREATE TABLE [foo]
(
	[id] INT NOT NULL IDENTITY,
	[bar] INT NULL,
	CONSTRAINT [foo_PK] PRIMARY KEY ([id]),
	UNIQUE ([bar])
);
";
		$this->assertEquals($expected, $this->getPlatform()->getAddTableDDL($table));
	}

	public function testGetDropTableDDL()
	{
		$table = new Table('foo');
		$expected = "
IF EXISTS (SELECT 1 FROM sysobjects WHERE type = 'U' AND name = 'foo')
BEGIN
	DECLARE @reftable_3 nvarchar(60), @constraintname_3 nvarchar(60)
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
	FETCH NEXT from refcursor into @reftable_3, @constraintname_3
	while @@FETCH_STATUS = 0
	BEGIN
		exec ('alter table '+@reftable_3+' drop constraint '+@constraintname_3)
		FETCH NEXT from refcursor into @reftable_3, @constraintname_3
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
	 * @dataProvider providerForTestGetIndicesDDL
	 */
	public function testAddIndicesDDL($table)
	{
		$expected = "
CREATE INDEX [babar] ON [foo] ([bar1],[bar2]);

CREATE INDEX [foo_index] ON [foo] ([bar1]);
";
		$this->assertEquals($expected, $this->getPLatform()->getAddIndicesDDL($table));
	}
	
	/**
	 * @dataProvider providerForTestGetIndexDDL
	 */
	public function testAddIndexDDL($index)
	{
		$expected = "
CREATE INDEX [babar] ON [foo] ([bar1],[bar2]);
";
		$this->assertEquals($expected, $this->getPLatform()->getAddIndexDDL($index));
	}

	/**
	 * @dataProvider providerForTestGetIndexDDL
	 */
	public function testDropIndexDDL($index)
	{
		$expected = "
DROP INDEX [babar];
";
		$this->assertEquals($expected, $this->getPLatform()->getDropIndexDDL($index));
	}
	
	/**
	 * @dataProvider providerForTestGetIndexDDL
	 */
	public function testGetIndexDDL($index)
	{
		$expected = 'INDEX [babar] ([bar1],[bar2])';
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
	 * @dataProvider providerForTestGetForeignKeysDDL
	 */
	public function testGetAddForeignKeysDDL($table)
	{
		$expected = "
BEGIN
ALTER TABLE [foo] ADD CONSTRAINT [foo_bar_FK] FOREIGN KEY ([bar_id]) REFERENCES [bar] ([id]) ON DELETE CASCADE
END
;

BEGIN
ALTER TABLE [foo] ADD CONSTRAINT [foo_baz_FK] FOREIGN KEY ([baz_id]) REFERENCES [baz] ([id])
END
;
";
		$this->assertEquals($expected, $this->getPLatform()->getAddForeignKeysDDL($table));
	}
	
	/**
	 * @dataProvider providerForTestGetForeignKeyDDL
	 */
	public function testGetAddForeignKeyDDL($fk)
	{
		$expected = "
BEGIN
ALTER TABLE [foo] ADD CONSTRAINT [foo_bar_FK] FOREIGN KEY ([bar_id]) REFERENCES [bar] ([id]) ON DELETE CASCADE
END
;
";
		$this->assertEquals($expected, $this->getPLatform()->getAddForeignKeyDDL($fk));
	}

	/**
	 * @dataProvider providerForTestGetForeignKeyDDL
	 */
	public function testGetDropForeignKeyDDL($fk)
	{
		$expected = "
ALTER TABLE [foo] DROP CONSTRAINT [foo_bar_FK];
";
		$this->assertEquals($expected, $this->getPLatform()->getDropForeignKeyDDL($fk));
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

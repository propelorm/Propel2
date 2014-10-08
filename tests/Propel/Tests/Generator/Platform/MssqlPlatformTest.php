<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Platform;

use Propel\Generator\Model\Column;
use Propel\Generator\Model\ColumnDefaultValue;
use Propel\Generator\Model\IdMethod;
use Propel\Generator\Model\IdMethodParameter;
use Propel\Generator\Model\Table;
use Propel\Generator\Platform\MssqlPlatform;

/**
 *
 */
class MssqlPlatformTest extends PlatformTestProvider
{
    /**
     * Get the Platform object for this class
     *
     * @return MssqlPlatform
     */
    protected function getPlatform()
    {
        return new MssqlPlatform();
    }

    public function testGetSequenceNameDefault()
    {
        $table = new Table('foo');
        $table->setIdMethod(IdMethod::NATIVE);
        $expected = 'foo_SEQ';
        $this->assertEquals($expected, $this->getPlatform()->getSequenceName($table));
    }

    public function testGetSequenceNameCustom()
    {
        $table = new Table('foo');
        $table->setIdMethod(IdMethod::NATIVE);
        $idMethodParameter = new IdMethodParameter();
        $idMethodParameter->setValue('foo_sequence');
        $table->addIdMethodParameter($idMethodParameter);
        $table->setIdMethod(IdMethod::NATIVE);
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

IF EXISTS (SELECT 1 FROM sysobjects WHERE type ='RI' AND name='book_fk_ea464c')
    ALTER TABLE [book] DROP CONSTRAINT [book_fk_ea464c];

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
    CONSTRAINT [book_pk] PRIMARY KEY ([id])
);

CREATE INDEX [book_i_639136] ON [book] ([title]);

BEGIN
ALTER TABLE [book] ADD CONSTRAINT [book_fk_ea464c] FOREIGN KEY ([author_id]) REFERENCES [author] ([id])
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
    CONSTRAINT [author_pk] PRIMARY KEY ([id])
);

EOF;
        $this->assertEquals($expected, $this->getPlatform()->getAddTablesDDL($database));
    }

    /**
     * @dataProvider providerForTestGetAddTablesDDLSchema
     */
    public function testGetAddTablesDDLSchemas($schema)
    {
        $database = $this->getDatabaseFromSchema($schema);
        $expected = <<<EOF

-----------------------------------------------------------------------
-- x.book
-----------------------------------------------------------------------

IF EXISTS (SELECT 1 FROM sysobjects WHERE type ='RI' AND name='book_fk_4444ca')
    ALTER TABLE [x].[book] DROP CONSTRAINT [book_fk_4444ca];

IF EXISTS (SELECT 1 FROM sysobjects WHERE type = 'U' AND name = 'x.book')
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
            and tables.name = 'x.book'
    OPEN refcursor
    FETCH NEXT from refcursor into @reftable_3, @constraintname_3
    while @@FETCH_STATUS = 0
    BEGIN
        exec ('alter table '+@reftable_3+' drop constraint '+@constraintname_3)
        FETCH NEXT from refcursor into @reftable_3, @constraintname_3
    END
    CLOSE refcursor
    DEALLOCATE refcursor
    DROP TABLE [x].[book]
END

CREATE TABLE [x].[book]
(
    [id] INT NOT NULL IDENTITY,
    [title] VARCHAR(255) NOT NULL,
    [author_id] INT NULL,
    CONSTRAINT [book_pk] PRIMARY KEY ([id])
);

CREATE INDEX [book_i_639136] ON [x].[book] ([title]);

BEGIN
ALTER TABLE [x].[book] ADD CONSTRAINT [book_fk_4444ca] FOREIGN KEY ([author_id]) REFERENCES [y].[author] ([id])
END
;

-----------------------------------------------------------------------
-- y.author
-----------------------------------------------------------------------

IF EXISTS (SELECT 1 FROM sysobjects WHERE type = 'U' AND name = 'y.author')
BEGIN
    DECLARE @reftable_4 nvarchar(60), @constraintname_4 nvarchar(60)
    DECLARE refcursor CURSOR FOR
    select reftables.name tablename, cons.name constraintname
        from sysobjects tables,
            sysobjects reftables,
            sysobjects cons,
            sysreferences ref
        where tables.id = ref.rkeyid
            and cons.id = ref.constid
            and reftables.id = ref.fkeyid
            and tables.name = 'y.author'
    OPEN refcursor
    FETCH NEXT from refcursor into @reftable_4, @constraintname_4
    while @@FETCH_STATUS = 0
    BEGIN
        exec ('alter table '+@reftable_4+' drop constraint '+@constraintname_4)
        FETCH NEXT from refcursor into @reftable_4, @constraintname_4
    END
    CLOSE refcursor
    DEALLOCATE refcursor
    DROP TABLE [y].[author]
END

CREATE TABLE [y].[author]
(
    [id] INT NOT NULL IDENTITY,
    [first_name] VARCHAR(100) NULL,
    [last_name] VARCHAR(100) NULL,
    CONSTRAINT [author_pk] PRIMARY KEY ([id])
);

-----------------------------------------------------------------------
-- x.book_summary
-----------------------------------------------------------------------

IF EXISTS (SELECT 1 FROM sysobjects WHERE type ='RI' AND name='book_summary_fk_23450f')
    ALTER TABLE [x].[book_summary] DROP CONSTRAINT [book_summary_fk_23450f];

IF EXISTS (SELECT 1 FROM sysobjects WHERE type = 'U' AND name = 'x.book_summary')
BEGIN
    DECLARE @reftable_5 nvarchar(60), @constraintname_5 nvarchar(60)
    DECLARE refcursor CURSOR FOR
    select reftables.name tablename, cons.name constraintname
        from sysobjects tables,
            sysobjects reftables,
            sysobjects cons,
            sysreferences ref
        where tables.id = ref.rkeyid
            and cons.id = ref.constid
            and reftables.id = ref.fkeyid
            and tables.name = 'x.book_summary'
    OPEN refcursor
    FETCH NEXT from refcursor into @reftable_5, @constraintname_5
    while @@FETCH_STATUS = 0
    BEGIN
        exec ('alter table '+@reftable_5+' drop constraint '+@constraintname_5)
        FETCH NEXT from refcursor into @reftable_5, @constraintname_5
    END
    CLOSE refcursor
    DEALLOCATE refcursor
    DROP TABLE [x].[book_summary]
END

CREATE TABLE [x].[book_summary]
(
    [id] INT NOT NULL IDENTITY,
    [book_id] INT NOT NULL,
    [summary] VARCHAR(MAX) NOT NULL,
    CONSTRAINT [book_summary_pk] PRIMARY KEY ([id])
);

BEGIN
ALTER TABLE [x].[book_summary] ADD CONSTRAINT [book_summary_fk_23450f] FOREIGN KEY ([book_id]) REFERENCES [x].[book] ([id]) ON DELETE CASCADE
END
;

EOF;
        $this->assertEquals($expected, $this->getPlatform()->getAddTablesDDL($database));
    }

    /**
     * @dataProvider providerForTestGetAddTablesSkipSQLDDL
     */
    public function testGetAddTablesSkipSQLDDL($schema)
    {
        $database = $this->getDatabaseFromSchema($schema);
        $expected = '';
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
    CONSTRAINT [foo_pk] PRIMARY KEY ([id])
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
    CONSTRAINT [foo_pk] PRIMARY KEY ([foo],[bar])
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
    CONSTRAINT [foo_pk] PRIMARY KEY ([id]),
    UNIQUE ([bar])
);
";
        $this->assertEquals($expected, $this->getPlatform()->getAddTableDDL($table));
    }

    /**
     * @dataProvider providerForTestGetAddTableDDLSchema
     */
    public function testGetAddTableDDLSchema($schema)
    {
        $table = $this->getTableFromSchema($schema, 'Woopah.foo');
        $expected = "
CREATE TABLE [Woopah].[foo]
(
    [id] INT NOT NULL IDENTITY,
    [bar] INT NULL,
    CONSTRAINT [foo_pk] PRIMARY KEY ([id])
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
    DECLARE @reftable_6 nvarchar(60), @constraintname_6 nvarchar(60)
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
    FETCH NEXT from refcursor into @reftable_6, @constraintname_6
    while @@FETCH_STATUS = 0
    BEGIN
        exec ('alter table '+@reftable_6+' drop constraint '+@constraintname_6)
        FETCH NEXT from refcursor into @reftable_6, @constraintname_6
    END
    CLOSE refcursor
    DEALLOCATE refcursor
    DROP TABLE [foo]
END
";
        $this->assertEquals($expected, $this->getPlatform()->getDropTableDDL($table));
    }

    /**
     * @dataProvider providerForTestGetAddTableDDLSchema
     */
    public function testGetDropTableDDLSchema($schema)
    {
        $table = $this->getTableFromSchema($schema, 'Woopah.foo');
        $expected = "
IF EXISTS (SELECT 1 FROM sysobjects WHERE type = 'U' AND name = 'Woopah.foo')
BEGIN
    DECLARE @reftable_7 nvarchar(60), @constraintname_7 nvarchar(60)
    DECLARE refcursor CURSOR FOR
    select reftables.name tablename, cons.name constraintname
        from sysobjects tables,
            sysobjects reftables,
            sysobjects cons,
            sysreferences ref
        where tables.id = ref.rkeyid
            and cons.id = ref.constid
            and reftables.id = ref.fkeyid
            and tables.name = 'Woopah.foo'
    OPEN refcursor
    FETCH NEXT from refcursor into @reftable_7, @constraintname_7
    while @@FETCH_STATUS = 0
    BEGIN
        exec ('alter table '+@reftable_7+' drop constraint '+@constraintname_7)
        FETCH NEXT from refcursor into @reftable_7, @constraintname_7
    END
    CLOSE refcursor
    DEALLOCATE refcursor
    DROP TABLE [Woopah].[foo]
END
";
        $this->assertEquals($expected, $this->getPlatform()->getDropTableDDL($table));
    }

    public function testGetColumnDDLCustomSqlType()
    {
        $column = new Column('foo');
        $column->getDomain()->copy($this->getPlatform()->getDomainForType('DOUBLE'));
        $column->getDomain()->replaceScale(2);
        $column->getDomain()->replaceSize(3);
        $column->setNotNull(true);
        $column->getDomain()->setDefaultValue(new ColumnDefaultValue(123, ColumnDefaultValue::TYPE_VALUE));
        $column->getDomain()->replaceSqlType('DECIMAL(5,6)');
        $expected = '[foo] DECIMAL(5,6) DEFAULT 123 NOT NULL';
        $this->assertEquals($expected, $this->getPlatform()->getColumnDDL($column));
    }

    public function testGetPrimaryKeyDDLSimpleKey()
    {
        $table = new Table('foo');
        $column = new Column('bar');
        $column->setPrimaryKey(true);
        $table->addColumn($column);
        $expected = 'CONSTRAINT [foo_pk] PRIMARY KEY ([bar])';
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
        $expected = 'CONSTRAINT [foo_pk] PRIMARY KEY ([bar1],[bar2])';
        $this->assertEquals($expected, $this->getPlatform()->getPrimaryKeyDDL($table));
    }

    /**
     * @dataProvider providerForTestPrimaryKeyDDL
     */
    public function testGetDropPrimaryKeyDDL($table)
    {
        $expected = "
ALTER TABLE [foo] DROP CONSTRAINT [foo_pk];
";
        $this->assertEquals($expected, $this->getPlatform()->getDropPrimaryKeyDDL($table));
    }

    /**
     * @dataProvider providerForTestPrimaryKeyDDL
     */
    public function testGetAddPrimaryKeyDDL($table)
    {
        $expected = "
ALTER TABLE [foo] ADD CONSTRAINT [foo_pk] PRIMARY KEY ([bar]);
";
        $this->assertEquals($expected, $this->getPlatform()->getAddPrimaryKeyDDL($table));
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
        $this->assertEquals($expected, $this->getPlatform()->getAddIndicesDDL($table));
    }

    /**
     * @dataProvider providerForTestGetIndexDDL
     */
    public function testAddIndexDDL($index)
    {
        $expected = "
CREATE INDEX [babar] ON [foo] ([bar1],[bar2]);
";
        $this->assertEquals($expected, $this->getPlatform()->getAddIndexDDL($index));
    }

    /**
     * @dataProvider providerForTestGetIndexDDL
     */
    public function testDropIndexDDL($index)
    {
        $expected = "
DROP INDEX [babar];
";
        $this->assertEquals($expected, $this->getPlatform()->getDropIndexDDL($index));
    }

    /**
     * @dataProvider providerForTestGetIndexDDL
     */
    public function testGetIndexDDL($index)
    {
        $expected = 'INDEX [babar] ([bar1],[bar2])';
        $this->assertEquals($expected, $this->getPlatform()->getIndexDDL($index));
    }

    /**
     * @dataProvider providerForTestGetUniqueDDL
     */
    public function testGetUniqueDDL($index)
    {
        $expected = 'UNIQUE ([bar1],[bar2])';
        $this->assertEquals($expected, $this->getPlatform()->getUniqueDDL($index));
    }

    /**
     * @dataProvider providerForTestGetForeignKeysDDL
     */
    public function testGetAddForeignKeysDDL($table)
    {
        $expected = "
BEGIN
ALTER TABLE [foo] ADD CONSTRAINT [foo_bar_fk] FOREIGN KEY ([bar_id]) REFERENCES [bar] ([id]) ON DELETE CASCADE
END
;

BEGIN
ALTER TABLE [foo] ADD CONSTRAINT [foo_baz_fk] FOREIGN KEY ([baz_id]) REFERENCES [baz] ([id])
END
;
";
        $this->assertEquals($expected, $this->getPlatform()->getAddForeignKeysDDL($table));
    }

    /**
     * @dataProvider providerForTestGetForeignKeyDDL
     */
    public function testGetAddForeignKeyDDL($fk)
    {
        $expected = "
BEGIN
ALTER TABLE [foo] ADD CONSTRAINT [foo_bar_fk] FOREIGN KEY ([bar_id]) REFERENCES [bar] ([id]) ON DELETE CASCADE
END
;
";
        $this->assertEquals($expected, $this->getPlatform()->getAddForeignKeyDDL($fk));
    }

    /**
     * @dataProvider providerForTestGetForeignKeySkipSqlDDL
     */
    public function testGetAddForeignKeySkipSqlDDL($fk)
    {
        $expected = '';
        $this->assertEquals($expected, $this->getPlatform()->getAddForeignKeyDDL($fk));
    }

    /**
     * @dataProvider providerForTestGetForeignKeyDDL
     */
    public function testGetDropForeignKeyDDL($fk)
    {
        $expected = "
ALTER TABLE [foo] DROP CONSTRAINT [foo_bar_fk];
";
        $this->assertEquals($expected, $this->getPlatform()->getDropForeignKeyDDL($fk));
    }

    /**
     * @dataProvider providerForTestGetForeignKeySkipSqlDDL
     */
    public function testGetDropForeignKeySkipSqlDDL($fk)
    {
        $expected = '';
        $this->assertEquals($expected, $this->getPlatform()->getDropForeignKeyDDL($fk));
    }

    /**
     * @dataProvider providerForTestGetForeignKeyDDL
     */
    public function testGetForeignKeyDDL($fk)
    {
        $expected = 'CONSTRAINT [foo_bar_fk] FOREIGN KEY ([bar_id]) REFERENCES [bar] ([id]) ON DELETE CASCADE';
        $this->assertEquals($expected, $this->getPlatform()->getForeignKeyDDL($fk));
    }

    /**
     * @dataProvider providerForTestGetForeignKeySkipSqlDDL
     */
    public function testGetForeignKeySkipSqlDDL($fk)
    {
        $expected = '';
        $this->assertEquals($expected, $this->getPlatform()->getForeignKeyDDL($fk));
    }

    public function testGetCommentBlockDDL()
    {
        $expected = "
-----------------------------------------------------------------------
-- foo bar
-----------------------------------------------------------------------
";
        $this->assertEquals($expected, $this->getPlatform()->getCommentBlockDDL('foo bar'));
    }
}

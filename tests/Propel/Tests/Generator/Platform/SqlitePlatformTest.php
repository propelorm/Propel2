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
use Propel\Generator\Platform\SqlitePlatform;
use Propel\Runtime\Adapter\AdapterFactory;
use Propel\Runtime\Connection\ConnectionFactory;

/**
 *
 */
class SqlitePlatformTest extends PlatformTestProvider
{
    /**
     * Get the Platform object for this class
     *
     * @return Platform
     */
    protected function getPlatform()
    {
        return new SqlitePlatform();
    }

    public function testQuoteConnected()
    {
        $p = $this->getPlatform();
        $p->setConnection(ConnectionFactory::create(array('dsn' => 'sqlite::memory:'), AdapterFactory::create('sqlite')));

        $unquoted = "Naughty ' string";
        $quoted = $p->quote($unquoted);

        $expected = "'Naughty '' string'";
        $this->assertEquals($expected, $quoted);
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

DROP TABLE IF EXISTS [book];

CREATE TABLE [book]
(
    [id] INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    [title] VARCHAR(255) NOT NULL,
    [author_id] INTEGER,
    UNIQUE ([id]),
    FOREIGN KEY ([author_id]) REFERENCES [author] ([id])
);

CREATE INDEX [book_i_639136] ON [book] ([title]);

-----------------------------------------------------------------------
-- author
-----------------------------------------------------------------------

DROP TABLE IF EXISTS [author];

CREATE TABLE [author]
(
    [id] INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    [first_name] VARCHAR(100),
    [last_name] VARCHAR(100),
    UNIQUE ([id])
);

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
    [id] INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    [bar] VARCHAR(255) NOT NULL
);
";
        $this->assertEquals($expected, $this->getPlatform()->getAddTableDDL($table));
    }

    /**
     * @dataProvider providerForTestGetAddTableDDLNonIntegerPK
     */
    public function testGetAddTableDDLNonIntegerPK($schema)
    {
        $table = $this->getTableFromSchema($schema);
        $expected = "
-- This is foo table
CREATE TABLE [foo]
(
    [foo] VARCHAR(255) NOT NULL,
    [bar] VARCHAR(255) NOT NULL,
    PRIMARY KEY ([foo])
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
    [foo] INTEGER NOT NULL,
    [bar] INTEGER NOT NULL,
    [baz] VARCHAR(255) NOT NULL,
    PRIMARY KEY ([foo],[bar])
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
    [id] INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    [bar] INTEGER,
    UNIQUE ([bar])
);
";
        $this->assertEquals($expected, $this->getPlatform()->getAddTableDDL($table));
    }

    public function testGetDropTableDDL()
    {
        $table = new Table('foo');
        $expected = "
DROP TABLE IF EXISTS [foo];
";
        $this->assertEquals($expected, $this->getPlatform()->getDropTableDDL($table));
    }

    public function testGetColumnDDL()
    {
        $c = new Column('foo');
        $c->getDomain()->copy($this->getPlatform()->getDomainForType('DOUBLE'));
        $c->getDomain()->replaceScale(2);
        $c->getDomain()->replaceSize(3);
        $c->setNotNull(true);
        $c->getDomain()->setDefaultValue(new ColumnDefaultValue(123, ColumnDefaultValue::TYPE_VALUE));
        $expected = '[foo] DOUBLE(3,2) DEFAULT 123 NOT NULL';
        $this->assertEquals($expected, $this->getPlatform()->getColumnDDL($c));
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

    public function testGetPrimaryKeyDDLCompositeKeyWithAutoIncrement()
    {
        $table = new Table('foo');
        $table->setIdMethod(IdMethod::NATIVE);

        $column1 = new Column('bar');
        $column1->setPrimaryKey(true);
        $table->addColumn($column1);

        $column2 = new Column('baz');
        $column2->setPrimaryKey(true);
        $column2->setAutoIncrement(true);
        $table->addColumn($column2);

        $expected = '';
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
        $expected = 'PRIMARY KEY ([bar1],[bar2])';
        $this->assertEquals($expected, $this->getPlatform()->getPrimaryKeyDDL($table));
    }

    /**
     * @dataProvider providerForTestPrimaryKeyDDL
     */
    public function testGetDropPrimaryKeyDDL($table)
    {
        // not supported by SQLite
        $expected = '';
        $this->assertEquals($expected, $this->getPlatform()->getDropPrimaryKeyDDL($table));
    }

    /**
     * @dataProvider providerForTestPrimaryKeyDDL
     */
    public function testGetAddPrimaryKeyDDL($table)
    {
        // not supported by SQLite
        $expected = '';
        $this->assertEquals($expected, $this->getPlatform()->getAddPrimaryKeyDDL($table));
    }

    /**
     * @dataProvider providerForTestGetIndicesDDL
     */
    public function testAddIndicesDDL($table)
    {
        $expected = '
CREATE INDEX [babar] ON [foo] ([bar1],[bar2]);

CREATE INDEX [foo_index] ON [foo] ([bar1]);
';
        $this->assertEquals($expected, $this->getPlatform()->getAddIndicesDDL($table));
    }

    /**
     * @dataProvider providerForTestGetIndexDDL
     */
    public function testAddIndexDDL($index)
    {
        $expected = '
CREATE INDEX [babar] ON [foo] ([bar1],[bar2]);
';
        $this->assertEquals($expected, $this->getPlatform()->getAddIndexDDL($index));
    }

    /**
     * @dataProvider providerForTestGetIndexDDL
     */
    public function testDropIndexDDL($index)
    {
        $expected = '
DROP INDEX [babar];
';
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
        $expected = "";
        $this->assertEquals($expected, $this->getPlatform()->getAddForeignKeysDDL($table));
    }

    /**
     * @dataProvider providerForTestGetForeignKeyDDL
     */
    public function testGetAddForeignKeyDDL($fk)
    {
        $expected = "";
        $this->assertEquals($expected, $this->getPlatform()->getAddForeignKeyDDL($fk));
    }

    /**
     * @dataProvider providerForTestGetForeignKeyDDL
     */
    public function testGetDropForeignKeyDDL($fk)
    {
        $expected = '';
        $this->assertEquals($expected, $this->getPlatform()->getDropForeignKeyDDL($fk));
    }

    /**
     * @dataProvider providerForTestGetForeignKeyDDL
     */
    public function testGetForeignKeyDDL($fk)
    {
        $expected = 'FOREIGN KEY ([bar_id]) REFERENCES [bar] ([id])
    ON DELETE CASCADE';
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

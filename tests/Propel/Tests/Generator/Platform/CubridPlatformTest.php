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
use Propel\Generator\Model\Index;
use Propel\Generator\Model\Table;
use Propel\Generator\Platform\CubridPlatform;

/**
 *
 */
class CubridPlatformTest extends PlatformTestProvider
{
    /**
     * Get the Platform object for this class
     *
     * @return Platform
     */
    protected function getPlatform()
    {
        return new CubridPlatform();
    }

    public function testConstructorInitializeQuoteIdentifierToTrue()
    {
        $this->assertTrue($this->getPlatform()->getIdentifierQuoting());
    }

    public function testQuoteIdentifierIsAlwaysTrue()
    {
        $platform = $this->getPlatform();
        $platform->setIdentifierQuoting(true);
        $this->assertTrue($platform->getIdentifierQuoting());

        $platform->setIdentifierQuoting(false);
        $this->assertTrue($platform->getIdentifierQuoting());
    }

    public function testGetAutoincrement()
    {
        $this->assertEquals('AUTO_INCREMENT', $this->getPlatform()->getAutoincrement());
    }

    public function testGetMaxColumnNameLength()
    {
        $this->assertEquals(254, $this->getPlatform()->getMaxColumnNameLength());
    }

    public function testSupportsNativeDeleteTrigger()
    {
        $this->assertTrue($this->getPlatform()->supportsNativeDeleteTrigger());
    }

    public function testHasStreamBlobImpl()
    {
        $this->assertTrue($this->getPlatform()->hasStreamBlobImpl());
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
     * @dataProvider providerForTestGetAddTablesDDLSchema
     */
    public function testGetAddTablesDDLSchema($schema)
    {
        $database = $this->getDatabaseFromSchema($schema);
        $expected = <<<EOF

-----------------------------------------------------------------------
-- book
-----------------------------------------------------------------------

DROP TABLE IF EXISTS `book_summary`;

DROP TABLE IF EXISTS `book`;

CREATE TABLE `book`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(255) NOT NULL,
    `author_id` INTEGER,
    PRIMARY KEY (`id`)
);

CREATE INDEX `book_I_1` ON `book` (`title`);

-----------------------------------------------------------------------
-- author
-----------------------------------------------------------------------

DROP TABLE IF EXISTS `author`;

CREATE TABLE `author`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `first_name` VARCHAR(100),
    `last_name` VARCHAR(100),
    PRIMARY KEY (`id`)
);

-----------------------------------------------------------------------
-- book_summary
-----------------------------------------------------------------------

DROP TABLE IF EXISTS `book_summary`;

CREATE TABLE `book_summary`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `book_id` INTEGER NOT NULL,
    `summary` STRING NOT NULL,
    PRIMARY KEY (`id`)
);

-----------------------------------------------------------------------
-- book Foreign Key Definition
-----------------------------------------------------------------------

ALTER TABLE `book` ADD CONSTRAINT `book_FK_1`
    FOREIGN KEY (`author_id`)
    REFERENCES `author` (`id`);

-----------------------------------------------------------------------
-- author Foreign Key Definition
-----------------------------------------------------------------------

-----------------------------------------------------------------------
-- book_summary Foreign Key Definition
-----------------------------------------------------------------------

ALTER TABLE `book_summary` ADD CONSTRAINT `book_summary_FK_1`
    FOREIGN KEY (`book_id`)
    REFERENCES `book` (`id`)
    ON DELETE CASCADE;

EOF;
        $this->assertEquals($expected, $this->getPlatform()->getAddTablesDDL($database));
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

DROP TABLE IF EXISTS `book`;

CREATE TABLE `book`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(255) NOT NULL,
    `author_id` INTEGER,
    PRIMARY KEY (`id`)
);

CREATE INDEX `book_I_1` ON `book` (`title`);

-----------------------------------------------------------------------
-- author
-----------------------------------------------------------------------

DROP TABLE IF EXISTS `author`;

CREATE TABLE `author`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `first_name` VARCHAR(100),
    `last_name` VARCHAR(100),
    PRIMARY KEY (`id`)
);

-----------------------------------------------------------------------
-- book Foreign Key Definition
-----------------------------------------------------------------------

ALTER TABLE `book` ADD CONSTRAINT `book_FK_1`
    FOREIGN KEY (`author_id`)
    REFERENCES `author` (`id`);

-----------------------------------------------------------------------
-- author Foreign Key Definition
-----------------------------------------------------------------------

EOF;
        $this->assertEquals($expected, $this->getPlatform()->getAddTablesDDL($database));
    }

    /**
     * @dataProvider providerForTestGetAddTablesSkipSQLDDL
     */
    public function testGetAddTablesSkipSQLDDL($schema)
    {
        $database = $this->getDatabaseFromSchema($schema);
        $expected = "";
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
CREATE TABLE `foo`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `bar` VARCHAR(255) NOT NULL,
    PRIMARY KEY (`id`)
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
CREATE TABLE `foo`
(
    `foo` INTEGER NOT NULL,
    `bar` INTEGER NOT NULL,
    `baz` VARCHAR(255) NOT NULL,
    PRIMARY KEY (`foo`,`bar`)
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
CREATE TABLE `foo`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `bar` INTEGER,
    PRIMARY KEY (`id`),
    UNIQUE (`bar`)
);
";
        $this->assertEquals($expected, $this->getPlatform()->getAddTableDDL($table));
    }

    public function testGetAddTableDDLIndex()
    {
        $schema = <<<EOF
<database name="test">
    <table name="foo">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <column name="bar" type="INTEGER" />
        <index>
            <index-column name="bar" />
        </index>
    </table>
</database>
EOF;
        $table = $this->getTableFromSchema($schema);
        $expected = "
CREATE TABLE `foo`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `bar` INTEGER,
    PRIMARY KEY (`id`)
);

CREATE INDEX `foo_I_1` ON `foo` (`bar`);
";
        $actual = $this->getPlatform()->getAddTableDDL($table) . $this->getPlatform()->getAddIndicesDDL($table);
        $this->assertEquals($expected, $actual);
    }

    public function testGetAddTableDDLForeignKey()
    {
        $schema = <<<EOF
<database name="test">
    <table name="foo">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <column name="bar_id" type="INTEGER" />
        <foreign-key foreignTable="bar">
            <reference local="bar_id" foreign="id" />
        </foreign-key>
    </table>
    <table name="bar">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
    </table>
</database>
EOF;
        $table = $this->getTableFromSchema($schema);
        $expected = "
CREATE TABLE `foo`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `bar_id` INTEGER,
    PRIMARY KEY (`id`)
);

ALTER TABLE `foo` ADD CONSTRAINT `foo_FK_1`
    FOREIGN KEY (`bar_id`)
    REFERENCES `bar` (`id`);
";
        $actual = $this->getPlatform()->getAddTableDDL($table) . $this->getPlatform()->getAddForeignKeysDDL($table);
        $this->assertEquals($expected, $actual);
    }

    public function testGetAddTableDDLForeignKeySkipSql()
    {
        $schema = <<<EOF
<database name="test">
    <table name="foo">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <column name="bar_id" type="INTEGER" />
        <foreign-key foreignTable="bar" skipSql="true">
            <reference local="bar_id" foreign="id" />
        </foreign-key>
    </table>
    <table name="bar">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
    </table>
</database>
EOF;
        $table = $this->getTableFromSchema($schema);
        $expected = "
CREATE TABLE `foo`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `bar_id` INTEGER,
    PRIMARY KEY (`id`)
);
";
        $actual = $this->getPlatform()->getAddTableDDL($table) . $this->getPlatform()->getAddForeignKeysDDL($table);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @dataProvider providerForTestGetAddTableDDLSchema
     */
    public function testGetAddTableDDLSchema($schema)
    {
        $table = $this->getTableFromSchema($schema, 'foo');
        $expected = "
CREATE TABLE `foo`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `bar` INTEGER,
    PRIMARY KEY (`id`)
);
";
        $this->assertEquals($expected, $this->getPlatform()->getAddTableDDL($table));
    }

    public function testGetDropTableDDL()
    {
        $table = new Table('foo');
        $expected = "
DROP TABLE IF EXISTS `foo`;
";
        $this->assertEquals($expected, $this->getPlatform()->getDropTableDDL($table));
    }

    /**
     * @dataProvider providerForTestGetAddTableDDLSchema
     */
    public function testGetDropTableDDLSchema($schema)
    {
        $table = $this->getTableFromSchema($schema, 'foo');
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
        $expected = '`foo` DOUBLE DEFAULT 123 NOT NULL';
        $this->assertEquals($expected, $this->getPlatform()->getColumnDDL($column));
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
        $expected = '`foo` DECIMAL(5,6) DEFAULT 123 NOT NULL';
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
     * @dataProvider providerForTestPrimaryKeyDDL
     */
    public function testGetDropPrimaryKeyDDL($table)
    {
        $expected = "
ALTER TABLE `foo` DROP PRIMARY KEY;
";
        $this->assertEquals($expected, $this->getPlatform()->getDropPrimaryKeyDDL($table));
    }

    /**
     * @dataProvider providerForTestPrimaryKeyDDL
     */
    public function testGetAddPrimaryKeyDDL($table)
    {
        $expected = "
ALTER TABLE `foo` ADD PRIMARY KEY (`bar`);
";
        $this->assertEquals($expected, $this->getPlatform()->getAddPrimaryKeyDDL($table));
    }

    /**
     * @dataProvider providerForTestGetIndicesDDL
     */
    public function testAddIndicesDDL($table)
    {
        $expected = "
CREATE INDEX `babar` ON `foo` (`bar1`,`bar2`);

CREATE INDEX `foo_index` ON `foo` (`bar1`);
";
        $this->assertEquals($expected, $this->getPLatform()->getAddIndicesDDL($table));
    }

    /**
     * @dataProvider providerForTestGetIndexDDL
     */
    public function testAddIndexDDL($index)
    {
        $expected = "
CREATE INDEX `babar` ON `foo` (`bar1`,`bar2`);
";
        $this->assertEquals($expected, $this->getPLatform()->getAddIndexDDL($index));
    }

    /**
     * @dataProvider providerForTestGetIndexDDL
     */
    public function testDropIndexDDL($index)
    {
        $expected = "
DROP INDEX `babar` ON `foo`;
";
        $this->assertEquals($expected, $this->getPLatform()->getDropIndexDDL($index));
    }

    /**
     * @dataProvider providerForTestGetIndexDDL
     */
    public function testGetIndexDDL($index)
    {
        $expected = 'INDEX `babar` ON `foo`(`bar1`, `bar2`)';
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
        $expected = 'INDEX `bar_index` ON `foo`(`bar1`(5))';
        $this->assertEquals($expected, $this->getPLatform()->getIndexDDL($index));
    }

    /**
     * @dataProvider providerForTestGetUniqueDDL
     */
    public function testGetUniqueDDL($index)
    {
        $expected = 'UNIQUE (`bar1`,`bar2`)';
        $this->assertEquals($expected, $this->getPLatform()->getUniqueDDL($index));
    }

    /**
     * @dataProvider providerForTestGetForeignKeysDDL
     */
    public function testGetAddForeignKeysDDL($table)
    {
        $expected = "
ALTER TABLE `foo` ADD CONSTRAINT `foo_bar_FK`
    FOREIGN KEY (`bar_id`)
    REFERENCES `bar` (`id`)
    ON DELETE CASCADE;

ALTER TABLE `foo` ADD CONSTRAINT `foo_baz_FK`
    FOREIGN KEY (`baz_id`)
    REFERENCES `baz` (`id`)
    ON DELETE SET NULL;
";
        $this->assertEquals($expected, $this->getPlatform()->getAddForeignKeysDDL($table));
    }

    /**
     * @dataProvider providerForTestGetForeignKeyDDL
     */
    public function testGetAddForeignKeyDDL($fk)
    {
        $expected = "
ALTER TABLE `foo` ADD CONSTRAINT `foo_bar_FK`
    FOREIGN KEY (`bar_id`)
    REFERENCES `bar` (`id`)
    ON DELETE CASCADE;
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
ALTER TABLE `foo` DROP FOREIGN KEY `foo_bar_FK`;
";
        $this->assertEquals($expected, $this->getPLatform()->getDropForeignKeyDDL($fk));
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
        $expected = "CONSTRAINT `foo_bar_FK`
    FOREIGN KEY (`bar_id`)
    REFERENCES `bar` (`id`)
    ON DELETE CASCADE";
        $this->assertEquals($expected, $this->getPLatform()->getForeignKeyDDL($fk));
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
        $this->assertEquals($expected, $this->getPLatform()->getCommentBlockDDL('foo bar'));
    }

}

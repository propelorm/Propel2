<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Platform;

use Propel\Generator\Model\Column;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\Domain;
use Propel\Generator\Model\ForeignKey;
use Propel\Generator\Model\Index;
use Propel\Generator\Model\Table;
use Propel\Generator\Model\Unique;

/**
 * provider for platform DDL unit tests
 */
abstract class PlatformTestProvider extends PlatformTestBase
{
    /**
     * @return void
     */
    public function assertCreateTableMatches(string $expected, $schema, ?string $tableName = 'foo' )
    {
        $table = $this->getTableFromSchema($schema, $tableName);
        $this->assertEquals($expected, $this->getPlatform()->getAddTableDDL($table));
    }

    /**
     * @return string[][]
     */
    public function providerForTestGetAddTablesDDL()
    {
        $schema = <<<EOF
<database name="test" identifierQuoting="true">
    <table name="book">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="title" type="VARCHAR" size="255" required="true"/>
        <index>
            <index-column name="title"/>
        </index>
        <column name="author_id" type="INTEGER"/>
        <foreign-key foreignTable="author">
            <reference local="author_id" foreign="id"/>
        </foreign-key>
    </table>
    <table name="author">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="first_name" type="VARCHAR" size="100"/>
        <column name="last_name" type="VARCHAR" size="100"/>
    </table>
</database>
EOF;

        return [[$schema]];
    }

    public function providerForTestGetAddTablesDDLSchema()
    {
        $schema = <<<EOF
<database name="test" schema="x" identifierQuoting="true">
    <table name="book">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="title" type="VARCHAR" size="255" required="true"/>
        <index>
            <index-column name="title"/>
        </index>
        <column name="author_id" type="INTEGER"/>
        <foreign-key foreignTable="author" foreignSchema="y">
            <reference local="author_id" foreign="id"/>
        </foreign-key>
    </table>
    <table name="author" schema="y">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="first_name" type="VARCHAR" size="100"/>
        <column name="last_name" type="VARCHAR" size="100"/>
    </table>
    <table name="book_summary">
        <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER"/>
        <column name="book_id" required="true" type="INTEGER"/>
        <column name="summary" required="true" type="LONGVARCHAR"/>
        <foreign-key foreignTable="book" onDelete="cascade">
            <reference local="book_id" foreign="id"/>
        </foreign-key>
    </table>
</database>
EOF;

        return [[$schema]];
    }

    public function providerForTestGetAddTablesSkipSQLDDL()
    {
        $schema = <<<EOF
<database name="test" identifierQuoting="true">
    <table name="book" skipSql="true">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="title" type="VARCHAR" size="255" required="true"/>
        <index>
            <index-column name="title"/>
        </index>
        <column name="author_id" type="INTEGER"/>
        <foreign-key foreignTable="author">
            <reference local="author_id" foreign="id"/>
        </foreign-key>
    </table>
    <table name="author" skipSql="true">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="first_name" type="VARCHAR" size="100"/>
        <column name="last_name" type="VARCHAR" size="100"/>
    </table>
</database>
EOF;

        return [[$schema]];
    }

    public function providerForTestGetAddTableDDLSimplePK()
    {
        $schema = <<<EOF
<database name="test" identifierQuoting="true">
    <table name="foo" description="This is foo table">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="bar" type="VARCHAR" size="255" required="true"/>
    </table>
</database>
EOF;

        return [[$schema]];
    }

    public function providerForTestGetAddTableDDLNonIntegerPK()
    {
        $schema = <<<EOF
<database name="test" identifierQuoting="true">
    <table name="foo" description="This is foo table">
        <column name="foo" primaryKey="true" type="VARCHAR"/>
        <column name="bar" type="VARCHAR" size="255" required="true"/>
    </table>
</database>
EOF;

        return [[$schema]];
    }

    public function providerForTestGetAddTableDDLCompositePK()
    {
        $schema = <<<EOF
<database name="test" identifierQuoting="true">
    <table name="foo">
        <column name="foo" primaryKey="true" type="INTEGER"/>
        <column name="bar" primaryKey="true" type="INTEGER"/>
        <column name="baz" type="VARCHAR" size="255" required="true"/>
    </table>
</database>
EOF;

        return [[$schema]];
    }

    public function providerForTestGetAddTableDDLUniqueIndex()
    {
        $schema = <<<EOF
<database name="test" identifierQuoting="true">
    <table name="foo">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="bar" type="INTEGER"/>
        <unique>
            <unique-column name="bar"/>
        </unique>
    </table>
</database>
EOF;

        return [[$schema]];
    }

    public function providerForTestGetAddTableDDLSchema()
    {
        $schema = <<<EOF
<database name="test" identifierQuoting="true">
    <table name="foo" schema="Woopah">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="bar" type="INTEGER"/>
    </table>
</database>
EOF;

        return [[$schema]];
    }

    public function providerForTestGetUniqueDDL()
    {
        $table = new Table('foo');
        $table->setIdentifierQuoting(true);
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

        return [
        [$index],
        ];
    }

    public function providerForTestGetIndicesDDL()
    {
        $table = new Table('foo');
        $table->setIdentifierQuoting(true);
        $column1 = new Column('bar1');
        $column1->getDomain()->copy(new Domain('FOOTYPE'));
        $table->addColumn($column1);
        $column2 = new Column('bar2');
        $column2->getDomain()->copy(new Domain('BARTYPE'));
        $table->addColumn($column2);
        $index1 = new Index('babar');
        $index1->addColumn($column1);
        $index1->addColumn($column2);
        $table->addIndex($index1);
        $index2 = new Index('foo_index');
        $index2->addColumn($column1);
        $table->addIndex($index2);

        return [
        [$table],
        ];
    }

    public function providerForTestGetIndexDDL()
    {
        $table = new Table('foo');
        $table->setIdentifierQuoting(true);
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

        return [
        [$index],
        ];
    }

    /**
     * @return array
     */
    public function providerForTestGetUniqueIndexDDL(): array
    {
        $table = new Table('foo');
        $table->setIdentifierQuoting(true);
        $column1 = new Column('bar1');
        $column1->getDomain()->copy(new Domain('FOOTYPE'));
        $table->addColumn($column1);
        $index = new Unique('babar');
        $index->addColumn($column1);
        $table->addIndex($index);

        return [
        [$index],
        ];
    }

    public function providerForTestPrimaryKeyDDL()
    {
        $table = new Table('foo');
        $table->setIdentifierQuoting(true);
        $column = new Column('bar');
        $column->setPrimaryKey(true);
        $table->addColumn($column);

        return [
        [$table],
        ];
    }

    public function providerForTestGetForeignKeyDDL()
    {
        $db = new Database();
        $db->setIdentifierQuoting(true);
        $table1 = new Table('foo');
        $db->addTable($table1);
        $column1 = new Column('bar_id');
        $column1->getDomain()->copy(new Domain('FOOTYPE'));
        $table1->addColumn($column1);

        $table2 = new Table('bar');
        $db->addTable($table2);
        $column2 = new Column('id');
        $column2->getDomain()->copy(new Domain('BARTYPE'));

        $table2->addColumn($column2);

        $fk = new ForeignKey('foo_bar_fk');
        $fk->setForeignTableCommonName('bar');
        $fk->addReference($column1, $column2);
        $fk->setOnDelete('CASCADE');
        $table1->addForeignKey($fk);

        return [
        [$fk],
        ];
    }

    public function providerForTestGetForeignKeySkipSqlDDL()
    {
        $arr = self::providerForTestGetForeignKeyDDL();
        $fk = $arr[0][0];
        $fk->setSkipSql(true);

        return [
        [$fk],
        ];
    }

    public function providerForTestGetForeignKeysDDL()
    {
        $db = new Database();
        $db->setIdentifierQuoting(true);
        $table1 = new Table('foo');
        $db->addTable($table1);

        $column1 = new Column('bar_id');
        $column1->getDomain()->copy(new Domain('FOOTYPE'));
        $table1->addColumn($column1);

        $table2 = new Table('bar');
        $db->addTable($table2);
        $column2 = new Column('id');
        $column2->getDomain()->copy(new Domain('BARTYPE'));
        $table2->addColumn($column2);

        $fk = new ForeignKey('foo_bar_fk');
        $fk->setForeignTableCommonName('bar');
        $fk->addReference($column1, $column2);
        $fk->setOnDelete('CASCADE');
        $table1->addForeignKey($fk);

        $column3 = new Column('baz_id');
        $column3->getDomain()->copy(new Domain('BAZTYPE'));
        $table1->addColumn($column3);
        $table3 = new Table('baz');
        $db->addTable($table3);
        $column4 = new Column('id');
        $column4->getDomain()->copy(new Domain('BAZTYPE'));
        $table3->addColumn($column4);

        $fk = new ForeignKey('foo_baz_fk');
        $fk->setForeignTableCommonName('baz');
        $fk->addReference($column3, $column4);
        $fk->setOnDelete('SETNULL');
        $table1->addForeignKey($fk);

        return [
        [$table1],
        ];
    }

    public function providerForTestCreateSchemaWithUuidColumns()
    {
        $schema = <<<EOF
<database name="test" identifierQuoting="true">
    <table name="foo">
        <column name="uuid" primaryKey="true" type="UUID" default="vendor_specific_default()"/>
        <column name="other_uuid" type="UUID"/>
    </table>
</database>
EOF;

        return [[$schema]];
    }

    public function providerForTestCreateSchemaWithUuidBinaryColumns()
    {
        $schema = <<<EOF
<database name="test" identifierQuoting="true">
    <table name="foo">
        <column name="uuid-bin" primaryKey="true" type="UUID_BINARY" default="vendor_specific_default()"/>
        <column name="other_uuid-bin" type="UUID_BINARY"/>
    </table>
</database>
EOF;

        return [[$schema]];
    }
}

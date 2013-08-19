<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Util;

use Propel\Generator\Util\SchemaValidator;
use Propel\Generator\Util\QuickBuilder;
use Propel\Generator\Model\Schema;
use Propel\Generator\Model\Column;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\Table;
use Propel\Tests\TestCase;

class SchemaValidatorTest extends TestCase
{
    protected function getSchemaForTable($table)
    {
        $database = new Database();
        $database->addTable($table);

        $schema = new Schema();
        $schema->addDatabase($database);

        return $schema;
    }

    public function testValidateReturnsTrueForEmptySchema()
    {
        $schema = new Schema();
        $validator = new SchemaValidator($schema);
        $this->assertTrue($validator->validate());
    }

    public function testValidateReturnsTrueForValidSchema()
    {
        $xmlSchema = <<<EOF
<database name="bookstore">
    <table name="book">
        <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
        <column name="title" type="VARCHAR" size="100" primaryString="true" />
    </table>
</database>
EOF;
        $builder = new QuickBuilder();
        $builder->setSchema($xmlSchema);

        $database = $builder->getDatabase();

        $schema = new Schema();
        $schema->addDatabase($database);

        $validator = new SchemaValidator($schema);
        $this->assertTrue($validator->validate());
    }

    public function testDatabasePackageName()
    {

        $schema = <<<EOF
<database name="bookstore" package="my.sub-directory">
    <table name="book">
        <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
        <column name="title" type="VARCHAR" size="100" primaryString="true" />
    </table>
</database>
EOF;
        $dom = new \DomDocument('1.0', 'UTF-8');
        $dom->loadXML($schema);

        $this->assertTrue($dom->schemaValidate(__DIR__.'/../../../../../resources/xsd/database.xsd'));
    }

    public function testValidateReturnsFalseWhenTwoTablesHaveSamePhpName()
    {
        $table1 = new Table('foo');
        $table2 = new Table('bar');
        $table2->setPhpName('Foo');

        $database = new Database();
        $database->addTable($table1);
        $database->addTable($table2);

        $schema = new Schema();
        $schema->addDatabase($database);

        $validator = new SchemaValidator($schema);

        $this->assertFalse($validator->validate());
        $this->assertContains('Table "bar" declares a phpName already used in another table', $validator->getErrors());
    }

    public function testValidateReturnsTrueWhenTwoTablesHaveSamePhpNameInDifferentNamespaces()
    {
        $column1 = new Column('id');
        $column1->setPrimaryKey(true);

        $table1 = new Table('foo');
        $table1->addColumn($column1);
        $table1->setNamespace('Foo');

        $column2 = new Column('id');
        $column2->setPrimaryKey(true);

        $table2 = new Table('bar');
        $table2->addColumn($column2);
        $table2->setPhpName('Foo');
        $table2->setNamespace('Bar');

        $database = new Database();
        $database->addTable($table1);
        $database->addTable($table2);

        $schema = new Schema();
        $schema->addDatabase($database);

        $validator = new SchemaValidator($schema);

        $this->assertTrue($validator->validate());
    }

    public function testValidateReturnsFalseWhenTableHasNoPk()
    {
        $schema = $this->getSchemaForTable(new Table('foo'));
        $validator = new SchemaValidator($schema);

        $this->assertFalse($validator->validate());
        $this->assertContains('Table "foo" does not have a primary key defined. Propel requires all tables to have a primary key.', $validator->getErrors());
    }

    public function testValidateReturnsTrueWhenTableHasNoPkButIsAView()
    {
        $table = new Table('foo');
        $table->setSkipSql(true);

        $schema = $this->getSchemaForTable($table);
        $validator = new SchemaValidator($schema);

        $this->assertTrue($validator->validate());
    }

    public function testValidateReturnsFalseWhenTableHasAReservedName()
    {
        $schema = $this->getSchemaForTable(new Table('TABLE_NAME'));
        $validator = new SchemaValidator($schema);

        $this->assertFalse($validator->validate());
        $this->assertContains('Table "TABLE_NAME" uses a reserved keyword as name', $validator->getErrors());
    }

    public function testValidateReturnsFalseWhenTwoColumnsHaveSamePhpName()
    {
        $column1 = new Column('foo');
        $column2 = new Column('bar');
        $column2->setPhpName('Foo');

        $table = new Table('foo_table');
        $table->addColumn($column1);
        $table->addColumn($column2);

        $schema = $this->getSchemaForTable($table);

        $validator = new SchemaValidator($schema);

        $this->assertFalse($validator->validate());
        $this->assertContains('Column "bar" declares a phpName already used in table "foo_table"', $validator->getErrors());
    }
}

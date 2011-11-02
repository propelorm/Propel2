<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Tests\Generator\Util;

use Propel\Generator\Util\SchemaValidator;
use Propel\Generator\Util\PropelQuickBuilder;

use Propel\Generator\Model\AppData;
use Propel\Generator\Model\Column;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\Table;

/**
 *
 * @package    generator.util
 */
class SchemaValidatorTest extends \PHPUnit_Framework_TestCase
{
    protected function getAppDataForTable($table)
    {
        $database = new Database();
        $database->addTable($table);
        $appData = new AppData();
        $appData->addDatabase($database);

        return $appData;
    }

    public function testValidateReturnsTrueForEmptySchema()
    {
        $schema = new AppData();
        $validator = new SchemaValidator($schema);
        $this->assertTrue($validator->validate());
    }

    public function testValidateReturnsTrueForValidSchema()
    {
        $schema = <<<EOF
<database name="bookstore">
    <table name="book">
        <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
        <column name="title" type="VARCHAR" size="100" primaryString="true" />
    </table>
</database>
EOF;
        $builder = new PropelQuickBuilder();
        $builder->setSchema($schema);
        $database = $builder->getDatabase();
        $appData = new AppData();
        $appData->addDatabase($database);
        $validator = new SchemaValidator($appData);
        $this->assertTrue($validator->validate());
    }

    public function testValidateReturnsFalseWhenTwoTablesHaveSamePhpName()
    {
        $table1 = new Table('foo');
        $table2 = new Table('bar');
        $table2->setPhpName('Foo');
        $database = new Database();
        $database->addTable($table1);
        $database->addTable($table2);
        $appData = new AppData();
        $appData->addDatabase($database);
        $validator = new SchemaValidator($appData);
        $this->assertFalse($validator->validate());
        $this->assertContains('Table "bar" declares a phpName already used in another table', $validator->getErrors());
    }

    public function testValidateReturnsFalseWhenTableHasNoPk()
    {
        $appData = $this->getAppDataForTable(new Table('foo'));
        $validator = new SchemaValidator($appData);
        $this->assertFalse($validator->validate());
        $this->assertContains('Table "foo" does not have a primary key defined. Propel requires all tables to have a primary key.', $validator->getErrors());
    }

    public function testValidateReturnsTrueWhenTableHasNoPkButIsAView()
    {
        $table = new Table('foo');
        $table->setSkipSql(true);
        $appData = $this->getAppDataForTable($table);
        $validator = new SchemaValidator($appData);
        $this->assertTrue($validator->validate());
    }

    public function testValidateReturnsFalseWhenTableHasAReservedName()
    {
        $appData = $this->getAppDataForTable(new Table('TABLE_NAME'));
        $validator = new SchemaValidator($appData);
        $this->assertFalse($validator->validate());
        $this->assertContains('Table "TABLE_NAME" uses a reserved keyword as name', $validator->getErrors());
    }

    public function testValidateReturnsFalseWhenTwoColumnssHaveSamePhpName()
    {
        $column1 = new Column('foo');
        $column2 = new Column('bar');
        $column2->setPhpName('Foo');
        $table = new Table('foo_table');
        $table->addColumn($column1);
        $table->addColumn($column2);
        $appData = $this->getAppDataForTable($table);
        $validator = new SchemaValidator($appData);
        $this->assertFalse($validator->validate());
        $this->assertContains('Column "bar" declares a phpName already used in table "foo_table"', $validator->getErrors());
    }


}

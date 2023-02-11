<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Reverse;

use PDO;
use Propel\Generator\Config\QuickGeneratorConfig;
use Propel\Generator\Model\ColumnDefaultValue;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\PropelTypes;
use Propel\Generator\Platform\DefaultPlatform;
use Propel\Generator\Reverse\PgsqlSchemaParser;
use Propel\Runtime\Propel;
use Propel\Tests\TestCaseFixturesDatabase;

/**
 * Tests for Pgsql database schema parser.
 *
 * @author Alan Pinstein
 * @group pgsql
 * @group database
 */
class PgsqlSchemaParserTest extends TestCaseFixturesDatabase
{
    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        Propel::init(__DIR__ . '/../../../../Fixtures/reverse/pgsql/build/conf/reverse-bookstore-conf.php');

        $this->con = Propel::getConnection('reverse-bookstore');
        $this->con->beginTransaction();

        if ('pgsql' !== $this->con->getAttribute(PDO::ATTR_DRIVER_NAME)) {
            $this->markTestSkipped('This test is designed for PostgreSQL');
        }
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        if ($this->con) {
            $this->con->rollback();
        }

        parent::tearDown();
        Propel::init(__DIR__ . '/../../../../Fixtures/bookstore/build/conf/bookstore-conf.php');
    }

    public function parseDataProvider()
    {
        return [
            // columnDDL, expectedColumnPhpName, type, expectedColumnDefaultType, expectedColumnDefaultValue, expectedSize, expectedScale
            ['my_column varchar(20) default null', 'MyColumn', PropelTypes::VARCHAR, ColumnDefaultValue::TYPE_VALUE, 'NULL', 20, null],
            ["my_column varchar(20) default ''", 'MyColumn', PropelTypes::VARCHAR, ColumnDefaultValue::TYPE_VALUE, '', 20, null],
            ['my_column numeric(11,0) default 0', 'MyColumn', PropelTypes::DECIMAL, ColumnDefaultValue::TYPE_VALUE, 0, 11, 0],
            ['my_column numeric(55,8) default 0', 'MyColumn', PropelTypes::DECIMAL, ColumnDefaultValue::TYPE_VALUE, 0, 55, 8],
            ['my_column uuid default null', 'MyColumn', PropelTypes::UUID, null, null, null, null],
        ];
    }

    /**
     * @dataProvider parseDataProvider
     *
     * @return void
     */
    public function testParse($columnDDL, $expectedPhpName, $expectedType, $expectedDefaultType, $expectedDefaultValue, $expectedSize, $expectedScale)
    {
        $this->con->query("create table foo ( {$columnDDL} );");
        $parser = new PgsqlSchemaParser($this->con);
        $parser->setGeneratorConfig(new QuickGeneratorConfig());

        $database = new Database();
        $database->setSchema('public');
        $database->setPlatform(new DefaultPlatform());

        // make sure our DDL insert produced exactly the SQL we inserted
        $this->assertGreaterThanOrEqual(1, $parser->parse($database), 'We parsed at least one table.');
        $table = $database->getTable('foo');
        $columns = $table->getColumns();
        $this->assertEquals(1, count($columns));
        $column = $columns[0];
        $this->assertNotEmpty($column);

        // check out our rev-eng column info
        $this->assertEquals($expectedPhpName, $column->getPhpName());
        $this->assertEquals($expectedType, $column->getType());

        $defaultValue = $column->getDefaultValue();
        if($expectedDefaultType === null){
            $this->assertNull($expectedDefaultType);
        } else {
            $this->assertEquals($expectedDefaultType, $defaultValue->getType());
            $this->assertEquals($expectedDefaultValue, $defaultValue->getValue());
        }
        
        $this->assertEquals($expectedSize, $column->getSize());
        $this->assertEquals($expectedScale, $column->getScale());
    }
}

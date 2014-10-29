<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Reverse;

use Propel\Generator\Config\QuickGeneratorConfig;
use Propel\Generator\Model\FieldDefaultValue;
use Propel\Generator\Model\Database;
use Propel\Generator\Platform\SqlDefaultPlatform;
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
    protected function setUp()
    {
        parent::setUp();
        Propel::init(__DIR__ . '/../../../../Fixtures/reverse/pgsql/build/conf/reverse-bookstore-conf.php');

        $this->con = Propel::getConnection('reverse-bookstore');
        $this->con->beginTransaction();

        if ('pgsql' !== $this->con->getAttribute(\PDO::ATTR_DRIVER_NAME)) {
            $this->markTestSkipped('This test is designed for PostgreSQL');
        }
    }

    protected function tearDown()
    {
        if ($this->con) {
            $this->con->rollback();
        }

        parent::tearDown();
        Propel::init(__DIR__ . '/../../../../Fixtures/bookstore/build/conf/bookstore-conf.php');
    }

    public function parseDataProvider()
    {
        return array(
            // columnDDL, expectedFieldPhpName, expectedFieldDefaultType, expectedFieldDefaultValue, expectedSize, expectedScale
            array("my_column varchar(20) default null", "MyField", FieldDefaultValue::TYPE_VALUE, "NULL", 20, null),
            array("my_column varchar(20) default ''", "MyField", FieldDefaultValue::TYPE_VALUE, "", 20, null),
            array("my_column numeric(11,0) default 0", "MyField", FieldDefaultValue::TYPE_VALUE, 0, 11, 0),
            array("my_column numeric(55,8) default 0", "MyField", FieldDefaultValue::TYPE_VALUE, 0, 55, 8),
        );
    }

    /**
     * @dataProvider parseDataProvider
     */
    public function testParse($columnDDL, $expectedFieldPhpName, $expectedFieldDefaultType, $expectedFieldDefaultValue, $expectedSize, $expectedScale)
    {
        $this->con->query("create table foo ( {$columnDDL} );");
        $parser = new PgsqlSchemaParser($this->con);
        $parser->setGeneratorConfig(new QuickGeneratorConfig());

        $database = new Database();
        $database->setPlatform(new SqlDefaultPlatform());

        // make sure our DDL insert produced exactly the SQL we inserted
        $this->assertGreaterThanOrEqual(1, $parser->parse($database), 'We parsed at least one table.');
        $table = $database->getEntity('foo');
        $columns = $table->getFields();
        $this->assertEquals(1, count($columns));

        // check out our rev-eng column info
        $defaultValue = $columns[0]->getDefaultValue();
        $this->assertEquals($expectedFieldPhpName, $columns[0]->getName());
        $this->assertEquals($expectedFieldDefaultType, $defaultValue->getType());
        $this->assertEquals($expectedFieldDefaultValue, $defaultValue->getValue());
        $this->assertEquals($expectedSize, $columns[0]->getSize());
        $this->assertEquals($expectedScale, $columns[0]->getScale());
    }
}

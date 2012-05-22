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
use Propel\Generator\Model\ColumnDefaultValue;
use Propel\Generator\Model\Database;
use Propel\Generator\Platform\DefaultPlatform;
use Propel\Generator\Reverse\PgsqlSchemaParser;
use Propel\Runtime\Propel;
use Propel\Tests\TestCase;


/**
 * Tests for Pgsql database schema parser.
 *
 * @author Alan Pinstein
 */
class PgsqlSchemaParserTest extends TestCase
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
        $this->con->rollback();

        parent::tearDown();
        Propel::init(__DIR__ . '/../../../../Fixtures/bookstore/build/conf/bookstore-conf.php');
    }

    public function parseDataProvider()
    {
        return array(
            // columnDDL, expectedColumnPhpName, expectedColumnDefaultType, expectedColumnDefaultValue
            array("my_column varchar(20) default null", "MyColumn", ColumnDefaultValue::TYPE_VALUE, "NULL"),
            array("my_column varchar(20) default ''", "MyColumn", ColumnDefaultValue::TYPE_VALUE, ""),
        );
    }

    /**
     * @dataProvider parseDataProvider
     */
    public function testParse($columnDDL, $expectedColumnPhpName, $expectedColumnDefaultType, $expectedColumnDefaultValue)
    {
        $this->markTestSkipped('Skipped as we now use one database for the whole test suite');

        $this->con->query("create table foo ( {$columnDDL} );");
        $parser = new PgsqlSchemaParser($this->con);
        $parser->setGeneratorConfig(new QuickGeneratorConfig());

        $database = new Database();
        $database->setPlatform(new DefaultPlatform());

        // make sure our DDL insert produced exactly the SQL we inserted
        $this->assertEquals(1, $parser->parse($database), 'One table and one view defined should return one as we exclude views');
        $tables = $database->getTables();
        $this->assertEquals(1, count($tables));
        $table = $tables[0];
        $columns = $table->getColumns();
        $this->assertEquals(1, count($columns));

        // check out our rev-eng column info
        $defaultValue = $columns[0]->getDefaultValue();
        $this->assertEquals($expectedColumnPhpName, $columns[0]->getPhpName());
        $this->assertEquals($expectedColumnDefaultType, $defaultValue->getType());
        $this->assertEquals($expectedColumnDefaultValue, $defaultValue->getValue());
    }
}

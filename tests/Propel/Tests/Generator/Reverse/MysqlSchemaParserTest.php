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
use Propel\Generator\Model\Database;
use Propel\Generator\Platform\DefaultPlatform;
use Propel\Generator\Reverse\MysqlSchemaParser;

use Propel\Runtime\Propel;
use Propel\Tests\TestCaseFixturesDatabase;

/**
 * Tests for Mysql database schema parser.
 *
 * @author William Durand
 *
 * @group database
 */
class MysqlSchemaParserTest extends TestCaseFixturesDatabase
{

    public function testParse()
    {
        $this->markTestSkipped('Skipped as we now use one database for the whole test suite');

        $parser = new MysqlSchemaParser(Propel::getServiceContainer()->getConnection('reverse-bookstore'));
        $parser->setGeneratorConfig(new QuickGeneratorConfig());

        $database = new Database();
        $database->setPlatform(new DefaultPlatform());

        $this->assertEquals(1, $parser->parse($database), 'One table and one view defined should return one as we exclude views');

        $tables = $database->getTables();
        $this->assertEquals(1, count($tables));

        $table = $tables[0];
        $this->assertEquals('Book', $table->getPhpName());
        $this->assertEquals(4, count($table->getColumns()));
    }
}

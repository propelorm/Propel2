<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Tests\Generator\Reverse;

use Propel\Generator\Config\QuickGeneratorConfig;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\PropelTypes;
use Propel\Generator\Platform\DefaultPlatform;
use Propel\Generator\Reverse\MysqlSchemaParser;
use Propel\Generator\Task\PropelConvertConfTask;

use Propel\Runtime\Propel;

/**
 * Tests for Mysql database schema parser.
 *
 * @author      William Durand
 * @version     $Revision$
 * @package     propel.generator.reverse.mysql
 */
class MysqlSchemaParserTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $xmlDom = new \DOMDocument();
        $xmlDom->load(__DIR__ . '/../../../../Fixtures/reverse/mysql/runtime-conf.xml');
        $xml = simplexml_load_string($xmlDom->saveXML());
        $phpconf = OpenedPropelConvertConfTask::simpleXmlToArray($xml);

        Propel::setConfiguration($phpconf['propel']);
    }

    protected function tearDown()
    {
        parent::tearDown();
        Propel::init(__DIR__ . '/../../../../Fixtures/bookstore/build/conf/bookstore-conf.php');
    }

    public function testParse()
    {
        $parser = new MysqlSchemaParser(Propel::getConnection('reverse-bookstore'));
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

class OpenedPropelConvertConfTask extends PropelConvertConfTask
{
    public static function simpleXmlToArray($xml)
    {
        return parent::simpleXmlToArray($xml);
    }
}

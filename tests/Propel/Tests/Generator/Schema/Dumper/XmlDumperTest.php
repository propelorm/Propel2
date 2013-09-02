<?php

namespace Propel\Tests\Generator\Schema\Dumper;

use Propel\Generator\Schema\Dumper\XmlDumper;

class XmlDumperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * The XmlDumper instance.
     *
     * @var XmlDumper
     */
    private $dumper;

    public function testDumpDatabaseSchema()
    {
        $database = include realpath(__DIR__.'/../../../Resources/blog-database.php');

        $this->assertSame($this->getExpectedXml('blog-database.xml'), $this->dumper->dump($database));
    }

    public function testDumpSchema()
    {
        $schema = include realpath(__DIR__.'/../../../Resources/blog-schema.php');

        $this->assertSame($this->getExpectedXml('blog-schema.xml'), $this->dumper->dumpSchema($schema, true));
    }

    protected function getExpectedXml($filename)
    {
        return trim(file_get_contents(realpath(__DIR__.'/../../../Resources/'.$filename)));
    }

    protected function setUp()
    {
        $this->dumper = new XmlDumper();
    }

    protected function tearDown()
    {
        $this->dumper = null;
    }
}

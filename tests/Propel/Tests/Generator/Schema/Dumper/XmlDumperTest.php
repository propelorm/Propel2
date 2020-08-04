<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Schema\Dumper;

use PHPUnit\Framework\TestCase;
use Propel\Generator\Schema\Dumper\XmlDumper;

class XmlDumperTest extends TestCase
{
    /**
     * The XmlDumper instance.
     *
     * @var \Propel\Generator\Schema\Dumper\XmlDumper
     */
    private $dumper;

    /**
     * @return void
     */
    public function testDumpDatabaseSchema()
    {
        $database = include realpath(__DIR__ . '/../../../Resources/blog-database.php');

        $this->assertSame($this->getExpectedXml('blog-database.xml'), $this->dumper->dump($database));
    }

    /**
     * @return void
     */
    public function testDumpSchema()
    {
        $schema = include realpath(__DIR__ . '/../../../Resources/blog-schema.php');

        $this->assertSame($this->getExpectedXml('blog-schema.xml'), $this->dumper->dumpSchema($schema, true));
    }

    /**
     * @param string $filename
     *
     * @return string
     */
    protected function getExpectedXml($filename)
    {
        return trim(file_get_contents(realpath(__DIR__ . '/../../../Resources/' . $filename)));
    }

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->dumper = new XmlDumper();
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        $this->dumper = null;
    }
}

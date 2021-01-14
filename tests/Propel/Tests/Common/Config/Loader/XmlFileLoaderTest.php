<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Common\Config\Loader;

use InvalidArgumentException;
use Propel\Common\Config\Exception\InputOutputException;
use Propel\Common\Config\Exception\InvalidArgumentException as PropelInvalidArgumentException;
use Propel\Common\Config\FileLocator;
use Propel\Common\Config\Loader\XmlFileLoader;
use Propel\Tests\TestCase;
use Propel\Generator\Util\VfsTrait;

class XmlFileLoaderTest extends TestCase
{
    use VfsTrait;

    /** @var XmlFileLoader */
    protected $loader;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->loader = new XmlFileLoader(new FileLocator($this->getRoot()->url()));
    }

    /**
     * @return void
     */
    public function testSupports()
    {
        $this->assertTrue($this->loader->supports('foo.xml'), '->supports() returns true if the resource is loadable');
        $this->assertTrue($this->loader->supports('foo.xml.dist'), '->supports() returns true if the resource is loadable');
        $this->assertFalse($this->loader->supports('foo.yml.dist'), '->supports() returns true if the resource is loadable');
        $this->assertFalse($this->loader->supports('foo.bar'), '->supports() returns true if the resource is loadable');
        $this->assertFalse($this->loader->supports('foo.bar.dist'), '->supports() returns true if the resource is loadable');
    }

    /**
     * @return void
     */
    public function testXmlFileCanBeLoaded()
    {
        $content = <<< XML
<?xml version='1.0' standalone='yes'?>
<properties>
  <foo>bar</foo>
  <bar>baz</bar>
</properties>
XML;
        $this->newFile('parameters.xml', $content);

        $test = $this->loader->load('parameters.xml');
        $this->assertEquals('bar', $test['foo']);
        $this->assertEquals('baz', $test['bar']);
    }

    /**
     * @return void
     */
    public function testXmlFileDoesNotExist()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The file "inexistent.xml" does not exist (in:');

        $this->loader->load('inexistent.xml');
    }

    /**
     * @return void
     */
    public function testXmlFileHasInvalidContent()
    {
        $this->expectException(PropelInvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid xml content');

        $content = <<<EOF
not xml content
only plain
text
EOF;
        $this->newFile('nonvalid.xml', $content);

        @$this->loader->load('nonvalid.xml');
    }

    /**
     * @return void
     */
    public function testXmlFileIsEmpty()
    {
        $this->newFile('empty.xml', '');

        $actual = $this->loader->load('empty.xml');

        $this->assertEquals([], $actual);
    }

    /**
     * @requires OS ^(?!Win.*)
     *
     * @return void
     */
    public function testXmlFileNotReadableThrowsException()
    {
        $this->expectException(InputOutputException::class);
        $this->expectExceptionMessage("You don't have permissions to access configuration file notreadable.xml.");

        $content = <<< XML
<?xml version='1.0' standalone='yes'?>
<properties>
  <foo>bar</foo>
  <bar>baz</bar>
</properties>
XML;

        $this->newFile('notreadable.xml', $content)->chmod(200);

        $actual = $this->loader->load('notreadable.xml');
        $this->assertEquals('bar', $actual['foo']);
        $this->assertEquals('baz', $actual['bar']);
    }
}

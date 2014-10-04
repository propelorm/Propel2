<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Common\Config\Loader;

use Propel\Common\Config\FileLocator;
use Propel\Common\Config\Loader\XmlFileLoader;
use Propel\Tests\Common\Config\ConfigTestCase;

class XmlFileLoaderTest extends ConfigTestCase
{
    protected $loader;

    protected function setUp()
    {
        $this->loader = new XmlFileLoader(new FileLocator(sys_get_temp_dir()));
    }

    public function testSupports()
    {
        $this->assertTrue($this->loader->supports('foo.xml'), '->supports() returns true if the resource is loadable');
        $this->assertTrue($this->loader->supports('foo.xml.dist'), '->supports() returns true if the resource is loadable');
        $this->assertFalse($this->loader->supports('foo.yml.dist'), '->supports() returns true if the resource is loadable');
        $this->assertFalse($this->loader->supports('foo.bar'), '->supports() returns true if the resource is loadable');
        $this->assertFalse($this->loader->supports('foo.bar.dist'), '->supports() returns true if the resource is loadable');
    }

    public function testXmlFileCanBeLoaded()
    {
        $content = <<< XML
<?xml version='1.0' standalone='yes'?>
<properties>
  <foo>bar</foo>
  <bar>baz</bar>
</properties>
XML;
        $this->dumpTempFile('parameters.xml', $content);

        $test = $this->loader->load('parameters.xml');
        $this->assertEquals('bar', $test['foo']);
        $this->assertEquals('baz', $test['bar']);
    }

    /**
     * @expectedException        \InvalidArgumentException
     * @expectedExceptionMessage The file "inexistent.xml" does not exist (in:
     */
    public function testXmlFileDoesNotExist()
    {
        $this->loader->load('inexistent.xml');
    }

    /**
     * @expectedException        Propel\Common\Config\Exception\InvalidArgumentException
     * @expectedExceptionMessage Invalid xml content
     */
    public function testXmlFileHasInvalidContent()
    {
        $content = <<<EOF
not xml content
only plain
text
EOF;
        $this->dumpTempFile('nonvalid.xml', $content);

        @$this->loader->load('nonvalid.xml');
    }

    public function testXmlFileIsEmpty()
    {
        $content = '';
        $this->dumpTempFile('empty.xml', $content);

        $actual = $this->loader->load('empty.xml');

        $this->assertEquals(array(), $actual);
    }

    /**
     * @expectedException Propel\Common\Config\Exception\InputOutputException
     * @expectedExceptionMessage You don't have permissions to access configuration file notreadable.xml.
     */
    public function testXmlFileNotReadableThrowsException()
    {
        $content = <<< XML
<?xml version='1.0' standalone='yes'?>
<properties>
  <foo>bar</foo>
  <bar>baz</bar>
</properties>
XML;

        $this->dumpTempFile('notreadable.xml', $content);
        $this->getFilesystem()->chmod(sys_get_temp_dir() . '/notreadable.xml', 0200);

        $actual = $this->loader->load('notreadable.xml');
        $this->assertEquals('bar', $actual['foo']);
        $this->assertEquals('baz', $actual['bar']);

    }
}
